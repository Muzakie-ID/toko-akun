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
                <svg class="h-3 w-3 text-amber-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z" clip-rule="evenodd" />
                </svg>
                <span class="font-medium text-ink">{{ product.rating }}</span>
                <span>{{ product.terjual }} terjual</span>
            </div>
        </div>
    </article>
</template>
