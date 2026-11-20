<script setup>
// Kartu produk ringkas untuk HP (grid 2 kolom / deretan geser), mengikuti referensi kartu aplikasi.
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { brandColors } from '@/lib/brand'
import { angka, rupiah } from '@/lib/format'

const props = defineProps({ product: { type: Object, required: true } })

const warna = computed(() => brandColors(props.product.nama))
const user = computed(() => usePage().props.auth?.user)
const diskon = computed(() =>
    props.product.harga_asli && props.product.harga_asli > props.product.harga
        ? Math.round((1 - props.product.harga / props.product.harga_asli) * 100)
        : null,
)

const toggleWishlist = () => {
    if (!user.value) {
        router.get('/login', { kembali: `/produk/${props.product.slug}` })
        return
    }
    router.post(`/wishlist/${props.product.slug}`, {}, { preserveScroll: true, preserveState: true })
}
</script>

<template>
    <article class="relative flex h-full flex-col rounded-2xl bg-white p-2 ring-1 ring-slate-200/80">
        <div class="relative flex aspect-[4/3] items-center justify-center overflow-hidden rounded-xl" :style="{ background: warna.bg }">
            <img v-if="product.logo_url" :src="product.logo_url" :alt="`Logo ${product.nama}`" class="h-12 w-12 rounded-xl object-contain" />
            <span v-else class="text-4xl font-black" :style="{ color: warna.fg }">{{ product.nama.charAt(0) }}</span>
            <span v-if="diskon" class="absolute bottom-1.5 left-1.5 rounded-md bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold text-white">-{{ diskon }}%</span>
            <span v-if="!product.stok_ada" class="absolute bottom-1.5 left-1.5 rounded-md bg-white/90 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600">Habis</span>
        </div>
        <button
            type="button"
            class="absolute top-1 right-1 z-10 flex h-10 w-10 items-center justify-center"
            :aria-label="product.di_wishlist ? `Hapus ${product.nama} dari wishlist` : `Simpan ${product.nama} ke wishlist`"
            :aria-pressed="product.di_wishlist"
            @click="toggleWishlist"
        >
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-white/90 shadow-sm" :class="product.di_wishlist ? 'text-rose-500' : 'text-slate-500'">
                <Icon :icon="product.di_wishlist ? 'heroicons:heart-solid' : 'heroicons:heart'" class="h-4 w-4" />
            </span>
        </button>

        <div class="flex flex-1 flex-col px-1 pt-2">
            <h3 class="truncate text-sm font-semibold text-ink">
                <Link :href="`/produk/${product.slug}`" class="after:absolute after:inset-0">{{ product.nama }}</Link>
            </h3>
            <p class="truncate text-[11px] text-slate-500">{{ product.jenis_akun_label }} · {{ product.durasi_label }}</p>
            <p class="mt-1 flex items-center gap-1 text-[11px] text-slate-500">
                <template v-if="product.jumlah_ulasan > 0">
                    <Icon icon="heroicons:star-solid" class="h-3 w-3 text-amber-400" />
                    <span class="font-semibold text-ink">{{ product.rating.toLocaleString('id-ID') }}</span>
                    <span aria-hidden="true">·</span>
                </template>
                {{ angka(product.terjual) }} terjual
            </p>
            <div class="mt-auto flex items-end justify-between gap-1 pt-1.5">
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-ink">{{ rupiah(product.harga) }}</p>
                    <p v-if="diskon" class="truncate text-[10px] text-slate-400 line-through">{{ rupiah(product.harga_asli) }}</p>
                </div>
                <span class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-ink text-white" aria-hidden="true">
                    <Icon icon="heroicons:plus" class="h-4 w-4" />
                </span>
            </div>
        </div>
    </article>
</template>
