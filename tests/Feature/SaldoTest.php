<?php

namespace Tests\Feature;

use App\Enums\MetodeBayar;
use App\Enums\OrderStatus;
use App\Enums\SaldoTipe;
use App\Enums\TopupStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\Topup;
use App\Models\User;
use App\Services\Saldo\SaldoLedger;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SaldoTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_test';

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        app(Settings::class)->set([
            'qris.enabled' => true, 'qris.base_url' => 'https://qris.test',
            'qris.api_key' => 'k', 'qris.webhook_secret' => self::SECRET,
        ]);
    }

    private function userWithSaldo(int $saldo, UserRole $role = UserRole::Customer): User
    {
        $user = User::factory()->role($role)->create();
        if ($saldo > 0) {
            app(SaldoLedger::class)->credit($user, $saldo, SaldoTipe::Penyesuaian, 'Saldo awal');
        }

        return $user->fresh();
    }

    private function variant(): ProductVariant
    {
        $variant = ProductVariant::factory()->create(['harga' => 25000, 'harga_reseller' => 20000]);
        Stock::factory()->count(3)->for($variant, 'variant')->withSlots()->create();

        return $variant;
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function webhook(array $event): TestResponse
    {
        $body = json_encode($event);
        $ts = (string) (int) round(microtime(true) * 1000);

        return $this->call('POST', '/webhooks/qrismuz', server: [
            'HTTP_X_TIMESTAMP' => $ts,
            'HTTP_X_SIGNATURE' => hash_hmac('sha256', $ts.'.'.$body, self::SECRET),
            'CONTENT_TYPE' => 'application/json',
        ], content: $body);
    }

    public function test_reseller_price_follows_current_saldo(): void
    {
        $variant = $this->variant();
        $url = "/produk/{$variant->product->slug}";

        $this->actingAs($this->userWithSaldo(1000000))->get($url)
            ->assertInertia(fn (AssertableInertia $page) => $page->where('varian.0.harga', 20000)->where('hargaReseller', true));
        $this->actingAs($this->userWithSaldo(999999))->get($url)
            ->assertInertia(fn (AssertableInertia $page) => $page->where('varian.0.harga', 25000)->where('hargaReseller', false));
        $this->actingAs($this->userWithSaldo(0, UserRole::Reseller))->get($url)
            ->assertInertia(fn (AssertableInertia $page) => $page->where('varian.0.harga', 20000));

        app(Settings::class)->set(['reseller.otomatis' => false]);
        $this->actingAs($this->userWithSaldo(5000000))->get($url)
            ->assertInertia(fn (AssertableInertia $page) => $page->where('varian.0.harga', 25000));
    }

    public function test_pay_with_saldo_uses_reseller_price_checked_before_debit_and_delivers_instantly(): void
    {
        $variant = $this->variant();
        $user = $this->userWithSaldo(1010000);
        $user->cartItems()->create(['product_variant_id' => $variant->id, 'qty' => 1]);

        $this->actingAs($user)->post('/checkout', ['metode_bayar' => 'saldo']);

        $order = Order::sole();
        $this->assertSame(MetodeBayar::Saldo, $order->metode_bayar);
        $this->assertSame(OrderStatus::Selesai, $order->status);
        $this->assertSame(20000, $order->total);
        $this->assertSame(990000, $user->fresh()->saldo);
        $this->assertDatabaseHas('saldo_mutations', ['order_id' => $order->id, 'tipe' => 'belanja', 'jumlah' => -20000, 'saldo_akhir' => 990000]);

        // Saldo kini di bawah Rp1jt: harga kembali normal
        $this->get("/produk/{$variant->product->slug}")->assertInertia(fn (AssertableInertia $page) => $page->where('varian.0.harga', 25000));
    }

    public function test_insufficient_saldo_creates_nothing(): void
    {
        $variant = $this->variant();
        $user = $this->userWithSaldo(10000);
        $user->cartItems()->create(['product_variant_id' => $variant->id, 'qty' => 1]);

        $this->actingAs($user)->post('/checkout', ['metode_bayar' => 'saldo'])
            ->assertSessionHasErrors(['metode_bayar' => 'Saldo tidak cukup. Top-up dulu atau pilih QRIS.']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10000, $user->fresh()->saldo);
        $this->assertSame(1, $user->cartItems()->count());
    }

    public function test_topup_creates_qris_invoice_and_validates_amount(): void
    {
        Http::fake(['qris.test/v1/transactions' => Http::response([
            'id' => 'TX1', 'final_amount' => 100087, 'unique_code' => 87, 'qr_image_url' => 'https://qris.test/qr.png',
            'checkout_url' => 'https://qris.test/pay/TX1', 'expires_at' => '2026-11-20T10:15:00.000Z',
        ])]);
        $user = User::factory()->create();

        $this->actingAs($user)->post('/saldo/topup', ['jumlah' => 5000])->assertSessionHasErrors('jumlah');
        $this->post('/saldo/topup', ['jumlah' => 100000])->assertRedirect('/saldo/topup/'.Topup::sole()->no_invoice);

        $topup = Topup::sole();
        $this->assertSame(100087, $topup->total);
        Http::assertSent(fn ($request) => $request['merchant_ref'] === $topup->no_invoice && $request['amount'] === 100000);
    }

    public function test_topup_requires_qris(): void
    {
        app(Settings::class)->set(['qris.enabled' => false]);

        $this->actingAs(User::factory()->create())->post('/saldo/topup', ['jumlah' => 100000])
            ->assertSessionHasErrors(['jumlah' => 'Top-up sedang tidak tersedia. Coba lagi nanti.']);
    }

    public function test_paid_topup_webhook_credits_saldo_exactly_once(): void
    {
        $topup = Topup::factory()->create();
        $data = ['merchant_ref' => $topup->no_invoice, 'final_amount' => $topup->total, 'paid_at' => '2026-11-20T10:00:00.000Z'];

        $this->webhook(['id' => 'whe_1', 'type' => 'transaction.paid', 'data' => $data])->assertOk();
        $this->webhook(['id' => 'whe_1', 'type' => 'transaction.paid', 'data' => $data])->assertOk();
        $this->webhook(['id' => 'whe_2', 'type' => 'transaction.late_paid', 'data' => $data])->assertOk();

        $this->assertSame(TopupStatus::Lunas, $topup->fresh()->status);
        $this->assertSame(100000, $topup->user->fresh()->saldo);
        $this->assertDatabaseCount('saldo_mutations', 1);
    }

    public function test_topup_webhook_with_wrong_amount_does_not_credit(): void
    {
        $topup = Topup::factory()->create();

        $this->webhook(['id' => 'whe_3', 'type' => 'transaction.paid', 'data' => ['merchant_ref' => $topup->no_invoice, 'final_amount' => 999]]);

        $this->assertSame(TopupStatus::MenungguBayar, $topup->fresh()->status);
        $this->assertSame(0, $topup->user->fresh()->saldo);
    }

    public function test_late_paid_topup_after_expiry_is_still_credited(): void
    {
        $topup = Topup::factory()->create(['expires_at' => now()->subHour()]);

        $this->artisan('orders:expire')->assertSuccessful();
        $this->assertSame(TopupStatus::Kedaluwarsa, $topup->fresh()->status);

        $this->webhook(['id' => 'whe_4', 'type' => 'transaction.late_paid', 'data' => ['merchant_ref' => $topup->no_invoice, 'final_amount' => $topup->total]]);

        $this->assertSame(100000, $topup->user->fresh()->saldo);
    }

    public function test_topup_page_is_private(): void
    {
        $topup = Topup::factory()->create();

        $this->actingAs(User::factory()->create())->get("/saldo/topup/{$topup->no_invoice}")->assertNotFound();
        $this->actingAs($topup->user)->get("/saldo/topup/{$topup->no_invoice}")->assertOk();
    }

    public function test_refund_saldo_order_returns_money_to_saldo(): void
    {
        $user = $this->userWithSaldo(0);
        $order = Order::factory()->for($user)->status(OrderStatus::Selesai)->create(['metode_bayar' => MetodeBayar::Saldo, 'total' => 25000]);
        $qrisOrder = Order::factory()->for($user)->qris()->status(OrderStatus::Selesai)->create(['total' => 30087]);
        $staff = User::factory()->role(UserRole::Karyawan)->create();

        $this->actingAs($staff)->post("/admin/pesanan/{$order->no_invoice}/refund", ['catatan' => 'Akun mati'])
            ->assertSessionHas('success', 'Pesanan direfund, dana dikembalikan ke saldo pembeli.');
        $this->post("/admin/pesanan/{$qrisOrder->no_invoice}/refund", ['catatan' => 'Ganti rugi', 'ke_saldo' => true]);

        $this->assertSame(55087, $user->fresh()->saldo);
        $this->assertSame(2, $user->saldoMutations()->where('tipe', 'refund')->count());
    }

    public function test_admin_adjusts_saldo_but_cannot_go_negative(): void
    {
        $user = $this->userWithSaldo(10000);
        $admin = User::factory()->role(UserRole::Admin)->create();

        $this->actingAs($admin)->post("/admin/pengguna/{$user->id}/saldo", ['jumlah' => 5000, 'keterangan' => 'Kompensasi'])->assertSessionHas('success');
        $this->post("/admin/pengguna/{$user->id}/saldo", ['jumlah' => -20000, 'keterangan' => 'Koreksi'])
            ->assertSessionHas('error', "Saldo {$user->name} tidak cukup untuk dikurangi sebesar itu.");

        $this->assertSame(15000, $user->fresh()->saldo);
        $this->assertDatabaseHas('saldo_mutations', ['user_id' => $user->id, 'tipe' => 'penyesuaian', 'jumlah' => 5000, 'created_by' => $admin->id]);

        $this->actingAs(User::factory()->role(UserRole::Karyawan)->create())
            ->post("/admin/pengguna/{$user->id}/saldo", ['jumlah' => 5000, 'keterangan' => 'x'])->assertForbidden();
    }

    public function test_saldo_page_shows_balance_reseller_progress_and_history(): void
    {
        $user = $this->userWithSaldo(250000);

        $this->actingAs($user)->get('/saldo')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Saldo')
            ->where('saldo', 250000)
            ->where('reseller.aktif', false)
            ->where('reseller.batas', 1000000)
            ->has('mutasi.data', 1));
    }

    public function test_admin_topup_list(): void
    {
        Topup::factory()->create(['status' => TopupStatus::Lunas, 'paid_at' => now()]);

        $this->actingAs(User::factory()->role(UserRole::Karyawan)->create())->get('/admin/topup')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Topups/Index')
                ->has('topups.data', 1)
                ->where('totalMasukBulanIni', 100000));
    }
}
