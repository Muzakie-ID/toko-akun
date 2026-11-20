<script setup>
import { UiCheckbox, UiChip, UiSearch, UiSelect } from '@/Components/ui'
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import StatusBadge from '@/Components/Admin/StatusBadge.vue'
import { rupiah } from '@/lib/format'

const props = defineProps({
    orders: { type: Object, required: true },
    filters: { type: Object, required: true },
    statusTabs: { type: Array, required: true },
})

const form = reactive({ ...props.filters })

const apply = (patch = {}) => {
    Object.assign(form, patch)
    const query = Object.fromEntries(Object.entries(form).filter(([, v]) => v !== '' && v !== false && v !== null))
    if (query.perlu_cek) query.perlu_cek = 1
    router.get('/admin/pesanan', query, { preserveState: true, preserveScroll: true, replace: true })
}

const totalSemua = props.statusTabs.reduce((t, s) => t + s.jumlah, 0)
</script>

<template>
    <div>
        <Head title="Pesanan" />
        <h1 class="text-xl font-semibold tracking-tight text-ink sm:text-2xl">Pesanan</h1>

        <!-- Tab status -->
        <div class="mt-4 flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none]" role="tablist" aria-label="Status pesanan">
            <UiChip role="tab" :active="!form.status" :count="totalSemua" @click="apply({ status: '' })">Semua</UiChip>
            <UiChip v-for="tab in statusTabs" :key="tab.value" role="tab" :active="form.status === tab.value" :count="tab.jumlah" @click="apply({ status: tab.value })">{{ tab.label }}</UiChip>
        </div>

        <section class="mt-4 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70">
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 p-3 sm:gap-3 sm:p-4">
                <UiSearch v-model="form.q" class="sm:min-w-60 sm:flex-1" label="Cari pesanan" placeholder="No. invoice, nama, atau email pembeli" @search="apply()" />
                <UiSelect v-model="form.metode" class="sm:w-48" aria-label="Metode bayar" :options="[{ value: '', label: 'Semua metode' }, { value: 'qris', label: 'QRIS' }, { value: 'manual', label: 'Transfer manual' }, { value: 'saldo', label: 'Saldo' }]" @change="apply()" />
                <UiCheckbox v-model="form.perlu_cek" label="Perlu dicek" class="h-11 rounded-xl px-3 ring-1 ring-slate-300 ring-inset" @change="apply()" />
            </div>

            <!-- HP: kartu -->
            <ul class="divide-y divide-slate-100 md:hidden">
                <li v-for="o in orders.data" :key="o.invoice">
                    <Link :href="`/admin/pesanan/${o.invoice}`" class="flex items-start gap-3 px-4 py-3.5 active:bg-slate-50">
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1 text-sm font-medium text-ink">
                                <span class="truncate">{{ o.pembeli }}</span>
                                <Icon v-if="o.perlu_cek" icon="heroicons:exclamation-triangle-solid" class="h-4 w-4 shrink-0 text-rose-500" aria-label="Perlu dicek" />
                            </p>
                            <p class="truncate text-xs text-slate-500">{{ o.produk }} · {{ o.jumlah_item }} akun · {{ o.metode }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-400">{{ o.invoice }} · {{ o.tanggal }}</p>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-1">
                            <span class="text-sm font-semibold text-ink">{{ rupiah(o.total) }}</span>
                            <StatusBadge :tone="o.status_tone" :label="o.status_label" />
                        </div>
                    </Link>
                </li>
                <li v-if="!orders.data.length" class="px-4 py-14 text-center text-sm text-slate-500">Tidak ada pesanan yang cocok dengan filter.</li>
            </ul>
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Invoice</th>
                            <th class="px-5 py-3 font-medium">Tanggal</th>
                            <th class="px-5 py-3 font-medium">Pembeli</th>
                            <th class="px-5 py-3 font-medium">Produk</th>
                            <th class="px-5 py-3 font-medium">Metode</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 text-right font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="o in orders.data" :key="o.invoice" class="cursor-pointer hover:bg-slate-50/70" @click="router.visit(`/admin/pesanan/${o.invoice}`)">
                            <td class="px-5 py-3.5">
                                <Link :href="`/admin/pesanan/${o.invoice}`" class="font-medium text-ink hover:text-accent-600" @click.stop>{{ o.invoice }}</Link>
                                <Icon v-if="o.perlu_cek" icon="heroicons:exclamation-triangle-solid" class="ml-1 inline h-4 w-4 text-rose-500" aria-label="Perlu dicek" />
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-slate-600">{{ o.tanggal }}</td>
                            <td class="px-5 py-3.5 text-ink">{{ o.pembeli }}</td>
                            <td class="px-5 py-3.5 text-slate-600">{{ o.produk }} <span class="text-xs text-slate-400">· {{ o.jumlah_item }} akun</span></td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-slate-600">{{ o.metode }}</td>
                            <td class="px-5 py-3.5"><StatusBadge :tone="o.status_tone" :label="o.status_label" /></td>
                            <td class="px-5 py-3.5 text-right font-medium whitespace-nowrap text-ink">{{ rupiah(o.total) }}</td>
                        </tr>
                        <tr v-if="!orders.data.length">
                            <td colspan="7" class="px-5 py-14 text-center text-slate-500">Tidak ada pesanan yang cocok dengan filter.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="orders" />
        </section>
    </div>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'

export default { layout: AdminLayout }
</script>
