<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;

/**
 * Format baris pesanan yang sama untuk dashboard dan daftar pesanan admin.
 */
class OrderPresenter
{
    /**
     * @return array{invoice: string, tanggal: string, pembeli: string, produk: string, jumlah_item: int, total: int, metode: string, status: string, status_label: string, status_tone: string, perlu_cek: bool}
     */
    public static function row(Order $order): array
    {
        $products = $order->items->pluck('snapshot_produk')->unique()->values();

        return [
            'invoice' => $order->no_invoice,
            'tanggal' => $order->created_at->translatedFormat('d M Y, H:i'),
            'pembeli' => $order->user->name,
            'produk' => $products->take(2)->implode(', ').($products->count() > 2 ? ' +'.($products->count() - 2) : ''),
            'jumlah_item' => $order->items->count(),
            'total' => $order->total,
            'metode' => $order->metode_bayar->label(),
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'status_tone' => $order->status->tone(),
            'perlu_cek' => $order->late_paid_at !== null || str_contains((string) $order->catatan_admin, 'nominal berbeda'),
        ];
    }
}
