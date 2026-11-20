<?php

namespace App\Services\Orders;

use App\Enums\MetodeBayar;
use App\Enums\OrderStatus;
use App\Enums\SaldoTipe;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockSlot;
use App\Notifications\OrderDelivered;
use App\Services\Payment\PaymentGatewayException;
use App\Services\Payment\QrismuzClient;
use App\Services\Saldo\SaldoLedger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Perubahan status pesanan: lunas → kirim kredensial otomatis, tolak bukti, batal, refund, kedaluwarsa.
 */
class OrderLifecycle
{
    public function __construct(
        private QrismuzClient $qrismuz,
        private SaldoLedger $ledger,
    ) {}

    /**
     * Tandai lunas lalu kirim kredensial. Aman dipanggil berulang (webhook bisa terkirim dua kali).
     */
    public function markPaid(Order $order, ?Carbon $paidAt = null): Order
    {
        $updated = DB::transaction(function () use ($order, $paidAt): bool {
            $locked = Order::lockForUpdate()->find($order->id);

            // Kedaluwarsa/dibatalkan hanya bisa dilunasi bila uangnya memang masuk telat (keputusan admin)
            $canBePaid = $locked->status->isAwaitingPayment()
                || $locked->status === OrderStatus::Kedaluwarsa
                || ($locked->status === OrderStatus::Dibatalkan && $locked->late_paid_at !== null);
            if (! $canBePaid) {
                return false;
            }

            $locked->update([
                'status' => OrderStatus::Diproses,
                'paid_at' => $paidAt ?? now(),
                'alasan_tolak' => null,
            ]);

            return true;
        });

        $order->refresh();

        if (! $updated) {
            return $order;
        }

        return $this->fulfill($order);
    }

    /**
     * Ambil slot stok kosong (FIFO per varian) untuk setiap item yang belum terkirim.
     * Bila stok kurang, pesanan tetap "Diproses" dan akan dilengkapi saat stok baru masuk.
     */
    public function fulfill(Order $order): Order
    {
        if ($order->status !== OrderStatus::Diproses) {
            return $order;
        }

        $deliveredNow = DB::transaction(function () use ($order): int {
            $delivered = 0;
            $items = $order->items()->whereDoesntHave('slot')->lockForUpdate()->get();

            foreach ($items as $item) {
                $slot = StockSlot::query()
                    ->whereNull('order_item_id')
                    ->whereHas('stock', fn ($query) => $query->where('product_variant_id', $item->product_variant_id))
                    ->orderBy('stock_id')
                    ->orderBy('nomor')
                    ->lockForUpdate()
                    ->first();

                if (! $slot) {
                    continue;
                }

                $slot->update(['order_item_id' => $item->id]);
                $item->update([
                    'delivered_at' => now(),
                    'aktif_sampai' => now()->addDays($item->snapshot_durasi_hari),
                ]);
                $delivered++;
            }

            if ($delivered > 0) {
                $productCounts = $items->filter(fn (OrderItem $item) => $item->delivered_at !== null)
                    ->groupBy(fn (OrderItem $item) => $item->variant?->product_id)
                    ->map->count();

                foreach ($productCounts as $productId => $count) {
                    Product::whereKey($productId)->increment('total_terjual', $count);
                }
            }

            if (! $order->items()->whereDoesntHave('slot')->exists()) {
                $order->update(['status' => OrderStatus::Selesai]);
            }

            return $delivered;
        });

        $order->refresh();

        if ($deliveredNow > 0) {
            $order->user->notify(new OrderDelivered($order));
        }

        return $order;
    }

    /**
     * Lengkapi pesanan "Diproses" yang menunggu stok untuk varian tertentu (dipanggil setelah input stok).
     *
     * @param  list<int>  $variantIds
     */
    public function fulfillWaitingOrders(array $variantIds): int
    {
        $orders = Order::query()
            ->where('status', OrderStatus::Diproses)
            ->whereHas('items', fn ($query) => $query->whereIn('product_variant_id', $variantIds)->whereDoesntHave('slot'))
            ->orderBy('paid_at')
            ->get();

        return $orders->filter(fn (Order $order) => $this->fulfill($order)->status === OrderStatus::Selesai)->count();
    }

    /**
     * Pembeli upload bukti transfer manual.
     */
    public function submitProof(Order $order, string $path): Order
    {
        $order->update([
            'bukti_bayar_path' => $path,
            'status' => OrderStatus::MenungguVerifikasi,
            'alasan_tolak' => null,
        ]);

        return $order;
    }

    /**
     * Bukti ditolak: pembeli bisa upload ulang selama belum kedaluwarsa.
     */
    public function rejectProof(Order $order, string $reason): Order
    {
        $order->update([
            'status' => OrderStatus::MenungguBayar,
            'alasan_tolak' => $reason,
        ]);

        return $order;
    }

    public function cancel(Order $order, string $note): Order
    {
        if ($order->metode_bayar === MetodeBayar::Qris && $order->gateway_id && $order->status->isAwaitingPayment()) {
            try {
                $this->qrismuz->cancelTransaction($order->gateway_id);
            } catch (PaymentGatewayException $e) {
                Log::warning('Gagal membatalkan tagihan QRIS', ['order' => $order->no_invoice, 'error' => $e->getMessage()]);
            }
        }

        $order->update([
            'status' => OrderStatus::Dibatalkan,
            'catatan_admin' => trim($order->catatan_admin."\n".$note),
        ]);

        return $order;
    }

    /**
     * Refund: bila $toSaldo, total pesanan dikembalikan ke saldo pembeli; selain itu dana dikembalikan
     * di luar sistem. Pesanan yang dibayar dengan saldo selalu dikembalikan ke saldo.
     * Slot yang sudah terkirim tetap terpakai.
     */
    public function refund(Order $order, string $note, bool $toSaldo = false): Order
    {
        $toSaldo = $toSaldo || $order->metode_bayar === MetodeBayar::Saldo;

        DB::transaction(function () use ($order, $note, $toSaldo): void {
            $order->update([
                'status' => OrderStatus::Refund,
                'catatan_admin' => trim($order->catatan_admin."\nRefund".($toSaldo ? ' ke saldo' : '').': '.$note),
            ]);

            if ($toSaldo) {
                $this->ledger->credit($order->user, $order->total, SaldoTipe::Refund, "Refund {$order->no_invoice}", ['order' => $order]);
            }
        });

        return $order;
    }

    public function expire(Order $order): Order
    {
        if ($order->status === OrderStatus::MenungguBayar) {
            $order->update(['status' => OrderStatus::Kedaluwarsa]);
        }

        return $order;
    }
}
