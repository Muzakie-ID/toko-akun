<script setup>
import { router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import { rupiah } from '@/lib/format'

const props = defineProps({
    produk: { type: Array, required: true },
    terlaris: { type: Array, required: true },
    filters: { type: Object, required: true },
    opsi: { type: Object, required: true },
})

const f = reactive({
    q: props.filters.q,
    kategori: [...props.filters.kategori],
    jenis: [...props.filters.jenis],
    durasi: [...props.filters.durasi],
    harga_max: props.filters.harga_max ?? props.opsi.harga_tertinggi,
    urut: props.filters.urut,
})

const apply = () => {
    const query = { ...f }
    if (!query.q) delete query.q
    if (!query.harga_max || query.harga_max >= props.opsi.harga_tertinggi) delete query.harga_max
    if (query.urut === 'populer') delete query.urut
    router.get('/', query, { preserveState: true, preserveScroll: true, replace: true, only: ['produk', 'filters'] })
}

const toggle = (list, value) => {
    const i = list.indexOf(value)
    i === -1 ? list.push(value) : list.splice(i, 1)
    apply()
}

const reset = () => {
    Object.assign(f, { q: '', kategori: [], jenis: [], durasi: [], harga_max: props.opsi.harga_tertinggi, urut: 'populer' })
    apply()
}

const adaFilter = () => f.q || f.kategori.length || f.jenis.length || f.durasi.length || f.harga_max < props.opsi.harga_tertinggi
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

    <!-- Filter cepat: tipe akun + urutan -->
    <div class="border-y border-slate-100 bg-white">
        <div class="mx-auto flex max-w-[1320px] items-center gap-2 overflow-x-auto px-4 py-3.5 lg:px-15">
            <button
                v-for="j in opsi.jenis"
                :key="j.value"
                type="button"
                :aria-pressed="f.jenis.includes(j.value)"
                class="flex h-9 shrink-0 items-center gap-1.5 rounded-full border px-4 text-sm font-medium transition"
                :class="f.jenis.includes(j.value) ? 'border-ink bg-ink text-white' : 'border-slate-200 text-slate-600 hover:border-slate-400 hover:text-ink'"
                @click="toggle(f.jenis, j.value)"
            >
                <Icon :icon="j.value === 'sharing' ? 'heroicons:user-group' : 'heroicons:user'" class="h-3.5 w-3.5" />
                {{ j.label }}
            </button>
            <button
                v-for="d in opsi.durasi"
                :key="d.value"
                type="button"
                :aria-pressed="f.durasi.includes(d.value)"
                class="flex h-9 shrink-0 items-center gap-1.5 rounded-full border px-4 text-sm font-medium transition"
                :class="f.durasi.includes(d.value) ? 'border-ink bg-ink text-white' : 'border-slate-200 text-slate-600 hover:border-slate-400 hover:text-ink'"
                @click="toggle(f.durasi, d.value)"
            >
                <Icon icon="heroicons:clock" class="h-3.5 w-3.5" />
                {{ d.label }}
            </button>
            <div class="ml-auto flex shrink-0 items-center gap-2">
                <label for="urut" class="text-sm text-slate-500">Urutkan</label>
                <select id="urut" v-model="f.urut" class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-ink focus:border-slate-400 focus:outline-none" @change="apply">
                    <option value="populer">Terpopuler</option>
                    <option value="terbaru">Terbaru</option>
                    <option value="termurah">Harga terendah</option>
                    <option value="termahal">Harga tertinggi</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Konten: sidebar + grid -->
    <section id="katalog" class="mx-auto max-w-[1320px] px-4 py-8 lg:px-15">
        <div class="flex gap-8">
            <!-- Sidebar filter -->
            <aside class="hidden w-64 shrink-0 lg:block">
                <div class="sticky top-24 space-y-7">
                    <fieldset v-if="opsi.kategori.length">
                        <legend class="text-sm font-semibold text-ink">Kategori</legend>
                        <div class="mt-3 space-y-2.5">
                            <label v-for="k in opsi.kategori" :key="k.value" class="flex cursor-pointer items-center justify-between gap-2.5 text-sm text-slate-600 hover:text-ink">
                                <span class="flex items-center gap-2.5">
                                    <input type="checkbox" :checked="f.kategori.includes(k.value)" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-400" @change="toggle(f.kategori, k.value)" />
                                    {{ k.label }}
                                </span>
                                <span class="text-xs text-slate-400">{{ k.jumlah }}</span>
                            </label>
                        </div>
                    </fieldset>

                    <fieldset v-if="opsi.harga_tertinggi">
                        <legend class="text-sm font-semibold text-ink">Harga maksimal</legend>
                        <p class="mt-3 text-sm font-medium text-ink">{{ rupiah(f.harga_max) }}</p>
                        <input v-model.number="f.harga_max" type="range" min="0" :max="opsi.harga_tertinggi" :step="1000" class="mt-2 w-full accent-brand-600" aria-label="Harga maksimal" @change="apply" />
                    </fieldset>

                    <fieldset>
                        <legend class="text-sm font-semibold text-ink">Durasi</legend>
                        <div class="mt-3 space-y-2.5">
                            <label v-for="d in opsi.durasi" :key="d.value" class="flex cursor-pointer items-center justify-between gap-2.5 text-sm text-slate-600 hover:text-ink">
                                <span class="flex items-center gap-2.5">
                                    <input type="checkbox" :checked="f.durasi.includes(d.value)" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-400" @change="toggle(f.durasi, d.value)" />
                                    {{ d.label }}
                                </span>
                                <span class="text-xs text-slate-400">{{ d.jumlah }}</span>
                            </label>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-sm font-semibold text-ink">Jenis Akun</legend>
                        <div class="mt-3 space-y-2.5">
                            <label v-for="j in opsi.jenis" :key="j.value" class="flex cursor-pointer items-center justify-between gap-2.5 text-sm text-slate-600 hover:text-ink">
                                <span class="flex items-center gap-2.5">
                                    <input type="checkbox" :checked="f.jenis.includes(j.value)" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-400" @change="toggle(f.jenis, j.value)" />
                                    {{ j.label }}
                                </span>
                                <span class="text-xs text-slate-400">{{ j.jumlah }}</span>
                            </label>
                        </div>
                    </fieldset>

                    <button type="button" :disabled="!adaFilter()" class="flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 transition hover:border-brand-400 hover:text-ink disabled:opacity-40" @click="reset">
                        <Icon icon="heroicons:arrow-path" class="h-4 w-4" />
                        Reset Filter
                    </button>
                </div>
            </aside>

            <!-- Grid produk -->
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold text-ink">Produk Premium untuk Kamu</h2>
                <p class="mt-1 text-sm text-slate-500">Temukan akun dan layanan digital sesuai kebutuhanmu.</p>

                <p v-if="filters.q" class="mt-3 text-sm text-slate-600">Hasil pencarian "<span class="font-semibold text-ink">{{ filters.q }}</span>" &middot; {{ produk.length }} produk</p>
                <div v-if="produk.length" class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <ProductCard v-for="p in produk" :key="p.id" :product="p" />
                </div>
                <div v-else class="mt-10 flex flex-col items-center rounded-2xl border border-dashed border-slate-200 py-14 text-center">
                    <Icon icon="heroicons:magnifying-glass" class="h-10 w-10 text-slate-300" />
                    <p class="mt-3 text-sm font-semibold text-ink">Produk tidak ditemukan</p>
                    <p class="mt-1 text-sm text-slate-500">Coba kata kunci lain atau hapus filter.</p>
                    <button type="button" class="mt-4 h-10 rounded-xl bg-brand-600 px-5 text-sm font-semibold text-white hover:bg-brand-700" @click="reset">Reset Filter</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Paling Laris -->
    <section v-if="terlaris.length" id="terlaris" class="mx-auto max-w-[1320px] px-4 pb-14 lg:px-15">
        <div class="flex items-end justify-between">
            <div>
                <h2 class="text-2xl font-bold text-ink">Paling Laris</h2>
                <p class="mt-1 text-sm text-slate-500">Produk yang paling banyak dibeli oleh pelanggan kami.</p>
            </div>
            <a href="#katalog" class="shrink-0 text-sm font-semibold text-brand-600 hover:text-brand-700">Lihat Semua &rarr;</a>
        </div>
        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <ProductCardSmall v-for="p in terlaris" :key="p.id" :product="p" />
        </div>
    </section>

    <!-- Cara order -->
    <section id="cara-order" class="border-t border-slate-100 bg-surface py-14">
        <div class="mx-auto max-w-[1320px] px-4 lg:px-15">
            <h2 class="text-2xl font-bold text-ink">Cara Order</h2>
            <ol class="mt-6 grid gap-4 sm:grid-cols-3">
                <li v-for="(langkah, i) in [
                    { judul: 'Pilih produk & durasi', isi: 'Temukan akun premium yang kamu butuhkan, pilih durasi berlangganan.' },
                    { judul: 'Bayar via QRIS atau transfer', isi: 'Scan QRIS dari e-wallet/m-banking, atau transfer bank lalu unggah bukti.' },
                    { judul: 'Kredensial terkirim otomatis', isi: 'Begitu pembayaran masuk, email & password langsung muncul di Pesanan Saya.' },
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
