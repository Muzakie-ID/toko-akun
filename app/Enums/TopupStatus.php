<?php

namespace App\Enums;

enum TopupStatus: string
{
    case MenungguBayar = 'menunggu_bayar';
    case Lunas = 'lunas';
    case Kedaluwarsa = 'kedaluwarsa';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::MenungguBayar => 'Menunggu Pembayaran',
            self::Lunas => 'Berhasil',
            self::Kedaluwarsa => 'Kedaluwarsa',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::MenungguBayar => 'amber',
            self::Lunas => 'emerald',
            self::Kedaluwarsa, self::Dibatalkan => 'slate',
        };
    }
}
