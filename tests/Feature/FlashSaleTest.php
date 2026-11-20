<?php

namespace Tests\Feature;

use App\Enums\MetodeBayar;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\Orders\OrderCheckout;
use App\Services\Sale\FlashSalePricing;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class FlashSaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        app(Settings::class)->set(['manual.enabled' => true, 'manual.rekening' => [['bank' => 'BCA', 'nomor' => '1', 'nama' => 'A']]]);
    }

    private function variant(int $stock = 5, array $attributes = []): ProductVariant
    {
        $variant = ProductVariant::factory()->create(['harga' => 25000, 'harga_asli' => 45000, ...$attributes]);
        Stock::factory()->count($stock)->for($variant, 'variant')->withSlots()->create();

        return $variant;
    }

    private function sale(ProductVariant $variant, int $harga = 15000, ?int $kuota = null, array $saleAttributes = []): FlashSaleItem
    {
        $sale = FlashSale::factory()->create($saleAttributes);

        return $sale->items()->create(['product_variant_id' => $variant->id, 'harga_sale' => $harga, 'kuota' => $kuota]);
    }

    public function test_sale_price_applies_only_during_active_window(): void
    {
        $now = $this->variant();
        $this->sale($now);
        $later = $this->variant();
        $this->sale($later, saleAttributes: ['mulai_at' => now()->addDay(), 'selesai_at' => now()->addDays(2)]);
        $off = $this->variant();
        $this->sale($off, saleAttributes: ['is_aktif' => false]);

        $this->assertSame(15000, $now->priceFor(null));
        $this->assertSame(25000, $later->priceFor(null));
        $this->assertSame(25000, $off->priceFor(null));

        $this->travel(4)->hours();
        app(FlashSalePricing::class)->flush();
        $this->assertSame(25000, $now->priceFor(null));
    }

    public function test_reseller_gets_the_cheaper_of_reseller_and_sale_price(): void
    {
        $variant = $this->variant(attributes: ['harga_reseller' => 12000]);
        $this->sale($variant, 15000);
        $reseller = User::factory()->role(UserRole::Reseller)->create();

        $this->assertSame(12000, $variant->priceFor($reseller));
        $this->assertNull($variant->flashSaleFor($reseller));
        $this->assertSame(15000, $variant->priceFor(User::factory()->create()));
    }

    public function test_checkout_applies_sale_price_up_to_remaining_quota(): void
    {
        $variant = $this->variant();
        $item = $this->sale($variant, 15000, kuota: 2);
        $user = User::factory()->create();

        $order = app(OrderCheckout::class)->checkout($user, [$variant->id => 3], MetodeBayar::Manual);

        $this->assertSame(15000 + 15000 + 25000, $order->total);
        $this->assertSame(2, $order->items()->where('flash_sale_item_id', $item->id)->count());
        $this->assertSame(0, $item->sisa());
        $this->assertSame(25000, $variant->fresh()->priceFor(null));
    }

    public function test_quota_returns_when_order_is_cancelled_or_expires(): void
    {
        $variant = $this->variant();
        $item = $this->sale($variant, 15000, kuota: 1);
        $order = app(OrderCheckout::class)->checkout(User::factory()->create(), [$variant->id => 1], MetodeBayar::Manual);
        $this->assertSame(0, $item->sisa());

        $order->update(['status' => OrderStatus::Kedaluwarsa]);

        $this->assertSame(1, $item->sisa());
    }

    public function test_catalog_and_detail_show_sale_price_badge_and_countdown(): void
    {
        $variant = $this->variant();
        $item = $this->sale($variant, 15000, kuota: 10);

        $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('produk.0.harga', 15000)
            ->where('produk.0.harga_asli', 25000)
            ->where('produk.0.flash_sale', true)
            ->where('flashSale.nama', $item->flashSale->nama)
            ->has('flashSale.selesai_at')
            ->has('flashSale.produk', 1));

        $this->get('/?sale=1')->assertInertia(fn (AssertableInertia $page) => $page->has('produk', 1));

        $this->get("/produk/{$variant->product->slug}")->assertInertia(fn (AssertableInertia $page) => $page
            ->where('varian.0.harga', 15000)
            ->where('varian.0.harga_asli', 25000)
            ->where('varian.0.flash_sale.sisa', 10)
            ->has('varian.0.flash_sale.selesai_at'));
    }

    public function test_cart_shows_quota_aware_prices(): void
    {
        $variant = $this->variant();
        $this->sale($variant, 15000, kuota: 1);
        $user = User::factory()->create();
        $user->cartItems()->create(['product_variant_id' => $variant->id, 'qty' => 2]);

        $this->actingAs($user)->get('/keranjang')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('items.0.harga', 15000)
            ->where('items.0.harga_normal', 25000)
            ->where('items.0.sisa_sale', 1)
            ->where('items.0.flash_sale', true));
    }
}
