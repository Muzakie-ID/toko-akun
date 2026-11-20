<?php

namespace App\Services\Orders;

use App\Enums\MetodeBayar;
use App\Enums\OrderStatus;
use App\Enums\SaldoTipe;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Payment\PaymentGatewayException;
use App\Services\Payment\QrismuzClient;
use App\Services\Saldo\InsufficientSaldoException;
use App\Services\Saldo\SaldoLedger;
use App\Services\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderCheckout
{
    public function __construct(
        private Settings $settings,
        private QrismuzClient $qrismuz,
        private OrderLifecycle $lifecycle,
        private SaldoLedger $ledger,
    ) {}

    /**
     * Buat pesanan dari daftar varian + qty. Harga & stok selalu dihitung ulang di server.
     *
     * @param  array<int, int>  $quantities  product_variant_id => qty
     *
     * @throws ValidationException
     * @throws PaymentGatewayException
     */
    public function checkout(User $user, array $quantities, MetodeBayar $metode): Order
    {
        $this->ensureMethodAvailable($metode);
        // Saldo terbaru menentukan harga reseller (dicek sebelum saldo dipotong)
        $user->refresh();

        $variants = ProductVariant::query()
            ->with('product')
            ->withCount('availableSlots')
            ->whereIn('id', array_keys($quantities))
            ->get()
            ->keyBy('id');

        $errors = [];
        foreach ($quantities as $variantId => $qty) {
            $variant = $variants->get($variantId);

            if (! $variant || ! $variant->is_aktif || ! $variant->product->is_aktif) {
                $errors[] = 'Salah satu produk sudah tidak tersedia. Hapus dari keranjang.';
            } elseif ($variant->available_slots_count < $qty) {
                $errors[] = "Stok {$variant->product->nama} {$variant->jenis_akun->label()} {$variant->durasi_label} tinggal {$variant->available_slots_count}.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['checkout' => array_values(array_unique($errors))]);
        }

        $order = DB::transaction(function () use ($user, $quantities, $variants, $metode): Order {
            $subtotal = 0;
            $items = [];

            foreach ($quantities as $variantId => $qty) {
                $variant = $variants[$variantId];
                $price = $variant->priceFor($user);

                // Satu baris per akun/slot agar tiap baris punya kredensial sendiri
                foreach (range(1, $qty) as $_) {
                    $items[] = [
                        'product_variant_id' => $variant->id,
                        'snapshot_produk' => $variant->product->nama,
                        'snapshot_jenis_akun' => $variant->jenis_akun,
                        'snapshot_durasi_hari' => $variant->durasi_hari,
                        'harga' => $price,
                    ];
                    $subtotal += $price;
                }
            }

            $order = Order::create([
                'user_id' => $user->id,
                'no_invoice' => Order::generateInvoiceNumber(),
                'metode_bayar' => $metode,
                'status' => OrderStatus::MenungguBayar,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'expires_at' => $metode === MetodeBayar::Manual
                    ? now()->addHours((int) $this->settings->get('manual.batas_jam'))
                    : null,
            ]);
            $order->items()->createMany($items);

            if ($metode === MetodeBayar::Saldo) {
                try {
                    $this->ledger->debit($user, $subtotal, SaldoTipe::Belanja, "Bayar {$order->no_invoice}", ['order' => $order]);
                } catch (InsufficientSaldoException) {
                    throw ValidationException::withMessages(['metode_bayar' => 'Saldo tidak cukup. Top-up dulu atau pilih QRIS.']);
                }
            }

            $user->cartItems()->whereIn('product_variant_id', array_keys($quantities))->delete();

            return $order;
        });

        return match ($metode) {
            MetodeBayar::Qris => tap($order, fn (Order $o) => $this->createQrisInvoice($o)),
            MetodeBayar::Saldo => $this->lifecycle->markPaid($order),
            MetodeBayar::Manual => $order,
        };
    }

    /**
     * @throws PaymentGatewayException
     */
    private function createQrisInvoice(Order $order): void
    {
        try {
            $transaction = $this->qrismuz->createTransaction(
                $order->no_invoice,
                $order->subtotal,
                route('orders.show', $order),
                ['order_id' => $order->id, 'user_id' => $order->user_id],
            );
        } catch (PaymentGatewayException $e) {
            $this->lifecycle->cancel($order, 'Gagal membuat tagihan QRIS: '.$e->getMessage());

            throw $e;
        }

        $order->update([
            'gateway_id' => $transaction['id'],
            'kode_unik' => $transaction['unique_code'],
            'total' => $transaction['final_amount'],
            'checkout_url' => $transaction['checkout_url'],
            'qr_image_url' => $transaction['qr_image_url'],
            'qris_payload' => $transaction['qris_payload'],
            'expires_at' => $transaction['expires_at'] ? Carbon::parse($transaction['expires_at'])->setTimezone(config('app.timezone')) : now()->addMinutes(15),
        ]);
    }

    /**
     * @throws ValidationException
     */
    private function ensureMethodAvailable(MetodeBayar $metode): void
    {
        $ready = match ($metode) {
            MetodeBayar::Qris => $this->settings->qrisReady(),
            MetodeBayar::Manual => $this->settings->manualReady(),
            MetodeBayar::Saldo => true,
        };

        if (! $ready) {
            throw ValidationException::withMessages(['metode_bayar' => 'Metode pembayaran ini sedang tidak tersedia.']);
        }
    }
}
