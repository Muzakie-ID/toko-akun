<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\Orders\OrderLifecycle;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CustomerShopTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        app(Settings::class)->set(['manual.enabled' => true, 'manual.rekening' => [['bank' => 'BCA', 'nomor' => '123', 'nama' => 'Akunhaus']]]);
    }

    private function variant(array $attributes = [], int $stock = 3): ProductVariant
    {
        $variant = ProductVariant::factory()->create($attributes);
        Stock::factory()->count($stock)->for($variant, 'variant')->withSlots()->create();

        return $variant;
    }

    public function test_catalog_shows_cheapest_matching_variant_and_hides_inactive(): void
    {
        $netflix = Product::factory()->create(['nama' => 'Netflix Premium']);
        $this->variant(['product_id' => $netflix->id, 'jenis_akun' => 'private', 'durasi_hari' => 30, 'harga' => 45000]);
        $this->variant(['product_id' => $netflix->id, 'jenis_akun' => 'sharing', 'durasi_hari' => 7, 'harga' => 8000]);
        $this->variant(['product_id' => Product::factory()->create(['nama' => 'Mati', 'is_aktif' => false])->id]);

        $this->get('/')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Catalog')
            ->has('produk', 1)
            ->where('produk.0.harga', 8000)
            ->where('produk.0.durasi_label', '7 Hari'));

        $this->get('/?jenis[]=private')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('produk.0.harga', 45000)
            ->where('produk.0.jenis_akun_label', 'Private'));

        $this->get('/?q=spotify')->assertInertia(fn (AssertableInertia $page) => $page->has('produk', 0));
    }

    public function test_reseller_sees_reseller_price_on_detail(): void
    {
        $variant = $this->variant(['harga' => 25000, 'harga_reseller' => 20000]);
        $reseller = User::factory()->role(UserRole::Reseller)->create();

        $this->actingAs($reseller)->get("/produk/{$variant->product->slug}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('ProductDetail')
                ->where('varian.0.harga', 20000)
                ->where('varian.0.stok', 3));
    }

    public function test_inactive_product_detail_is_not_found(): void
    {
        $product = Product::factory()->create(['is_aktif' => false]);

        $this->get("/produk/{$product->slug}")->assertNotFound();
    }

    public function test_guest_must_login_to_add_to_cart(): void
    {
        $variant = $this->variant();

        $this->post('/keranjang', ['product_variant_id' => $variant->id])->assertRedirect('/login');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_cart_quantity_is_capped_to_available_stock(): void
    {
        $variant = $this->variant(stock: 2);
        $user = User::factory()->create();

        $this->actingAs($user)->post('/keranjang', ['product_variant_id' => $variant->id, 'qty' => 5])->assertSessionHas('success');
        $this->post('/keranjang', ['product_variant_id' => $variant->id, 'beli_sekarang' => true])->assertRedirect('/keranjang');

        $this->assertSame(2, $user->cartItems()->sole()->qty);
    }

    public function test_checkout_creates_order_and_redirects_to_payment_page(): void
    {
        $variant = $this->variant(['harga' => 25000]);
        $user = User::factory()->create();
        $user->cartItems()->create(['product_variant_id' => $variant->id, 'qty' => 2]);

        $response = $this->actingAs($user)->post('/checkout', ['metode_bayar' => 'manual']);

        $order = Order::sole();
        $response->assertRedirect("/pesanan/{$order->no_invoice}");
        $this->assertSame(50000, $order->total);
        $this->assertSame(0, $user->cartItems()->count());
    }

    public function test_buyer_cannot_see_someone_elses_order(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->create())->get("/pesanan/{$order->no_invoice}")->assertNotFound();
    }

    public function test_proof_upload_moves_order_to_verification(): void
    {
        Storage::fake('local');
        $order = Order::factory()->create();

        $this->actingAs($order->user)->post("/pesanan/{$order->no_invoice}/bukti", ['bukti' => UploadedFile::fake()->image('bukti.jpg')])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(OrderStatus::MenungguVerifikasi, $order->status);
        Storage::disk('local')->assertExists($order->bukti_bayar_path);
    }

    public function test_proof_must_be_an_image(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($order->user)->post("/pesanan/{$order->no_invoice}/bukti", ['bukti' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('bukti');

        $this->assertSame(OrderStatus::MenungguBayar, $order->fresh()->status);
    }

    public function test_credentials_are_shown_only_after_payment(): void
    {
        $variant = $this->variant();
        $order = Order::factory()->create();
        OrderItem::factory()->for($order)->forVariant($variant)->create();

        $this->actingAs($order->user)->get("/pesanan/{$order->no_invoice}")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('order.items.0.kredensial', null));

        app(OrderLifecycle::class)->markPaid($order);

        $this->get("/pesanan/{$order->no_invoice}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('OrderShow')
                ->where('order.status', 'selesai')
                ->has('order.items.0.kredensial.email'));
    }

    public function test_login_returns_to_local_path_only(): void
    {
        $user = User::factory()->create();

        $this->get('/login?kembali=/produk/netflix');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/produk/netflix');

        $this->post('/logout');
        $this->get('/login?kembali=//evil.test');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/');
    }
}
