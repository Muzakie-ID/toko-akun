<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\TopupStatus;
use App\Models\Order;
use App\Models\Topup;
use App\Services\Orders\OrderLifecycle;
use App\Services\Saldo\TopupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:expire')]
#[Description('Tandai kedaluwarsa pesanan & top-up yang lewat batas bayar (QRIS diberi jeda 10 menit untuk webhook).')]
class ExpireOrders extends Command
{
    public function handle(OrderLifecycle $lifecycle, TopupService $topups): int
    {
        $expiredTopups = Topup::query()
            ->where('status', TopupStatus::MenungguBayar)
            ->where('expires_at', '<', now()->subMinutes(10))
            ->get()
            ->each(fn (Topup $topup) => $topups->expire($topup));

        $orders = Order::query()
            ->where('status', OrderStatus::MenungguBayar)
            ->where('expires_at', '<', now()->subMinutes(10))
            ->get();

        $orders->each(fn (Order $order) => $lifecycle->expire($order));

        $this->info("{$orders->count()} pesanan dan {$expiredTopups->count()} top-up ditandai kedaluwarsa.");

        return self::SUCCESS;
    }
}
