<script setup>
import { UiButton } from '@/Components/ui'
import { Head, Link } from '@inertiajs/vue3'
import ProductCard from '@/Components/ProductCard.vue'
import ProductCardCompact from '@/Components/ProductCardCompact.vue'

defineProps({ produk: { type: Array, required: true } })
</script>

<template>
    <section class="mx-auto max-w-[1320px] px-4 py-8 lg:px-15">
        <Head title="Wishlist" />
        <h1 class="text-xl font-bold text-ink sm:text-2xl">Wishlist</h1>
        <p class="mt-1 text-sm text-slate-500">Produk yang kamu simpan untuk dibeli nanti.</p>

        <div v-if="produk.length" class="mt-5 grid grid-cols-2 gap-3 md:hidden">
            <ProductCardCompact v-for="p in produk" :key="p.id" :product="p" />
        </div>
        <div v-if="produk.length" class="mt-6 hidden gap-4 md:grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <ProductCard v-for="p in produk" :key="p.id" :product="p" />
        </div>
        <div v-else class="mt-16 flex flex-col items-center text-center">
            <Icon icon="heroicons:heart" class="h-12 w-12 text-slate-300" />
            <p class="mt-4 text-sm font-semibold text-ink">Wishlist masih kosong</p>
            <p class="mt-1 text-sm text-slate-500">Ketuk ikon hati di produk untuk menyimpannya.</p>
            <UiButton href="/" class="mt-4">Lihat Produk</UiButton>
        </div>
    </section>
</template>

<script>
import PublicLayout from '@/Layouts/PublicLayout.vue'

export default { layout: PublicLayout }
</script>
