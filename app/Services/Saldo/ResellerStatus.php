<?php

namespace App\Services\Saldo;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Settings;

/**
 * Harga reseller berlaku untuk role reseller (ditetapkan admin) ATAU siapa pun yang saldonya saat ini
 * >= batas di Pengaturan (default Rp1.000.000). Dinamis: saldo turun di bawah batas = harga normal lagi.
 */
class ResellerStatus
{
    public function __construct(private Settings $settings) {}

    public function appliesTo(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->role === UserRole::Reseller || $this->qualifiesBySaldo($user);
    }

    public function qualifiesBySaldo(User $user): bool
    {
        return $this->isEnabled() && $user->saldo >= $this->threshold();
    }

    public function isEnabled(): bool
    {
        return (bool) $this->settings->get('reseller.otomatis') && $this->threshold() > 0;
    }

    public function threshold(): int
    {
        return (int) $this->settings->get('reseller.min_saldo');
    }
}
