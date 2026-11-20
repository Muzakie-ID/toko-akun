<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Topup;
use App\Models\WebhookEvent;
use App\Services\Orders\OrderLifecycle;
use App\Services\Payment\QrismuzClient;
use App\Services\Saldo\TopupService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Webhook dari gateway qris-muz: transaction.paid / transaction.expired / transaction.late_paid,
 * untuk pesanan (INV-) maupun top-up saldo (TOP-).
 * Ditandatangani HMAC atas "timestamp.body"; event ganda (X-Event-Id sama) diabaikan.
 */
class QrismuzWebhookController extends Controller
{
    public function __invoke(Request $request, QrismuzClient $qrismuz, OrderLifecycle $lifecycle, TopupService $topups): Response
    {
        $body = $request->getContent();

        if (! $qrismuz->verifyWebhook($body, (string) $request->header('X-Timestamp'), (string) $request->header('X-Signature'))) {
            return response('Invalid signature', 401);
        }

        $event = json_decode($body, true);
        $eventId = (string) ($event['id'] ?? $request->header('X-Event-Id', ''));
        $type = (string) ($event['type'] ?? '');
        $data = $event['data'] ?? [];
        $merchantRef = (string) ($data['merchant_ref'] ?? '');

        if ($eventId === '' || $type === '') {
            return response('Bad payload', 400);
        }

        try {
            WebhookEvent::create(['event_id' => $eventId, 'type' => $type, 'merchant_ref' => $merchantRef]);
        } catch (UniqueConstraintViolationException) {
            return response('Duplicate', 200);
        }

        if (str_starts_with($merchantRef, Topup::INVOICE_PREFIX)) {
            $this->handleTopup($merchantRef, $type, $data, $topups);

            return response('OK', 200);
        }

        $order = Order::firstWhere('no_invoice', $merchantRef);

        if (! $order) {
            Log::warning('Webhook qris-muz untuk pesanan tidak dikenal', ['event' => $eventId, 'merchant_ref' => $merchantRef]);

            return response('Unknown order', 200);
        }

        match ($type) {
            'transaction.paid' => $this->handlePaid($order, $data, $lifecycle),
            'transaction.expired' => $lifecycle->expire($order),
            'transaction.late_paid' => $this->handleLatePaid($order, $data),
            default => null,
        };

        return response('OK', 200);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handlePaid(Order $order, array $data, OrderLifecycle $lifecycle): void
    {
        if ((int) ($data['final_amount'] ?? 0) !== $order->total) {
            $order->update(['catatan_admin' => trim($order->catatan_admin."\nWebhook lunas dengan nominal berbeda: Rp".($data['final_amount'] ?? '?').'. Perlu dicek manual.')]);
            Log::warning('Nominal webhook qris-muz tidak cocok', ['order' => $order->no_invoice, 'data' => $data]);

            return;
        }

        $paidAt = isset($data['paid_at']) ? Carbon::parse($data['paid_at'])->setTimezone(config('app.timezone')) : null;
        $lifecycle->markPaid($order, $paidAt);
    }

    /**
     * Uang masuk setelah kedaluwarsa/dibatalkan: jangan proses otomatis, admin memutuskan dari panel.
     *
     * @param  array<string, mixed>  $data
     */
    private function handleLatePaid(Order $order, array $data): void
    {
        DB::transaction(function () use ($order, $data): void {
            $order->update([
                'late_paid_at' => isset($data['paid_at']) ? Carbon::parse($data['paid_at'])->setTimezone(config('app.timezone')) : now(),
                'catatan_admin' => trim($order->catatan_admin."\nPembayaran QRIS masuk setelah tagihan ".($order->status === OrderStatus::Dibatalkan ? 'dibatalkan' : 'kedaluwarsa').'. Proses atau refund dari panel.'),
            ]);
        });
    }

    /**
     * Top-up: lunas (termasuk bayar telat) = saldo dikreditkan sebesar jumlah top-up; nominal harus cocok.
     *
     * @param  array<string, mixed>  $data
     */
    private function handleTopup(string $merchantRef, string $type, array $data, TopupService $topups): void
    {
        $topup = Topup::firstWhere('no_invoice', $merchantRef);

        if (! $topup) {
            Log::warning('Webhook qris-muz untuk top-up tidak dikenal', ['merchant_ref' => $merchantRef]);

            return;
        }

        if (in_array($type, ['transaction.paid', 'transaction.late_paid'], true)) {
            if ((int) ($data['final_amount'] ?? 0) !== $topup->total) {
                Log::warning('Nominal webhook top-up tidak cocok', ['topup' => $topup->no_invoice, 'data' => $data]);

                return;
            }

            $topups->markPaid($topup, isset($data['paid_at']) ? Carbon::parse($data['paid_at'])->setTimezone(config('app.timezone')) : null);
        } elseif ($type === 'transaction.expired') {
            $topups->expire($topup);
        }
    }
}
