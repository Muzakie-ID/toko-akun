<?php

namespace App\Models;

use App\Enums\JenisAkun;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return Attribute::get(function (): string {
            $hari = $this->durasi_hari;

            return match (true) {
                $hari % 365 === 0 => ($hari / 365).' Tahun',
                $hari % 30 === 0 => ($hari / 30).' Bulan',
                default => $hari.' Hari',
            };
        });
    }
}
