<?php

namespace App\Models;

use App\Enums\SaldoTipe;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Buku besar saldo: setiap perubahan saldo user tercatat di sini bersama saldo akhirnya.
 */
#[Fillable(['user_id', 'tipe', 'jumlah', 'saldo_akhir', 'keterangan', 'order_id', 'topup_id', 'created_by'])]
class SaldoMutation extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipe' => SaldoTipe::class,
            'jumlah' => 'integer',
            'saldo_akhir' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function topup(): BelongsTo
    {
        return $this->belongsTo(Topup::class);
    }
}
