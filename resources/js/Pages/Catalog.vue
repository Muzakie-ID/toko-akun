<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed, reactive, ref } from 'vue'
import Countdown from '@/Components/Countdown.vue'
import { UiButton, UiCheckbox, UiChip, UiSearch, UiSelect } from '@/Components/ui'
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
const opsiUrut = [
    { value: 'populer', label: 'Terpopuler' },
    { value: 'terbaru', label: 'Terbaru' },
    { value: 'termurah', label: 'Harga terendah' },
    { value: 'termahal', label: 'Harga tertinggi' },
]

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
        <div class="flex gap-2 px-4 pt-3">
            <UiSearch v-model="f.q" size="lg" label="Cari produk" placeholder="Cari Netflix, ChatGPT, Canva…" @search="cariHp" />
            <div class="relative">
                <UiButton size="lg" square icon="heroicons:adjustments-horizontal" class="rounded-2xl" label="Filter & urutkan" @click="filterOpen = true" />
                <span v-if="jumlahFilter" class="pointer-events-none absolute -top-1 -right-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-gold px-1 text-[10px] font-bold text-white">{{ jumlahFilter }}</span>
            </div>
        </div>

        <!-- Chip kategori -->
        <div class="mt-3 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none]" role="tablist" aria-label="Kategori">
            <UiChip size="sm" role="tab" :active="!f.kategori.length" @click="pilihKategori(null)">Semua</UiChip>
            <UiChip v-if="f.sale" size="sm" active icon="heroicons:bolt-solid" aria-label="Hapus filter flash sale" @click="f.sale = false; apply()">Flash Sale <Icon icon="heroicons:x-mark" class="h-4 w-4" /></UiChip>
            <UiChip v-for="k in opsi.kategori" :key="k.value" size="sm" role="tab" :active="f.kategori.length === 1 && f.kategori[0] === k.value" @click="pilihKategori(k.value)">{{ k.label }}</UiChip>
        </div>

        <template v-if="!adaFilter()">
            <!-- Banner utama -->
            <div class="mx-4 mt-3 overflow-hidden rounded-3xl bg-cream p-5">
                <p v-if="promo.diskonMaks" class="inline-flex rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-rose-600">Hemat s.d. {{ promo.diskonMaks }}%</p>
                <h1 class="mt-2 text-2xl leading-tight font-extrabold tracking-tight text-ink">Langganan Premium,<br /><span class="text-gold">Lebih Hemat</span></h1>
                <p class="mt-1.5 text-sm text-slate-600">Akun dikirim otomatis begitu pembayaran masuk.</p>
                <UiButton href="#katalog" native class="mt-4" icon-right="heroicons:arrow-right">Belanja Sekarang</UiButton>
            </div>

            <!-- Flash sale (hanya saat ada sale berlangsung) -->
            <section v-if="flashSale" class="mx-4 mt-3 overflow-hidden rounded-3xl bg-gradient-to-br from-rose-500 to-orange-500 pt-4 pb-3 text-white">
                <div class="flex items-start justify-between gap-3 px-4">
                    <div class="min-w-0">
                        <p class="flex items-center gap-1 text-base font-bold"><Icon icon="heroicons:bolt-solid" class="h-5 w-5" /> {{ flashSale.nama }}</p>
                        <p class="text-xs text-white/80">Berakhir dalam</p>
                    </div>
                    <UiButton size="sm" variant="white" class="-my-1 bg-white/15 text-white hover:bg-white/25" @click="lihatSale">Lihat semua</UiButton>
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
                    <UiButton href="#katalog" native variant="link" class="text-sm text-slate-500">Lihat semua</UiButton>
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
                <UiButton href="#katalog" native size="lg" class="mt-6" icon-right="heroicons:arrow-right">Lihat Semua Produk</UiButton>
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
                <UiButton variant="white" class="ml-auto" icon-right="heroicons:arrow-right" @click="lihatSale">Lihat semua sale</UiButton>
            </div>
            <ul class="mt-5 grid grid-cols-2 gap-3 text-ink lg:grid-cols-4 xl:grid-cols-6">
                <li v-for="p in flashSale.produk.slice(0, 6)" :key="p.id"><ProductCardCompact :product="p" /></li>
            </ul>
        </div>
    </section>

    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" class="mx-auto hidden max-w-[1320px] px-4 md:block lg:px-15">
        <ol class="flex h-12 items-center gap-2 text-sm text-slate-500">
            <li><Link href="/" class="hover:text-ink">Beranda</Link></li>
            <li aria-hidden="true"><Icon icon="heroicons:chevron-right" class="h-3.5 w-3.5 text-slate-400" /></li>
            <li aria-current="page" class="font-medium text-ink">Semua Produk</li>
        </ol>
    </nav>

    <!-- Filter cepat: tipe akun + urutan -->
    <div class="hidden border-y border-slate-100 bg-white md:block">
        <div class="mx-auto flex max-w-[1320px] items-center gap-2 overflow-x-auto px-4 py-3 lg:px-15 lg:py-3.5">
            <UiChip active icon="heroicons:adjustments-horizontal" class="lg:hidden" :aria-expanded="filterOpen" @click="filterOpen = true">
                Filter<span v-if="jumlahFilter"> ({{ jumlahFilter }})</span>
            </UiChip>
            <UiChip v-for="j in opsi.jenis" :key="j.value" :active="f.jenis.includes(j.value)" :icon="j.value === 'sharing' ? 'heroicons:user-group' : 'heroicons:user'" @click="toggle(f.jenis, j.value)">{{ j.label }}</UiChip>
            <UiChip v-for="d in opsi.durasi" :key="d.value" :active="f.durasi.includes(d.value)" icon="heroicons:clock" @click="toggle(f.durasi, d.value)">{{ d.label }}</UiChip>
            <div class="ml-auto hidden shrink-0 items-center gap-2 lg:flex">
                <label for="urut" class="text-sm text-slate-500">Urutkan</label>
                <UiSelect id="urut" v-model="f.urut" size="sm" class="w-44" :options="opsiUrut" @change="apply" />
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
                    <UiButton variant="ghost" square icon="heroicons:x-mark" class="rounded-full" label="Tutup filter" @click="filterOpen = false" />
                </div>
                <div class="space-y-7 lg:sticky lg:top-24">
                    <fieldset class="lg:hidden">
                        <legend class="text-sm font-semibold text-ink">Urutkan</legend>
                        <UiSelect v-model="f.urut" class="mt-3" aria-label="Urutkan" :options="opsiUrut" @change="apply" />
                    </fieldset>
                    <fieldset v-if="opsi.kategori.length">
                        <legend class="text-sm font-semibold text-ink">Kategori</legend>
                        <div class="mt-2 space-y-0.5">
                            <UiCheckbox v-for="k in opsi.kategori" :key="k.value" v-model="f.kategori" :value="k.value" @change="apply">{{ k.label }}<template #end><span class="text-xs text-slate-400">{{ k.jumlah }}</span></template></UiCheckbox>
                        </div>
                    </fieldset>

                    <fieldset v-if="opsi.harga_tertinggi">
                        <legend class="text-sm font-semibold text-ink">Harga maksimal</legend>
                        <p class="mt-3 text-sm font-medium text-ink">{{ rupiah(f.harga_max) }}</p>
                        <input v-model.number="f.harga_max" type="range" min="0" :max="opsi.harga_tertinggi" :step="1000" class="mt-2 w-full accent-[var(--ui-primary)]" aria-label="Harga maksimal" @change="apply" />
                    </fieldset>

                    <fieldset>
                        <legend class="text-sm font-semibold text-ink">Durasi</legend>
                        <div class="mt-2 space-y-0.5">
                            <UiCheckbox v-for="d in opsi.durasi" :key="d.value" v-model="f.durasi" :value="d.value" @change="apply">{{ d.label }}<template #end><span class="text-xs text-slate-400">{{ d.jumlah }}</span></template></UiCheckbox>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-sm font-semibold text-ink">Jenis Akun</legend>
                        <div class="mt-2 space-y-0.5">
                            <UiCheckbox v-for="j in opsi.jenis" :key="j.value" v-model="f.jenis" :value="j.value" @change="apply">{{ j.label }}<template #end><span class="text-xs text-slate-400">{{ j.jumlah }}</span></template></UiCheckbox>
                        </div>
                    </fieldset>

                    <div class="flex gap-2">
                        <UiButton variant="secondary" icon="heroicons:arrow-path" class="flex-1" :disabled="!adaFilter()" @click="reset">Reset Filter</UiButton>
                        <UiButton class="flex-1 lg:hidden" @click="filterOpen = false">Lihat {{ produk.length }} produk</UiButton>
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
                    <UiButton class="mt-4" icon="heroicons:arrow-path" @click="reset">Reset Filter</UiButton>
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
            <UiButton href="#katalog" native variant="link" icon-right="heroicons:arrow-right">Lihat Semua</UiButton>
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
