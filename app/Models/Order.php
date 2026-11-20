<?php

namespace App\Models;

use App\Enums\MetodeBayar;
use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'user_id', 'no_invoice', 'metode_bayar', 'status', 'subtotal', 'kode_unik', 'total',
    'gateway_id', 'checkout_url', 'qr_image_url', 'qris_payload', 'expires_at', 'paid_at',
    'late_paid_at', 'bukti_bayar_path', 'alasan_tolak', 'catatan_admin',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metode_bayar' => MetodeBayar::class,
            'status' => OrderStatus::class,
            'subtotal' => 'integer',
            'kode_unik' => 'integer',
            'total' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'late_paid_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'no_invoice';
    }

    public static function generateInvoiceNumber(): string
    {
        return 'INV-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
