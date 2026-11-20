<?php

namespace Database\Factories;

use App\Models\ProductVariant;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_variant_id' => ProductVariant::factory(),
            'email' => fake()->unique()->safeEmail(),
            'password' => fake()->password(10),
            'total_slot' => 1,
        ];
    }

    /**
     * Buat slot sesuai total_slot (stok tanpa slot tidak bisa dijual).
     */
    public function withSlots(int $count = 1, ?string $info = null): static
    {
        return $this->state(fn (array $attributes) => ['total_slot' => $count])
            ->afterCreating(function (Stock $stock) use ($count, $info): void {
                foreach (range(1, $count) as $nomor) {
                    $stock->slots()->create(['nomor' => $nomor, 'info' => $info ? "{$info} {$nomor}" : null]);
                }
            });
    }
}
