<script setup>
import { Head, Link } from '@inertiajs/vue3'
import StatusBadge from '@/Components/Admin/StatusBadge.vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import { rupiah } from '@/lib/format'

defineProps({ orders: { type: Object, required: true } })
</script>

<template>
    <section class="mx-auto max-w-[1000px] px-4 py-8 lg:px-15">
        <Head title="Pesanan Saya" />
        <h1 class="text-2xl font-bold text-ink">Pesanan Saya</h1>

        <div v-if="orders.data.length" class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
            <Link v-for="o in orders.data" :key="o.invoice" :href="`/pesanan/${o.invoice}`" class="flex flex-wrap items-center gap-x-6 gap-y-2 border-b border-slate-100 px-5 py-4 last:border-b-0 hover:bg-slate-50">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-ink">{{ o.produk }}</p>
                    <p class="text-xs text-slate-500">{{ o.invoice }} · {{ o.tanggal }} · {{ o.jumlah_item }} akun</p>
                </div>
                <p class="text-sm font-bold text-ink">{{ rupiah(o.total) }}</p>
                <StatusBadge :tone="o.status_tone" :label="o.status_label" />
                <span class="text-xs font-semibold" :class="o.perlu_bayar ? 'text-brand-600' : 'text-slate-400'">{{ o.perlu_bayar ? 'Bayar' : 'Detail' }} &rarr;</span>
            </Link>
            <Pagination :paginator="orders" />
        </div>

        <div v-else class="mt-16 flex flex-col items-center text-center">
            <Icon icon="heroicons:shopping-bag" class="h-12 w-12 text-slate-300" />
            <p class="mt-4 text-sm font-semibold text-ink">Belum ada pesanan</p>
            <Link href="/" class="mt-4 inline-flex h-10 items-center rounded-xl bg-brand-600 px-5 text-sm font-semibold text-white hover:bg-brand-700">Mulai Belanja</Link>
        </div>
    </section>
</template>

<script>
import PublicLayout from '@/Layouts/PublicLayout.vue'

export default { layout: PublicLayout }
</script>
