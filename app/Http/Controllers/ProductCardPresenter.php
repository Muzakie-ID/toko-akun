<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

/**
 * Format kartu produk yang sama untuk katalog, Paling Laris, dan wishlist.
 */
class ProductCardPresenter
{
    /**
     * Kartu katalog menampilkan varian termurah yang lolos filter tipe & durasi.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>|null
     */
    public static function card(Product $product, ?User $user, array $filters, bool $wishlisted = false): ?array
    {
        $variants = $product->variants
            ->when($filters['jenis'] ?? null, fn ($v, $jenis) => $v->filter(fn (ProductVariant $variant) => in_array($variant->jenis_akun->value, $jenis, true)))
            ->when($filters['durasi'] ?? null, fn ($v, $durasi) => $v->whereIn('durasi_hari', array_map('intval', $durasi)));

        $cheapest = $variants->sortBy(fn (ProductVariant $variant) => $variant->priceFor($user))->first();

        if (! $cheapest) {
            return null;
        }

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'nama' => $product->nama,
            'kategori_slug' => $product->category->slug,
            'harga' => $cheapest->priceFor($user),
            'harga_asli' => $cheapest->harga_asli,
            'jenis_akun_label' => $cheapest->jenis_akun->label(),
            'durasi_label' => $cheapest->durasi_label,
            'jumlah_varian' => $product->variants->count(),
            'terjual' => $product->total_terjual,
            'is_populer' => $product->is_populer,
            'stok_ada' => $product->variants->sum('available_slots_count') > 0,
            'logo_url' => $product->logoUrl(),
            'rating' => (float) $product->rating,
            'jumlah_ulasan' => $product->jumlah_ulasan,
            'di_wishlist' => $wishlisted,
        ];
    }
}
