<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Notifications\PromotedToReseller;
use App\Services\Orders\OrderLifecycle;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ResellerPromotionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function unpaidOrder(User $user, int $total): Order
    {
        $variant = ProductVariant::factory()->create();
        Stock::factory()->for($variant, 'variant')->withSlots()->create();
        $order = Order::factory()->for($user)->create(['subtotal' => $total, 'total' => $total]);
        OrderItem::factory()->for($order)->forVariant($variant)->create();

        return $order;
    }

    public function test_customer_becomes_reseller_when_paid_total_reaches_one_million(): void
    {
        $user = User::factory()->create();
        Order::factory()->for($user)->status(OrderStatus::Selesai)->create(['total' => 600000]);
        Order::factory()->for($user)->status(OrderStatus::Refund)->create(['total' => 900000]);
        $order = $this->unpaidOrder($user, 400000);

        app(OrderLifecycle::class)->markPaid($order);

        $this->assertSame(UserRole::Reseller, $user->fresh()->role);
        Notification::assertSentTo($user, PromotedToReseller::class);
    }

    public function test_customer_below_threshold_stays_customer(): void
    {
        $user = User::factory()->create();
        Order::factory()->for($user)->status(OrderStatus::Refund)->create(['total' => 900000]);
        $order = $this->unpaidOrder($user, 999999);

        app(OrderLifecycle::class)->markPaid($order);

        $this->assertSame(UserRole::Customer, $user->fresh()->role);
        Notification::assertNotSentTo($user, PromotedToReseller::class);
    }

    public function test_staff_and_disabled_setting_are_never_promoted(): void
    {
        $karyawan = User::factory()->role(UserRole::Karyawan)->create();
        app(OrderLifecycle::class)->markPaid($this->unpaidOrder($karyawan, 2000000));
        $this->assertSame(UserRole::Karyawan, $karyawan->fresh()->role);

        app(Settings::class)->set(['reseller.otomatis' => false]);
        $customer = User::factory()->create();
        app(OrderLifecycle::class)->markPaid($this->unpaidOrder($customer, 2000000));
        $this->assertSame(UserRole::Customer, $customer->fresh()->role);
    }

    public function test_daily_command_promotes_existing_customers_after_threshold_is_lowered(): void
    {
        $eligible = User::factory()->create();
        Order::factory()->for($eligible)->status(OrderStatus::Selesai)->create(['total' => 600000]);
        $notYet = User::factory()->create();
        Order::factory()->for($notYet)->status(OrderStatus::Selesai)->create(['total' => 100000]);
        app(Settings::class)->set(['reseller.min_belanja' => 500000]);

        $this->artisan('resellers:promote')->expectsOutput('1 customer dinaikkan menjadi reseller.')->assertSuccessful();

        $this->assertSame(UserRole::Reseller, $eligible->fresh()->role);
        $this->assertSame(UserRole::Customer, $notYet->fresh()->role);
    }

    public function test_account_page_shows_progress_for_customers_only(): void
    {
        $customer = User::factory()->create();
        Order::factory()->for($customer)->status(OrderStatus::Selesai)->create(['total' => 250000]);

        $this->actingAs($customer)->get('/akun')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('menujuReseller.total_belanja', 250000)
            ->where('menujuReseller.batas', 1000000));

        $this->actingAs(User::factory()->role(UserRole::Reseller)->create())->get('/akun')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('menujuReseller', null));
    }
}
