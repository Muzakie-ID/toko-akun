<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WishlistController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $products = $user->wishlists()
            ->with(['product' => fn ($query) => $query->with(['category', 'variants' => fn ($v) => $v->where('is_aktif', true)->withCount('availableSlots')])])
            ->latest()
            ->get()
            ->map(fn (Wishlist $wishlist) => $wishlist->product)
            ->filter(fn (?Product $product) => $product?->is_aktif)
            ->map(fn (Product $product) => ProductCardPresenter::card($product, $user, [], wishlisted: true))
            ->filter()
            ->values();

        return Inertia::render('Wishlist', ['produk' => $products]);
    }

    /**
     * Tambah bila belum ada, hapus bila sudah ada.
     */
    public function toggle(Request $request, Product $product): RedirectResponse
    {
        $user = $request->user();
        $existing = $user->wishlists()->where('product_id', $product->id)->first();

        if ($existing) {
            $existing->delete();

            return back()->with('success', "{$product->nama} dihapus dari wishlist.");
        }

        abort_unless($product->is_aktif, 404);
        $user->wishlists()->create(['product_id' => $product->id]);

        return back()->with('success', "{$product->nama} disimpan ke wishlist.");
    }
}
