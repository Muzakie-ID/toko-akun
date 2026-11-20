<?php

namespace Database\Seeders;

use App\Enums\JenisAkun;
use App\Enums\MetodeBayar;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Database\Seeder;

/**
 * Data contoh untuk mencoba dashboard secara lokal: stok, pembeli, dan pesanan 12 bulan terakhir.
 * Jalankan manual: php artisan db:seed --class=DemoSeeder (jangan di produksi).
 */
class DemoSeeder extends Seeder
{
    public function run(Settings $settings): void
    {
        $this->call(CatalogSeeder::class);

        $settings->set([
            'store.target_bulanan' => 3000000,
            'store.whatsapp' => '6281234567890',
            'manual.enabled' => true,
            'manual.rekening' => [['bank' => 'BCA', 'nomor' => '1234567890', 'nama' => 'Akunhaus']],
        ]);

        $variants = ProductVariant::with('product')->get();

        foreach ($variants as $variant) {
            $isSharing = $variant->jenis_akun === JenisAkun::Sharing;
            Stock::factory()->count(20)->for($variant, 'variant')
                ->withSlots($isSharing ? 4 : 1, $isSharing ? 'Profil' : null)
                ->create();
        }

        $buyers = User::factory()->count(25)->create();

        foreach (range(0, 140) as $i) {
            $variant = $variants->random();
            $buyer = $buyers->random();
            $createdAt = now()->subDays(random_int(0, 360))->setTime(random_int(8, 22), random_int(0, 59));
            $status = $createdAt->greaterThan(now()->subDays(2))
                ? fake()->randomElement([OrderStatus::MenungguBayar, OrderStatus::MenungguVerifikasi, OrderStatus::Selesai])
                : fake()->randomElement([OrderStatus::Selesai, OrderStatus::Selesai, OrderStatus::Selesai, OrderStatus::Kedaluwarsa]);
            $isPaid = $status === OrderStatus::Selesai;

            $order = Order::factory()->for($buyer)->create([
                'metode_bayar' => fake()->randomElement(MetodeBayar::cases()),
                'status' => $status,
                'subtotal' => $variant->harga,
                'total' => $variant->harga,
                'paid_at' => $isPaid ? $createdAt->copy()->addMinutes(5) : null,
                'expires_at' => $createdAt->copy()->addDay(),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $item = $order->items()->create([
                'product_variant_id' => $variant->id,
                'snapshot_produk' => $variant->product->nama,
                'snapshot_jenis_akun' => $variant->jenis_akun,
                'snapshot_durasi_hari' => $variant->durasi_hari,
                'harga' => $variant->harga,
                'delivered_at' => $isPaid ? $order->paid_at : null,
                'aktif_sampai' => $isPaid ? $order->paid_at->copy()->addDays($variant->durasi_hari) : null,
            ]);

            if ($isPaid) {
                $slot = $variant->availableSlots()->orderBy('stock_slots.id')->first();
                $slot?->update(['order_item_id' => $item->id]);
                $variant->product->increment('total_terjual');
            }
        }
    }
}
