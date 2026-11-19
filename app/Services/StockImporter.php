<?php

namespace App\Services;

use App\Enums\JenisAkun;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Input stok massal. Satu akun per baris:
 *
 *   email|password                     → private (1 slot)
 *   email|password|slot                → slot per baris (kosong = pakai default form)
 *   email|password|slot|info           → info tambahan
 *
 * Untuk sharing, info per slot dipisah ";" (mis. "Profil 1 PIN 1111;Profil 2 PIN 2222").
 * Satu info saja = berlaku untuk semua slot. Baris kosong dan baris diawali "#" diabaikan.
 */
class StockImporter
{
    public const MAX_LINES = 1000;

    public const MAX_SHARING_SLOT = 50;

    /**
     * Parse teks dan tandai baris yang error (format, duplikat dalam batch, atau sudah ada di stok varian ini).
     *
     * @return list<array{baris: int, email: string, password: string, slot: int|null, info: list<string>, errors: list<string>}>
     */
    public function parse(string $text, ProductVariant $variant, ?int $defaultSlot): array
    {
        $rows = [];
        $seenEmails = [];

        foreach (preg_split('/\R/', $text) as $index => $rawLine) {
            $line = trim($rawLine);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $row = $this->parseLine($line, $index + 1, $variant->jenis_akun, $defaultSlot);

            $emailKey = mb_strtolower($row['email']);
            if ($row['email'] !== '' && isset($seenEmails[$emailKey])) {
                $row['errors'][] = "Duplikat dengan baris {$seenEmails[$emailKey]}.";
            } elseif ($row['email'] !== '') {
                $seenEmails[$emailKey] = $row['baris'];
            }

            $rows[] = $row;
        }

        return $this->markExistingStocks($rows, $variant);
    }

    /**
     * Simpan baris yang valid. Baris error dilewati.
     *
     * @param  list<array{baris: int, email: string, password: string, slot: int|null, info: list<string>, errors: list<string>}>  $rows
     * @return int jumlah akun tersimpan
     */
    public function import(array $rows, ProductVariant $variant, User $admin): int
    {
        $validRows = array_filter($rows, fn (array $row): bool => $row['errors'] === []);

        DB::transaction(function () use ($validRows, $variant, $admin): void {
            foreach ($validRows as $row) {
                $stock = Stock::create([
                    'product_variant_id' => $variant->id,
                    'email' => $row['email'],
                    'password' => $row['password'],
                    'total_slot' => $row['slot'],
                    'created_by' => $admin->id,
                ]);

                $stock->slots()->createMany(array_map(
                    fn (int $nomor): array => [
                        'nomor' => $nomor,
                        'info' => $row['info'][$nomor - 1] ?? $row['info'][0] ?? null,
                    ],
                    range(1, $row['slot']),
                ));
            }
        });

        return count($validRows);
    }

    /**
     * @return array{baris: int, email: string, password: string, slot: int|null, info: list<string>, errors: list<string>}
     */
    private function parseLine(string $line, int $lineNumber, JenisAkun $jenis, ?int $defaultSlot): array
    {
        $parts = array_map(trim(...), explode('|', $line));
        $row = [
            'baris' => $lineNumber,
            'email' => $parts[0],
            'password' => $parts[1] ?? '',
            'slot' => null,
            'info' => [],
            'errors' => [],
        ];

        if (count($parts) < 2) {
            $row['errors'][] = 'Format salah, gunakan email|password.';

            return $row;
        }

        if (count($parts) > 4) {
            $row['errors'][] = 'Terlalu banyak tanda "|". Format: email|password|slot|info (password tidak boleh mengandung "|").';

            return $row;
        }

        if ($row['email'] === '' || preg_match('/\s/', $row['email']) || mb_strlen($row['email']) > 255) {
            $row['errors'][] = 'Email/username kosong, mengandung spasi, atau terlalu panjang.';
        }

        if ($row['password'] === '') {
            $row['errors'][] = 'Password kosong.';
        }

        $row['slot'] = $this->resolveSlot($parts[2] ?? '', $jenis, $defaultSlot, $row['errors']);
        $row['info'] = $this->resolveInfo($parts[3] ?? '', $jenis, $row['slot'], $row['errors']);

        return $row;
    }

    /**
     * @param  list<string>  $errors
     */
    private function resolveSlot(string $slotText, JenisAkun $jenis, ?int $defaultSlot, array &$errors): ?int
    {
        if ($slotText !== '' && ! ctype_digit($slotText)) {
            $errors[] = "Slot \"{$slotText}\" harus berupa angka.";

            return null;
        }

        if ($jenis === JenisAkun::Private) {
            if ($slotText !== '' && (int) $slotText !== 1) {
                $errors[] = 'Akun private hanya boleh 1 slot.';
            }

            return 1;
        }

        $slot = $slotText !== '' ? (int) $slotText : $defaultSlot;

        if ($slot === null) {
            $errors[] = 'Jumlah slot belum diisi (isi di baris atau di "Slot default").';

            return null;
        }

        if ($slot < 2 || $slot > self::MAX_SHARING_SLOT) {
            $errors[] = 'Slot akun sharing harus 2–'.self::MAX_SHARING_SLOT.'.';

            return null;
        }

        return $slot;
    }

    /**
     * @param  list<string>  $errors
     * @return list<string>
     */
    private function resolveInfo(string $infoText, JenisAkun $jenis, ?int $slot, array &$errors): array
    {
        if ($jenis === JenisAkun::Private) {
            return $infoText === '' ? [] : [$infoText];
        }

        $infos = array_values(array_filter(array_map(trim(...), explode(';', $infoText)), fn (string $info): bool => $info !== ''));

        if ($infos === []) {
            $errors[] = 'Akun sharing wajib punya info tambahan (mis. profil/PIN per slot).';
        } elseif ($slot !== null && count($infos) > 1 && count($infos) !== $slot) {
            $errors[] = 'Jumlah info ('.count($infos).") tidak sama dengan jumlah slot ({$slot}). Pisahkan info per slot dengan \";\".";
        }

        return $infos;
    }

    /**
     * @param  list<array{baris: int, email: string, password: string, slot: int|null, info: list<string>, errors: list<string>}>  $rows
     * @return list<array{baris: int, email: string, password: string, slot: int|null, info: list<string>, errors: list<string>}>
     */
    private function markExistingStocks(array $rows, ProductVariant $variant): array
    {
        $hashes = array_map(fn (array $row): string => Stock::hashEmail($row['email']), $rows);

        $existing = $variant->stocks()
            ->whereIn('email_hash', array_unique($hashes))
            ->pluck('email_hash')
            ->flip();

        foreach ($rows as $i => $row) {
            if ($row['email'] !== '' && $existing->has($hashes[$i])) {
                $rows[$i]['errors'][] = 'Akun ini sudah ada di stok varian ini.';
            }
        }

        return $rows;
    }
}
