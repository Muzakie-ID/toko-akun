<?php

namespace App\Models;

use App\Enums\TopupStatus;
use Database\Factories\TopupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Tagihan top-up saldo lewat QRIS qris-muz. Saldo yang masuk = jumlah (kode unik tidak ikut dikreditkan).
 */
#[Fillable([
    'user_id', 'no_invoice', 'jumlah', 'kode_unik', 'total', 'status', 'gateway_id',
    'checkout_url', 'qr_image_url', 'qris_payload', 'expires_at', 'paid_at',
])]
class Topup extends Model
{
    /** @use HasFactory<TopupFactory> */
    use HasFactory;

    public const INVOICE_PREFIX = 'TOP-';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TopupStatus::class,
            'jumlah' => 'integer',
            'kode_unik' => 'integer',
            'total' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'no_invoice';
    }

    public static function generateInvoiceNumber(): string
    {
        return self::INVOICE_PREFIX.now()->format('ymd').'-'.Str::upper(Str::random(6));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
