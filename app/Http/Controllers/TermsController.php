<?php

namespace App\Http\Controllers;

use App\Services\Saldo\ResellerStatus;
use App\Services\Settings;
use Inertia\Inertia;
use Inertia\Response;

class TermsController extends Controller
{
    /**
     * Syarat & ketentuan; angka batas waktu, top-up, dan reseller mengikuti Pengaturan admin.
     */
    public function __invoke(Settings $settings, ResellerStatus $resellerStatus): Response
    {
        return Inertia::render('Terms', [
            'toko' => [
                'nama' => $settings->get('store.nama'),
                'whatsapp' => $settings->get('store.whatsapp') ?: null,
            ],
            'pembayaran' => [
                'qris' => $settings->qrisReady(),
                'qris_menit' => (int) $settings->get('qris.expiry_min'),
                'manual' => $settings->manualReady(),
                'manual_jam' => (int) $settings->get('manual.batas_jam'),
            ],
            'topup' => [
                'min' => (int) $settings->get('topup.min'),
                'max' => (int) $settings->get('topup.max'),
            ],
            'reseller' => [
                'otomatis' => $resellerStatus->isEnabled(),
                'batas' => $resellerStatus->threshold(),
            ],
            'diperbarui' => '21 November 2026',
        ]);
    }
}
