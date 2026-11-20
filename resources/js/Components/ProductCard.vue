<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { brandColors } from '@/lib/brand'
import { angka, rupiah } from '@/lib/format'

const props = defineProps({ product: { type: Object, required: true } })

const warna = computed(() => brandColors(props.product.nama))
const user = computed(() => usePage().props.auth?.user)

const toggleWishlist = () => {
    if (!user.value) {
        router.get('/login', { kembali: `/produk/${props.product.slug}` })
        return
    }
    router.post(`/wishlist/${props.product.slug}`, {}, { preserveScroll: true, preserveState: true })
}
const diskon = computed(() => {
    if (!props.product.harga_asli || props.product.harga_asli <= props.product.harga) return null
    return Math.round((1 - props.product.harga / props.product.harga_asli) * 100)
})
</script>

<template>
    <article class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-200/60">
        <div class="relative flex h-[140px] items-center justify-center" :style="{ background: warna.bg }">
            <img v-if="product.logo_url" :src="product.logo_url" :alt="`Logo ${product.nama}`" class="h-16 w-16 rounded-2xl object-contain" />
            <span v-else class="text-5xl font-black" :style="{ color: warna.fg }">{{ product.nama.charAt(0) }}</span>
            <button
                type="button"
                class="absolute top-2 right-2 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/85 backdrop-blur transition hover:scale-110"
                :class="product.di_wishlist ? 'text-rose-500' : 'text-slate-500 hover:text-rose-500'"
                :aria-label="product.di_wishlist ? `Hapus ${product.nama} dari wishlist` : `Simpan ${product.nama} ke wishlist`"
                :aria-pressed="product.di_wishlist"
                @click="toggleWishlist"
            >
                <Icon :icon="product.di_wishlist ? 'heroicons:heart-solid' : 'heroicons:heart'" class="h-5 w-5" />
            </button>
            <span v-if="!product.stok_ada" class="absolute top-3 left-3 rounded-full bg-white/90 px-2 py-0.5 text-[11px] font-semibold text-slate-600">Stok habis</span>
        </div>

        <div class="flex flex-1 flex-col px-4 pt-3.5 pb-4">
            <h3 class="text-[15px] font-semibold text-ink">
                <Link :href="`/produk/${product.slug}`" class="after:absolute after:inset-0">{{ product.nama }}</Link>
            </h3>
            <span class="mt-2 inline-flex w-fit items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                {{ product.jenis_akun_label }} &bull; {{ product.durasi_label }}
                <span v-if="product.jumlah_varian > 1" class="ml-1 text-slate-400">+{{ product.jumlah_varian - 1 }} pilihan</span>
            </span>
            <div class="mt-2.5 flex items-center gap-1.5 text-xs text-slate-500">
                <template v-if="product.jumlah_ulasan > 0">
                    <Icon icon="heroicons:star-solid" class="h-3.5 w-3.5 text-amber-400" />
                    <span class="font-semibold text-ink">{{ product.rating.toLocaleString('id-ID') }}</span>
                    <span>({{ angka(product.jumlah_ulasan) }})</span>
                </template>
                <Icon icon="heroicons:fire-solid" class="h-3.5 w-3.5 text-orange-500" :class="{ 'ml-1.5': product.jumlah_ulasan > 0 }" />
                <span>{{ angka(product.terjual) }} terjual</span>
            </div>

            <div class="mt-auto pt-3">
                <p class="text-xs text-slate-400">Mulai dari</p>
                <div class="flex flex-wrap items-baseline gap-x-2">
                    <span class="text-lg font-bold text-ink">{{ rupiah(product.harga) }}</span>
                    <span v-if="diskon" class="text-xs text-slate-400 line-through">{{ rupiah(product.harga_asli) }}</span>
                    <span v-if="diskon" class="rounded-md bg-rose-100 px-1.5 py-0.5 text-[11px] font-bold text-rose-600">-{{ diskon }}%</span>
                </div>
                <span class="relative mt-2.5 flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-brand-600 text-sm font-semibold text-white transition group-hover:bg-brand-700" aria-hidden="true">
                    <Icon icon="heroicons:shopping-cart" class="h-4 w-4" />
                    {{ product.stok_ada ? 'Pilih Paket' : 'Lihat Detail' }}
                </span>
            </div>
        </div>
    </article>
</template>
