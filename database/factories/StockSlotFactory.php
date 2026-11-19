<?php

namespace Database\Factories;

use App\Models\Stock;
use App\Models\StockSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockSlot>
 */
class StockSlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stock_id' => Stock::factory(),
            'nomor' => 1,
            'info' => null,
        ];
    }
}
