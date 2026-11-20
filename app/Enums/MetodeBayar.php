<?php

namespace App\Enums;

enum MetodeBayar: string
{
    /** QRIS otomatis lewat gateway qris-muz (dikonfirmasi webhook). */
    case Qris = 'qris';

    /** Transfer bank manual + upload bukti, diverifikasi admin/karyawan. */
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Qris => 'QRIS (otomatis)',
            self::Manual => 'Transfer Manual',
        };
    }
}
