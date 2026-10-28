<script setup>
// Data mock katalog — ponytail: ganti dengan data dari Controller saat backend hidup
const produk = [
    { id: 1, nama: 'Netflix Premium', harga: 25000, harga_asli: 45000, rating: '4.9', jumlah_ulasan: '2.1K', terjual: '12.4K', jenis_akun_label: 'Personal', durasi_label: '1 Bulan' },
    { id: 2, nama: 'Spotify Premium', harga: 15000, harga_asli: 27000, rating: '4.9', jumlah_ulasan: '3.4K', terjual: '18.2K', jenis_akun_label: 'Personal', durasi_label: '1 Bulan' },
    { id: 3, nama: 'ChatGPT Plus', harga: 65000, harga_asli: 99000, rating: '4.8', jumlah_ulasan: '1.8K', terjual: '9.1K', jenis_akun_label: 'Private', durasi_label: '1 Bulan' },
    { id: 4, nama: 'YouTube Premium', harga: 12000, harga_asli: 22000, rating: '4.8', jumlah_ulasan: '2.7K', terjual: '15.6K', jenis_akun_label: 'Sharing', durasi_label: '1 Bulan' },
    { id: 5, nama: 'Canva Pro', harga: 10000, harga_asli: 18000, rating: '4.9', jumlah_ulasan: '4.2K', terjual: '21.0K', jenis_akun_label: 'Sharing', durasi_label: '1 Tahun' },
    { id: 6, nama: 'Microsoft 365', harga: 35000, harga_asli: 60000, rating: '4.7', jumlah_ulasan: '900', terjual: '5.3K', jenis_akun_label: 'Personal', durasi_label: '1 Tahun' },
    { id: 7, nama: 'CapCut Pro', harga: 20000, harga_asli: 35000, rating: '4.8', jumlah_ulasan: '1.1K', terjual: '6.8K', jenis_akun_label: 'Personal', durasi_label: '1 Bulan' },
    { id: 8, nama: 'Google One 200GB', harga: 18000, harga_asli: 30000, rating: '4.9', jumlah_ulasan: '1.5K', terjual: '8.7K', jenis_akun_label: 'Personal', durasi_label: '1 Tahun' },
]

const kategoriFilter = ['Streaming', 'AI Tools', 'Desain', 'Office', 'Cloud Storage', 'Game', 'Lainnya']
const durasiFilter = ['1 Bulan', '3 Bulan', '6 Bulan', '1 Tahun']
const jenisFilter = ['Personal', 'Private', 'Sharing', 'Upgrade']
</script>

<template>
    <section class="relative overflow-hidden bg-gradient-to-br from-brand-700 via-brand-600 to-brand-500">
        <div class="mx-auto flex max-w-[1320px] items-center justify-between gap-8 px-4 py-16 lg:px-15 lg:py-20">
            <div class="max-w-xl">
                <h1 class="text-3xl leading-tight font-extrabold tracking-tight text-white sm:text-4xl lg:text-[44px]">
                    Semua Produk Premium, Satu Tempat.
                </h1>
                <p class="mt-4 text-base text-brand-100">
                    Kredensial dikirim otomatis setelah pembayaran terverifikasi. Proses cepat, harga bersahabat.
                </p>
                <a
                    href="#katalog"
                    class="mt-6 inline-flex h-11 items-center gap-2 rounded-xl bg-white px-6 text-sm font-semibold text-brand-700 shadow-sm transition hover:bg-brand-50"
                >
                    Lihat Semua Produk
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </div>

            <!-- Dekorasi huruf brand -->
            <div class="hidden shrink-0 items-center gap-5 text-6xl font-black text-white/30 lg:flex" aria-hidden="true">
                <span>N</span><span>S</span><span>C</span><span>Y</span><span>Ca</span>
            </div>
        </div>
    </section>

    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" class="mx-auto max-w-[1320px] px-4 lg:px-15">
        <ol class="flex h-12 items-center gap-2 text-sm text-slate-500">
            <li><a href="/" class="hover:text-brand-600">Beranda</a></li>
            <li aria-hidden="true">&rsaquo;</li>
            <li aria-current="page" class="font-medium text-ink">Semua Produk</li>
        </ol>
    </nav>

    <!-- Filter chips -->
    <div class="border-y border-slate-100 bg-white">
        <div class="mx-auto flex max-w-[1320px] items-center gap-2 overflow-x-auto px-4 py-3.5 lg:px-15">
            <button v-for="chip in ['Kategori', 'Durasi', 'Jenis Akun', 'Harga']" :key="chip" type="button"
                class="flex h-9 shrink-0 items-center gap-1.5 rounded-full border border-slate-200 px-4 text-sm font-medium text-slate-600 transition hover:border-brand-400 hover:text-brand-600">
                {{ chip }}
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
            <button type="button" class="flex h-9 shrink-0 items-center gap-1.5 rounded-full border border-slate-200 px-4 text-sm font-medium text-slate-600 transition hover:border-brand-400 hover:text-brand-600">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                </svg>
                All Filters
            </button>
            <div class="ml-auto hidden shrink-0 items-center gap-2 sm:flex">
                <span class="text-sm text-slate-500">Urutkan</span>
                <button type="button" class="flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 px-3.5 text-sm font-medium text-ink">
                    Terpopuler
                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Konten: sidebar + grid -->
    <section id="katalog" class="mx-auto max-w-[1320px] px-4 py-8 lg:px-15">
        <div class="flex gap-8">
            <!-- Sidebar filter -->
            <aside class="hidden w-64 shrink-0 lg:block">
                <div class="sticky top-24 space-y-7">
                    <fieldset>
                        <legend class="text-sm font-semibold text-ink">Kategori</legend>
                        <div class="mt-3 space-y-2.5">
                            <label v-for="k in kategoriFilter" :key="k" class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600 hover:text-ink">
                                <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-400" />
                                {{ k }}
                            </label>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-sm font-semibold text-ink">Rentang Harga</legend>
                        <input type="range" min="0" max="500000" value="150000" class="mt-4 w-full accent-brand-600" aria-label="Rentang harga maksimum" />
                        <p class="mt-1.5 text-xs text-slate-500">Rp 0 &ndash; Rp 500.000</p>
                    </fieldset>

                    <fieldset>
                        <legend class="text-sm font-semibold text-ink">Durasi</legend>
                        <div class="mt-3 space-y-2.5">
                            <label v-for="d in durasiFilter" :key="d" class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600 hover:text-ink">
                                <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-400" />
                                {{ d }}
                            </label>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-sm font-semibold text-ink">Jenis Akun</legend>
                        <div class="mt-3 space-y-2.5">
                            <label v-for="j in jenisFilter" :key="j" class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600 hover:text-ink">
                                <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-400" />
                                {{ j }}
                            </label>
                        </div>
                    </fieldset>

                    <button type="button" class="h-10 w-full rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 transition hover:border-brand-400 hover:text-brand-600">
                        Reset Filter
                    </button>
                </div>
            </aside>

            <!-- Grid produk -->
            <div class="min-w-0 flex-1">
                <h2 class="text-xl font-bold text-ink">Semua Produk</h2>
                <p class="mt-1 text-sm text-slate-500">{{ produk.length }} produk tersedia</p>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <a v-for="p in produk" :key="p.id" :href="`/produk/${p.id}`" class="block">
                        <ProductCard :product="p" />
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Paling Laris -->
    <section id="terlaris" class="mx-auto max-w-[1320px] px-4 pb-14 lg:px-15">
        <div class="flex items-end justify-between">
            <div>
                <h2 class="text-2xl font-bold text-ink">Paling Laris</h2>
                <p class="mt-1 text-sm text-slate-500">Produk yang paling banyak dibeli oleh pelanggan kami.</p>
            </div>
            <a href="#katalog" class="shrink-0 text-sm font-semibold text-brand-600 hover:text-brand-700">Lihat Semua &rarr;</a>
        </div>
        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <ProductCardSmall v-for="p in produk.slice(0, 6)" :key="p.id" :product="p" />
        </div>
    </section>

    <!-- Cara order -->
    <section id="cara-order" class="border-t border-slate-100 bg-surface py-14">
        <div class="mx-auto max-w-[1320px] px-4 lg:px-15">
            <h2 class="text-2xl font-bold text-ink">Cara Order</h2>
            <ol class="mt-6 grid gap-4 sm:grid-cols-3">
                <li v-for="(langkah, i) in [
                    { judul: 'Pilih produk & durasi', isi: 'Temukan akun premium yang kamu butuhkan, pilih durasi berlangganan.' },
                    { judul: 'Bayar & upload bukti', isi: 'Transfer ke rekening yang tertera, lalu unggah bukti pembayaran.' },
                    { judul: 'Kredensial terkirim otomatis', isi: 'Setelah diverifikasi, email & password langsung muncul di Pesanan Saya.' },
                ]" :key="langkah.judul" class="rounded-2xl border border-slate-200 bg-white p-5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">{{ i + 1 }}</span>
                    <h3 class="mt-3 text-sm font-semibold text-ink">{{ langkah.judul }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ langkah.isi }}</p>
                </li>
            </ol>
        </div>
    </section>
</template>

<script>
import PublicLayout from '@/Layouts/PublicLayout.vue'
import ProductCard from '@/Components/ProductCard.vue'
import ProductCardSmall from '@/Components/ProductCardSmall.vue'

export default {
    layout: PublicLayout,
    components: { ProductCard, ProductCardSmall },
}
</script>
