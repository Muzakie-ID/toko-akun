<?php

namespace App\Models;

use Database\Factories\StockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu kredensial akun. Private = 1 slot, Sharing = beberapa slot (tiap slot punya info sendiri).
 * Email & password disimpan terenkripsi; email_hash dipakai untuk cek duplikat tanpa mendekripsi.
 */
#[Fillable(['product_variant_id', 'email', 'password', 'total_slot', 'created_by'])]
#[Hidden(['email', 'password', 'email_hash'])]
class Stock extends Model
{
    /** @use HasFactory<StockFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Stock $stock): void {
            $stock->email_hash = self::hashEmail($stock->email);
        });
    }

    public static function hashEmail(string $email): string
    {
        return hash_hmac('sha256', mb_strtolower(trim($email)), (string) config('app.key'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email' => 'encrypted',
            'password' => 'encrypted',
            'total_slot' => 'integer',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(StockSlot::class)->orderBy('nomor');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
