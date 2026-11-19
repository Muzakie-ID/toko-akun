<?php

namespace App\Models;

use Database\Factories\StockSlotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu slot pembeli di sebuah stok. Info = data tambahan per slot (mis. "Profil 2, PIN 1234").
 */
#[Fillable(['stock_id', 'nomor', 'info'])]
#[Hidden(['info'])]
class StockSlot extends Model
{
    /** @use HasFactory<StockSlotFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nomor' => 'integer',
            'info' => 'encrypted',
        ];
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
