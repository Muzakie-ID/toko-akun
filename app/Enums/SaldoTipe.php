<?php

namespace App\Enums;

enum SaldoTipe: string
{
    case Topup = 'topup';
    case Belanja = 'belanja';
    case Refund = 'refund';
    case Penyesuaian = 'penyesuaian';

    public function label(): string
    {
        return match ($this) {
            self::Topup => 'Top-up',
            self::Belanja => 'Belanja',
            self::Refund => 'Refund',
            self::Penyesuaian => 'Penyesuaian admin',
        };
    }
}
