<?php

namespace App\Models;

use App\Enums\JenisAkun;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Satu baris = satu akun/slot (qty dipecah per baris saat checkout).
 * Kredensial dibaca live dari stok lewat slot, jadi update stok (klaim garansi) langsung terlihat pembeli.
 */
#[Fillable(['order_id', 'product_variant_id', 'snapshot_produk', 'snapshot_jenis_akun', 'snapshot_durasi_hari', 'harga', 'delivered_at', 'aktif_sampai'])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot_jenis_akun' => JenisAkun::class,
            'snapshot_durasi_hari' => 'integer',
            'harga' => 'integer',
            'delivered_at' => 'datetime',
            'aktif_sampai' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function slot(): HasOne
    {
        return $this->hasOne(StockSlot::class);
    }

    public function durasiLabel(): string
    {
        return ProductVariant::labelDurasi($this->snapshot_durasi_hari);
    }

    /**
     * Kredensial untuk ditampilkan ke pembeli/admin. Null bila belum terkirim.
     *
     * @return array{email: string, password: string, info: string|null}|null
     */
    public function credentials(): ?array
    {
        $slot = $this->slot;

        if (! $slot) {
            return null;
        }

        return [
            'email' => $slot->stock->email,
            'password' => $slot->stock->password,
            'info' => $slot->info,
        ];
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
