<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Services\Settings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Client API gateway QRIS qris-muz (https://github.com/Muzakie-ID/qris-muz).
 * Membuat tagihan dengan merchant_ref = no_invoice; pelunasan dikonfirmasi lewat webhook.
 */
class QrismuzClient
{
    public function __construct(private Settings $settings) {}

    /**
     * @return array{id: string, final_amount: int, unique_code: int, checkout_url: string|null, qr_image_url: string|null, qris_payload: string|null, expires_at: string|null}
     *
     * @throws PaymentGatewayException
     */
    public function createTransaction(Order $order, string $returnUrl): array
    {
        $response = $this->send(fn (PendingRequest $http) => $http
            ->withHeaders(['Idempotency-Key' => $order->no_invoice])
            ->post('/v1/transactions', [
                'amount' => $order->subtotal,
                'merchant_ref' => $order->no_invoice,
                'expiry_min' => (int) $this->settings->get('qris.expiry_min'),
                'return_url' => $returnUrl,
                'metadata' => ['order_id' => $order->id, 'user_id' => $order->user_id],
            ]));

        return [
            'id' => (string) $response['id'],
            'final_amount' => (int) $response['final_amount'],
            'unique_code' => (int) ($response['unique_code'] ?? 0),
            'checkout_url' => $response['checkout_url'] ?? null,
            'qr_image_url' => $response['qr_image_url'] ?? null,
            'qris_payload' => $response['qris_payload'] ?? null,
            'expires_at' => $response['expires_at'] ?? null,
        ];
    }

    /**
     * Batalkan tagihan PENDING (tidak memicu webhook).
     *
     * @throws PaymentGatewayException
     */
    public function cancelTransaction(string $gatewayId): void
    {
        $this->send(fn (PendingRequest $http) => $http->post("/v1/transactions/{$gatewayId}/cancel"));
    }

    /**
     * Cek koneksi & status listener, dipakai tombol "Tes koneksi" di pengaturan.
     *
     * @return array{listener_online: bool, confirmation: string}
     *
     * @throws PaymentGatewayException
     */
    public function status(): array
    {
        $response = $this->send(fn (PendingRequest $http) => $http->get('/v1/status'));

        return [
            'listener_online' => (bool) ($response['listener_online'] ?? false),
            'confirmation' => (string) ($response['confirmation'] ?? 'unknown'),
        ];
    }

    /**
     * Cocokkan tanda tangan webhook: hex(HMAC-SHA256(secret, timestamp + "." + body)), toleransi jam 5 menit.
     */
    public function verifyWebhook(string $body, string $timestamp, string $signature): bool
    {
        $secret = (string) $this->settings->get('qris.webhook_secret');

        if ($secret === '' || $timestamp === '' || ! ctype_digit($timestamp)) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$body, $secret);
        $isFresh = abs((int) round(microtime(true) * 1000) - (int) $timestamp) <= 5 * 60 * 1000;

        return $isFresh && hash_equals($expected, $signature);
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     * @return array<string, mixed>
     *
     * @throws PaymentGatewayException
     */
    private function send(callable $call): array
    {
        $baseUrl = rtrim((string) $this->settings->get('qris.base_url'), '/');

        if ($baseUrl === '' || ! $this->settings->hasSecret('qris.api_key')) {
            throw new PaymentGatewayException('Gateway QRIS belum dikonfigurasi.');
        }

        $http = Http::baseUrl($baseUrl)
            ->withToken((string) $this->settings->get('qris.api_key'))
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(15);

        try {
            return $call($http)->throw()->json() ?? [];
        } catch (RequestException $e) {
            $message = $e->response->json('message') ?? $e->response->json('error') ?? 'HTTP '.$e->response->status();

            throw new PaymentGatewayException('Gateway QRIS menolak permintaan: '.(is_array($message) ? implode(', ', $message) : $message), previous: $e);
        } catch (ConnectionException $e) {
            throw new PaymentGatewayException('Gateway QRIS tidak bisa dihubungi.', previous: $e);
        }
    }
}
