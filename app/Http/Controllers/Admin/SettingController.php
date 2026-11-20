<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentGatewayException;
use App\Services\Payment\QrismuzClient;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Khusus admin: pengaturan toko, gateway QRIS qris-muz, dan transfer manual.
 * API key & webhook secret tidak pernah dikirim balik ke browser; kosong = tidak diubah.
 */
class SettingController extends Controller
{
    public function edit(Settings $settings): Response
    {
        return Inertia::render('Admin/Settings', [
            'settings' => [
                'store_nama' => $settings->get('store.nama'),
                'store_whatsapp' => $settings->get('store.whatsapp'),
                'store_target_bulanan' => $settings->get('store.target_bulanan'),
                'qris_enabled' => (bool) $settings->get('qris.enabled'),
                'qris_base_url' => $settings->get('qris.base_url'),
                'qris_expiry_min' => $settings->get('qris.expiry_min'),
                'manual_enabled' => (bool) $settings->get('manual.enabled'),
                'manual_rekening' => $settings->get('manual.rekening'),
                'manual_batas_jam' => $settings->get('manual.batas_jam'),
                'reseller_otomatis' => (bool) $settings->get('reseller.otomatis'),
                'reseller_min_saldo' => $settings->get('reseller.min_saldo'),
                'topup_min' => $settings->get('topup.min'),
                'topup_max' => $settings->get('topup.max'),
            ],
            'secrets' => [
                'qris_api_key' => $settings->hasSecret('qris.api_key'),
                'qris_webhook_secret' => $settings->hasSecret('qris.webhook_secret'),
            ],
            'webhookUrl' => route('webhooks.qrismuz'),
            'qrisReady' => $settings->qrisReady(),
        ]);
    }

    public function update(Request $request, Settings $settings): RedirectResponse
    {
        $data = $request->validate([
            'store_nama' => ['required', 'string', 'max:60'],
            'store_whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+ ]*$/'],
            'store_target_bulanan' => ['required', 'integer', 'min:0'],
            'qris_enabled' => ['boolean'],
            'qris_base_url' => ['nullable', 'required_if:qris_enabled,true', 'url:https,http', 'max:255'],
            'qris_api_key' => ['nullable', 'string', 'max:255'],
            'qris_webhook_secret' => ['nullable', 'string', 'max:255'],
            'qris_expiry_min' => ['required', 'integer', 'min:5', 'max:1440'],
            'manual_enabled' => ['boolean'],
            'manual_rekening' => ['array', 'max:5'],
            'manual_rekening.*.bank' => ['required', 'string', 'max:40'],
            'manual_rekening.*.nomor' => ['required', 'string', 'max:40'],
            'manual_rekening.*.nama' => ['required', 'string', 'max:80'],
            'manual_batas_jam' => ['required', 'integer', 'min:1', 'max:168'],
            'reseller_otomatis' => ['boolean'],
            'reseller_min_saldo' => ['required', 'integer', 'min:1000'],
            'topup_min' => ['required', 'integer', 'min:1000'],
            'topup_max' => ['required', 'integer', 'gt:topup_min', 'max:100000000'],
        ], [
            'qris_base_url.required_if' => 'URL API gateway wajib diisi bila QRIS diaktifkan.',
        ], [
            'qris_base_url' => 'URL API gateway',
            'qris_expiry_min' => 'masa berlaku tagihan',
            'manual_batas_jam' => 'batas waktu transfer',
            'reseller_min_saldo' => 'minimal saldo reseller',
            'topup_min' => 'minimal top-up',
            'topup_max' => 'maksimal top-up',
            'manual_rekening.*.bank' => 'nama bank',
            'manual_rekening.*.nomor' => 'nomor rekening',
            'manual_rekening.*.nama' => 'nama pemilik rekening',
        ]);

        $values = [
            'store.nama' => $data['store_nama'],
            'store.whatsapp' => (string) ($data['store_whatsapp'] ?? ''),
            'store.target_bulanan' => (int) $data['store_target_bulanan'],
            'qris.enabled' => $request->boolean('qris_enabled'),
            'qris.base_url' => rtrim((string) ($data['qris_base_url'] ?? ''), '/'),
            'qris.expiry_min' => (int) $data['qris_expiry_min'],
            'manual.enabled' => $request->boolean('manual_enabled'),
            'manual.rekening' => array_values($data['manual_rekening'] ?? []),
            'manual.batas_jam' => (int) $data['manual_batas_jam'],
            'reseller.otomatis' => $request->boolean('reseller_otomatis'),
            'reseller.min_saldo' => (int) $data['reseller_min_saldo'],
            'topup.min' => (int) $data['topup_min'],
            'topup.max' => (int) $data['topup_max'],
        ];

        foreach (['qris_api_key' => 'qris.api_key', 'qris_webhook_secret' => 'qris.webhook_secret'] as $field => $key) {
            if (filled($data[$field] ?? null)) {
                $values[$key] = trim($data[$field]);
            }
        }

        $settings->set($values);

        if ($values['qris.enabled'] && ! $settings->qrisReady()) {
            return back()->with('error', 'Pengaturan disimpan, tetapi QRIS belum aktif: isi URL API, API key, dan webhook secret.');
        }

        return back()->with('success', 'Pengaturan disimpan.');
    }

    public function testQris(QrismuzClient $qrismuz): RedirectResponse
    {
        try {
            $status = $qrismuz->status();
        } catch (PaymentGatewayException $e) {
            return back()->with('error', 'Tes koneksi gagal: '.$e->getMessage());
        }

        return back()->with('success', $status['listener_online']
            ? 'Terhubung ke gateway. HP listener online, konfirmasi pembayaran realtime.'
            : 'Terhubung ke gateway, tetapi HP listener sedang offline. Pembayaran akan terkonfirmasi tertunda.');
    }
}
