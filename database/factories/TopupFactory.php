<?php

namespace Database\Factories;

use App\Enums\TopupStatus;
use App\Models\Topup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Topup>
 */
class TopupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'no_invoice' => Topup::generateInvoiceNumber(),
            'jumlah' => 100000,
            'kode_unik' => 87,
            'total' => 100087,
            'status' => TopupStatus::MenungguBayar,
            'gateway_id' => fake()->unique()->bothify('01??##??##??##'),
            'expires_at' => now()->addMinutes(15),
        ];
    }
}
