<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Settings;
use App\Services\TermsContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TermsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'intro' => 'Aturan {nama_toko}.',
            'bagian' => [
                ['judul' => 'Reseller', 'icon' => 'heroicons:wallet', 'isi' => "- Minimal **{saldo_reseller}**\n- {kode_asing} tetap"],
            ],
            'perbarui_tanggal' => true,
            ...$overrides,
        ];
    }

    public function test_guest_sees_default_terms_with_placeholders_filled_from_settings(): void
    {
        app(Settings::class)->set(['manual.batas_jam' => 12, 'store.whatsapp' => '628123']);

        $this->get(route('terms'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Terms')
                ->has('bagian', count(TermsContent::defaults()['bagian']))
                ->where('bagian.3.isi', fn (string $isi) => str_contains($isi, '**12 jam**') && ! str_contains($isi, '{'))
                ->where('whatsapp', '628123'));
    }

    public function test_admin_edits_terms_and_public_page_follows(): void
    {
        Carbon::setTestNow('2026-12-05 10:00');
        app(Settings::class)->set(['store.nama' => 'Toko Uji', 'reseller.min_saldo' => 2000000]);
        $admin = User::factory()->role(UserRole::Admin)->create();

        $this->actingAs($admin)->put(route('admin.terms.update'), $this->payload())->assertSessionHasNoErrors();

        $this->get(route('terms'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('intro', 'Aturan Toko Uji.')
            ->where('bagian.0.isi', "- Minimal **Rp2.000.000**\n- {kode_asing} tetap")
            ->where('diperbarui', '5 Desember 2026'));
    }

    public function test_date_is_kept_when_admin_only_fixes_typos(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();
        Carbon::setTestNow('2026-12-05');
        $this->actingAs($admin)->put(route('admin.terms.update'), $this->payload());

        Carbon::setTestNow('2026-12-20');
        $this->actingAs($admin)->put(route('admin.terms.update'), $this->payload(['intro' => 'Diperbaiki', 'perbarui_tanggal' => false]));

        $this->assertSame('2026-12-05', app(TermsContent::class)->raw()['diperbarui']);
        $this->assertSame('Diperbaiki', app(TermsContent::class)->raw()['intro']);
    }

    public function test_invalid_icon_and_empty_sections_are_rejected(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();

        $this->actingAs($admin)
            ->put(route('admin.terms.update'), $this->payload(['bagian' => [['judul' => 'X', 'icon' => 'mdi:skull', 'isi' => 'Y']]]))
            ->assertSessionHasErrors('bagian.0.icon');
        $this->actingAs($admin)
            ->put(route('admin.terms.update'), $this->payload(['bagian' => []]))
            ->assertSessionHasErrors('bagian');
    }

    public function test_karyawan_cannot_edit_terms(): void
    {
        $karyawan = User::factory()->role(UserRole::Karyawan)->create();

        $this->actingAs($karyawan)->get(route('admin.terms.edit'))->assertForbidden();
        $this->actingAs($karyawan)->put(route('admin.terms.update'), $this->payload())->assertForbidden();
    }
}
