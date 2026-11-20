<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class WishlistReviewTest extends TestCase
{
    use RefreshDatabase;

    private function deliveredItem(?User $buyer = null, OrderStatus $status = OrderStatus::Selesai): OrderItem
    {
        $variant = ProductVariant::factory()->create();
        $order = Order::factory()->status($status)->create($buyer ? ['user_id' => $buyer->id] : []);

        return OrderItem::factory()->for($order)->forVariant($variant)->create([
            'delivered_at' => $status === OrderStatus::Selesai ? now() : null,
        ]);
    }

    public function test_wishlist_toggle_and_listing(): void
    {
        $product = ProductVariant::factory()->create()->product;
        $user = User::factory()->create();

        $this->actingAs($user)->post("/wishlist/{$product->slug}")->assertSessionHas('success');
        $this->get('/wishlist')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Wishlist')
            ->has('produk', 1)
            ->where('produk.0.di_wishlist', true));
        $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page->where('produk.0.di_wishlist', true));

        $this->post("/wishlist/{$product->slug}");

        $this->assertDatabaseCount('wishlists', 0);
    }

    public function test_guest_cannot_use_wishlist(): void
    {
        $product = Product::factory()->create();

        $this->post("/wishlist/{$product->slug}")->assertRedirect('/login');
    }

    public function test_buyer_reviews_delivered_item_and_product_rating_updates(): void
    {
        $item = $this->deliveredItem();
        $buyer = $item->order->user;
        $other = $this->deliveredItem();
        Review::factory()->create(['order_item_id' => $other->id, 'product_id' => $item->variant->product_id, 'rating' => 3]);

        $this->actingAs($buyer)->post("/ulasan/{$item->id}", ['rating' => 5, 'komentar' => 'Mantap'])->assertSessionHas('success');
        $this->post("/ulasan/{$item->id}", ['rating' => 4, 'komentar' => 'Diubah']);

        $product = $item->variant->product->fresh();
        $this->assertSame(1, $item->review()->count());
        $this->assertSame('3.5', $product->rating);
        $this->assertSame(2, $product->jumlah_ulasan);
    }

    public function test_review_is_rejected_before_delivery_or_for_other_buyer(): void
    {
        $pending = $this->deliveredItem(status: OrderStatus::MenungguBayar);
        $delivered = $this->deliveredItem();

        $this->actingAs($pending->order->user)->post("/ulasan/{$pending->id}", ['rating' => 5])
            ->assertSessionHas('error', 'Ulasan bisa diberikan setelah akun diterima.');
        $this->actingAs(User::factory()->create())->post("/ulasan/{$delivered->id}", ['rating' => 5])->assertNotFound();
        $this->actingAs($delivered->order->user)->post("/ulasan/{$delivered->id}", ['rating' => 6])->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_hidden_review_leaves_product_rating_and_detail_page(): void
    {
        $review = Review::factory()->create(['order_item_id' => $this->deliveredItem()->id, 'rating' => 1, 'komentar' => 'spam']);
        $product = $review->product;
        $admin = User::factory()->role(UserRole::Karyawan)->create();

        $this->actingAs($admin)->post("/admin/ulasan/{$review->id}/tampil")->assertSessionHas('success');

        $this->assertSame(0, $product->fresh()->jumlah_ulasan);
        $this->get("/produk/{$product->slug}")->assertInertia(fn (AssertableInertia $page) => $page->has('ulasan.daftar', 0));
    }

    public function test_product_detail_shows_masked_reviewer_name_and_distribution(): void
    {
        $buyer = User::factory()->create(['name' => 'Budi Santoso']);
        $review = Review::factory()->create(['order_item_id' => $this->deliveredItem($buyer)->id, 'rating' => 4]);

        $this->get("/produk/{$review->product->slug}")->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ulasan.daftar.0.nama', 'Budi S.')
            ->where('ulasan.sebaran.1.bintang', 4)
            ->where('ulasan.sebaran.1.jumlah', 1)
            ->where('produk.jumlah_ulasan', 1));
    }
}
