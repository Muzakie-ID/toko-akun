<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'user_id' => fn (array $attributes) => OrderItem::find($attributes['order_item_id'])->order->user_id,
            'product_id' => fn (array $attributes) => OrderItem::find($attributes['order_item_id'])->variant->product_id,
            'rating' => 5,
            'komentar' => fake()->sentence(),
            'is_tampil' => true,
        ];
    }
}
