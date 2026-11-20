<?php

namespace App\Services\Saldo;

use App\Enums\SaldoTipe;
use App\Enums\TopupStatus;
use App\Models\Topup;
use App\Models\User;
use App\Services\Payment\PaymentGatewayException;
use App\Services\Payment\QrismuzClient;
use App\Services\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Top-up saldo lewat QRIS qris-muz. Saldo dikreditkan saat webhook paid (sekali saja per top-up).
 */
class TopupService
{
    public function __construct(
        private Settings $settings,
        private QrismuzClient $qrismuz,
        private SaldoLedger $ledger,
    ) {}

    /**
     * @throws ValidationException
     * @throws PaymentGatewayException
     */
    public function create(User $user, int $amount): Topup
    {
        if (! $this->settings->qrisReady()) {
            throw ValidationException::withMessages(['jumlah' => 'Top-up sedang tidak tersedia. Coba lagi nanti.']);
        }

        $min = (int) $this->settings->get('topup.min');
        $max = (int) $this->settings->get('topup.max');
        if ($amount < $min || $amount > $max) {
            throw ValidationException::withMessages(['jumlah' => 'Nominal top-up Rp'.number_format($min, 0, ',', '.').' sampai Rp'.number_format($max, 0, ',', '.').'.']);
        }

        $topup = Topup::create([
            'user_id' => $user->id,
            'no_invoice' => Topup::generateInvoiceNumber(),
            'jumlah' => $amount,
            'total' => $amount,
            'status' => TopupStatus::MenungguBayar,
        ]);

        try {
            $transaction = $this->qrismuz->createTransaction(
                $topup->no_invoice,
                $amount,
                route('saldo.topup.show', $topup),
                ['topup_id' => $topup->id, 'user_id' => $user->id],
            );
        } catch (PaymentGatewayException $e) {
            $topup->update(['status' => TopupStatus::Dibatalkan]);

            throw $e;
        }

        $topup->update([
            'gateway_id' => $transaction['id'],
            'kode_unik' => $transaction['unique_code'],
            'total' => $transaction['final_amount'],
            'checkout_url' => $transaction['checkout_url'],
            'qr_image_url' => $transaction['qr_image_url'],
            'qris_payload' => $transaction['qris_payload'],
            'expires_at' => $transaction['expires_at']
                ? Carbon::parse($transaction['expires_at'])->setTimezone(config('app.timezone'))
                : now()->addMinutes((int) $this->settings->get('qris.expiry_min')),
        ]);

        return $topup;
    }

    /**
     * Kreditkan saldo. Uang yang masuk telat (setelah kedaluwarsa) tetap dikreditkan karena tidak ada
     * stok yang dipesan. Aman dipanggil berulang.
     */
    public function markPaid(Topup $topup, ?Carbon $paidAt = null): Topup
    {
        DB::transaction(function () use ($topup, $paidAt): void {
            $locked = Topup::lockForUpdate()->find($topup->id);

            if ($locked->status === TopupStatus::Lunas) {
                return;
            }

            $locked->update(['status' => TopupStatus::Lunas, 'paid_at' => $paidAt ?? now()]);
            $this->ledger->credit($locked->user, $locked->jumlah, SaldoTipe::Topup, "Top-up {$locked->no_invoice}", ['topup' => $locked]);
        });

        return $topup->refresh();
    }

    public function expire(Topup $topup): Topup
    {
        if ($topup->status === TopupStatus::MenungguBayar) {
            $topup->update(['status' => TopupStatus::Kedaluwarsa]);
        }

        return $topup;
    }
}
