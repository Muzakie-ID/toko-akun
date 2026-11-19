<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(string $id, string $email, bool $verified = true): void
    {
        $googleUser = (new SocialiteUser)
            ->setRaw(['email_verified' => $verified])
            ->map(['id' => $id, 'name' => 'Budi Google', 'email' => $email]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_register_creates_customer(): void
    {
        $this->post('/register', [
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => 'admin', // harus diabaikan
        ])->assertRedirect('/');

        $user = User::firstWhere('email', 'budi@example.com');
        $this->assertSame(UserRole::Customer, $user->fresh()->role);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $_) {
            $this->post('/login', ['email' => $user->email, 'password' => 'salah'])->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertStatus(429);
        $this->assertGuest();
    }

    public function test_google_login_links_existing_email_account(): void
    {
        $user = User::factory()->create(['email' => 'budi@example.com', 'name' => 'Budi Asli']);
        $this->fakeGoogleUser('g-123', 'budi@example.com');

        $this->get('/auth/google/callback')->assertRedirect('/');

        $user->refresh();
        $this->assertSame('g-123', $user->google_id);
        $this->assertSame('Budi Asli', $user->name); // nama tidak ditimpa
        $this->assertSame(1, User::count());
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_login_refuses_linking_unverified_email(): void
    {
        User::factory()->create(['email' => 'budi@example.com']);
        $this->fakeGoogleUser('g-123', 'budi@example.com', verified: false);

        $this->get('/auth/google/callback')->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_google_login_creates_new_verified_customer(): void
    {
        $this->fakeGoogleUser('g-999', 'baru@example.com');

        $this->get('/auth/google/callback')->assertRedirect('/');

        $user = User::firstWhere('google_id', 'g-999');
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->password);
        $this->assertSame(UserRole::Customer, $user->fresh()->role);
    }

    public function test_google_login_handles_cancelled_or_invalid_state(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andThrow(new InvalidStateException);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get('/auth/google/callback')->assertRedirect('/login')->assertSessionHasErrors('email');
    }

    public function test_forgot_password_does_not_reveal_unknown_email(): void
    {
        Notification::fake();

        $this->post('/lupa-password', ['email' => 'tidak-ada@example.com'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_google_user_can_set_password_via_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => null, 'google_id' => 'g-1']);

        $this->post('/lupa-password', ['email' => $user->email])->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        $this->get("/reset-password/{$token}?email={$user->email}")->assertOk();

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'passwordbaru',
            'password_confirmation' => 'passwordbaru',
        ])->assertRedirect('/login')->assertSessionHas('status');

        $this->assertTrue(Hash::check('passwordbaru', $user->fresh()->password));
    }

    public function test_role_middleware(): void
    {
        Route::middleware(['web', 'auth', 'role:admin,karyawan'])->get('/_test/panel', fn () => 'ok');

        $customer = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['role' => UserRole::Admin])->save();

        $this->actingAs($customer)->get('/_test/panel')->assertForbidden();
        $this->actingAs($admin)->get('/_test/panel')->assertOk();
    }

    public function test_orders_page_requires_login_and_bad_product_id_is_404(): void
    {
        $this->get('/pesanan')->assertRedirect('/login');
        $this->get('/produk/abc')->assertNotFound();
    }
}
