<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

/**
 * Pengaturan toko yang diubah admin dari panel (bukan .env).
 * Kunci rahasia (API key, webhook secret) disimpan terenkripsi dan tidak pernah dikirim ke browser.
 */
class Settings
{
    /** @var list<string> */
    public const SECRET_KEYS = ['qris.api_key', 'qris.webhook_secret'];

    /** @var array<string, mixed> */
    public const DEFAULTS = [
        'store.nama' => 'Akunhaus',
        'store.whatsapp' => '',
        'store.target_bulanan' => 20000000,
        'qris.enabled' => false,
        'qris.base_url' => '',
        'qris.api_key' => '',
        'qris.webhook_secret' => '',
        'qris.expiry_min' => 15,
        'manual.enabled' => true,
        'manual.rekening' => [],
        'manual.batas_jam' => 24,
    ];

    /** @var array<string, mixed>|null */
    private ?array $cache = null;

    public function get(string $key): mixed
    {
        $all = $this->all();

        return $all[$key] ?? self::DEFAULTS[$key] ?? null;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            $stored = json_encode($value);

            if (in_array($key, self::SECRET_KEYS, true)) {
                $stored = Crypt::encryptString($stored);
            }

            Setting::updateOrCreate(['key' => $key], ['value' => $stored]);
        }

        $this->cache = null;
    }

    public function hasSecret(string $key): bool
    {
        return (string) $this->get($key) !== '';
    }

    public function qrisReady(): bool
    {
        return $this->get('qris.enabled')
            && $this->get('qris.base_url') !== ''
            && $this->hasSecret('qris.api_key')
            && $this->hasSecret('qris.webhook_secret');
    }

    public function manualReady(): bool
    {
        return $this->get('manual.enabled') && $this->get('manual.rekening') !== [];
    }

    /**
     * @return array<string, mixed>
     */
    private function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $this->cache = Setting::all()->mapWithKeys(function (Setting $setting): array {
            $raw = $setting->value;

            if (in_array($setting->key, self::SECRET_KEYS, true) && $raw !== null) {
                $raw = Crypt::decryptString($raw);
            }

            return [$setting->key => json_decode((string) $raw, true)];
        })->all();

        return $this->cache;
    }
}
