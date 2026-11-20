<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Buat atau ubah ulasan untuk satu akun yang sudah diterima pembeli.
     */
    public function store(Request $request, OrderItem $item): RedirectResponse
    {
        $order = $item->order;
        abort_unless($order->user_id === $request->user()->id, 404);

        if ($order->status !== OrderStatus::Selesai || $item->delivered_at === null || $item->product_variant_id === null) {
            return back()->with('error', 'Ulasan bisa diberikan setelah akun diterima.');
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'komentar' => ['nullable', 'string', 'max:1000'],
        ], ['rating.required' => 'Pilih jumlah bintang.']);

        $item->review()->updateOrCreate([], [
            'user_id' => $order->user_id,
            'product_id' => $item->variant->product_id,
            'rating' => $data['rating'],
            'komentar' => $data['komentar'] ?? null,
        ]);

        return back()->with('success', 'Terima kasih, ulasanmu tersimpan.');
    }
}
