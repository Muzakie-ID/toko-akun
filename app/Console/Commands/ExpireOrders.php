<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Orders\OrderLifecycle;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:expire')]
#[Description('Tandai kedaluwarsa pesanan yang lewat batas bayar (QRIS diberi jeda 10 menit untuk webhook).')]
class ExpireOrders extends Command
{
    public function handle(OrderLifecycle $lifecycle): int
    {
        $orders = Order::query()
            ->where('status', OrderStatus::MenungguBayar)
            ->where('expires_at', '<', now()->subMinutes(10))
            ->get();

        $orders->each(fn (Order $order) => $lifecycle->expire($order));

        $this->info("{$orders->count()} pesanan ditandai kedaluwarsa.");

        return self::SUCCESS;
    }
}
