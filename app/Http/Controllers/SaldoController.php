<?php

namespace App\Http\Controllers;

use App\Enums\TopupStatus;
use App\Enums\UserRole;
use App\Models\SaldoMutation;
use App\Models\Topup;
use App\Services\Payment\PaymentGatewayException;
use App\Services\Saldo\ResellerStatus;
use App\Services\Saldo\TopupService;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaldoController extends Controller
{
    public function index(Request $request, Settings $settings, ResellerStatus $resellerStatus): Response
    {
        $user = $request->user();

        return Inertia::render('Saldo', [
            'saldo' => $user->saldo,
            'reseller' => [
                'aktif' => $resellerStatus->appliesTo($user),
                'permanen' => $user->role === UserRole::Reseller,
                'otomatis' => $resellerStatus->isEnabled(),
                'batas' => $resellerStatus->threshold(),
            ],
            'topup' => [
                'tersedia' => $settings->qrisReady(),
                'min' => (int) $settings->get('topup.min'),
                'max' => (int) $settings->get('topup.max'),
            ],
            'menunggu' => $user->topups()
                ->where('status', TopupStatus::MenungguBayar)
                ->where('expires_at', '>', now())
                ->latest()
                ->get()
                ->map(fn (Topup $topup): array => [
                    'invoice' => $topup->no_invoice,
                    'total' => $topup->total,
                    'expires_label' => $topup->expires_at?->translatedFormat('H:i'),
                ]),
            'mutasi' => $user->saldoMutations()
                ->latest()
                ->orderByDesc('id')
                ->paginate(15)
                ->through(fn (SaldoMutation $mutation): array => [
                    'id' => $mutation->id,
                    'tanggal' => $mutation->created_at->translatedFormat('d M Y, H:i'),
                    'tipe' => $mutation->tipe->label(),
                    'keterangan' => $mutation->keterangan,
                    'jumlah' => $mutation->jumlah,
                    'saldo_akhir' => $mutation->saldo_akhir,
                ]),
        ]);
    }

    public function storeTopup(Request $request, TopupService $topups): RedirectResponse
    {
        $data = $request->validate(['jumlah' => ['required', 'integer', 'min:1']], attributes: ['jumlah' => 'nominal top-up']);

        try {
            $topup = $topups->create($request->user(), (int) $data['jumlah']);
        } catch (PaymentGatewayException) {
            return back()->with('error', 'Pembayaran QRIS sedang bermasalah. Coba lagi sebentar.');
        }

        return to_route('saldo.topup.show', $topup);
    }

    public function showTopup(Request $request, Topup $topup): Response
    {
        abort_unless($topup->user_id === $request->user()->id, 404);

        return Inertia::render('TopupShow', [
            'topup' => [
                'invoice' => $topup->no_invoice,
                'status' => $topup->status->value,
                'status_label' => $topup->status->label(),
                'status_tone' => $topup->status->tone(),
                'jumlah' => $topup->jumlah,
                'kode_unik' => $topup->kode_unik,
                'total' => $topup->total,
                'qr_image_url' => $topup->qr_image_url,
                'checkout_url' => $topup->checkout_url,
                'expires_at' => $topup->expires_at?->toIso8601String(),
                'paid_at' => $topup->paid_at?->translatedFormat('d M Y, H:i'),
            ],
            'saldo' => $request->user()->saldo,
        ]);
    }
}
