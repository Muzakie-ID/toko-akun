<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Services\Sale\FlashSalePricing;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu varian di flash sale. kuota null = tanpa batas.
 * Kuota terpakai = akun dari pesanan yang belum batal/kedaluwarsa, jadi kuota kembali bila pesanan gugur.
 */
#[Fillable(['flash_sale_id', 'product_variant_id', 'harga_sale', 'kuota'])]
class FlashSaleItem extends Model
{
    /** @var list<OrderStatus> */
    public const STATUS_TIDAK_MENGHITUNG_KUOTA = [OrderStatus::Dibatalkan, OrderStatus::Kedaluwarsa];

    protected static function booted(): void
    {
        $flush = fn () => app(FlashSalePricing::class)->flush();
        static::saved($flush);
        static::deleted($flush);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'harga_sale' => 'integer',
            'kuota' => 'integer',
        ];
    }

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relasi untuk withCount: akun yang memakai kuota (pesanan belum gugur).
     */
    public function terpakaiItems(): HasMany
    {
        return $this->orderItems()->whereHas('order', fn ($query) => $query->whereNotIn('status', self::STATUS_TIDAK_MENGHITUNG_KUOTA));
    }

    public function terpakai(): int
    {
        return $this->terpakaiItems()->count();
    }

    /**
     * Sisa kuota; null bila tanpa batas.
     */
    public function sisa(?int $terpakai = null): ?int
    {
        if ($this->kuota === null) {
            return null;
        }

        return max(0, $this->kuota - ($terpakai ?? $this->terpakai()));
    }
}
