<script setup>
// ponytail: mock detail produk — data hardcoded, ganti dari Controller saat backend hidup
const produk = {
    id: 1,
    nama: 'Netflix Premium',
    kategori: 'Streaming',
    jenis_akun_label: 'Personal',
    rating: '4.9',
    jumlah_ulasan: '2.1K',
    terjual: '12.4K',
    deskripsi:
        'Nikmati film dan series favorit kualitas 4K UHD. Akun bergaransi selama masa aktif — klaim garansi cukup hubungi admin, kredensial pengganti langsung tampil di Pesanan Saya.',
}

const varian = [
    { durasi_label: '1 Bulan', harga: 25000, harga_asli: 45000 },
    { durasi_label: '3 Bulan', harga: 65000, harga_asli: 120000 },
    { durasi_label: '6 Bulan', harga: 120000, harga_asli: 230000 },
    { durasi_label: '1 Tahun', harga: 220000, harga_asli: 440000 },
]

const stokTersedia = 14

const ulasan = [
    { nama: 'Rizky A.', rating: 5, teks: 'Kirim instan, akun lancar jaya. Recommended!', tanggal: '2 hari lalu' },
    { nama: 'Dina P.', rating: 5, teks: 'Udah langganan 6 bulan, cuma sekali klaim garansi dan langsung diganti.', tanggal: '1 minggu lalu' },
    { nama: 'Bagas W.', rating: 4, teks: 'Bagus, tapi proses verifikasi malam agak lambat.', tanggal: '2 minggu lalu' },
]
</script>

<template>
    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" class="mx-auto max-w-[1320px] px-4 lg:px-15">
        <ol class="flex h-12 items-center gap-2 text-sm text-slate-500">
            <li><a href="/" class="hover:text-brand-600">Beranda</a></li>
            <li aria-hidden="true">&rsaquo;</li>
            <li><a href="/" class="hover:text-brand-600">{{ produk.kategori }}</a></li>
            <li aria-hidden="true">&rsaquo;</li>
            <li aria-current="page" class="font-medium text-ink">{{ produk.nama }}</li>
        </ol>
    </nav>

    <section class="mx-auto max-w-[1320px] px-4 pb-12 lg:px-15">
        <div class="grid gap-8 lg:grid-cols-2">
            <!-- Banner -->
            <div class="flex h-72 items-center justify-center rounded-3xl bg-gradient-to-br from-brand-50 via-brand-100 to-brand-200 sm:h-96">
                <span class="text-8xl font-black text-brand-600/80">{{ produk.nama.charAt(0) }}</span>
            </div>

            <!-- Info -->
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-ink">{{ produk.nama }}</h1>
                <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-slate-500">
                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                        {{ produk.jenis_akun_label }} Akun
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <Icon icon="heroicons:star-solid" class="h-4 w-4 text-amber-400" />
                        <span class="font-semibold text-ink">{{ produk.rating }}</span> ({{ produk.jumlah_ulasan }})
                    </span>
                    <span>{{ produk.terjual }} terjual</span>
                    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                        Stok {{ stokTersedia }} tersedia
                    </span>
                </div>

                <p class="mt-4 text-sm leading-7 text-slate-600">{{ produk.deskripsi }}</p>

                <!-- Varian durasi -->
                <h2 class="mt-6 text-sm font-semibold text-ink">Pilih Durasi</h2>
                <div class="mt-2.5 grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                    <button
                        v-for="(v, i) in varian"
                        :key="v.durasi_label"
                        type="button"
                        class="rounded-xl border px-3 py-2.5 text-left transition"
                        :class="i === 0
                            ? 'border-brand-500 bg-brand-50 ring-1 ring-brand-500'
                            : 'border-slate-200 hover:border-brand-300'"
                    >
                        <span class="block text-xs font-medium" :class="i === 0 ? 'text-brand-700' : 'text-slate-600'">{{ v.durasi_label }}</span>
                        <span class="mt-0.5 block text-sm font-bold text-ink">Rp {{ v.harga.toLocaleString('id-ID') }}</span>
                        <span class="block text-[11px] text-slate-400 line-through">Rp {{ v.harga_asli.toLocaleString('id-ID') }}</span>
                    </button>
                </div>

                <!-- Aksi -->
                <div class="mt-6 flex flex-col gap-2.5 sm:flex-row">
                    <button
                        type="button"
                        class="flex h-12 flex-1 items-center justify-center gap-2 rounded-xl bg-brand-600 text-sm font-semibold text-white transition hover:bg-brand-700 focus-visible:ring-2 focus-visible:ring-brand-300 focus-visible:outline-none"
                    >
                        <Icon icon="heroicons:shopping-cart" class="h-4.5 w-4.5" />
                        Beli Sekarang
                    </button>
                    <button
                        type="button"
                        class="flex h-12 flex-1 items-center justify-center gap-2 rounded-xl border border-brand-600 text-sm font-semibold text-brand-600 transition hover:bg-brand-50"
                    >
                        <Icon icon="heroicons:shopping-bag" class="h-4.5 w-4.5" />
                        Masukkan Keranjang
                    </button>
                </div>
            </div>
        </div>

        <!-- Ulasan -->
        <div class="mt-12">
            <h2 class="text-xl font-bold text-ink">Ulasan Pembeli</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <article v-for="u in ulasan" :key="u.nama" class="rounded-2xl border border-slate-200 p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-ink">{{ u.nama }}</span>
                        <span class="text-xs text-slate-400">{{ u.tanggal }}</span>
                    </div>
                    <div class="mt-1 flex gap-0.5" :aria-label="`Rating ${u.rating} dari 5`">
                        <Icon v-for="n in u.rating" :key="n" icon="heroicons:star-solid" class="h-3.5 w-3.5 text-amber-400" />
                    </div>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ u.teks }}</p>
                </article>
            </div>
        </div>
    </section>
</template>

<script>
import PublicLayout from '@/Layouts/PublicLayout.vue'

export default { layout: PublicLayout }
</script>
