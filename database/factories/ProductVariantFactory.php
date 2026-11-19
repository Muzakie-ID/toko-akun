<?php

namespace Database\Factories;

use App\Enums\JenisAkun;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'jenis_akun' => JenisAkun::Private,
            'durasi_hari' => 30,
            'harga' => 25000,
            'harga_asli' => 45000,
            'is_aktif' => true,
        ];
    }

    /**
     * Varian tipe sharing (1 akun dibagi beberapa slot).
     */
    public function sharing(): static
    {
        return $this->state(fn (array $attributes) => [
            'jenis_akun' => JenisAkun::Sharing,
            'harga' => 12000,
        ]);
    }
}
