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
        <!-- Banner: warna spesifik per brand (field warna_banner dari data) -->
        <div class="relative flex h-[140px] items-center justify-center" :style="product.warna_banner ? { background: product.warna_banner } : undefined" :class="product.warna_banner ? '' : 'bg-gradient-to-br from-brand-50 via-brand-100 to-brand-200'">
            <span class="text-5xl font-black" :style="product.warna_teks ? { color: product.warna_teks } : undefined" :class="product.warna_teks ? '' : 'text-brand-600/80'">{{ product.nama.charAt(0) }}</span>
            <button
                class="absolute top-3 right-3 rounded-full bg-white/80 p-1.5 text-slate-500 backdrop-blur transition hover:text-rose-500"
                :aria-label="`Simpan ${product.nama} ke wishlist`"
                type="button"
            >
                <Icon icon="heroicons:heart" class="h-5 w-5" />
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
                <Icon icon="heroicons:star-solid" class="h-3.5 w-3.5 text-amber-400" />
                <span class="font-semibold text-ink">{{ product.rating }}</span>
                <span>({{ product.jumlah_ulasan }})</span>
                <Icon icon="heroicons:fire-solid" class="ml-1.5 h-3.5 w-3.5 text-orange-500" />
                <span>{{ product.terjual }} terjual</span>
            </div>

            <div class="mt-auto pt-3">
                <!-- Harga -->
                <div class="flex items-baseline gap-2">
                    <span class="text-lg font-bold text-ink">{{ rupiah(product.harga) }}</span>
                    <span v-if="diskon" class="text-xs text-slate-400 line-through">{{ rupiah(product.harga_asli) }}</span>
                    <span v-if="diskon" class="rounded-md bg-rose-100 px-1.5 py-0.5 text-[11px] font-bold text-rose-600">-{{ diskon }}%</span>
                </div>

                <!-- Tombol Beli -->
                <button
                    type="button"
                    class="mt-2.5 flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-brand-600 text-sm font-semibold text-white transition hover:bg-brand-700 focus-visible:ring-2 focus-visible:ring-brand-300 focus-visible:outline-none"
                >
                    <Icon icon="heroicons:shopping-cart" class="h-4 w-4" />
                    Beli Sekarang
                </button>
            </div>
        </div>
    </article>
</template>
