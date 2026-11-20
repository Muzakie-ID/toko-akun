<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { brandColors } from '@/lib/brand'
import { angka, rupiah } from '@/lib/format'

const props = defineProps({
    produk: { type: Object, required: true },
    varian: { type: Array, required: true },
    hargaReseller: { type: Boolean, default: false },
})

const user = computed(() => usePage().props.auth?.user)
const warna = computed(() => brandColors(props.produk.nama))

const jenisList = computed(() => [...new Map(props.varian.map((v) => [v.jenis_akun, v.jenis_akun_label])).entries()])
const jenis = ref(jenisList.value[0]?.[0] ?? null)
const durasiList = computed(() => props.varian.filter((v) => v.jenis_akun === jenis.value))
const pilihan = ref(durasiList.value.find((v) => v.stok > 0)?.id ?? durasiList.value[0]?.id ?? null)
const terpilih = computed(() => props.varian.find((v) => v.id === pilihan.value))
const qty = ref(1)
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
                <li aria-hidden="true">&rsaquo;</li>
                <li><Link :href="`/?kategori[]=${produk.kategori_slug}`" class="hover:text-ink">{{ produk.kategori }}</Link></li>
                <li aria-hidden="true">&rsaquo;</li>
                <li aria-current="page" class="font-medium text-ink">{{ produk.nama }}</li>
            </ol>
        </nav>

        <section class="mx-auto max-w-[1320px] px-4 pb-14 lg:px-15">
            <div class="grid gap-8 lg:grid-cols-2">
                <div class="flex h-72 items-center justify-center rounded-3xl sm:h-96" :style="{ background: warna.bg }">
                    <span class="text-8xl font-black" :style="{ color: warna.fg }">{{ produk.nama.charAt(0) }}</span>
                </div>

                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-ink">{{ produk.nama }}</h1>
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-slate-500">
                        <span v-if="produk.rating > 0" class="inline-flex items-center gap-1">
                            <Icon icon="heroicons:star-solid" class="h-4 w-4 text-amber-400" />
                            <span class="font-semibold text-ink">{{ produk.rating.toLocaleString('id-ID') }}</span>
                        </span>
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
                                <span v-if="v.stok === 0" class="block text-[11px] font-semibold text-rose-600">Habis</span>
                            </button>
                        </div>
                        <p v-if="hargaReseller" class="mt-2 text-xs font-medium text-amber-700">Harga reseller sudah diterapkan.</p>

                        <!-- Ringkasan & aksi -->
                        <div v-if="terpilih" class="mt-6 rounded-2xl border border-slate-200 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm text-slate-500">{{ terpilih.jenis_akun_label }} · {{ terpilih.durasi_label }}</p>
                                    <p class="text-2xl font-bold text-ink">{{ rupiah(terpilih.harga * qty) }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" class="h-9 w-9 rounded-lg border border-slate-200 text-slate-600 disabled:opacity-40" :disabled="qty <= 1" aria-label="Kurangi jumlah" @click="qty--">&minus;</button>
                                    <span class="w-8 text-center text-sm font-semibold text-ink" aria-live="polite">{{ qty }}</span>
                                    <button type="button" class="h-9 w-9 rounded-lg border border-slate-200 text-slate-600 disabled:opacity-40" :disabled="qty >= terpilih.stok" aria-label="Tambah jumlah" @click="qty++">+</button>
                                </div>
                            </div>
                            <p class="mt-2 text-xs" :class="terpilih.stok ? 'text-emerald-700' : 'text-rose-600'">
                                {{ terpilih.stok ? `Stok tersedia: ${terpilih.stok}` : 'Stok sedang habis' }}
                            </p>
                            <div class="mt-4 flex flex-col gap-2.5 sm:flex-row">
                                <button type="button" :disabled="!terpilih.stok || processing" class="flex h-12 flex-1 items-center justify-center gap-2 rounded-xl bg-brand-600 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-40" @click="beli(true)">
                                    <Icon icon="heroicons:bolt" class="h-4 w-4" /> Beli Sekarang
                                </button>
                                <button type="button" :disabled="!terpilih.stok || processing" class="flex h-12 flex-1 items-center justify-center gap-2 rounded-xl border border-brand-600 text-sm font-semibold text-ink transition hover:bg-slate-50 disabled:opacity-40" @click="beli(false)">
                                    <Icon icon="heroicons:shopping-bag" class="h-4 w-4" /> Masukkan Keranjang
                                </button>
                            </div>
                            <p v-if="!user" class="mt-2 text-center text-xs text-slate-500">Kamu akan diminta masuk dulu sebelum membeli.</p>
                        </div>
                    </template>
                    <p v-else class="mt-6 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">Produk ini belum bisa dibeli.</p>
                </div>
            </div>
        </section>
    </div>
</template>

<script>
import PublicLayout from '@/Layouts/PublicLayout.vue'

export default { layout: PublicLayout }
</script>
