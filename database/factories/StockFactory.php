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
}
