<script setup>
import { Head, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import StatusBadge from '@/Components/Admin/StatusBadge.vue'
import { rupiah } from '@/lib/format'

const props = defineProps({
    topups: { type: Object, required: true },
    filters: { type: Object, required: true },
    statuses: { type: Array, required: true },
    totalMasukBulanIni: { type: Number, required: true },
})

const filter = reactive({ ...props.filters })
const apply = () => router.get('/admin/topup', Object.fromEntries(Object.entries(filter).filter(([, v]) => v !== '')), { preserveState: true, replace: true })
</script>

<template>
    <div>
        <Head title="Top-up Saldo" />
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-ink">Top-up Saldo</h1>
                <p class="mt-1 text-sm text-slate-500">Saldo masuk otomatis lewat webhook QRIS. Koreksi manual dilakukan di halaman Pengguna.</p>
            </div>
            <div class="rounded-2xl bg-white px-5 py-3 ring-1 ring-slate-200/70">
                <p class="text-xs text-slate-500">Top-up masuk bulan ini</p>
                <p class="text-xl font-semibold text-ink">{{ rupiah(totalMasukBulanIni) }}</p>
            </div>
        </div>

        <section class="mt-5 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70">
            <form class="flex flex-wrap gap-3 border-b border-slate-100 p-4" @submit.prevent="apply">
                <label class="flex h-10 min-w-60 flex-1 items-center gap-2 rounded-xl bg-slate-50 px-3 ring-1 ring-slate-200/70">
                    <Icon icon="heroicons:magnifying-glass" class="h-4 w-4 text-slate-400" />
                    <input v-model="filter.q" type="search" placeholder="No. top-up, nama, atau email" aria-label="Cari top-up" class="w-full bg-transparent text-sm focus:outline-none" />
                </label>
                <select v-model="filter.status" aria-label="Filter status" class="h-10 rounded-xl bg-white px-3 text-sm ring-1 ring-slate-200 focus:outline-none" @change="apply">
                    <option value="">Semua status</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </form>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">No. Top-up</th>
                            <th class="px-5 py-3 font-medium">Pembeli</th>
                            <th class="px-5 py-3 text-right font-medium">Saldo masuk</th>
                            <th class="px-5 py-3 text-right font-medium">Dibayar</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="t in topups.data" :key="t.invoice">
                            <td class="px-5 py-3"><p class="font-medium text-ink">{{ t.invoice }}</p><p class="text-xs text-slate-400">{{ t.tanggal }}</p></td>
                            <td class="px-5 py-3"><p class="text-ink">{{ t.pembeli }}</p><p class="text-xs text-slate-400">{{ t.email }}</p></td>
                            <td class="px-5 py-3 text-right font-medium text-ink">{{ rupiah(t.jumlah) }}</td>
                            <td class="px-5 py-3 text-right text-slate-600">{{ rupiah(t.total) }}<p v-if="t.paid_at" class="text-xs text-slate-400">{{ t.paid_at }}</p></td>
                            <td class="px-5 py-3"><StatusBadge :tone="t.status_tone" :label="t.status_label" /></td>
                        </tr>
                        <tr v-if="!topups.data.length"><td colspan="5" class="px-5 py-14 text-center text-slate-500">Belum ada top-up.</td></tr>
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="topups" />
        </section>
    </div>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'

export default { layout: AdminLayout }
</script>
