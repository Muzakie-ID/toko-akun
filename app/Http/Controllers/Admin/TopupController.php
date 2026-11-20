<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TopupStatus;
use App\Http\Controllers\Controller;
use App\Models\Topup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Daftar top-up saldo (baca saja; saldo masuk otomatis lewat webhook qris-muz).
 */
class TopupController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(TopupStatus::class)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim((string) ($filters['q'] ?? ''));

        $topups = Topup::query()
            ->with('user:id,name,email')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('no_invoice', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))))
            ->latest()
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Topup $topup): array => [
                'invoice' => $topup->no_invoice,
                'tanggal' => $topup->created_at->translatedFormat('d M Y, H:i'),
                'pembeli' => $topup->user->name,
                'email' => $topup->user->email,
                'jumlah' => $topup->jumlah,
                'total' => $topup->total,
                'status_label' => $topup->status->label(),
                'status_tone' => $topup->status->tone(),
                'paid_at' => $topup->paid_at?->translatedFormat('d M Y, H:i'),
            ]);

        return Inertia::render('Admin/Topups/Index', [
            'topups' => $topups,
            'filters' => ['status' => $filters['status'] ?? '', 'q' => $search],
            'statuses' => array_map(fn (TopupStatus $status): array => ['value' => $status->value, 'label' => $status->label()], TopupStatus::cases()),
            'totalMasukBulanIni' => (int) Topup::where('status', TopupStatus::Lunas)->where('paid_at', '>=', now()->startOfMonth())->sum('jumlah'),
        ]);
    }
}
