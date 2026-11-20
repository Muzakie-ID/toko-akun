<?php

namespace Tests\Feature;

use App\Enums\MetodeBayar;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Notifications\OrderDelivered;
use App\Services\Orders\OrderCheckout;
use App\Services\Orders\OrderLifecycle;
use App\Services\Payment\PaymentGatewayException;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_test';

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        app(Settings::class)->set([
            'qris.enabled' => true,
            'qris.base_url' => 'https://qris.test',
            'qris.api_key' => 'qm_live_test',
            'qris.webhook_secret' => self::WEBHOOK_SECRET,
            'manual.enabled' => true,
            'manual.rekening' => [['bank' => 'BCA', 'nomor' => '123', 'nama' => 'Akunhaus']],
        ]);
    }

    private function variantWithStock(int $slots = 1, int $stocks = 1, array $variantAttributes = []): ProductVariant
    {
        $variant = ProductVariant::factory()->create($variantAttributes);
        Stock::factory()->count($stocks)->for($variant, 'variant')->withSlots($slots)->create();

        return $variant;
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function postWebhook(array $event, ?string $secret = self::WEBHOOK_SECRET): TestResponse
    {
        $body = json_encode($event);
        $timestamp = (string) (int) round(microtime(true) * 1000);

        return $this->call('POST', '/webhooks/qrismuz', server: [
            'HTTP_X_TIMESTAMP' => $timestamp,
            'HTTP_X_SIGNATURE' => hash_hmac('sha256', $timestamp.'.'.$body, $secret),
            'CONTENT_TYPE' => 'application/json',
        ], content: $body);
    }

    public function test_manual_checkout_creates_one_row_per_account_and_clears_cart(): void
    {
        $variant = $this->variantWithStock(slots: 1, stocks: 3, variantAttributes: ['harga' => 25000]);
        $user = User::factory()->create();
        $user->cartItems()->create(['product_variant_id' => $variant->id, 'qty' => 2]);

        $order = app(OrderCheckout::class)->checkout($user, [$variant->id => 2], MetodeBayar::Manual);

        $this->assertSame(OrderStatus::MenungguBayar, $order->status);
        $this->assertSame(50000, $order->total);
        $this->assertSame(2, $order->items()->count());
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertTrue($order->expires_at->between(now()->addHours(23), now()->addHours(25)));
    }

    public function test_reseller_pays_reseller_price(): void
    {
        $variant = $this->variantWithStock(variantAttributes: ['harga' => 25000, 'harga_reseller' => 20000]);
        $reseller = User::factory()->role(UserRole::Reseller)->create();

        $order = app(OrderCheckout::class)->checkout($reseller, [$variant->id => 1], MetodeBayar::Manual);

        $this->assertSame(20000, $order->total);
    }

    public function test_checkout_fails_when_stock_is_not_enough(): void
    {
        $variant = $this->variantWithStock(slots: 1, stocks: 1);

        try {
            app(OrderCheckout::class)->checkout(User::factory()->create(), [$variant->id => 2], MetodeBayar::Manual);
            $this->fail('Checkout seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('tinggal 1', $e->errors()['checkout'][0]);
        }

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_qris_checkout_creates_gateway_invoice_with_final_amount(): void
    {
        Http::fake(['qris.test/v1/transactions' => Http::response([
            'id' => '01M6SGD18EMZHM2WPRZ4FT2ZHB',
            'status' => 'PENDING',
            'amount' => 25000,
            'unique_code' => 87,
            'final_amount' => 25087,
            'qris_payload' => '000201',
            'qr_image_url' => 'https://qris.test/qr.png',
            'checkout_url' => 'https://qris.test/pay/01M6',
            'expires_at' => '2026-11-20T10:15:00.000Z',
        ])]);
        $variant = $this->variantWithStock(variantAttributes: ['harga' => 25000]);

        $order = app(OrderCheckout::class)->checkout(User::factory()->create(), [$variant->id => 1], MetodeBayar::Qris);

        $this->assertSame(25087, $order->total);
        $this->assertSame(87, $order->kode_unik);
        $this->assertSame('01M6SGD18EMZHM2WPRZ4FT2ZHB', $order->gateway_id);
        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer qm_live_test')
            && $request->hasHeader('Idempotency-Key', $order->no_invoice)
            && $request['merchant_ref'] === $order->no_invoice
            && $request['amount'] === 25000);
    }

    public function test_qris_gateway_failure_cancels_order(): void
    {
        Http::fake(['qris.test/*' => Http::response(['message' => 'QRIS source not found'], 422)]);
        $variant = $this->variantWithStock();

        try {
            app(OrderCheckout::class)->checkout(User::factory()->create(), [$variant->id => 1], MetodeBayar::Qris);
            $this->fail('Seharusnya gagal.');
        } catch (PaymentGatewayException $e) {
            $this->assertSame('Gateway QRIS menolak permintaan: QRIS source not found', $e->getMessage());
        }

        $this->assertSame(OrderStatus::Dibatalkan, Order::sole()->status);
    }

    public function test_disabled_method_is_rejected(): void
    {
        app(Settings::class)->set(['qris.enabled' => false]);
        $variant = $this->variantWithStock();

        $this->expectException(ValidationException::class);

        app(OrderCheckout::class)->checkout(User::factory()->create(), [$variant->id => 1], MetodeBayar::Qris);
    }

    public function test_paid_order_gets_slots_fifo_and_completes(): void
    {
        $variant = ProductVariant::factory()->sharing()->create(['durasi_hari' => 7]);
        $older = Stock::factory()->for($variant, 'variant')->withSlots(2, 'Profil')->create(['email' => 'lama@x.com']);
        Stock::factory()->for($variant, 'variant')->withSlots(2, 'Profil')->create(['email' => 'baru@x.com']);
        $order = Order::factory()->create();
        OrderItem::factory()->count(2)->for($order)->forVariant($variant)->create();

        $order = app(OrderLifecycle::class)->markPaid($order);

        $this->assertSame(OrderStatus::Selesai, $order->status);
        $credentials = $order->items->map->credentials();
        $this->assertSame(['lama@x.com', 'lama@x.com'], $credentials->pluck('email')->all());
        $this->assertSame(['Profil 1', 'Profil 2'], $credentials->pluck('info')->all());
        $this->assertTrue($order->items[0]->aktif_sampai->isSameDay(now()->addDays(7)));
        $this->assertSame(2, $variant->product->fresh()->total_terjual);
        $this->assertSame(0, $older->slots()->whereNull('order_item_id')->count());
        Notification::assertSentTo($order->user, OrderDelivered::class);
    }

    public function test_paid_order_without_enough_stock_waits_and_completes_after_import(): void
    {
        $variant = $this->variantWithStock(slots: 1, stocks: 1);
        $order = Order::factory()->create();
        OrderItem::factory()->count(2)->for($order)->forVariant($variant)->create();

        $order = app(OrderLifecycle::class)->markPaid($order);

        $this->assertSame(OrderStatus::Diproses, $order->status);
        $this->assertSame(1, $order->items()->whereHas('slot')->count());

        $admin = User::factory()->role(UserRole::Admin)->create();
        $this->actingAs($admin)->post('/admin/stok/import', [
            'product_variant_id' => $variant->id, 'akun' => 'baru@x.com|pw', 'aksi' => 'simpan',
        ]);

        $this->assertSame(1, session('import_result.pesanan_terkirim'));
        $this->assertSame(OrderStatus::Selesai, $order->fresh()->status);
    }

    public function test_mark_paid_twice_does_not_assign_extra_slots(): void
    {
        $variant = $this->variantWithStock(slots: 1, stocks: 2);
        $order = Order::factory()->create();
        OrderItem::factory()->for($order)->forVariant($variant)->create();
        $lifecycle = app(OrderLifecycle::class);

        $lifecycle->markPaid($order);
        $lifecycle->markPaid($order->fresh());

        $this->assertSame(1, $variant->availableSlots()->count());
    }

    public function test_webhook_with_bad_signature_is_rejected(): void
    {
        $order = Order::factory()->qris()->create();

        $this->postWebhook(['id' => 'whe_1', 'type' => 'transaction.paid', 'data' => ['merchant_ref' => $order->no_invoice, 'final_amount' => $order->total]], secret: 'salah')
            ->assertUnauthorized();

        $this->assertSame(OrderStatus::MenungguBayar, $order->fresh()->status);
    }

    public function test_paid_webhook_delivers_order_once_even_if_sent_twice(): void
    {
        $variant = $this->variantWithStock(slots: 1, stocks: 2);
        $order = Order::factory()->qris()->create();
        OrderItem::factory()->for($order)->forVariant($variant)->create();
        $event = ['id' => 'whe_1', 'type' => 'transaction.paid', 'data' => [
            'merchant_ref' => $order->no_invoice, 'final_amount' => $order->total, 'paid_at' => '2026-11-20T10:03:20.000Z',
        ]];

        $this->postWebhook($event)->assertOk();
        $this->postWebhook($event)->assertOk();

        $order->refresh();
        $this->assertSame(OrderStatus::Selesai, $order->status);
        $this->assertSame('2026-11-20 10:03:20', $order->paid_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame(1, $variant->availableSlots()->count());
        $this->assertDatabaseCount('webhook_events', 1);
    }

    public function test_paid_webhook_with_wrong_amount_is_flagged_not_paid(): void
    {
        $order = Order::factory()->qris()->create();

        $this->postWebhook(['id' => 'whe_2', 'type' => 'transaction.paid', 'data' => ['merchant_ref' => $order->no_invoice, 'final_amount' => 1000]])
            ->assertOk();

        $order->refresh();
        $this->assertSame(OrderStatus::MenungguBayar, $order->status);
        $this->assertStringContainsString('nominal berbeda: Rp1000', $order->catatan_admin);
    }

    public function test_expired_and_late_paid_webhooks(): void
    {
        $order = Order::factory()->qris()->create();

        $this->postWebhook(['id' => 'whe_3', 'type' => 'transaction.expired', 'data' => ['merchant_ref' => $order->no_invoice]]);
        $this->assertSame(OrderStatus::Kedaluwarsa, $order->fresh()->status);

        $this->postWebhook(['id' => 'whe_4', 'type' => 'transaction.late_paid', 'data' => ['merchant_ref' => $order->no_invoice, 'paid_at' => '2026-11-20T11:00:00.000Z']]);
        $order->refresh();
        $this->assertSame(OrderStatus::Kedaluwarsa, $order->status);
        $this->assertNotNull($order->late_paid_at);
        $this->assertStringContainsString('setelah tagihan kedaluwarsa', $order->catatan_admin);
    }

    public function test_expire_command_only_expires_overdue_unpaid_orders(): void
    {
        $overdue = Order::factory()->create(['expires_at' => now()->subHour()]);
        $fresh = Order::factory()->create(['expires_at' => now()->addHour()]);
        $waitingVerification = Order::factory()->status(OrderStatus::MenungguVerifikasi)->create(['expires_at' => now()->subHour()]);

        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame(OrderStatus::Kedaluwarsa, $overdue->fresh()->status);
        $this->assertSame(OrderStatus::MenungguBayar, $fresh->fresh()->status);
        $this->assertSame(OrderStatus::MenungguVerifikasi, $waitingVerification->fresh()->status);
    }
}
