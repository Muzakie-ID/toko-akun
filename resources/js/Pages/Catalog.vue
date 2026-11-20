<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed, reactive, ref } from 'vue'
import Countdown from '@/Components/Countdown.vue'
import ProductCardCompact from '@/Components/ProductCardCompact.vue'
import { rupiah } from '@/lib/format'

const props = defineProps({
    produk: { type: Array, required: true },
    terlaris: { type: Array, required: true },
    filters: { type: Object, required: true },
    opsi: { type: Object, required: true },
    promo: { type: Object, required: true },
    flashSale: { type: Object, default: null },
})

const user = computed(() => usePage().props.auth?.user)

// Chip kategori di HP: pilih satu (seperti referensi); panel filter tetap bisa pilih banyak
const pilihKategori = (slug) => {
    f.kategori = slug ? [slug] : []
    apply()
}
const cariHp = () => apply()

const f = reactive({
    q: props.filters.q,
    kategori: [...props.filters.kategori],
    jenis: [...props.filters.jenis],
    durasi: [...props.filters.durasi],
    harga_max: props.filters.harga_max ?? props.opsi.harga_tertinggi,
    urut: props.filters.urut,
    sale: props.filters.sale ?? false,
})

const apply = () => {
    const query = { ...f }
    if (!query.q) delete query.q
    if (!query.harga_max || query.harga_max >= props.opsi.harga_tertinggi) delete query.harga_max
    if (query.urut === 'populer') delete query.urut
    if (query.sale) query.sale = 1
    else delete query.sale
    router.get('/', query, { preserveState: true, preserveScroll: true, replace: true, only: ['produk', 'filters'] })
}

const toggle = (list, value) => {
    const i = list.indexOf(value)
    i === -1 ? list.push(value) : list.splice(i, 1)
    apply()
}

const reset = () => {
    Object.assign(f, { q: '', kategori: [], jenis: [], durasi: [], harga_max: props.opsi.harga_tertinggi, urut: 'populer', sale: false })
    apply()
}

const lihatSale = () => {
    f.sale = true
    apply()
}
// Saat sale berakhir, muat ulang agar harga kembali normal
const saleSelesai = () => router.reload()
const adaFilter = () => f.sale || f.q || f.kategori.length || f.jenis.length || f.durasi.length || f.harga_max < props.opsi.harga_tertinggi

// Di HP, sidebar filter dibuka sebagai panel bawah
const filterOpen = ref(false)
const jumlahFilter = computed(() => f.kategori.length + f.jenis.length + f.durasi.length + (f.harga_max < props.opsi.harga_tertinggi ? 1 : 0))
</script>

<template>
    <!-- ===== Beranda HP (mobile-first) ===== -->
    <div class="md:hidden">
        <!-- Pencarian menonjol + filter -->
        <form class="flex gap-2 px-4 pt-3" role="search" @submit.prevent="cariHp">
            <label class="flex h-12 min-w-0 flex-1 items-center gap-2.5 rounded-2xl bg-slate-100 px-4 focus-within:ring-2 focus-within:ring-slate-300">
                <Icon icon="heroicons:magnifying-glass" class="h-5 w-5 shrink-0 text-slate-400" />
                <input v-model="f.q" type="search" aria-label="Cari produk" placeholder="Cari Netflix, ChatGPT, Canva…" class="w-full bg-transparent text-sm text-ink placeholder:text-slate-400 focus:outline-none" />
            </label>
            <button type="button" class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-ink text-white" aria-label="Filter & urutkan" @click="filterOpen = true">
                <Icon icon="heroicons:adjustments-horizontal" class="h-5 w-5" />
                <span v-if="jumlahFilter" class="absolute -top-1 -right-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-gold px-1 text-[10px] font-bold">{{ jumlahFilter }}</span>
            </button>
        </form>

        <!-- Chip kategori -->
        <div class="mt-3 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none]" role="tablist" aria-label="Kategori">
            <button
                type="button"
                role="tab"
                :aria-selected="!f.kategori.length"
                class="h-9 shrink-0 rounded-full px-4 text-sm font-medium transition"
                :class="!f.kategori.length ? 'bg-ink text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200'"
                @click="pilihKategori(null)"
            >Semua</button>
            <button
                v-if="f.sale"
                type="button"
                class="flex h-9 shrink-0 items-center gap-1 rounded-full bg-rose-500 px-4 text-sm font-medium text-white"
                aria-label="Hapus filter flash sale"
                @click="f.sale = false; apply()"
            ><Icon icon="heroicons:bolt-solid" class="h-4 w-4" /> Flash Sale <Icon icon="heroicons:x-mark" class="h-4 w-4" /></button>
            <button
                v-for="k in opsi.kategori"
                :key="k.value"
                type="button"
                role="tab"
                :aria-selected="f.kategori.length === 1 && f.kategori[0] === k.value"
                class="h-9 shrink-0 rounded-full px-4 text-sm font-medium transition"
                :class="f.kategori.length === 1 && f.kategori[0] === k.value ? 'bg-ink text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200'"
                @click="pilihKategori(k.value)"
            >{{ k.label }}</button>
        </div>

        <template v-if="!adaFilter()">
            <!-- Banner utama -->
            <div class="mx-4 mt-3 overflow-hidden rounded-3xl bg-cream p-5">
                <p v-if="promo.diskonMaks" class="inline-flex rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-rose-600">Hemat s.d. {{ promo.diskonMaks }}%</p>
                <h1 class="mt-2 text-2xl leading-tight font-extrabold tracking-tight text-ink">Langganan Premium,<br /><span class="text-gold">Lebih Hemat</span></h1>
                <p class="mt-1.5 text-sm text-slate-600">Akun dikirim otomatis begitu pembayaran masuk.</p>
                <a href="#katalog" class="mt-4 inline-flex h-11 items-center gap-2 rounded-xl bg-ink px-5 text-sm font-semibold text-white">
                    Belanja Sekarang <Icon icon="heroicons:arrow-right" class="h-4 w-4" />
                </a>
            </div>

            <!-- Flash sale (hanya saat ada sale berlangsung) -->
            <section v-if="flashSale" class="mx-4 mt-3 overflow-hidden rounded-3xl bg-gradient-to-br from-rose-500 to-orange-500 pt-4 pb-3 text-white">
                <div class="flex items-start justify-between gap-3 px-4">
                    <div class="min-w-0">
                        <p class="flex items-center gap-1 text-base font-bold"><Icon icon="heroicons:bolt-solid" class="h-5 w-5" /> {{ flashSale.nama }}</p>
                        <p class="text-xs text-white/80">Berakhir dalam</p>
                    </div>
                    <button type="button" class="-my-1 shrink-0 rounded-lg bg-white/15 px-3 py-2 text-xs font-semibold" @click="lihatSale">Lihat semua</button>
                </div>
                <div class="mt-2 px-4"><Countdown :to="flashSale.selesai_at" @selesai="saleSelesai" /></div>
                <ul class="mt-3 flex snap-x gap-2.5 overflow-x-auto px-4 pb-1 text-ink [scrollbar-width:none]">
                    <li v-for="p in flashSale.produk" :key="p.id" class="w-36 shrink-0 snap-start">
                        <ProductCardCompact :product="p" />
                    </li>
                </ul>
            </section>

            <!-- Kartu jaminan -->
            <div class="mx-4 mt-3 rounded-2xl bg-white p-4 ring-1 ring-slate-200/80">
                <p class="flex items-center gap-1.5 text-sm font-semibold text-ink"><Icon icon="heroicons:shield-check" class="h-4 w-4 text-gold" /> Kenapa belanja di sini</p>
                <ul class="mt-3 grid grid-cols-3 gap-2">
                    <li v-for="j in [
                        { icon: 'heroicons:bolt', label: 'Kirim', nilai: 'Otomatis' },
                        { icon: 'heroicons:arrow-path', label: 'Garansi', nilai: 'Ganti akun' },
                        { icon: 'heroicons:qr-code', label: 'Bayar', nilai: 'QRIS' },
                    ]" :key="j.label" class="flex items-center gap-2">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-cream text-gold"><Icon :icon="j.icon" class="h-4 w-4" /></span>
                        <span class="min-w-0 leading-tight"><span class="block text-[10px] text-slate-400">{{ j.label }}</span><span class="block truncate text-xs font-semibold text-ink">{{ j.nilai }}</span></span>
                    </li>
                </ul>
            </div>

            <!-- Banner saldo = harga reseller (penawaran nyata, tanpa hitung mundur palsu) -->
            <Link v-if="promo.resellerSaldo && !user?.harga_reseller" :href="user ? '/saldo' : '/register'" class="mx-4 mt-3 flex items-center gap-3 rounded-2xl bg-ink p-4 text-white">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/10 text-gold"><Icon icon="heroicons:sparkles" class="h-5 w-5" /></span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold">Harga reseller lebih murah</span>
                    <span class="block text-xs text-white/70">Saldo minimal {{ rupiah(promo.resellerSaldo) }}, otomatis berlaku.</span>
                </span>
                <span class="shrink-0 rounded-lg bg-white px-3 py-2 text-xs font-semibold text-ink">{{ user ? 'Top-up' : 'Daftar' }}</span>
            </Link>
            <div v-else-if="user?.harga_reseller" class="mx-4 mt-3 flex items-center gap-3 rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-200">
                <Icon icon="heroicons:sparkles-solid" class="h-5 w-5 shrink-0 text-amber-500" />
                <p class="text-sm font-medium text-amber-900">Harga reseller aktif untukmu di semua produk.</p>
            </div>

            <!-- Paling Laris: deretan geser -->
            <section v-if="terlaris.length" class="mt-6">
                <div class="flex items-center justify-between px-4">
                    <h2 class="text-base font-bold text-ink">Paling Laris</h2>
                    <a href="#katalog" class="-my-2 py-2 text-sm font-medium text-slate-500">Lihat semua</a>
                </div>
                <ul class="mt-3 flex snap-x gap-3 overflow-x-auto px-4 pb-1 [scrollbar-width:none]">
                    <li v-for="p in terlaris" :key="p.id" class="w-40 shrink-0 snap-start">
                        <ProductCardCompact :product="p" />
                    </li>
                </ul>
            </section>
        </template>
    </div>

    <!-- ===== Desktop ===== -->
    <section class="relative hidden overflow-hidden bg-cream md:block">
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

    <!-- Flash sale desktop -->
    <section v-if="flashSale && !adaFilter()" class="mx-auto mt-6 hidden max-w-[1320px] px-4 md:block lg:px-15">
        <div class="rounded-3xl bg-gradient-to-br from-rose-500 to-orange-500 p-6 text-white">
            <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                <h2 class="flex items-center gap-2 text-xl font-bold"><Icon icon="heroicons:bolt-solid" class="h-6 w-6" /> {{ flashSale.nama }}</h2>
                <div class="flex items-center gap-2 text-sm text-white/85">Berakhir dalam <Countdown :to="flashSale.selesai_at" @selesai="saleSelesai" /></div>
                <button type="button" class="ml-auto h-10 rounded-xl bg-white px-4 text-sm font-semibold text-ink" @click="lihatSale">Lihat semua sale</button>
            </div>
            <ul class="mt-5 grid grid-cols-2 gap-3 text-ink lg:grid-cols-4 xl:grid-cols-6">
                <li v-for="p in flashSale.produk.slice(0, 6)" :key="p.id"><ProductCardCompact :product="p" /></li>
            </ul>
        </div>
    </section>

    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" class="mx-auto hidden max-w-[1320px] px-4 md:block lg:px-15">
        <ol class="flex h-12 items-center gap-2 text-sm text-slate-500">
            <li><a href="/" class="hover:text-ink">Beranda</a></li>
            <li aria-hidden="true">&rsaquo;</li>
            <li aria-current="page" class="font-medium text-ink">Semua Produk</li>
        </ol>
    </nav>

    <!-- Filter cepat: tipe akun + urutan -->
    <div class="hidden border-y border-slate-100 bg-white md:block">
        <div class="mx-auto flex max-w-[1320px] items-center gap-2 overflow-x-auto px-4 py-3 lg:px-15 lg:py-3.5">
            <button
                type="button"
                class="flex h-10 shrink-0 items-center gap-1.5 rounded-full border border-ink bg-ink px-4 text-sm font-medium text-white lg:hidden"
                :aria-expanded="filterOpen"
                @click="filterOpen = true"
            >
                <Icon icon="heroicons:adjustments-horizontal" class="h-4 w-4" />
                Filter<span v-if="jumlahFilter"> ({{ jumlahFilter }})</span>
            </button>
            <button
                v-for="j in opsi.jenis"
                :key="j.value"
                type="button"
                :aria-pressed="f.jenis.includes(j.value)"
                class="flex h-10 shrink-0 items-center gap-1.5 rounded-full border px-4 text-sm font-medium transition lg:h-9"
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
                class="flex h-10 shrink-0 items-center gap-1.5 rounded-full border px-4 text-sm font-medium transition lg:h-9"
                :class="f.durasi.includes(d.value) ? 'border-ink bg-ink text-white' : 'border-slate-200 text-slate-600 hover:border-slate-400 hover:text-ink'"
                @click="toggle(f.durasi, d.value)"
            >
                <Icon icon="heroicons:clock" class="h-3.5 w-3.5" />
                {{ d.label }}
            </button>
            <div class="ml-auto hidden shrink-0 items-center gap-2 lg:flex">
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
    <section id="katalog" class="mx-auto max-w-[1320px] scroll-mt-20 px-4 pt-6 pb-8 md:py-8 lg:px-15">
        <div class="flex gap-8">
            <!-- Sidebar filter -->
            <div v-if="filterOpen" class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden" @click="filterOpen = false" />
            <aside
                class="w-64 shrink-0 lg:block"
                :class="filterOpen ? 'fixed inset-x-0 bottom-0 z-50 block max-h-[85vh] w-full overflow-y-auto rounded-t-3xl bg-white px-5 pt-4 pb-6 shadow-2xl' : 'hidden'"
                :role="filterOpen ? 'dialog' : undefined"
                :aria-modal="filterOpen ? 'true' : undefined"
                aria-label="Filter produk"
            >
                <div class="mb-4 flex items-center justify-between lg:hidden">
                    <h2 class="text-base font-semibold text-ink">Filter &amp; Urutkan</h2>
                    <button type="button" class="flex h-10 w-10 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100" aria-label="Tutup filter" @click="filterOpen = false">
                        <Icon icon="heroicons:x-mark" class="h-5 w-5" />
                    </button>
                </div>
                <div class="space-y-7 lg:sticky lg:top-24">
                    <fieldset class="lg:hidden">
                        <legend class="text-sm font-semibold text-ink">Urutkan</legend>
                        <select v-model="f.urut" aria-label="Urutkan" class="mt-3 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-ink focus:border-slate-400 focus:outline-none" @change="apply">
                            <option value="populer">Terpopuler</option>
                            <option value="terbaru">Terbaru</option>
                            <option value="termurah">Harga terendah</option>
                            <option value="termahal">Harga tertinggi</option>
                        </select>
                    </fieldset>
                    <fieldset v-if="opsi.kategori.length">
                        <legend class="text-sm font-semibold text-ink">Kategori</legend>
                        <div class="mt-2 space-y-0.5 lg:mt-3 lg:space-y-2.5">
                            <label v-for="k in opsi.kategori" :key="k.value" class="flex min-h-9 cursor-pointer items-center justify-between gap-2.5 text-sm text-slate-600 hover:text-ink lg:min-h-0">
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
                        <div class="mt-2 space-y-0.5 lg:mt-3 lg:space-y-2.5">
                            <label v-for="d in opsi.durasi" :key="d.value" class="flex min-h-9 cursor-pointer items-center justify-between gap-2.5 text-sm text-slate-600 hover:text-ink lg:min-h-0">
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
                        <div class="mt-2 space-y-0.5 lg:mt-3 lg:space-y-2.5">
                            <label v-for="j in opsi.jenis" :key="j.value" class="flex min-h-9 cursor-pointer items-center justify-between gap-2.5 text-sm text-slate-600 hover:text-ink lg:min-h-0">
                                <span class="flex items-center gap-2.5">
                                    <input type="checkbox" :checked="f.jenis.includes(j.value)" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-400" @change="toggle(f.jenis, j.value)" />
                                    {{ j.label }}
                                </span>
                                <span class="text-xs text-slate-400">{{ j.jumlah }}</span>
                            </label>
                        </div>
                    </fieldset>

                    <div class="flex gap-2">
                        <button type="button" :disabled="!adaFilter()" class="flex h-11 flex-1 items-center justify-center gap-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 transition hover:border-brand-400 hover:text-ink disabled:opacity-40 lg:h-10" @click="reset">
                            <Icon icon="heroicons:arrow-path" class="h-4 w-4" />
                            Reset Filter
                        </button>
                        <button type="button" class="h-11 flex-1 rounded-xl bg-brand-600 text-sm font-semibold text-white lg:hidden" @click="filterOpen = false">
                            Lihat {{ produk.length }} produk
                        </button>
                    </div>
                </div>
            </aside>

            <!-- Grid produk -->
            <div class="min-w-0 flex-1">
                <h2 class="text-base font-bold text-ink md:text-2xl">{{ f.sale ? 'Flash Sale' : adaFilter() ? 'Hasil' : 'Semua Produk' }}<span class="hidden md:inline">{{ adaFilter() ? '' : ' Premium untuk Kamu' }}</span></h2>
                <p class="mt-1 hidden text-sm text-slate-500 md:block">Temukan akun dan layanan digital sesuai kebutuhanmu.</p>

                <p v-if="filters.q" class="mt-3 text-sm text-slate-600">Hasil pencarian "<span class="font-semibold text-ink">{{ filters.q }}</span>" &middot; {{ produk.length }} produk</p>
                <div v-if="produk.length" class="mt-3 grid grid-cols-2 gap-3 md:hidden">
                    <ProductCardCompact v-for="p in produk" :key="p.id" :product="p" />
                </div>
                <div v-if="produk.length" class="mt-5 hidden gap-4 md:grid md:grid-cols-2 xl:grid-cols-4">
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
    <section v-if="terlaris.length" id="terlaris" class="mx-auto hidden max-w-[1320px] px-4 pb-14 md:block lg:px-15">
        <div class="flex items-end justify-between">
            <div>
                <h2 class="text-2xl font-bold text-ink">Paling Laris</h2>
                <p class="mt-1 text-sm text-slate-500">Produk yang paling banyak dibeli oleh pelanggan kami.</p>
            </div>
            <a href="#katalog" class="-my-2 shrink-0 py-2 text-sm font-semibold text-brand-600 hover:text-brand-700">Lihat Semua &rarr;</a>
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
