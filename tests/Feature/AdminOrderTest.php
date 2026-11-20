<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function staff(): User
    {
        return User::factory()->role(UserRole::Karyawan)->create();
    }

    private function orderWithItem(OrderStatus $status = OrderStatus::MenungguVerifikasi, int $stockSlots = 1): Order
    {
        $variant = ProductVariant::factory()->create();
        if ($stockSlots > 0) {
            Stock::factory()->for($variant, 'variant')->withSlots($stockSlots)->create(['email' => 'akun@x.com']);
        }
        $order = Order::factory()->status($status)->create();
        OrderItem::factory()->for($order)->forVariant($variant)->create();

        return $order;
    }

    public function test_customer_cannot_open_admin_orders_or_proof(): void
    {
        $order = $this->orderWithItem();

        $this->actingAs($order->user)->get('/admin/pesanan')->assertForbidden();
        $this->get("/admin/pesanan/{$order->no_invoice}/bukti")->assertForbidden();
    }

    public function test_index_filters_by_status_and_search(): void
    {
        $waiting = $this->orderWithItem();
        $this->orderWithItem(OrderStatus::Selesai);

        $this->actingAs($this->staff())->get('/admin/pesanan?status=menunggu_verifikasi')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Orders/Index')
                ->has('orders.data', 1)
                ->where('orders.data.0.invoice', $waiting->no_invoice));

        $this->get('/admin/pesanan?q='.urlencode($waiting->user->name))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('orders.data.0.invoice', $waiting->no_invoice));
    }

    public function test_detail_shows_credentials_and_available_actions(): void
    {
        $order = $this->orderWithItem(OrderStatus::MenungguVerifikasi);

        $this->actingAs($this->staff())->get("/admin/pesanan/{$order->no_invoice}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Orders/Show')
                ->where('aksi.verifikasi', true)
                ->where('aksi.tolak', true)
                ->where('aksi.refund', false)
                ->where('order.items.0.kredensial', null));
    }

    public function test_approve_manual_payment_delivers_accounts(): void
    {
        $order = $this->orderWithItem();

        $this->actingAs($this->staff())->post("/admin/pesanan/{$order->no_invoice}/verifikasi")
            ->assertSessionHas('success', 'Pembayaran diterima dan semua akun sudah terkirim ke pembeli.');

        $order->refresh();
        $this->assertSame(OrderStatus::Selesai, $order->status);
        $this->assertSame('akun@x.com', $order->items->first()->credentials()['email']);
    }

    public function test_approve_without_stock_keeps_order_processing(): void
    {
        $order = $this->orderWithItem(stockSlots: 0);

        $this->actingAs($this->staff())->post("/admin/pesanan/{$order->no_invoice}/verifikasi")
            ->assertSessionHas('success', 'Pembayaran diterima, tetapi stok belum cukup. Pesanan akan dilengkapi otomatis saat stok ditambah.');

        $this->assertSame(OrderStatus::Diproses, $order->fresh()->status);
    }

    public function test_qris_order_cannot_be_approved_manually(): void
    {
        $order = Order::factory()->qris()->create();

        $this->actingAs($this->staff())->post("/admin/pesanan/{$order->no_invoice}/verifikasi")
            ->assertSessionHas('error', 'Pesanan ini tidak bisa ditandai lunas dari panel.');

        $this->assertSame(OrderStatus::MenungguBayar, $order->fresh()->status);
    }

    public function test_late_paid_cancelled_order_can_be_processed(): void
    {
        $order = $this->orderWithItem(OrderStatus::Dibatalkan);
        $order->update(['metode_bayar' => 'qris', 'late_paid_at' => now()]);

        $this->actingAs($this->staff())->post("/admin/pesanan/{$order->no_invoice}/verifikasi");

        $this->assertSame(OrderStatus::Selesai, $order->fresh()->status);
    }

    public function test_reject_requires_reason_and_returns_order_to_awaiting_payment(): void
    {
        $order = $this->orderWithItem();
        $staff = $this->staff();

        $this->actingAs($staff)->post("/admin/pesanan/{$order->no_invoice}/tolak", [])->assertSessionHasErrors('alasan');
        $this->post("/admin/pesanan/{$order->no_invoice}/tolak", ['alasan' => 'Nominal kurang'])->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(OrderStatus::MenungguBayar, $order->status);
        $this->assertSame('Nominal kurang', $order->alasan_tolak);
    }

    public function test_cancel_qris_order_cancels_gateway_invoice(): void
    {
        app(Settings::class)->set(['qris.base_url' => 'https://qris.test', 'qris.api_key' => 'k']);
        Http::fake(['qris.test/*' => Http::response(['status' => 'CANCELLED'])]);
        $order = Order::factory()->qris()->create();

        $this->actingAs($this->staff())->post("/admin/pesanan/{$order->no_invoice}/batal", ['catatan' => 'Permintaan pembeli']);

        $order->refresh();
        $this->assertSame(OrderStatus::Dibatalkan, $order->status);
        $this->assertStringContainsString('Dibatalkan admin: Permintaan pembeli', $order->catatan_admin);
        Http::assertSent(fn ($request) => $request->url() === "https://qris.test/v1/transactions/{$order->gateway_id}/cancel");
    }

    public function test_paid_order_cannot_be_cancelled_but_can_be_refunded(): void
    {
        $order = $this->orderWithItem(OrderStatus::Selesai);
        $staff = $this->staff();

        $this->actingAs($staff)->post("/admin/pesanan/{$order->no_invoice}/batal", ['catatan' => 'x'])
            ->assertSessionHas('error', 'Pesanan yang sudah dibayar tidak bisa dibatalkan. Gunakan refund.');
        $this->post("/admin/pesanan/{$order->no_invoice}/refund", ['catatan' => 'Akun bermasalah'])->assertSessionHas('success');

        $this->assertSame(OrderStatus::Refund, $order->fresh()->status);
    }

    public function test_proof_is_served_to_staff(): void
    {
        Storage::fake('local');
        $path = UploadedFile::fake()->image('bukti.jpg')->store('bukti-bayar', 'local');
        $order = Order::factory()->status(OrderStatus::MenungguVerifikasi)->create(['bukti_bayar_path' => $path]);

        $this->actingAs($this->staff())->get("/admin/pesanan/{$order->no_invoice}/bukti")->assertOk();
    }
}
