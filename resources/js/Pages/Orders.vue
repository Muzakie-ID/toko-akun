<script setup>
import { UiButton } from '@/Components/ui'
import { Head, Link } from '@inertiajs/vue3'
import StatusBadge from '@/Components/Admin/StatusBadge.vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import { rupiah } from '@/lib/format'

defineProps({ orders: { type: Object, required: true } })
</script>

<template>
    <section class="mx-auto max-w-[1000px] px-4 py-8 lg:px-15">
        <Head title="Pesanan Saya" />
        <h1 class="text-xl font-bold text-ink sm:text-2xl">Pesanan Saya</h1>

        <div v-if="orders.data.length" class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
            <Link v-for="o in orders.data" :key="o.invoice" :href="`/pesanan/${o.invoice}`" class="flex items-start gap-3 border-b border-slate-100 px-4 py-4 last:border-b-0 hover:bg-slate-50 sm:items-center sm:gap-6 sm:px-5">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-ink">{{ o.produk }}</p>
                    <p class="text-xs text-slate-500">{{ o.jumlah_item }} akun · {{ o.tanggal }}</p>
                    <p class="text-[11px] text-slate-400">{{ o.invoice }}</p>
                </div>
                <div class="flex shrink-0 flex-col items-end gap-1 sm:flex-row sm:items-center sm:gap-4">
                    <p class="text-sm font-bold text-ink">{{ rupiah(o.total) }}</p>
                    <StatusBadge :tone="o.status_tone" :label="o.status_label" />
                    <span class="flex items-center gap-0.5 text-xs font-semibold" :class="o.perlu_bayar ? 'text-ink' : 'text-slate-400'">{{ o.perlu_bayar ? 'Bayar' : 'Detail' }} <Icon icon="heroicons:chevron-right" class="h-3.5 w-3.5" /></span>
                </div>
            </Link>
            <Pagination :paginator="orders" />
        </div>

        <div v-else class="mt-16 flex flex-col items-center text-center">
            <Icon icon="heroicons:shopping-bag" class="h-12 w-12 text-slate-300" />
            <p class="mt-4 text-sm font-semibold text-ink">Belum ada pesanan</p>
            <UiButton href="/" class="mt-4">Mulai Belanja</UiButton>
        </div>
    </section>
</template>

<script>
import PublicLayout from '@/Layouts/PublicLayout.vue'

export default { layout: PublicLayout }
</script>
