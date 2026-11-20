<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { brandColors } from '@/lib/brand'
import { angka, rupiah } from '@/lib/format'

const props = defineProps({ product: { type: Object, required: true } })
const warna = computed(() => brandColors(props.product.nama))
</script>

<template>
    <Link :href="`/produk/${product.slug}`" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 transition hover:border-brand-300 hover:shadow-sm">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg text-lg font-black" :style="{ background: warna.bg, color: warna.fg }">
            <img v-if="product.logo_url" :src="product.logo_url" alt="" class="h-7 w-7 object-contain" />
            <template v-else>{{ product.nama.charAt(0) }}</template>
        </div>
        <div class="min-w-0">
            <h3 class="truncate text-sm font-semibold text-ink">{{ product.nama }}</h3>
            <p class="mt-0.5 text-sm font-bold text-ink">{{ rupiah(product.harga) }}</p>
            <p class="mt-0.5 text-xs text-slate-500">{{ angka(product.terjual) }} terjual</p>
        </div>
    </Link>
</template>
