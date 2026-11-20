<script setup>
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
        <div class="mt-4 flex gap-1 overflow-x-auto pb-1" role="tablist">
            <button
                type="button"
                role="tab"
                :aria-selected="!form.status"
                class="shrink-0 rounded-full px-4 py-2 text-sm font-medium transition"
                :class="!form.status ? 'bg-ink text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'"
                @click="apply({ status: '' })"
            >Semua <span class="ml-1 opacity-60">{{ totalSemua }}</span></button>
            <button
                v-for="tab in statusTabs"
                :key="tab.value"
                type="button"
                role="tab"
                :aria-selected="form.status === tab.value"
                class="shrink-0 rounded-full px-4 py-2 text-sm font-medium transition"
                :class="form.status === tab.value ? 'bg-ink text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'"
                @click="apply({ status: tab.value })"
            >{{ tab.label }} <span class="ml-1 opacity-60">{{ tab.jumlah }}</span></button>
        </div>

        <section class="mt-4 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70">
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 p-3 sm:gap-3 sm:p-4">
                <form class="w-full min-w-0 sm:min-w-60 sm:flex-1" role="search" @submit.prevent="apply()">
                    <label class="flex h-10 items-center gap-2 rounded-xl bg-slate-50 px-3 ring-1 ring-slate-200/70 focus-within:ring-accent-300">
                        <Icon icon="heroicons:magnifying-glass" class="h-4 w-4 text-slate-400" />
                        <input v-model="form.q" type="search" placeholder="No. invoice, nama, atau email pembeli" aria-label="Cari pesanan" class="w-full bg-transparent text-sm focus:outline-none" />
                    </label>
                </form>
                <select v-model="form.metode" aria-label="Metode bayar" class="h-10 rounded-xl bg-white px-3 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" @change="apply()">
                    <option value="">Semua metode</option>
                    <option value="qris">QRIS</option>
                    <option value="manual">Transfer manual</option>
                </select>
                <label class="flex h-10 items-center gap-2 rounded-xl px-3 text-sm text-slate-600 ring-1 ring-slate-200">
                    <input v-model="form.perlu_cek" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-accent-600" @change="apply()" />
                    Perlu dicek
                </label>
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
