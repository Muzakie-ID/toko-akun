<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'snapshot_produk' => 'Netflix Premium',
            'snapshot_jenis_akun' => 'private',
            'snapshot_durasi_hari' => 30,
            'harga' => 25000,
        ];
    }

    /**
     * Ambil snapshot dari varian yang diberikan.
     */
    public function forVariant(ProductVariant $variant): static
    {
        return $this->state(fn (array $attributes) => [
            'product_variant_id' => $variant->id,
            'snapshot_produk' => $variant->product->nama,
            'snapshot_jenis_akun' => $variant->jenis_akun,
            'snapshot_durasi_hari' => $variant->durasi_hari,
            'harga' => $variant->harga,
        ]);
    }
}
