<?php

namespace Tests\Feature;

use App\Enums\JenisAkun;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductVariantAdminTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->role(UserRole::Admin)->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'jenis_akun' => 'sharing',
            'durasi_hari' => 7,
            'harga' => 8000,
            'harga_asli' => 12000,
            'harga_reseller' => 7000,
            'is_aktif' => true,
            ...$overrides,
        ];
    }

    public function test_customer_cannot_add_variant(): void
    {
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->create())->post("/admin/produk/{$product->id}/varian", $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('product_variants', 0);
    }

    public function test_store_adds_variant_to_product(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->staff())->post("/admin/produk/{$product->id}/varian", $this->payload())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Varian ditambahkan.');

        $variant = $product->variants()->sole();
        $this->assertSame(JenisAkun::Sharing, $variant->jenis_akun);
        $this->assertSame('7 Hari', $variant->durasi_label);
        $this->assertSame(7000, $variant->harga_reseller);
    }

    public function test_same_type_and_duration_cannot_be_added_twice_but_other_type_can(): void
    {
        $existing = ProductVariant::factory()->sharing()->create(['durasi_hari' => 30]);
        $url = "/admin/produk/{$existing->product_id}/varian";

        $this->actingAs($this->staff())->post($url, $this->payload(['durasi_hari' => 30]))
            ->assertSessionHasErrors(['durasi_hari' => 'Varian dengan tipe & durasi ini sudah ada di produk ini.']);
        $this->post($url, $this->payload(['jenis_akun' => 'private', 'durasi_hari' => 30]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('product_variants', 2);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string, 2: string}>
     */
    public static function invalidPrices(): array
    {
        return [
            'harga coret lebih kecil' => [['harga_asli' => 5000], 'harga_asli', 'Harga coret harus lebih besar dari harga jual.'],
            'harga reseller lebih mahal' => [['harga_reseller' => 9000], 'harga_reseller', 'Harga reseller tidak boleh lebih mahal dari harga jual.'],
            'tipe tidak dikenal' => [['jenis_akun' => 'upgrade'], 'jenis_akun', 'The selected tipe akun is invalid.'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidPrices')]
    public function test_invalid_variant_is_rejected(array $overrides, string $field, string $message): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->staff())->post("/admin/produk/{$product->id}/varian", $this->payload($overrides))
            ->assertSessionHasErrors([$field => $message]);

        $this->assertDatabaseCount('product_variants', 0);
    }

    public function test_update_changes_price_and_allows_same_duration(): void
    {
        $variant = ProductVariant::factory()->sharing()->create(['durasi_hari' => 7]);

        $this->actingAs($this->staff())->put("/admin/varian/{$variant->id}", $this->payload(['harga' => 9000, 'harga_asli' => null]))
            ->assertSessionHasNoErrors();

        $variant->refresh();
        $this->assertSame(9000, $variant->harga);
        $this->assertNull($variant->harga_asli);
    }

    public function test_type_and_duration_are_locked_once_variant_has_stock(): void
    {
        $variant = ProductVariant::factory()->sharing()->create(['durasi_hari' => 7]);
        Stock::factory()->for($variant, 'variant')->create();

        $this->actingAs($this->staff())->put("/admin/varian/{$variant->id}", $this->payload(['jenis_akun' => 'private', 'durasi_hari' => 30]))
            ->assertSessionHasErrors([
                'jenis_akun' => 'Tipe akun tidak bisa diubah karena varian ini sudah punya stok.',
                'durasi_hari' => 'Durasi tidak bisa diubah karena varian ini sudah punya stok.',
            ]);

        $this->assertSame(JenisAkun::Sharing, $variant->fresh()->jenis_akun);
    }

    public function test_variant_with_stock_cannot_be_deleted_but_empty_one_can(): void
    {
        $withStock = Stock::factory()->create()->variant;
        $empty = ProductVariant::factory()->create();

        $this->actingAs($this->staff())->delete("/admin/varian/{$withStock->id}")
            ->assertSessionHas('error', 'Varian ini sudah punya stok, jadi tidak bisa dihapus. Nonaktifkan saja.');
        $this->delete("/admin/varian/{$empty->id}")->assertSessionHas('success', 'Varian dihapus.');

        $this->assertModelExists($withStock);
        $this->assertModelMissing($empty);
    }
}
