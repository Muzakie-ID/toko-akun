<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\PromotedToReseller;

/**
 * Customer otomatis naik menjadi reseller begitu total belanja lunasnya mencapai batas di Pengaturan.
 * Hanya menaikkan (tidak pernah menurunkan); admin tetap bisa mengubah role manual.
 */
class ResellerPromotion
{
    public function __construct(private Settings $settings) {}

    public function isEnabled(): bool
    {
        return (bool) $this->settings->get('reseller.otomatis') && $this->threshold() > 0;
    }

    public function threshold(): int
    {
        return (int) $this->settings->get('reseller.min_belanja');
    }

    /**
     * Total belanja yang dihitung: pesanan lunas (diproses/selesai). Pesanan refund tidak dihitung.
     */
    public function totalSpent(User $user): int
    {
        return (int) $user->orders()->whereIn('status', OrderStatus::paidStatuses())->sum('total');
    }

    /**
     * @return bool true bila user baru saja dinaikkan menjadi reseller
     */
    public function promoteIfEligible(User $user): bool
    {
        if (! $this->isEnabled() || $user->role !== UserRole::Customer) {
            return false;
        }

        if ($this->totalSpent($user) < $this->threshold()) {
            return false;
        }

        $user->forceFill(['role' => UserRole::Reseller])->save();
        $user->notify(new PromotedToReseller($this->threshold()));

        return true;
    }

    /**
     * Periksa semua customer (dipakai command harian, mis. setelah admin menurunkan batas).
     */
    public function promoteAllEligible(): int
    {
        if (! $this->isEnabled()) {
            return 0;
        }

        $eligibleIds = User::query()
            ->where('role', UserRole::Customer)
            ->whereHas('orders', fn ($query) => $query->whereIn('status', OrderStatus::paidStatuses()))
            ->withSum(['orders as total_belanja' => fn ($query) => $query->whereIn('status', OrderStatus::paidStatuses())], 'total')
            ->get()
            ->filter(fn (User $user) => (int) $user->total_belanja >= $this->threshold())
            ->pluck('id');

        return User::whereIn('id', $eligibleIds)->get()->filter(fn (User $user) => $this->promoteIfEligible($user))->count();
    }
}
