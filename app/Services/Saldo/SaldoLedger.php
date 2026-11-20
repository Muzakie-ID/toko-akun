<?php

namespace App\Services\Saldo;

use App\Enums\SaldoTipe;
use App\Models\Order;
use App\Models\SaldoMutation;
use App\Models\Topup;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya jalan mengubah saldo user: baris user dikunci, saldo diubah, dan mutasi dicatat
 * dalam transaksi yang sama sehingga saldo tidak bisa minus atau terpotong dua kali.
 */
class SaldoLedger
{
    /**
     * @param  array{order?: Order|null, topup?: Topup|null, by?: User|null}  $refs
     */
    public function credit(User $user, int $amount, SaldoTipe $tipe, string $keterangan, array $refs = []): SaldoMutation
    {
        return $this->apply($user, abs($amount), $tipe, $keterangan, $refs);
    }

    /**
     * @param  array{order?: Order|null, topup?: Topup|null, by?: User|null}  $refs
     *
     * @throws InsufficientSaldoException
     */
    public function debit(User $user, int $amount, SaldoTipe $tipe, string $keterangan, array $refs = []): SaldoMutation
    {
        return $this->apply($user, -abs($amount), $tipe, $keterangan, $refs);
    }

    /**
     * @param  array{order?: Order|null, topup?: Topup|null, by?: User|null}  $refs
     *
     * @throws InsufficientSaldoException
     */
    private function apply(User $user, int $delta, SaldoTipe $tipe, string $keterangan, array $refs): SaldoMutation
    {
        $mutation = DB::transaction(function () use ($user, $delta, $tipe, $keterangan, $refs): SaldoMutation {
            $locked = User::lockForUpdate()->findOrFail($user->id);
            $newBalance = $locked->saldo + $delta;

            if ($newBalance < 0) {
                throw new InsufficientSaldoException('Saldo tidak cukup.');
            }

            $locked->forceFill(['saldo' => $newBalance])->save();

            return SaldoMutation::create([
                'user_id' => $locked->id,
                'tipe' => $tipe,
                'jumlah' => $delta,
                'saldo_akhir' => $newBalance,
                'keterangan' => $keterangan,
                'order_id' => $refs['order']?->id ?? null,
                'topup_id' => $refs['topup']?->id ?? null,
                'created_by' => $refs['by']?->id ?? null,
            ]);
        });

        $user->saldo = $mutation->saldo_akhir;

        return $mutation;
    }
}
