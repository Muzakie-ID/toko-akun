<?php

namespace App\Models;

use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ulasan pembeli: satu per item pesanan yang sudah terkirim. Admin bisa menyembunyikan (is_tampil).
 */
#[Fillable(['order_item_id', 'user_id', 'product_id', 'rating', 'komentar', 'is_tampil'])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_tampil' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Rating & jumlah ulasan di produk adalah agregat denormalisasi agar katalog cepat
        static::saved(fn (Review $review) => self::syncProductRating($review->product_id));
        static::deleted(fn (Review $review) => self::syncProductRating($review->product_id));
    }

    public static function syncProductRating(int $productId): void
    {
        $visible = self::where('product_id', $productId)->where('is_tampil', true);

        Product::whereKey($productId)->update([
            'rating' => round((float) $visible->clone()->avg('rating'), 1),
            'jumlah_ulasan' => $visible->clone()->count(),
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
