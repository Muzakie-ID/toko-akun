<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'nama' => $nama = fake()->unique()->words(2, true).' Premium',
            'slug' => Str::slug($nama),
            'deskripsi' => fake()->sentence(),
            'garansi_hari' => 30,
            'is_aktif' => true,
        ];
    }
}
