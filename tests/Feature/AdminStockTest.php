<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminStockTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->role(UserRole::Karyawan)->create();
    }

    private function soldStock(): Stock
    {
        $variant = ProductVariant::factory()->sharing()->create();
        $stock = Stock::factory()->for($variant, 'variant')->withSlots(2, 'Profil')->create(['email' => 'lama@x.com', 'password' => 'lama']);
        $item = OrderItem::factory()->for(Order::factory())->forVariant($variant)->create();
        $stock->slots()->where('nomor', 1)->update(['order_item_id' => $item->id]);

        return $stock;
    }

    public function test_index_lists_decrypted_stock_and_filters_by_exact_email(): void
    {
        $stock = $this->soldStock();
        Stock::factory()->withSlots()->create(['email' => 'lain@x.com']);

        $this->actingAs($this->staff())->get('/admin/stok?email=LAMA@x.com')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Stocks/Index')
                ->has('stocks.data', 1)
                ->where('stocks.data.0.email', 'lama@x.com')
                ->where('stocks.data.0.slot_terpakai', 1)
                ->where('stocks.data.0.slots.0.invoice', $stock->slots()->first()->orderItem->order->no_invoice));
    }

    public function test_warranty_update_is_visible_to_buyer_immediately(): void
    {
        $stock = $this->soldStock();
        $slot = $stock->slots()->where('nomor', 1)->first();

        $this->actingAs($this->staff())->put("/admin/stok/{$stock->id}", [
            'email' => 'baru@x.com',
            'password' => 'baru123',
            'slots' => [['id' => $slot->id, 'info' => 'Profil 1 PIN 9999']],
        ])->assertSessionHas('success');

        $this->assertSame(
            ['email' => 'baru@x.com', 'password' => 'baru123', 'info' => 'Profil 1 PIN 9999'],
            $slot->orderItem->fresh()->credentials(),
        );
    }

    public function test_update_rejects_email_already_in_same_variant(): void
    {
        $stock = $this->soldStock();
        Stock::factory()->for($stock->variant, 'variant')->create(['email' => 'ada@x.com']);

        $this->actingAs($this->staff())->put("/admin/stok/{$stock->id}", ['email' => 'ada@x.com', 'password' => 'x'])
            ->assertSessionHasErrors(['email' => 'Akun ini sudah ada di stok varian yang sama.']);
    }

    public function test_sold_stock_cannot_be_deleted_but_unsold_can(): void
    {
        $sold = $this->soldStock();
        $unsold = Stock::factory()->withSlots()->create();
        $staff = $this->staff();

        $this->actingAs($staff)->delete("/admin/stok/{$sold->id}")->assertSessionHas('error', 'Stok ini sudah terjual ke pembeli, jadi tidak bisa dihapus.');
        $this->delete("/admin/stok/{$unsold->id}")->assertSessionHas('success');

        $this->assertModelExists($sold);
        $this->assertModelMissing($unsold);
    }
}
