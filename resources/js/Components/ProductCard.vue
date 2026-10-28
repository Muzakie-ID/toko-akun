<script setup>
import { computed } from 'vue'

const props = defineProps({
    product: { type: Object, required: true },
    // ponytail: mock MVP — format sederhana; pakai Intl.NumberFormat saat backend hidup
})

const diskon = computed(() => {
    if (!props.product.harga_asli || props.product.harga_asli <= props.product.harga) return null
    return Math.round((1 - props.product.harga / props.product.harga_asli) * 100)
})

const rupiah = (n) => 'Rp ' + Number(n).toLocaleString('id-ID')
</script>

<template>
    <article class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-200/60">
        <!-- Banner -->
        <div class="relative flex h-[140px] items-center justify-center bg-gradient-to-br from-brand-50 via-brand-100 to-brand-200">
            <span class="text-5xl font-black text-brand-600/80">{{ product.nama.charAt(0) }}</span>
            <button
                class="absolute top-3 right-3 rounded-full bg-white/80 p-1.5 text-slate-500 backdrop-blur transition hover:text-rose-500"
                :aria-label="`Simpan ${product.nama} ke wishlist`"
                type="button"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
            </button>
        </div>

        <div class="flex flex-1 flex-col px-4 pt-3.5 pb-4">
            <!-- Title -->
            <h3 class="text-[15px] font-semibold text-ink">{{ product.nama }}</h3>

            <!-- Badge jenis + durasi -->
            <span class="mt-2 inline-flex w-fit items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                {{ product.jenis_akun_label }} &bull; {{ product.durasi_label }}
            </span>

            <!-- Stats -->
            <div class="mt-2.5 flex items-center gap-1.5 text-xs text-slate-500">
                <svg class="h-3.5 w-3.5 text-amber-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z" clip-rule="evenodd" />
                </svg>
                <span class="font-semibold text-ink">{{ product.rating }}</span>
                <span>({{ product.jumlah_ulasan }})</span>
                <svg class="ml-1.5 h-3.5 w-3.5 text-orange-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M13.5 3.25a.75.75 0 0 0-1.5 0v4.5a.75.75 0 0 1-1.5 0V3.25a.75.75 0 0 0-1.5 0v4.5a.75.75 0 0 1-1.5 0v-3.5a.75.75 0 0 0-1.5 0v6.19c0 .43.18.84.5 1.13l.5.45v1.98a.75.75 0 0 0 .75.75h4.5a.75.75 0 0 0 .75-.75v-1.98l.5-.45a1.7 1.7 0 0 0 .5-1.13V8.5a5.25 5.25 0 0 0-2.5-4.47Z" clip-rule="evenodd" />
                </svg>
                <span>{{ product.terjual }} terjual</span>
            </div>

            <div class="mt-auto pt-3">
                <!-- Harga -->
                <div class="flex items-baseline gap-2">
                    <span class="text-lg font-bold text-ink">{{ rupiah(product.harga) }}</span>
                    <span v-if="diskon" class="text-xs text-slate-400 line-through">{{ rupiah(product.harga_asli) }}</span>
                    <span v-if="diskon" class="rounded-md bg-emerald-100 px-1.5 py-0.5 text-[11px] font-bold text-emerald-700">-{{ diskon }}%</span>
                </div>

                <!-- Tombol Beli -->
                <button
                    type="button"
                    class="mt-2.5 flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-brand-600 text-sm font-semibold text-white transition hover:bg-brand-700 focus-visible:ring-2 focus-visible:ring-brand-300 focus-visible:outline-none"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>
                    Beli Sekarang
                </button>
            </div>
        </div>
    </article>
</template>
