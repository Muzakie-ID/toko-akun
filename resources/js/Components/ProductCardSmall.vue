<script setup>
defineProps({
    product: { type: Object, required: true },
})

const rupiah = (n) => 'Rp ' + Number(n).toLocaleString('id-ID')
</script>

<template>
    <article class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 transition hover:border-brand-300 hover:shadow-sm">
        <!-- Thumb -->
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-lg font-black text-brand-600">
            {{ product.nama.charAt(0) }}
        </div>

        <div class="min-w-0">
            <h3 class="truncate text-sm font-semibold text-ink">{{ product.nama }}</h3>
            <p class="mt-0.5 text-sm font-bold text-brand-600">{{ rupiah(product.harga) }}</p>
            <div class="mt-1 flex items-center gap-1 text-xs text-slate-500">
                <Icon icon="heroicons:star-solid" class="h-3 w-3 text-amber-400" />
                <span class="font-medium text-ink">{{ product.rating }}</span>
                <span>{{ product.terjual }} terjual</span>
            </div>
        </div>
    </article>
</template>
