<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\FlashSale;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminFlashSaleTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->role(UserRole::Karyawan)->create();
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function payload(array $items, array $overrides = []): array
    {
        return [
            'nama' => 'Flash Sale Gajian',
            'mulai_at' => now()->addHour()->format('Y-m-d\TH:i'),
            'selesai_at' => now()->addHours(5)->format('Y-m-d\TH:i'),
            'is_aktif' => true,
            'items' => $items,
            ...$overrides,
        ];
    }

    public function test_customer_cannot_manage_flash_sales(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/flash-sale')->assertForbidden();
    }

    public function test_staff_creates_flash_sale_in_store_timezone(): void
    {
        $this->travelTo('2026-11-20 10:00:00');
        $variant = ProductVariant::factory()->create(['harga' => 25000]);

        $this->actingAs($this->staff())->post('/admin/flash-sale', $this->payload(
            [['product_variant_id' => $variant->id, 'harga_sale' => 15000, 'kuota' => 10]],
            ['mulai_at' => '2026-11-20T12:00', 'selesai_at' => '2026-11-20T18:00'],
        ))->assertSessionHasNoErrors();

        $sale = FlashSale::sole();
        $this->assertSame('2026-11-20 12:00', $sale->mulai_at->format('Y-m-d H:i'));
        $this->assertSame('Asia/Jakarta', $sale->mulai_at->timezoneName);
        $this->assertSame(10, $sale->items()->sole()->kuota);
    }

    public function test_sale_price_must_be_below_normal_price_and_end_after_start(): void
    {
        $variant = ProductVariant::factory()->create(['harga' => 25000]);

        $this->actingAs($this->staff())->post('/admin/flash-sale', $this->payload(
            [['product_variant_id' => $variant->id, 'harga_sale' => 25000, 'kuota' => null]],
            ['selesai_at' => now()->subDay()->format('Y-m-d\TH:i')],
        ))->assertSessionHasErrors(['selesai_at' => 'Waktu selesai harus setelah waktu mulai.']);

        $this->post('/admin/flash-sale', $this->payload([['product_variant_id' => $variant->id, 'harga_sale' => 25000, 'kuota' => null]]))
            ->assertSessionHasErrors(['items.0.harga_sale' => 'Harga sale harus lebih murah dari harga jual (Rp25.000).']);

        $this->assertDatabaseCount('flash_sales', 0);
    }

    public function test_same_variant_cannot_be_in_two_overlapping_active_sales(): void
    {
        $variant = ProductVariant::factory()->create(['harga' => 25000]);
        $existing = FlashSale::factory()->create(['nama' => 'Sale Lama', 'mulai_at' => now(), 'selesai_at' => now()->addHours(3)]);
        $existing->items()->create(['product_variant_id' => $variant->id, 'harga_sale' => 20000]);
        $item = [['product_variant_id' => $variant->id, 'harga_sale' => 15000, 'kuota' => null]];

        $this->actingAs($this->staff())->post('/admin/flash-sale', $this->payload($item))
            ->assertSessionHasErrors('items.0.product_variant_id');
        $this->post('/admin/flash-sale', $this->payload($item, [
            'mulai_at' => now()->addHours(4)->format('Y-m-d\TH:i'), 'selesai_at' => now()->addHours(6)->format('Y-m-d\TH:i'),
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('flash_sales', 2);
    }

    public function test_update_keeps_existing_item_so_quota_usage_is_preserved(): void
    {
        $variant = ProductVariant::factory()->create(['harga' => 25000]);
        $other = ProductVariant::factory()->create(['harga' => 30000]);
        $sale = FlashSale::factory()->create();
        $item = $sale->items()->create(['product_variant_id' => $variant->id, 'harga_sale' => 15000, 'kuota' => 5]);
        OrderItem::factory()->for(Order::factory())->forVariant($variant)->create(['flash_sale_item_id' => $item->id]);

        $this->actingAs($this->staff())->put("/admin/flash-sale/{$sale->id}", $this->payload([
            ['product_variant_id' => $variant->id, 'harga_sale' => 14000, 'kuota' => 5],
            ['product_variant_id' => $other->id, 'harga_sale' => 20000, 'kuota' => null],
        ]))->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame(14000, $item->harga_sale);
        $this->assertSame(4, $item->sisa());
        $this->assertSame(2, $sale->items()->count());
    }

    public function test_sale_with_orders_cannot_be_deleted(): void
    {
        $variant = ProductVariant::factory()->create(['harga' => 25000]);
        $sale = FlashSale::factory()->create();
        $item = $sale->items()->create(['product_variant_id' => $variant->id, 'harga_sale' => 15000]);
        OrderItem::factory()->for(Order::factory())->forVariant($variant)->create(['flash_sale_item_id' => $item->id]);
        $empty = FlashSale::factory()->create();

        $this->actingAs($this->staff())->delete("/admin/flash-sale/{$sale->id}")
            ->assertSessionHas('error', 'Flash sale ini sudah ada pembelinya, jadi tidak bisa dihapus. Nonaktifkan saja.');
        $this->delete("/admin/flash-sale/{$empty->id}")->assertRedirect('/admin/flash-sale');

        $this->assertModelExists($sale);
        $this->assertModelMissing($empty);
    }

    public function test_index_and_dashboard_show_running_sale(): void
    {
        $variant = ProductVariant::factory()->create(['harga' => 25000]);
        $sale = FlashSale::factory()->create(['nama' => 'Sale Malam']);
        $sale->items()->create(['product_variant_id' => $variant->id, 'harga_sale' => 15000, 'kuota' => 20]);
        $staff = $this->staff();

        $this->actingAs($staff)->get('/admin/flash-sale')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sales.data.0.status', 'berlangsung')
            ->where('sales.data.0.jumlah_varian', 1));
        $this->get('/admin')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('flashSale.nama', 'Sale Malam')
            ->where('flashSale.kuota', 20));
    }
}
