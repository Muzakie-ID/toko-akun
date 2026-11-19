<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StockImportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->role(UserRole::Karyawan)->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(ProductVariant $variant, string $akun, string $aksi = 'simpan', array $overrides = []): array
    {
        return ['product_variant_id' => $variant->id, 'akun' => $akun, 'aksi' => $aksi, 'slot_default' => null, ...$overrides];
    }

    public function test_guest_is_redirected_and_customer_is_forbidden(): void
    {
        $variant = ProductVariant::factory()->create();
        $customer = User::factory()->create();

        $this->get('/admin/stok/import')->assertRedirect('/login');
        $this->actingAs($customer)->post('/admin/stok/import', $this->payload($variant, 'a@x.com|pw'))->assertForbidden();

        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_staff_sees_import_page_with_variants(): void
    {
        $variant = ProductVariant::factory()->sharing()->create(['durasi_hari' => 7]);

        $response = $this->actingAs($this->admin())->get('/admin/stok/import');

        $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/StockImport')
            ->where('variants.0.id', $variant->id)
            ->where('variants.0.jenis_akun', 'sharing')
            ->where('variants.0.durasi_label', '7 Hari'));
    }

    public function test_preview_saves_nothing_and_never_puts_password_in_session(): void
    {
        $variant = ProductVariant::factory()->create();

        $response = $this->actingAs($this->admin())
            ->post('/admin/stok/import', $this->payload($variant, "a@x.com|rahasia1\nrusak", 'preview'));

        $response->assertRedirect();
        $preview = session('import_preview');
        $this->assertSame([], $preview[0]['errors']);
        $this->assertArrayNotHasKey('password', $preview[0]);
        $this->assertSame(['Format salah, gunakan email|password.'], $preview[1]['errors']);
        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_private_import_saves_valid_lines_encrypted_and_skips_invalid(): void
    {
        $variant = ProductVariant::factory()->create();
        $admin = $this->admin();
        $akun = "# komentar diabaikan\nsatu@x.com|pw|1\n\n dua@x.com | p@ss word | | Login via web \ntiga@x.com|pw|2";

        $response = $this->actingAs($admin)->post('/admin/stok/import', $this->payload($variant, $akun));

        $response->assertRedirect();
        $result = session('import_result');
        $this->assertSame(2, $result['tersimpan']);
        $this->assertSame(5, $result['ditolak'][0]['baris']);
        $this->assertSame(['Akun private hanya boleh 1 slot.'], $result['ditolak'][0]['errors']);

        $stock = Stock::with('slots')->get()->firstWhere('email', 'dua@x.com');
        $this->assertSame('p@ss word', $stock->password);
        $this->assertSame(1, $stock->total_slot);
        $this->assertSame($admin->id, $stock->created_by);
        $this->assertSame(['Login via web'], $stock->slots->pluck('info')->all());

        $raw = DB::table('stocks')->where('id', $stock->id)->first();
        $this->assertNotSame('dua@x.com', $raw->email);
        $this->assertNotSame('p@ss word', $raw->password);
    }

    public function test_sharing_import_creates_slots_with_per_slot_or_shared_info(): void
    {
        $variant = ProductVariant::factory()->sharing()->create();
        $akun = "a@x.com|pw|3|Profil 1 PIN 1111;Profil 2 PIN 2222;Profil 3 PIN 3333\nb@x.com|pw||Profil bebas";

        $this->actingAs($this->admin())
            ->post('/admin/stok/import', $this->payload($variant, $akun, overrides: ['slot_default' => 2]));

        $this->assertSame(2, session('import_result.tersimpan'));
        $stocks = Stock::with('slots')->get()->keyBy('email');
        $this->assertSame(
            [1 => 'Profil 1 PIN 1111', 2 => 'Profil 2 PIN 2222', 3 => 'Profil 3 PIN 3333'],
            $stocks['a@x.com']->slots->pluck('info', 'nomor')->all(),
        );
        $this->assertSame(2, $stocks['b@x.com']->total_slot);
        $this->assertSame(['Profil bebas', 'Profil bebas'], $stocks['b@x.com']->slots->pluck('info')->all());
    }

    /**
     * @return array<string, array{0: string, 1: string|null, 2: string}>
     */
    public static function invalidSharingLines(): array
    {
        return [
            'info wajib' => ['a@x.com|pw|2', null, 'Akun sharing wajib punya info tambahan (mis. profil/PIN per slot).'],
            'jumlah info beda' => ['a@x.com|pw|3|P1;P2', null, 'Jumlah info (2) tidak sama dengan jumlah slot (3). Pisahkan info per slot dengan ";".'],
            'slot kosong tanpa default' => ['a@x.com|pw||P1', null, 'Jumlah slot belum diisi (isi di baris atau di "Slot default").'],
            'slot bukan angka' => ['a@x.com|pw|dua|P1', '2', 'Slot "dua" harus berupa angka.'],
            'slot sharing 1' => ['a@x.com|pw|1|P1', null, 'Slot akun sharing harus 2–50.'],
            'password mengandung pipa' => ['a@x.com|p|w|2|P1', null, 'Terlalu banyak tanda "|". Format: email|password|slot|info (password tidak boleh mengandung "|").'],
            'password kosong' => ['a@x.com||2|P1', null, 'Password kosong.'],
        ];
    }

    #[DataProvider('invalidSharingLines')]
    public function test_invalid_sharing_line_is_rejected_with_message(string $line, ?string $slotDefault, string $message): void
    {
        $variant = ProductVariant::factory()->sharing()->create();

        $this->actingAs($this->admin())
            ->post('/admin/stok/import', $this->payload($variant, $line, overrides: ['slot_default' => $slotDefault]));

        $this->assertSame(0, session('import_result.tersimpan'));
        $this->assertSame([$message], session('import_result.ditolak.0.errors'));
        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_duplicates_in_batch_and_existing_stock_are_rejected(): void
    {
        $variant = ProductVariant::factory()->create();
        Stock::factory()->for($variant, 'variant')->create(['email' => 'Lama@X.com']);

        $this->actingAs($this->admin())
            ->post('/admin/stok/import', $this->payload($variant, "baru@x.com|pw\nBARU@x.com|pw2\nlama@x.com|pw"));

        $this->assertSame(1, session('import_result.tersimpan'));
        $this->assertSame(['Duplikat dengan baris 1.'], session('import_result.ditolak.0.errors'));
        $this->assertSame(['Akun ini sudah ada di stok varian ini.'], session('import_result.ditolak.1.errors'));
        $this->assertDatabaseCount('stocks', 2);
    }

    public function test_same_account_may_exist_in_another_variant(): void
    {
        $otherVariant = ProductVariant::factory()->create(['durasi_hari' => 7]);
        Stock::factory()->for($otherVariant, 'variant')->create(['email' => 'a@x.com']);
        $variant = ProductVariant::factory()->create();

        $this->actingAs($this->admin())->post('/admin/stok/import', $this->payload($variant, 'a@x.com|pw'));

        $this->assertSame(1, session('import_result.tersimpan'));
    }

    public function test_more_than_max_lines_fails_validation(): void
    {
        $variant = ProductVariant::factory()->create();
        $akun = implode("\n", array_map(fn (int $i): string => "u{$i}@x.com|pw", range(1, 1001)));

        $response = $this->actingAs($this->admin())->post('/admin/stok/import', $this->payload($variant, $akun));

        $response->assertSessionHasErrors(['akun' => 'Maksimal 1000 baris per input.']);
        $this->assertDatabaseCount('stocks', 0);
    }
}
