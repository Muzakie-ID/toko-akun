<script setup>
// Data mock katalog — ponytail: ganti dengan data dari Controller saat backend hidup; warna_banner/warna_teks dari tabel brand
const produk = [
    { id: 1, nama: 'Netflix Premium', harga: 25000, harga_asli: 45000, rating: '4.9', jumlah_ulasan: '2.1K', terjual: '12.4K', jenis_akun_label: 'Personal', durasi_label: '1 Bulan', warna_banner: '#7f0f13', warna_teks: '#ffffff' },
    { id: 2, nama: 'Spotify Premium', harga: 15000, harga_asli: 29000, rating: '4.8', jumlah_ulasan: '1.5K', terjual: '8.9K', jenis_akun_label: 'Personal', durasi_label: '1 Bulan', warna_banner: '#0e5c2f', warna_teks: '#1ed760' },
    { id: 3, nama: 'ChatGPT Plus', harga: 85000, harga_asli: 120000, rating: '4.8', jumlah_ulasan: '980', terjual: '6.7K', jenis_akun_label: 'Private', durasi_label: '1 Bulan', warna_banner: '#0d7a61', warna_teks: '#ffffff' },
    { id: 4, nama: 'YouTube Premium', harga: 20000, harga_asli: 35000, rating: '4.9', jumlah_ulasan: '1.3K', terjual: '9.1K', jenis_akun_label: 'Sharing', durasi_label: '1 Bulan', warna_banner: '#ffffff', warna_teks: '#ff0000' },
    { id: 5, nama: 'Canva Pro', harga: 30000, harga_asli: 59000, rating: '4.8', jumlah_ulasan: '620', terjual: '4.2K', jenis_akun_label: 'Sharing', durasi_label: '1 Tahun', warna_banner: 'linear-gradient(135deg, #7d2ae8, #00c4cc)', warna_teks: '#ffffff' },
    { id: 6, nama: 'Microsoft 365', harga: 89000, harga_asli: 150000, rating: '4.7', jumlah_ulasan: '540', terjual: '3.1K', jenis_akun_label: 'Personal', durasi_label: '1 Tahun', warna_banner: '#ffffff', warna_teks: '#d83b01' },
    { id: 7, nama: 'CapCut Pro', harga: 25000, harga_asli: 45000, rating: '4.8', jumlah_ulasan: '430', terjual: '2.9K', jenis_akun_label: 'Personal', durasi_label: '1 Bulan', warna_banner: '#111111', warna_teks: '#ffffff' },
    { id: 8, nama: 'Google One 200GB', harga: 45000, harga_asli: 75000, rating: '4.8', jumlah_ulasan: '380', terjual: '1.8K', jenis_akun_label: 'Personal', durasi_label: '1 Tahun', warna_banner: '#ffffff', warna_teks: '#4285f4' },
]

const kategoriFilter = [
    { label: 'Streaming', count: 12 }, { label: 'AI Tools', count: 9 }, { label: 'Desain', count: 7 }, { label: 'Office', count: 6 }, { label: 'Cloud Storage', count: 5 }, { label: 'Game', count: 4 }, { label: 'Lainnya', count: 8 },
]
const durasiFilter = [
    { label: '1 Bulan', count: 28 }, { label: '3 Bulan', count: 12 }, { label: '6 Bulan', count: 6 }, { label: '1 Tahun', count: 8 },
]
const jenisFilter = [
    { label: 'Personal', count: 24 }, { label: 'Private', count: 18 }, { label: 'Sharing', count: 20 }, { label: 'Upgrade', count: 6 },
]
</script>

<template>
    <section class="relative overflow-hidden bg-cream">
        <div class="mx-auto flex max-w-[1320px] items-center justify-between gap-8 px-4 py-16 lg:px-15 lg:py-20">
            <div class="max-w-xl">
                <h1 class="text-3xl leading-tight font-extrabold tracking-tight text-ink sm:text-4xl lg:text-[44px]">
                    Langganan Premium,<br />
                    <span class="text-gold">Lebih Hemat</span>
                </h1>
                <p class="mt-4 text-base text-slate-600">
                    Proses otomatis &bull; Pengiriman instan &bull; Bantuan pelanggan
                </p>
                <a
                    href="#katalog"
                    class="mt-6 inline-flex h-11 items-center gap-2 rounded-xl bg-brand-900 px-6 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700"
                >
                    Lihat Semua Produk
                    <Icon icon="heroicons:arrow-right" class="h-4 w-4" />
                </a>
            </div>

            <!-- Dekorasi ikon app berwarna brand -->
            <div class="hidden shrink-0 items-center gap-4 lg:flex" aria-hidden="true">
                <span
                    v-for="b in [
                        { h: 'N', bg: '#e50914' },
                        { h: 'S', bg: '#1db954' },
                        { h: 'C', bg: '#10a37f' },
                        { h: 'Y', bg: '#ff0000' },
                        { h: 'Ca', bg: 'linear-gradient(135deg, #7d2ae8, #00c4cc)' },
                    ]"
                    :key="b.h"
                    class="flex h-14 w-14 rotate-3 items-center justify-center rounded-2xl text-xl font-black text-white shadow-lg"
                    :style="{ background: b.bg }"
                >{{ b.h }}</span>
            </div>
        </div>
    </section>

    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" class="mx-auto max-w-[1320px] px-4 lg:px-15">
        <ol class="flex h-12 items-center gap-2 text-sm text-slate-500">
            <li><a href="/" class="hover:text-ink">Beranda</a></li>
            <li aria-hidden="true">&rsaquo;</li>
            <li aria-current="page" class="font-medium text-ink">Semua Produk</li>
        </ol>
    </nav>

    <!-- Filter chips -->
    <div class="border-y border-slate-100 bg-white">
        <div class="mx-auto flex max-w-[1320px] items-center gap-2 overflow-x-auto px-4 py-3.5 lg:px-15">
            <button v-for="chip in [
                    { label: 'Kategori', icon: 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z' },
                    { label: 'Durasi', icon: 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' },
                    { label: 'Jenis Akun', icon: 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z' },
                    { label: 'Harga', icon: 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' },
                ]" :key="chip.label" type="button"
                class="flex h-9 shrink-0 items-center gap-1.5 rounded-full border border-slate-200 px-4 text-sm font-medium text-slate-600 transition hover:border-brand-400 hover:text-ink">
                <Icon icon="heroicons:squares-2x2" class="h-3.5 w-3.5" />
                {{ chip.label }}
                <Icon icon="heroicons:chevron-down" class="h-3.5 w-3.5" />
            </button>
            <button type="button" class="flex h-9 shrink-0 items-center gap-1.5 rounded-full border border-slate-200 px-4 text-sm font-medium text-slate-600 transition hover:border-brand-400 hover:text-brand-600">
                <Icon icon="heroicons:funnel" class="h-3.5 w-3.5" />
                All Filters
            </button>
            <div class="ml-auto hidden shrink-0 items-center gap-2 sm:flex">
                <span class="text-sm text-slate-500">Urutkan</span>
                <button type="button" class="flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 px-3.5 text-sm font-medium text-ink">
                    Terpopuler
                    <Icon icon="heroicons:chevron-down" class="h-3.5 w-3.5 text-slate-400" />
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
                            <label v-for="k in kategoriFilter" :key="k.label" class="flex cursor-pointer items-center justify-between gap-2.5 text-sm text-slate-600 hover:text-ink">
                                <span class="flex items-center gap-2.5">
                                    <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-400" />
                                    {{ k.label }}
                                </span>
                                <span class="text-xs text-slate-400">{{ k.count }}</span>
                            </label>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-sm font-semibold text-ink">Rentang Harga</legend>
                        <div class="mt-4 flex items-center gap-2">
                            <input type="text" inputmode="numeric" value="Rp 0" aria-label="Harga minimum" class="h-9 w-full rounded-lg border border-slate-200 px-2.5 text-xs text-ink focus:border-slate-400 focus:outline-none" />
                            <span class="text-slate-400">&ndash;</span>
                            <input type="text" inputmode="numeric" value="Rp 500.000" aria-label="Harga maksimum" class="h-9 w-full rounded-lg border border-slate-200 px-2.5 text-xs text-ink focus:border-slate-400 focus:outline-none" />
                        </div>
                        <input type="range" min="0" max="500000" value="150000" class="mt-3 w-full accent-brand-600" aria-label="Rentang harga maksimum" />
                    </fieldset>

                    <fieldset>
                        <legend class="text-sm font-semibold text-ink">Durasi</legend>
                        <div class="mt-3 space-y-2.5">
                            <label v-for="d in durasiFilter" :key="d.label" class="flex cursor-pointer items-center justify-between gap-2.5 text-sm text-slate-600 hover:text-ink">
                                <span class="flex items-center gap-2.5">
                                    <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-400" />
                                    {{ d.label }}
                                </span>
                                <span class="text-xs text-slate-400">{{ d.count }}</span>
                            </label>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-sm font-semibold text-ink">Jenis Akun</legend>
                        <div class="mt-3 space-y-2.5">
                            <label v-for="j in jenisFilter" :key="j.label" class="flex cursor-pointer items-center justify-between gap-2.5 text-sm text-slate-600 hover:text-ink">
                                <span class="flex items-center gap-2.5">
                                    <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-400" />
                                    {{ j.label }}
                                </span>
                                <span class="text-xs text-slate-400">{{ j.count }}</span>
                            </label>
                        </div>
                    </fieldset>

                    <button type="button" class="flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 transition hover:border-brand-400 hover:text-ink">
                        <Icon icon="heroicons:arrow-path" class="h-4 w-4" />
                        Reset Filter
                    </button>
                </div>
            </aside>

            <!-- Grid produk -->
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold text-ink">Produk Premium untuk Kamu</h2>
                <p class="mt-1 text-sm text-slate-500">Temukan akun dan layanan digital sesuai kebutuhanmu.</p>

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
