<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_open_dashboard(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_dashboard_summarises_revenue_target_actions_and_low_stock(): void
    {
        $this->travelTo('2026-11-20 10:00:00');
        app(Settings::class)->set(['store.target_bulanan' => 200000]);
        Order::factory()->status(OrderStatus::Selesai)->create(['total' => 50000, 'paid_at' => '2026-11-05 10:00:00']);
        Order::factory()->status(OrderStatus::Diproses)->create(['total' => 30000, 'paid_at' => '2026-11-10 10:00:00']);
        Order::factory()->status(OrderStatus::Selesai)->create(['total' => 40000, 'paid_at' => '2026-10-15 10:00:00', 'created_at' => '2026-10-15 09:00:00']);
        Order::factory()->status(OrderStatus::MenungguVerifikasi)->create(['total' => 99999]);
        $habis = ProductVariant::factory()->create();
        Stock::factory()->for(ProductVariant::factory()->create(), 'variant')->withSlots(10)->create();

        $response = $this->actingAs(User::factory()->role(UserRole::Karyawan)->create())->get('/admin');

        $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Dashboard')
            ->where('stats.0.label', 'Pendapatan')
            ->where('stats.0.nilai', 80000)
            ->where('stats.0.sebelumnya', 40000)
            ->where('stats.0.pertumbuhan', 100)
            ->where('chart.bulanan.11.nilai', 80000)
            ->where('chart.bulanan.10.nilai', 40000)
            ->where('target.persen', 40)
            ->where('perluTindakan.verifikasi', 1)
            ->where('perluTindakan.menungguStok', 1)
            ->has('pesananTerbaru', 4)
            ->has('stokMenipis', 1)
            ->where('stokMenipis.0.id', $habis->id)
            ->where('stokMenipis.0.tersedia', 0));
    }
}
