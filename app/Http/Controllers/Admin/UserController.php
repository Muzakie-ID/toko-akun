<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Khusus admin: kelola user, ubah role (reseller/karyawan/admin), buat akun staf.
 */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim((string) ($filters['q'] ?? ''));

        $users = User::query()
            ->withCount('orders')
            ->withSum(['orders as total_belanja' => fn ($query) => $query->whereIn('status', OrderStatus::paidStatuses())], 'total')
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->latest()
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'nama' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'via_google' => $user->google_id !== null,
                'jumlah_pesanan' => $user->orders_count,
                'total_belanja' => (int) $user->total_belanja,
                'bergabung' => $user->created_at->translatedFormat('d M Y'),
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => ['role' => $filters['role'] ?? '', 'q' => $search],
            'roles' => $this->roleOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
            'role' => ['required', Rule::in([UserRole::Karyawan->value, UserRole::Admin->value])],
        ], attributes: ['name' => 'nama']);

        $user = User::create($request->only('name', 'email', 'password'));
        $user->forceFill(['role' => $data['role'], 'email_verified_at' => now()])->save();

        return back()->with('success', "Akun {$user->name} dibuat sebagai {$data['role']}.");
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::enum(UserRole::class)]]);
        $newRole = UserRole::from($data['role']);

        if ($user->is($request->user())) {
            return back()->with('error', 'Kamu tidak bisa mengubah role akunmu sendiri.');
        }

        $isLastAdmin = $user->role === UserRole::Admin
            && $newRole !== UserRole::Admin
            && User::where('role', UserRole::Admin)->count() === 1;

        if ($isLastAdmin) {
            return back()->with('error', 'Harus ada minimal satu admin.');
        }

        $user->forceFill(['role' => $newRole])->save();

        return back()->with('success', "Role {$user->name} diubah menjadi {$newRole->value}.");
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function roleOptions(): array
    {
        return array_map(fn (UserRole $role): array => ['value' => $role->value, 'label' => ucfirst($role->value)], UserRole::cases());
    }
}
