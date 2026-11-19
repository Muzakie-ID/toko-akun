<?php

namespace App\Enums;

/**
 * Tipe akun yang ditentukan admin per varian & per stok.
 * Private = 1 akun untuk 1 pembeli; Sharing = 1 akun dibagi beberapa slot pembeli.
 */
enum JenisAkun: string
{
    case Private = 'private';
    case Sharing = 'sharing';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Private',
            self::Sharing => 'Sharing',
        };
    }
}
