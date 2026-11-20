<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Moderasi ulasan: sembunyikan ulasan spam/kasar tanpa menghapus datanya, atau hapus.
 */
class ReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'tampil' => ['nullable', Rule::in(['ya', 'tidak'])],
        ]);

        $reviews = Review::query()
            ->with(['user:id,name', 'product:id,nama,slug', 'orderItem.order:id,no_invoice'])
            ->when($filters['rating'] ?? null, fn ($query, $rating) => $query->where('rating', $rating))
            ->when($filters['tampil'] ?? null, fn ($query, $tampil) => $query->where('is_tampil', $tampil === 'ya'))
            ->latest()
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Review $review): array => [
                'id' => $review->id,
                'produk' => $review->product->nama,
                'produk_slug' => $review->product->slug,
                'pembeli' => $review->user->name,
                'invoice' => $review->orderItem->order->no_invoice,
                'rating' => $review->rating,
                'komentar' => $review->komentar,
                'is_tampil' => $review->is_tampil,
                'tanggal' => $review->created_at->translatedFormat('d M Y'),
            ]);

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
            'filters' => ['rating' => (string) ($filters['rating'] ?? ''), 'tampil' => $filters['tampil'] ?? ''],
        ]);
    }

    public function toggle(Review $review): RedirectResponse
    {
        $review->update(['is_tampil' => ! $review->is_tampil]);

        return back()->with('success', $review->is_tampil ? 'Ulasan ditampilkan lagi.' : 'Ulasan disembunyikan dari halaman produk.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return back()->with('success', 'Ulasan dihapus.');
    }
}
