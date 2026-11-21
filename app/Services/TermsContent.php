<?php

namespace App\Services;

use App\Services\Saldo\ResellerStatus;
use Illuminate\Support\Carbon;

/**
 * Isi halaman Syarat & Ketentuan yang diedit admin.
 *
 * Format teks: paragraf dipisah baris kosong, baris berawalan "- " menjadi poin, **teks** menjadi tebal.
 * Placeholder seperti {saldo_reseller} diganti nilai dari Pengaturan saat ditampilkan ke pembeli.
 */
class TermsContent
{
    public const SETTING_KEY = 'terms.konten';

    /** @var list<string> */
    public const ICONS = [
        'heroicons:document-text',
        'heroicons:user-circle',
        'heroicons:key',
        'heroicons:credit-card',
        'heroicons:wallet',
        'heroicons:shield-check',
        'heroicons:receipt-refund',
        'heroicons:no-symbol',
        'heroicons:lock-closed',
        'heroicons:arrow-path',
        'heroicons:information-circle',
        'heroicons:exclamation-triangle',
        'heroicons:chat-bubble-left-right',
        'heroicons:bolt',
    ];

    /** @var array<string, string> */
    public const PLACEHOLDERS = [
        'nama_toko' => 'Nama toko',
        'batas_qris_menit' => 'Batas bayar QRIS (menit)',
        'batas_manual_jam' => 'Batas unggah bukti transfer (jam)',
        'topup_min' => 'Minimal top-up',
        'topup_max' => 'Maksimal top-up',
        'saldo_reseller' => 'Saldo minimal harga reseller',
    ];

    private const DEFAULT_UPDATED_AT = '2026-11-21';

    public function __construct(private Settings $settings, private ResellerStatus $resellerStatus) {}

    /**
     * Isi mentah (placeholder belum diganti), untuk editor admin.
     *
     * @return array{intro: string, bagian: list<array{judul: string, icon: string, isi: string}>, diperbarui: string}
     */
    public function raw(): array
    {
        $stored = $this->settings->get(self::SETTING_KEY);

        return is_array($stored) ? $stored : [...self::defaults(), 'diperbarui' => self::DEFAULT_UPDATED_AT];
    }

    /**
     * @param  array{intro: string, bagian: list<array{judul: string, icon: string, isi: string}>}  $content
     */
    public function save(array $content, bool $touchDate): void
    {
        $this->settings->set([self::SETTING_KEY => [
            'intro' => $content['intro'],
            'bagian' => array_map(fn (array $section): array => [
                'judul' => $section['judul'],
                'icon' => $section['icon'],
                'isi' => $section['isi'],
            ], $content['bagian']),
            'diperbarui' => $touchDate ? now()->toDateString() : $this->raw()['diperbarui'],
        ]]);
    }

    /**
     * Isi siap tampil untuk pembeli.
     *
     * @return array{intro: string, bagian: list<array{judul: string, icon: string, isi: string}>, diperbarui: string}
     */
    public function rendered(): array
    {
        $raw = $this->raw();
        $values = $this->placeholderValues();
        $fill = fn (string $text): string => preg_replace_callback(
            '/\{(\w+)\}/',
            fn (array $match): string => $values[$match[1]] ?? $match[0],
            $text,
        );

        return [
            'intro' => $fill($raw['intro']),
            'bagian' => array_map(fn (array $section): array => [
                'judul' => $fill($section['judul']),
                'icon' => $section['icon'],
                'isi' => $fill($section['isi']),
            ], $raw['bagian']),
            'diperbarui' => Carbon::parse($raw['diperbarui'])->translatedFormat('j F Y'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function placeholderValues(): array
    {
        $rupiah = fn (int $value): string => 'Rp'.number_format($value, 0, ',', '.');

        return [
            'nama_toko' => (string) $this->settings->get('store.nama'),
            'batas_qris_menit' => (string) $this->settings->get('qris.expiry_min'),
            'batas_manual_jam' => (string) $this->settings->get('manual.batas_jam'),
            'topup_min' => $rupiah((int) $this->settings->get('topup.min')),
            'topup_max' => $rupiah((int) $this->settings->get('topup.max')),
            'saldo_reseller' => $rupiah($this->resellerStatus->threshold()),
        ];
    }

    /**
     * @return array{intro: string, bagian: list<array{judul: string, icon: string, isi: string}>}
     */
    public static function defaults(): array
    {
        return [
            'intro' => 'Halaman ini mengatur penggunaan layanan {nama_toko}, termasuk kebijakan privasi. Mohon dibaca sebelum mendaftar atau berbelanja. Dengan membuat akun atau melakukan pembelian, kamu dianggap telah membaca dan menyetujui seluruh ketentuan di bawah.',
            'bagian' => [
                ['judul' => 'Ketentuan umum', 'icon' => 'heroicons:document-text', 'isi' => <<<'TXT'
                {nama_toko} adalah toko daring yang menjual akses akun layanan digital premium (streaming, AI tools, desain, dan lainnya) dengan durasi tertentu.

                - {nama_toko} adalah penjual independen dan **tidak berafiliasi** dengan pemilik layanan yang akunnya dijual.
                - Nama dan merek layanan yang tercantum adalah milik pemiliknya masing-masing.
                - Semua harga dalam Rupiah dan sudah termasuk biaya yang tampil saat checkout.
                TXT],
                ['judul' => 'Akun pengguna', 'icon' => 'heroicons:user-circle', 'isi' => <<<'TXT'
                - Pembelian, saldo, dan wishlist memerlukan akun. Data pendaftaran harus benar dan email harus aktif.
                - Kamu bertanggung jawab menjaga kerahasiaan password akun {nama_toko}. Aktivitas dari akunmu dianggap dilakukan olehmu.
                - Kami dapat membekukan akun yang terindikasi penipuan atau penyalahgunaan.
                TXT],
                ['judul' => 'Produk & tipe akun', 'icon' => 'heroicons:key', 'isi' => <<<'TXT'
                Setiap produk dijual dalam varian berupa **tipe akun** dan **durasi** (misalnya 7 hari atau 1 bulan).

                - **Private**: satu akun dipakai sendiri oleh pembeli.
                - **Sharing**: satu akun dipakai bersama beberapa pembeli, masing-masing mendapat slot/profil sendiri. Jangan memakai, mengganti, atau menghapus slot milik pembeli lain.
                - Dilarang mengganti email, password, PIN, nama profil, atau paket akun yang dibeli, kecuali diizinkan dalam info tambahan pesanan.
                - Masa aktif dihitung sejak kredensial dikirim, bukan sejak akun pertama kali dipakai.
                - Selalu ikuti petunjuk di **info tambahan** yang menyertai kredensial.
                TXT],
                ['judul' => 'Pemesanan & pembayaran', 'icon' => 'heroicons:credit-card', 'isi' => <<<'TXT'
                - Harga dan ketersediaan stok dihitung ulang oleh sistem saat checkout. Harga yang berlaku adalah harga di halaman pembayaran.
                - **QRIS**: bayar sesuai nominal yang tertera, termasuk kode unik, dalam **{batas_qris_menit} menit**. Pembayaran terverifikasi otomatis dan akun langsung dikirim.
                - **Transfer manual**: unggah bukti transfer dalam **{batas_manual_jam} jam**. Akun dikirim setelah admin memverifikasi bukti.
                - **Saldo**: pesanan langsung lunas dan akun langsung dikirim.
                - Pesanan yang tidak dibayar sampai batas waktu otomatis **kedaluwarsa**. Bila uang masuk setelah itu, admin akan memproses pesanan atau mengembalikan dana ke saldo.
                - Bila stok habis setelah pembayaran, pesanan berstatus **diproses** dan akun dikirim setelah stok tersedia, atau dana dikembalikan.
                - Harga **flash sale** hanya berlaku selama jadwal sale dan sisa kuota per akun. Pembelian melebihi kuota dikenakan harga normal.
                TXT],
                ['judul' => 'Saldo & harga reseller', 'icon' => 'heroicons:wallet', 'isi' => <<<'TXT'
                - Saldo diisi lewat top-up QRIS, minimal **{topup_min}** dan maksimal **{topup_max}** per transaksi. Kode unik tidak ikut masuk saldo.
                - Saldo hanya dapat dipakai untuk berbelanja di {nama_toko} dan **tidak dapat ditarik tunai** atau dipindahkan ke pengguna lain.
                - Setiap perubahan saldo tercatat di riwayat saldo, termasuk penyesuaian oleh admin.
                - **Harga reseller** berlaku otomatis selama saldo saat ini minimal **{saldo_reseller}**. Status dicek ulang saat checkout. Bila saldo turun di bawah batas, harga kembali normal.
                TXT],
                ['judul' => 'Garansi', 'icon' => 'heroicons:shield-check', 'isi' => <<<'TXT'
                - Masa garansi tertera di halaman masing-masing produk. Produk tanpa keterangan garansi tidak bergaransi.
                - Garansi berlaku bila akun tidak bisa dipakai karena kendala dari sisi kami, misalnya password salah atau langganan berhenti sebelum masa aktif habis.
                - Klaim diajukan dari halaman detail pesanan dengan menyertakan nomor invoice dan bukti kendala (tangkapan layar).
                - Bentuk penggantian: perbaikan atau penggantian kredensial (langsung tampil di halaman pesanan). Bila penggantian tidak memungkinkan, admin dapat mengembalikan dana ke saldo.
                - **Garansi gugur** bila pembeli melanggar ketentuan tipe akun atau larangan di halaman ini, misalnya mengganti password atau memakai slot orang lain.
                TXT],
                ['judul' => 'Pembatalan & refund', 'icon' => 'heroicons:receipt-refund', 'isi' => <<<'TXT'
                - Pesanan yang **belum dibayar** dapat dibatalkan kapan saja.
                - Pesanan yang **sudah dikirim** tidak dapat dibatalkan atau ditukar, kecuali lewat klaim garansi.
                - Refund atas pesanan yang dibayar dengan saldo dikembalikan ke saldo. Refund pesanan lain dikembalikan ke saldo atau ke rekening pembeli sesuai kesepakatan dengan admin.
                - Pembayaran yang nominalnya tidak sesuai tidak melunasi pesanan secara otomatis dan akan dicek admin.
                TXT],
                ['judul' => 'Larangan', 'icon' => 'heroicons:no-symbol', 'isi' => <<<'TXT'
                Pengguna dilarang:

                - Menjual ulang, menyewakan, atau membagikan kredensial yang dibeli tanpa izin tertulis dari kami.
                - Memakai akun untuk kegiatan yang melanggar hukum atau ketentuan layanan pemilik akun.
                - Melakukan penipuan pembayaran, chargeback tanpa dasar, atau memanfaatkan celah sistem.

                Pelanggaran dapat berakibat pembatalan garansi, pembekuan akun, dan pembekuan saldo.
                TXT],
                ['judul' => 'Kebijakan privasi', 'icon' => 'heroicons:lock-closed', 'isi' => <<<'TXT'
                Kami hanya mengumpulkan data yang dibutuhkan untuk menjalankan toko:

                - **Data akun**: nama, email, dan password (disimpan dalam bentuk hash; tidak ada password bila masuk dengan Google).
                - **Data transaksi**: pesanan, pembayaran, bukti transfer, riwayat saldo, wishlist, dan ulasan.

                Data dipakai untuk memproses pesanan, mengirim kredensial, menangani garansi, dan mencegah penipuan.

                - Kredensial akun yang dibeli disimpan terenkripsi dan hanya tampil untuk pembelinya.
                - Data pembayaran QRIS diproses oleh penyedia pembayaran. Kami tidak menyimpan data kartu atau rekening e-wallet.
                - Kami **tidak menjual** data pribadi kepada pihak lain.
                - Ulasan yang kamu tulis tampil publik dengan nama akunmu.
                - Kamu dapat mengubah data profil di halaman Akun Saya, atau meminta penghapusan akun melalui admin.
                TXT],
                ['judul' => 'Perubahan ketentuan', 'icon' => 'heroicons:arrow-path', 'isi' => 'Ketentuan ini dapat diperbarui sewaktu-waktu. Tanggal pembaruan tertera di bagian atas halaman. Ketentuan yang berlaku untuk sebuah pesanan adalah ketentuan pada saat pesanan dibuat.'],
            ],
        ];
    }
}
