<script setup>
import { Head, Link } from '@inertiajs/vue3'
import ProductCard from '@/Components/ProductCard.vue'

defineProps({ produk: { type: Array, required: true } })
</script>

<template>
    <section class="mx-auto max-w-[1320px] px-4 py-8 lg:px-15">
        <Head title="Wishlist" />
        <h1 class="text-xl font-bold text-ink sm:text-2xl">Wishlist</h1>
        <p class="mt-1 text-sm text-slate-500">Produk yang kamu simpan untuk dibeli nanti.</p>

        <div v-if="produk.length" class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <ProductCard v-for="p in produk" :key="p.id" :product="p" />
        </div>
        <div v-else class="mt-16 flex flex-col items-center text-center">
            <Icon icon="heroicons:heart" class="h-12 w-12 text-slate-300" />
            <p class="mt-4 text-sm font-semibold text-ink">Wishlist masih kosong</p>
            <p class="mt-1 text-sm text-slate-500">Ketuk ikon hati di produk untuk menyimpannya.</p>
            <Link href="/" class="mt-4 inline-flex h-10 items-center rounded-xl bg-brand-600 px-5 text-sm font-semibold text-white hover:bg-brand-700">Lihat Produk</Link>
        </div>
    </section>
</template>

<script>
import PublicLayout from '@/Layouts/PublicLayout.vue'

export default { layout: PublicLayout }
</script>
