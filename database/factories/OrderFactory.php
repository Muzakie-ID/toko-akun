<?php

namespace Database\Factories;

use App\Enums\MetodeBayar;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'no_invoice' => Order::generateInvoiceNumber(),
            'metode_bayar' => MetodeBayar::Manual,
            'status' => OrderStatus::MenungguBayar,
            'subtotal' => 25000,
            'total' => 25000,
            'expires_at' => now()->addDay(),
        ];
    }

    public function qris(): static
    {
        return $this->state(fn (array $attributes) => [
            'metode_bayar' => MetodeBayar::Qris,
            'gateway_id' => fake()->unique()->bothify('01??##??##??##'),
            'kode_unik' => 87,
            'total' => $attributes['subtotal'] + 87,
        ]);
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }
}
