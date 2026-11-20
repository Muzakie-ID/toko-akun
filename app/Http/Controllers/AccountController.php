<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Account', [
            'akun' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'punya_password' => $user->password !== null,
                'via_google' => $user->google_id !== null,
                'bergabung' => $user->created_at->translatedFormat('d M Y'),
                'jumlah_pesanan' => $user->orders()->whereIn('status', OrderStatus::paidStatuses())->count(),
                'jumlah_wishlist' => $user->wishlists()->count(),
            ],
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
        ], attributes: ['name' => 'nama']);

        $user->fill($data);
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();

        return back()->with('success', 'Profil disimpan.');
    }

    /**
     * User Google yang belum punya password bisa langsung membuat password tanpa password lama.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => [Rule::requiredIf($user->password !== null), 'nullable', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => 'Password lama salah.',
        ], [
            'current_password' => 'password lama',
            'password' => 'password baru',
        ]);

        $user->forceFill([
            'password' => $request->input('password'),
            'remember_token' => Str::random(60),
        ])->save();

        return back()->with('success', 'Password diperbarui.');
    }
}
