<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Countdown from '@/Components/Countdown.vue'
import StarRating from '@/Components/StarRating.vue'
import { UiButton, UiQuantity } from '@/Components/ui'
import { brandColors } from '@/lib/brand'
import { angka, rupiah } from '@/lib/format'

const props = defineProps({
    produk: { type: Object, required: true },
    varian: { type: Array, required: true },
    hargaReseller: { type: Boolean, default: false },
    ulasan: { type: Object, required: true },
})

const semuaUlasan = ref(false)
const ulasanTampil = computed(() => (semuaUlasan.value ? props.ulasan.daftar : props.ulasan.daftar.slice(0, 6)))
const maxSebaran = computed(() => Math.max(...props.ulasan.sebaran.map((s) => s.jumlah), 1))
const toggleWishlist = () => {
    if (!user.value) {
        router.get('/login', { kembali: `/produk/${props.produk.slug}` })
        return
    }
    router.post(`/wishlist/${props.produk.slug}`, {}, { preserveScroll: true })
}

const user = computed(() => usePage().props.auth?.user)
const warna = computed(() => brandColors(props.produk.nama))

const jenisList = computed(() => [...new Map(props.varian.map((v) => [v.jenis_akun, v.jenis_akun_label])).entries()])
const jenis = ref(jenisList.value[0]?.[0] ?? null)
const durasiList = computed(() => props.varian.filter((v) => v.jenis_akun === jenis.value))
const pilihan = ref(durasiList.value.find((v) => v.stok > 0)?.id ?? durasiList.value[0]?.id ?? null)
const terpilih = computed(() => props.varian.find((v) => v.id === pilihan.value))
const qty = ref(1)

// Sama dengan checkout: harga sale hanya untuk sebanyak sisa kuota, sisanya harga normal
const total = computed(() => {
    const v = terpilih.value
    if (!v) return 0
    if (!v.flash_sale || v.flash_sale.sisa === null) return v.harga * qty.value
    const diSale = Math.min(qty.value, v.flash_sale.sisa)
    return diSale * v.harga + (qty.value - diSale) * v.harga_asli
})
const processing = ref(false)

const pilihJenis = (value) => {
    jenis.value = value
    const daftar = durasiList.value
    pilihan.value = (daftar.find((v) => v.stok > 0) ?? daftar[0])?.id ?? null
    qty.value = 1
}

const deskripsiJenis = {
    private: 'Akun khusus untukmu: email & password tidak dibagi dengan pembeli lain.',
    sharing: 'Satu akun dipakai bersama; kamu dapat satu slot (mis. profil sendiri). Lebih hemat.',
}

const beli = (beliSekarang) => {
    if (!user.value) {
        router.get('/login', { kembali: `/produk/${props.produk.slug}` })
        return
    }
    router.post('/keranjang', { product_variant_id: pilihan.value, qty: qty.value, beli_sekarang: beliSekarang }, {
        preserveScroll: true,
        onStart: () => (processing.value = true),
        onFinish: () => (processing.value = false),
    })
}
</script>

<template>
    <div>
        <Head :title="produk.nama" />
        <nav aria-label="Breadcrumb" class="mx-auto max-w-[1320px] px-4 lg:px-15">
            <ol class="flex h-12 items-center gap-2 text-sm text-slate-500">
                <li><Link href="/" class="hover:text-ink">Beranda</Link></li>
                <li aria-hidden="true"><Icon icon="heroicons:chevron-right" class="h-3.5 w-3.5 text-slate-400" /></li>
                <li><Link :href="`/?kategori[]=${produk.kategori_slug}`" class="hover:text-ink">{{ produk.kategori }}</Link></li>
                <li aria-hidden="true"><Icon icon="heroicons:chevron-right" class="h-3.5 w-3.5 text-slate-400" /></li>
                <li aria-current="page" class="font-medium text-ink">{{ produk.nama }}</li>
            </ol>
        </nav>

        <section class="mx-auto max-w-[1320px] px-4 pb-14 lg:px-15">
            <div class="grid gap-8 lg:grid-cols-2">
                <div class="relative flex h-56 items-center justify-center rounded-3xl sm:h-96" :style="{ background: warna.bg }">
                    <img v-if="produk.logo_url" :src="produk.logo_url" :alt="`Logo ${produk.nama}`" class="h-32 w-32 rounded-3xl object-contain" />
                    <span v-else class="text-8xl font-black" :style="{ color: warna.fg }">{{ produk.nama.charAt(0) }}</span>
                    <button
                        type="button"
                        class="absolute top-3 right-3 flex h-10 items-center gap-1.5 rounded-full bg-white/90 px-3.5 text-sm font-medium backdrop-blur sm:top-4 sm:right-4"
                        :class="produk.di_wishlist ? 'text-rose-600' : 'text-slate-700 hover:text-rose-600'"
                        :aria-pressed="produk.di_wishlist"
                        @click="toggleWishlist"
                    >
                        <Icon :icon="produk.di_wishlist ? 'heroicons:heart-solid' : 'heroicons:heart'" class="h-4 w-4" />
                        {{ produk.di_wishlist ? 'Tersimpan' : 'Simpan' }}
                    </button>
                </div>

                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-ink sm:text-3xl">{{ produk.nama }}</h1>
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-slate-500">
                        <a v-if="produk.jumlah_ulasan > 0" href="#ulasan" class="inline-flex items-center gap-1 hover:text-ink">
                            <Icon icon="heroicons:star-solid" class="h-4 w-4 text-amber-400" />
                            <span class="font-semibold text-ink">{{ produk.rating.toLocaleString('id-ID') }}</span>
                            ({{ angka(produk.jumlah_ulasan) }} ulasan)
                        </a>
                        <span>{{ angka(produk.terjual) }} terjual</span>
                        <span v-if="produk.garansi_hari" class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                            <Icon icon="heroicons:shield-check" class="h-3.5 w-3.5" /> Garansi {{ produk.garansi_hari }} hari
                        </span>
                    </div>
                    <p v-if="produk.deskripsi" class="mt-4 text-sm leading-7 whitespace-pre-line text-slate-600">{{ produk.deskripsi }}</p>

                    <template v-if="varian.length">
                        <!-- Tipe akun -->
                        <h2 class="mt-6 text-sm font-semibold text-ink">Tipe Akun</h2>
                        <div class="mt-2.5 grid gap-2.5 sm:grid-cols-2" role="radiogroup" aria-label="Tipe akun">
                            <button
                                v-for="[value, label] in jenisList"
                                :key="value"
                                type="button"
                                role="radio"
                                :aria-checked="jenis === value"
                                class="rounded-xl border px-4 py-3 text-left transition"
                                :class="jenis === value ? 'border-ink bg-slate-50 ring-1 ring-ink' : 'border-slate-200 hover:border-slate-400'"
                                @click="pilihJenis(value)"
                            >
                                <span class="flex items-center gap-2 text-sm font-semibold text-ink">
                                    <Icon :icon="value === 'sharing' ? 'heroicons:user-group' : 'heroicons:user'" class="h-4 w-4" />{{ label }}
                                </span>
                                <span class="mt-1 block text-xs leading-5 text-slate-500">{{ deskripsiJenis[value] }}</span>
                            </button>
                        </div>

                        <!-- Durasi -->
                        <h2 class="mt-6 text-sm font-semibold text-ink">Durasi</h2>
                        <div class="mt-2.5 grid grid-cols-2 gap-2.5 sm:grid-cols-4" role="radiogroup" aria-label="Durasi">
                            <button
                                v-for="v in durasiList"
                                :key="v.id"
                                type="button"
                                role="radio"
                                :aria-checked="pilihan === v.id"
                                :disabled="v.stok === 0"
                                class="rounded-xl border px-3 py-2.5 text-left transition disabled:cursor-not-allowed disabled:opacity-50"
                                :class="pilihan === v.id ? 'border-ink bg-slate-50 ring-1 ring-ink' : 'border-slate-200 hover:border-slate-400'"
                                @click="pilihan = v.id; qty = 1"
                            >
                                <span class="block text-xs font-medium text-slate-600">{{ v.durasi_label }}</span>
                                <span class="mt-0.5 block text-sm font-bold text-ink">{{ rupiah(v.harga) }}</span>
                                <span v-if="v.harga_asli && v.harga_asli > v.harga" class="block text-[11px] text-slate-400 line-through">{{ rupiah(v.harga_asli) }}</span>
                                <span v-if="v.flash_sale" class="mt-0.5 flex items-center gap-0.5 text-[11px] font-semibold text-orange-600"><Icon icon="heroicons:bolt-solid" class="h-3 w-3" />Sale</span>
                                <span v-if="v.stok === 0" class="block text-[11px] font-semibold text-rose-600">Habis</span>
                            </button>
                        </div>
                        <p v-if="hargaReseller" class="mt-2 text-xs font-medium text-amber-700">Harga reseller sudah diterapkan.</p>

                        <!-- Ringkasan & aksi -->
                        <div v-if="terpilih?.flash_sale" class="mt-6 flex flex-wrap items-center gap-x-3 gap-y-2 rounded-2xl bg-gradient-to-r from-rose-500 to-orange-500 px-4 py-3 text-white">
                            <p class="flex items-center gap-1 text-sm font-bold"><Icon icon="heroicons:bolt-solid" class="h-4 w-4" /> {{ terpilih.flash_sale.nama }}</p>
                            <span class="text-xs text-white/85">berakhir dalam</span>
                            <Countdown :to="terpilih.flash_sale.selesai_at" @selesai="router.reload()" />
                            <p v-if="terpilih.flash_sale.sisa !== null" class="w-full text-xs text-white/90">Sisa kuota harga sale: <b>{{ terpilih.flash_sale.sisa }}</b> akun. Selebihnya harga normal.</p>
                        </div>
                        <div v-if="terpilih" class="rounded-2xl border border-slate-200 p-4" :class="terpilih.flash_sale ? 'mt-3' : 'mt-6'">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm text-slate-500">{{ terpilih.jenis_akun_label }} · {{ terpilih.durasi_label }}</p>
                                    <p class="flex flex-wrap items-baseline gap-x-2 text-2xl font-bold text-ink">
                                        {{ rupiah(total) }}
                                        <span v-if="terpilih.harga_asli && terpilih.harga_asli * qty > total" class="text-sm font-normal text-slate-400 line-through">{{ rupiah(terpilih.harga_asli * qty) }}</span>
                                    </p>
                                </div>
                                <UiQuantity v-model="qty" :max="Math.max(terpilih.stok, 1)" />
                            </div>
                            <p class="mt-2 text-xs" :class="terpilih.stok ? 'text-emerald-700' : 'text-rose-600'">
                                {{ terpilih.stok ? `Stok tersedia: ${terpilih.stok}` : 'Stok sedang habis' }}
                            </p>
                            <div class="mt-4 flex flex-col gap-2.5 sm:flex-row">
                                <UiButton size="lg" icon="heroicons:bolt" class="w-full sm:flex-1" :disabled="!terpilih.stok" :loading="processing" @click="beli(true)">Beli Sekarang</UiButton>
                                <UiButton size="lg" variant="secondary" icon="heroicons:shopping-bag" class="w-full sm:flex-1" :disabled="!terpilih.stok || processing" @click="beli(false)">Masukkan Keranjang</UiButton>
                            </div>
                            <p v-if="!user" class="mt-2 text-center text-xs text-slate-500">Kamu akan diminta masuk dulu sebelum membeli.</p>
                        </div>
                    </template>
                    <p v-else class="mt-6 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">Produk ini belum bisa dibeli.</p>
                </div>
            </div>
            <!-- Ulasan -->
            <div id="ulasan" class="mt-10 scroll-mt-24 sm:mt-14">
                <h2 class="text-xl font-bold text-ink">Ulasan Pembeli</h2>
                <div v-if="produk.jumlah_ulasan" class="mt-4 grid gap-8 lg:grid-cols-[260px_1fr]">
                    <div>
                        <p class="text-5xl font-bold tracking-tight text-ink">{{ produk.rating.toLocaleString('id-ID') }}<span class="text-lg font-medium text-slate-400">/5</span></p>
                        <StarRating :model-value="Math.round(produk.rating)" size="h-5 w-5" class="mt-2" />
                        <p class="mt-1 text-sm text-slate-500">{{ angka(produk.jumlah_ulasan) }} ulasan</p>
                        <ul class="mt-4 space-y-1.5">
                            <li v-for="s in ulasan.sebaran" :key="s.bintang" class="flex items-center gap-2 text-xs text-slate-500">
                                <span class="w-3 text-right">{{ s.bintang }}</span>
                                <Icon icon="heroicons:star-solid" class="h-3 w-3 text-amber-400" />
                                <span class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100"><span class="block h-full rounded-full bg-amber-400" :style="{ width: `${(s.jumlah / maxSebaran) * 100}%` }" /></span>
                                <span class="w-6 text-right">{{ s.jumlah }}</span>
                            </li>
                        </ul>
                    </div>
                    <ul class="grid gap-3 md:grid-cols-2">
                        <li v-for="u in ulasanTampil" :key="u.id" class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-semibold text-ink">{{ u.nama }}</span>
                                <span class="text-xs text-slate-400">{{ u.tanggal }}</span>
                            </div>
                            <StarRating :model-value="u.rating" size="h-3.5 w-3.5" class="mt-1" />
                            <p v-if="u.komentar" class="mt-2 text-sm leading-6 text-slate-600">{{ u.komentar }}</p>
                        </li>
                        <li v-if="ulasan.daftar.length > ulasanTampil.length" class="md:col-span-2">
                            <UiButton variant="secondary" block icon-right="heroicons:chevron-down" @click="semuaUlasan = true">
                                Tampilkan {{ ulasan.daftar.length - ulasanTampil.length }} ulasan lainnya
                            </UiButton>
                        </li>
                    </ul>
                </div>
                <p v-else class="mt-3 rounded-2xl border border-dashed border-slate-200 px-5 py-8 text-center text-sm text-slate-500">
                    Belum ada ulasan. Ulasan hanya bisa ditulis oleh pembeli yang sudah menerima akun.
                </p>
            </div>
        </section>
    </div>
</template>

<script>
import PublicLayout from '@/Layouts/PublicLayout.vue'

export default { layout: PublicLayout }
</script>
