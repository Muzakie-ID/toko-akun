<?php

namespace App\Models;

use App\Enums\JenisAkun;
use App\Services\Saldo\ResellerStatus;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Satu varian = produk + tipe akun (private/sharing) + durasi. Stok kredensial terikat ke varian.
 */
#[Fillable(['product_id', 'jenis_akun', 'durasi_hari', 'harga', 'harga_asli', 'harga_reseller', 'is_aktif'])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis_akun' => JenisAkun::class,
            'durasi_hari' => 'integer',
            'harga' => 'integer',
            'harga_asli' => 'integer',
            'harga_reseller' => 'integer',
            'is_aktif' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Label durasi untuk tampilan: 7 → "7 Hari", 30 → "1 Bulan", 60 → "2 Bulan", 365 → "1 Tahun".
     */
    protected function durasiLabel(): Attribute
    {
        return Attribute::get(fn (): string => self::labelDurasi($this->durasi_hari));
    }

    public static function labelDurasi(int $hari): string
    {
        return match (true) {
            $hari % 365 === 0 => ($hari / 365).' Tahun',
            $hari % 30 === 0 => ($hari / 30).' Bulan',
            default => $hari.' Hari',
        };
    }

    /**
     * Slot stok yang belum terjual untuk varian ini.
     */
    public function availableSlots(): HasManyThrough
    {
        return $this->hasManyThrough(StockSlot::class, Stock::class)->whereNull('stock_slots.order_item_id');
    }

    /**
     * Harga yang berlaku untuk user: harga_reseller bila diisi dan user berstatus reseller
     * (role reseller atau saldo saat ini >= batas, lihat ResellerStatus).
     */
    public function priceFor(?User $user): int
    {
        if ($this->harga_reseller !== null && app(ResellerStatus::class)->appliesTo($user)) {
            return $this->harga_reseller;
        }

        return $this->harga;
    }
}
