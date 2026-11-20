<script setup>
import { UiSearch, UiSelect } from '@/Components/ui'
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
                <h1 class="text-xl font-semibold tracking-tight text-ink sm:text-2xl">Top-up Saldo</h1>
                <p class="mt-1 text-sm text-slate-500">Saldo masuk otomatis lewat webhook QRIS. Koreksi manual dilakukan di halaman Pengguna.</p>
            </div>
            <div class="rounded-2xl bg-white px-5 py-3 ring-1 ring-slate-200/70">
                <p class="text-xs text-slate-500">Top-up masuk bulan ini</p>
                <p class="text-xl font-semibold text-ink">{{ rupiah(totalMasukBulanIni) }}</p>
            </div>
        </div>

        <section class="mt-5 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70">
            <div class="flex flex-wrap gap-2 border-b border-slate-100 p-3 sm:gap-3 sm:p-4">
                <UiSearch v-model="filter.q" class="sm:min-w-60 sm:flex-1" label="Cari top-up" placeholder="No. top-up, nama, atau email" @search="apply" />
                <UiSelect v-model="filter.status" class="sm:w-48" aria-label="Filter status" placeholder="Semua status" :options="statuses" @change="apply" />
            </div>
            <!-- HP: kartu -->
            <ul class="divide-y divide-slate-100 md:hidden">
                <li v-for="t in topups.data" :key="t.invoice" class="flex items-start gap-3 px-4 py-3.5">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-ink">{{ t.pembeli }}</p>
                        <p class="truncate text-xs text-slate-500">{{ t.email }}</p>
                        <p class="mt-0.5 text-[11px] text-slate-400">{{ t.invoice }} · {{ t.tanggal }}</p>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-1">
                        <span class="text-sm font-semibold text-ink">{{ rupiah(t.jumlah) }}</span>
                        <StatusBadge :tone="t.status_tone" :label="t.status_label" />
                    </div>
                </li>
                <li v-if="!topups.data.length" class="px-4 py-14 text-center text-sm text-slate-500">Belum ada top-up.</li>
            </ul>
            <div class="hidden overflow-x-auto md:block">
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
