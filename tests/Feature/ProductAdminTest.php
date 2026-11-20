<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ProductAdminTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->role(UserRole::Karyawan)->create();
    }

    public function test_customer_cannot_manage_products(): void
    {
        $customer = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($customer)->post('/admin/produk', ['category_id' => $category->id, 'nama' => 'X', 'garansi_hari' => 0])
            ->assertForbidden();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_index_lists_products_with_counts_and_filters_by_search(): void
    {
        $netflix = Product::factory()->create(['nama' => 'Netflix Premium']);
        $variant = ProductVariant::factory()->for($netflix)->create();
        Stock::factory()->count(2)->for($variant, 'variant')->create();
        Product::factory()->create(['nama' => 'Spotify Premium']);

        $response = $this->actingAs($this->staff())->get('/admin/produk?q=netf');

        $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Products/Index')
            ->has('products.data', 1)
            ->where('products.data.0.nama', 'Netflix Premium')
            ->where('products.data.0.jumlah_varian', 1)
            ->where('products.data.0.jumlah_stok', 2));
    }

    public function test_store_creates_product_with_slug_from_name(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->staff())->post('/admin/produk', [
            'category_id' => $category->id,
            'nama' => 'YouTube Premium',
            'slug' => '',
            'garansi_hari' => 30,
            'is_aktif' => true,
            'is_populer' => false,
        ]);

        $product = Product::firstWhere('slug', 'youtube-premium');
        $response->assertRedirect("/admin/produk/{$product->id}/edit")->assertSessionHas('success');
        $this->assertSame('YouTube Premium', $product->nama);
        $this->assertSame(30, $product->garansi_hari);
    }

    public function test_store_rejects_duplicate_slug_and_empty_payload(): void
    {
        $existing = Product::factory()->create(['nama' => 'Canva Pro', 'slug' => 'canva-pro']);

        $this->actingAs($this->staff())
            ->post('/admin/produk', ['category_id' => $existing->category_id, 'nama' => 'Canva Pro', 'garansi_hari' => 0])
            ->assertSessionHasErrors(['slug' => 'Slug ini sudah dipakai produk lain. Ganti nama atau isi slug manual.']);

        $this->post('/admin/produk', [])->assertSessionHasErrors(['category_id', 'nama', 'garansi_hari']);

        $this->assertDatabaseCount('products', 1);
    }

    public function test_update_keeps_own_slug(): void
    {
        $product = Product::factory()->create(['nama' => 'Canva Pro', 'slug' => 'canva-pro', 'is_aktif' => true]);

        $this->actingAs($this->staff())->put("/admin/produk/{$product->id}", [
            'category_id' => $product->category_id,
            'nama' => 'Canva Pro',
            'slug' => 'canva-pro',
            'garansi_hari' => 7,
            'is_aktif' => false,
        ])->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame(7, $product->garansi_hari);
        $this->assertFalse($product->is_aktif);
    }

    public function test_product_without_stock_is_deleted_with_its_variants(): void
    {
        $variant = ProductVariant::factory()->create();
        $product = $variant->product;

        $this->actingAs($this->staff())->delete("/admin/produk/{$product->id}")
            ->assertRedirect('/admin/produk');

        $this->assertModelMissing($product);
        $this->assertModelMissing($variant);
    }

    public function test_product_with_stock_cannot_be_deleted(): void
    {
        $stock = Stock::factory()->create();
        $product = $stock->variant->product;

        $this->actingAs($this->staff())->delete("/admin/produk/{$product->id}")
            ->assertSessionHas('error', 'Produk ini sudah punya stok, jadi tidak bisa dihapus. Nonaktifkan saja agar tidak tampil di katalog.');

        $this->assertModelExists($product);
    }
}
