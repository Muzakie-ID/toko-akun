<?php

namespace Database\Seeders;

use App\Enums\JenisAkun;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Data contoh katalog (sama dengan mock di frontend) agar admin bisa langsung input stok.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect(['Streaming', 'AI Tools', 'Desain', 'Office', 'Cloud Storage', 'Game', 'Lainnya'])
            ->mapWithKeys(fn (string $nama): array => [
                $nama => Category::firstOrCreate(['slug' => Str::slug($nama)], ['nama' => $nama]),
            ]);

        /** @var array<int, array{nama: string, kategori: string, varian: list<array{0: JenisAkun, 1: int, 2: int, 3: int}>}> $products */
        $products = [
            ['nama' => 'Netflix Premium', 'kategori' => 'Streaming', 'varian' => [
                [JenisAkun::Private, 30, 45000, 65000],
                [JenisAkun::Sharing, 7, 8000, 12000],
                [JenisAkun::Sharing, 30, 25000, 45000],
            ]],
            ['nama' => 'Spotify Premium', 'kategori' => 'Streaming', 'varian' => [
                [JenisAkun::Private, 30, 15000, 29000],
                [JenisAkun::Private, 60, 28000, 55000],
            ]],
            ['nama' => 'ChatGPT Plus', 'kategori' => 'AI Tools', 'varian' => [
                [JenisAkun::Private, 30, 85000, 120000],
                [JenisAkun::Sharing, 30, 45000, 70000],
            ]],
            ['nama' => 'Canva Pro', 'kategori' => 'Desain', 'varian' => [
                [JenisAkun::Sharing, 365, 30000, 59000],
            ]],
        ];

        foreach ($products as $data) {
            $product = Product::firstOrCreate(['slug' => Str::slug($data['nama'])], [
                'category_id' => $categories[$data['kategori']]->id,
                'nama' => $data['nama'],
                'garansi_hari' => 30,
            ]);

            foreach ($data['varian'] as [$jenis, $durasiHari, $harga, $hargaAsli]) {
                $product->variants()->firstOrCreate(
                    ['jenis_akun' => $jenis, 'durasi_hari' => $durasiHari],
                    ['harga' => $harga, 'harga_asli' => $hargaAsli],
                );
            }
        }
    }
}
