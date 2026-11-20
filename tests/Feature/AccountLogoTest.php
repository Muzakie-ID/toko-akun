<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountLogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_profile_resets_verification_when_email_changes(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['email' => 'dipakai@x.com']);

        $this->actingAs($user)->put('/akun', ['name' => 'Nama Baru', 'email' => 'dipakai@x.com'])->assertSessionHasErrors('email');
        $this->put('/akun', ['name' => 'Nama Baru', 'email' => 'baru@x.com'])->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertNull($user->email_verified_at);
    }

    public function test_change_password_requires_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/akun/password', ['current_password' => 'salah', 'password' => 'passwordbaru', 'password_confirmation' => 'passwordbaru'])
            ->assertSessionHasErrors(['current_password' => 'Password lama salah.']);
        $this->put('/akun/password', ['current_password' => 'password', 'password' => 'passwordbaru', 'password_confirmation' => 'passwordbaru'])
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('passwordbaru', $user->fresh()->password));
    }

    public function test_google_user_without_password_can_create_one(): void
    {
        $user = User::factory()->create(['password' => null, 'google_id' => 'g-1']);

        $this->actingAs($user)->put('/akun/password', ['password' => 'passwordbaru', 'password_confirmation' => 'passwordbaru'])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('passwordbaru', $user->fresh()->password));
    }

    public function test_admin_uploads_replaces_and_removes_product_logo(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $staff = User::factory()->role(UserRole::Karyawan)->create();
        $payload = ['category_id' => $product->category_id, 'nama' => $product->nama, 'slug' => $product->slug, 'garansi_hari' => 30, '_method' => 'put'];

        $this->actingAs($staff)->post("/admin/produk/{$product->id}", [...$payload, 'logo' => UploadedFile::fake()->image('a.png', 128, 128)])->assertSessionHasNoErrors();
        $first = $product->fresh()->logo;
        Storage::disk('public')->assertExists($first);

        $this->post("/admin/produk/{$product->id}", [...$payload, 'logo' => UploadedFile::fake()->image('b.png', 128, 128)]);
        $second = $product->fresh()->logo;
        Storage::disk('public')->assertMissing($first);

        $this->post("/admin/produk/{$product->id}", [...$payload, 'hapus_logo' => true]);
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($product->fresh()->logo);
    }

    public function test_logo_must_be_raster_image_within_limits(): void
    {
        $product = Product::factory()->create();
        $staff = User::factory()->role(UserRole::Karyawan)->create();
        $payload = ['category_id' => $product->category_id, 'nama' => $product->nama, 'garansi_hari' => 30, '_method' => 'put'];

        $this->actingAs($staff)->post("/admin/produk/{$product->id}", [...$payload, 'logo' => UploadedFile::fake()->create('x.svg', 5, 'image/svg+xml')])
            ->assertSessionHasErrors('logo');
        $this->post("/admin/produk/{$product->id}", [...$payload, 'logo' => UploadedFile::fake()->image('kecil.png', 32, 32)])
            ->assertSessionHasErrors(['logo' => 'Logo minimal 64×64 piksel.']);
    }
}
