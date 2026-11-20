<?php

namespace App\Enums;

enum OrderStatus: string
{
    case MenungguBayar = 'menunggu_bayar';
    case MenungguVerifikasi = 'menunggu_verifikasi';
    case Diproses = 'diproses';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';
    case Kedaluwarsa = 'kedaluwarsa';
    case Refund = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::MenungguBayar => 'Menunggu Pembayaran',
            self::MenungguVerifikasi => 'Menunggu Verifikasi',
            self::Diproses => 'Diproses',
            self::Selesai => 'Selesai',
            self::Dibatalkan => 'Dibatalkan',
            self::Kedaluwarsa => 'Kedaluwarsa',
            self::Refund => 'Refund',
        };
    }

    /**
     * Warna badge di UI: amber, blue, violet, emerald, slate, rose.
     */
    public function tone(): string
    {
        return match ($this) {
            self::MenungguBayar => 'amber',
            self::MenungguVerifikasi => 'blue',
            self::Diproses => 'violet',
            self::Selesai => 'emerald',
            self::Dibatalkan, self::Kedaluwarsa => 'slate',
            self::Refund => 'rose',
        };
    }

    /** Status yang masih bisa dibayar pembeli. */
    public function isAwaitingPayment(): bool
    {
        return in_array($this, [self::MenungguBayar, self::MenungguVerifikasi], true);
    }

    /** Status yang dihitung sebagai pendapatan. */
    public static function paidStatuses(): array
    {
        return [self::Diproses, self::Selesai];
    }
}
