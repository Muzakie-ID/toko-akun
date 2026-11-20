<?php

namespace App\Models;

use App\Services\Sale\FlashSalePricing;
use Database\Factories\FlashSaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Flash sale: harga khusus untuk beberapa varian dalam rentang waktu tertentu (opsional dengan kuota).
 */
#[Fillable(['nama', 'mulai_at', 'selesai_at', 'is_aktif'])]
class FlashSale extends Model
{
    /** @use HasFactory<FlashSaleFactory> */
    use HasFactory;

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
            'mulai_at' => 'datetime',
            'selesai_at' => 'datetime',
            'is_aktif' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(FlashSaleItem::class);
    }

    /**
     * Sale yang aktif dan sedang dalam rentang waktunya.
     */
    #[Scope]
    protected function berlangsung(Builder $query): Builder
    {
        return $query->where('is_aktif', true)->where('mulai_at', '<=', now())->where('selesai_at', '>', now());
    }

    public function status(): string
    {
        return match (true) {
            ! $this->is_aktif => 'nonaktif',
            $this->mulai_at->isFuture() => 'akan_datang',
            $this->selesai_at->isPast() => 'selesai',
            default => 'berlangsung',
        };
    }
}
