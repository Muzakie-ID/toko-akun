<?php

namespace App\Http\Controllers;

use App\Enums\MetodeBayar;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Orders\OrderLifecycle;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman "Pesanan Saya" milik pembeli. Pesanan orang lain dibalas 404 agar keberadaannya tidak bocor.
 */
class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = $request->user()->orders()
            ->with('items:id,order_id,snapshot_produk')
            ->latest()
            ->orderByDesc('id')
            ->paginate(10)
            ->through(fn (Order $order): array => [
                'invoice' => $order->no_invoice,
                'tanggal' => $order->created_at->translatedFormat('d M Y, H:i'),
                'produk' => $order->items->pluck('snapshot_produk')->unique()->implode(', '),
                'jumlah_item' => $order->items->count(),
                'total' => $order->total,
                'status_label' => $order->status->label(),
                'status_tone' => $order->status->tone(),
                'perlu_bayar' => $order->status === OrderStatus::MenungguBayar,
            ]);

        return Inertia::render('Orders', ['orders' => $orders]);
    }

    public function show(Request $request, Order $order, Settings $settings): Response
    {
        $this->authorizeOwner($request, $order);
        $order->load('items.slot.stock');

        return Inertia::render('OrderShow', [
            'order' => [
                'invoice' => $order->no_invoice,
                'tanggal' => $order->created_at->translatedFormat('d M Y, H:i'),
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'status_tone' => $order->status->tone(),
                'metode' => $order->metode_bayar->value,
                'subtotal' => $order->subtotal,
                'kode_unik' => $order->kode_unik,
                'total' => $order->total,
                'expires_at' => $order->expires_at?->toIso8601String(),
                'expires_label' => $order->expires_at?->translatedFormat('d M Y, H:i'),
                'paid_at' => $order->paid_at?->translatedFormat('d M Y, H:i'),
                'qr_image_url' => $order->qr_image_url,
                'checkout_url' => $order->checkout_url,
                'qris_payload' => $order->qris_payload,
                'ada_bukti' => $order->bukti_bayar_path !== null,
                'alasan_tolak' => $order->alasan_tolak,
                'items' => $order->items->map(fn (OrderItem $item): array => [
                    'id' => $item->id,
                    'produk' => $item->snapshot_produk,
                    'varian' => "{$item->snapshot_jenis_akun->label()} · {$item->durasiLabel()}",
                    'harga' => $item->harga,
                    'aktif_sampai' => $item->aktif_sampai?->translatedFormat('d M Y'),
                    // Kredensial hanya dikirim bila pesanan sudah lunas (bukan saat refund/batal)
                    'kredensial' => in_array($order->status, OrderStatus::paidStatuses(), true) ? $item->credentials() : null,
                ]),
            ],
            'rekening' => $order->metode_bayar === MetodeBayar::Manual ? $settings->get('manual.rekening') : [],
            'whatsapp' => $settings->get('store.whatsapp'),
        ]);
    }

    public function uploadProof(Request $request, Order $order, OrderLifecycle $lifecycle): RedirectResponse
    {
        $this->authorizeOwner($request, $order);

        $request->validate(
            ['bukti' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']],
            ['bukti.max' => 'Ukuran bukti maksimal 2 MB.'],
            ['bukti' => 'bukti transfer'],
        );

        if ($order->metode_bayar !== MetodeBayar::Manual || ! $order->status->isAwaitingPayment()) {
            return back()->with('error', 'Pesanan ini tidak menunggu bukti transfer.');
        }

        $path = $request->file('bukti')->store('bukti-bayar', 'local');
        $lifecycle->submitProof($order, $path);

        return back()->with('success', 'Bukti terkirim. Kami akan memverifikasi secepatnya.');
    }

    private function authorizeOwner(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->id, 404);
    }
}
