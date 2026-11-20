<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminUserSettingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->role(UserRole::Admin)->create();
    }

    public function test_karyawan_cannot_manage_users_or_settings(): void
    {
        $karyawan = User::factory()->role(UserRole::Karyawan)->create();

        $this->actingAs($karyawan)->get('/admin/pengguna')->assertForbidden();
        $this->put('/admin/pengaturan', [])->assertForbidden();
    }

    public function test_admin_changes_role_but_not_own_role(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();

        $this->actingAs($admin)->put("/admin/pengguna/{$customer->id}/role", ['role' => 'reseller'])->assertSessionHas('success');
        $this->put("/admin/pengguna/{$admin->id}/role", ['role' => 'customer'])->assertSessionHas('error', 'Kamu tidak bisa mengubah role akunmu sendiri.');

        $this->assertSame(UserRole::Reseller, $customer->fresh()->role);
        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
    }

    public function test_admin_creates_staff_account(): void
    {
        $this->actingAs($this->admin())->post('/admin/pengguna', [
            'name' => 'Karyawan Baru', 'email' => 'staf@x.com', 'password' => 'rahasia123', 'role' => 'karyawan',
        ])->assertSessionHas('success');

        $this->assertSame(UserRole::Karyawan, User::firstWhere('email', 'staf@x.com')->role);
    }

    public function test_admin_cannot_create_customer_from_staff_form(): void
    {
        $this->actingAs($this->admin())->post('/admin/pengguna', [
            'name' => 'X', 'email' => 'x@x.com', 'password' => 'rahasia123', 'role' => 'customer',
        ])->assertSessionHasErrors('role');
    }

    /**
     * @return array<string, mixed>
     */
    private function settingsPayload(array $overrides = []): array
    {
        return [
            'store_nama' => 'Akunhaus', 'store_whatsapp' => '62812', 'store_target_bulanan' => 1000000,
            'qris_enabled' => true, 'qris_base_url' => 'https://qris.test/', 'qris_api_key' => 'qm_live_1',
            'qris_webhook_secret' => 'whsec_1', 'qris_expiry_min' => 15,
            'manual_enabled' => true, 'manual_rekening' => [['bank' => 'BCA', 'nomor' => '123', 'nama' => 'Akunhaus']],
            'manual_batas_jam' => 24, 'reseller_otomatis' => true, 'reseller_min_belanja' => 1000000,
            ...$overrides,
        ];
    }

    public function test_settings_save_secrets_encrypted_and_keep_them_when_blank(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put('/admin/pengaturan', $this->settingsPayload())->assertSessionHas('success');
        $this->put('/admin/pengaturan', $this->settingsPayload(['qris_api_key' => '', 'qris_webhook_secret' => null]))->assertSessionHas('success');

        $settings = app(Settings::class);
        $this->assertSame('qm_live_1', $settings->get('qris.api_key'));
        $this->assertSame('https://qris.test', $settings->get('qris.base_url'));
        $this->assertTrue($settings->qrisReady());
        $this->assertDatabaseMissing('settings', ['key' => 'qris.api_key', 'value' => '"qm_live_1"']);

        $this->get('/admin/pengaturan')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('secrets.qris_api_key', true)
            ->missing('settings.qris_api_key'));
    }

    public function test_enabling_qris_requires_base_url(): void
    {
        $this->actingAs($this->admin())->put('/admin/pengaturan', $this->settingsPayload(['qris_base_url' => '']))
            ->assertSessionHasErrors(['qris_base_url' => 'URL API gateway wajib diisi bila QRIS diaktifkan.']);
    }

    public function test_connection_test_reports_listener_state(): void
    {
        app(Settings::class)->set(['qris.enabled' => true, 'qris.base_url' => 'https://qris.test', 'qris.api_key' => 'k', 'qris.webhook_secret' => 's']);
        Http::fake(['qris.test/v1/status' => Http::response(['listener_online' => false, 'confirmation' => 'delayed'])]);

        $this->actingAs($this->admin())->post('/admin/pengaturan/tes-qris')
            ->assertSessionHas('success', 'Terhubung ke gateway, tetapi HP listener sedang offline. Pembayaran akan terkonfirmasi tertunda.');
    }

    public function test_category_crud_and_delete_guard(): void
    {
        $staff = User::factory()->role(UserRole::Karyawan)->create();
        $used = Product::factory()->create()->category;

        $this->actingAs($staff)->post('/admin/kategori', ['nama' => 'Musik'])->assertSessionHas('success');
        $this->post('/admin/kategori', ['nama' => 'musik'])->assertSessionHasErrors(['slug' => 'Kategori dengan nama ini sudah ada.']);
        $this->delete("/admin/kategori/{$used->id}")->assertSessionHas('error');
        $this->delete('/admin/kategori/'.Category::firstWhere('slug', 'musik')->id)->assertSessionHas('success');

        $this->assertModelExists($used);
    }
}
