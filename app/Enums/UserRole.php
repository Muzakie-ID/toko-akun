<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Reseller = 'reseller';
    case Karyawan = 'karyawan';
    case Admin = 'admin';

    /** Role yang boleh membuka panel admin (Karyawan dibatasi per menu). */
    public function isStaff(): bool
    {
        return in_array($this, [self::Karyawan, self::Admin], true);
    }
}
