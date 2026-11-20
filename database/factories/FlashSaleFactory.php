<?php

namespace Database\Factories;

use App\Models\FlashSale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FlashSale>
 */
class FlashSaleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'Flash Sale '.fake()->word(),
            'mulai_at' => now()->subHour(),
            'selesai_at' => now()->addHours(3),
            'is_aktif' => true,
        ];
    }
}
