<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        $this->rememberReturnPath($request);

        return Inertia::render('Auth/Login', [
            'status' => $request->session()->get('status', ''),
        ]);
    }

    public function showRegister(Request $request): Response
    {
        $this->rememberReturnPath($request);

        return Inertia::render('Auth/Register');
    }

    /**
     * Simpan ?kembali=/produk/x sebagai tujuan setelah login. Hanya path lokal agar tidak jadi open redirect.
     */
    private function rememberReturnPath(Request $request): void
    {
        $path = (string) $request->query('kembali', '');

        if (str_starts_with($path, '/') && ! str_starts_with($path, '//') && ! str_contains($path, '\\')) {
            $request->session()->put('url.intended', url($path));
        }
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        // Password di-hash otomatis oleh cast 'hashed'; role default = customer
        $user = User::create($data);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function googleRedirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function googleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            // User membatalkan izin, state session kedaluwarsa, atau Google error
            Log::info('Login Google gagal', ['error' => $e->getMessage()]);

            return redirect()->route('login')->withErrors(['email' => 'Login dengan Google gagal atau dibatalkan. Silakan coba lagi.']);
        }

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // Tautkan akun email yang sudah ada hanya jika Google menjamin email ini milik user,
                // supaya orang lain tidak bisa mengambil alih akun lewat email yang belum terverifikasi.
                if (! ($googleUser->user['email_verified'] ?? false)) {
                    return redirect()->route('login')->withErrors(['email' => 'Email ini sudah terdaftar. Silakan masuk dengan password.']);
                }

                $user->forceFill([
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
            } else {
                $user = User::create([
                    'name' => $googleUser->getName() ?: Str::before($googleUser->getEmail(), '@'),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
            }
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function showForgotPassword(Request $request): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => $request->session()->get('status', ''),
        ]);
    }

    /**
     * Juga dipakai user Google (tanpa password) untuk membuat password pertama kali.
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            return back()->withErrors(['email' => 'Tunggu sebentar sebelum meminta link lagi.']);
        }

        // Pesan sama untuk email terdaftar maupun tidak, agar daftar email user tidak bisa ditebak
        return back()->with('status', 'Jika email terdaftar, link reset password sudah kami kirim. Cek inbox atau folder spam.');
    }

    public function showResetPassword(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'Link reset tidak valid atau sudah kedaluwarsa. Minta link baru.']);
        }

        return redirect()->route('login')->with('status', 'Password berhasil diubah. Silakan masuk.');
    }
}
