<script setup>
import { UiButton } from '@/Components/ui'
import { Head, Link, router } from '@inertiajs/vue3'
import Pagination from '@/Components/Admin/Pagination.vue'
import StatusBadge from '@/Components/Admin/StatusBadge.vue'
import Countdown from '@/Components/Countdown.vue'

defineProps({ sales: { type: Object, required: true } })

const hapus = (sale) => confirm(`Hapus flash sale "${sale.nama}"?`) && router.delete(`/admin/flash-sale/${sale.id}`, { preserveScroll: true })
</script>

<template>
    <div>
        <Head title="Flash Sale" />
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-ink sm:text-2xl">Flash Sale</h1>
                <p class="mt-1 text-sm text-slate-500">Harga khusus berjangka waktu; banner &amp; hitung mundur tampil di beranda saat berlangsung.</p>
            </div>
            <UiButton href="/admin/flash-sale/create" icon="heroicons:plus">Buat Flash Sale</UiButton>
        </div>

        <section class="mt-5 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70">
            <!-- HP: kartu -->
            <ul class="divide-y divide-slate-100 md:hidden">
                <li v-for="s in sales.data" :key="s.id" class="flex items-start gap-3 px-4 py-3.5">
                    <Link :href="`/admin/flash-sale/${s.id}/edit`" class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-ink">{{ s.nama }}</p>
                        <p class="text-xs text-slate-500">{{ s.mulai }} <Icon icon="heroicons:arrow-long-right" class="inline h-3.5 w-3.5 text-slate-400" /> {{ s.selesai }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ s.jumlah_varian }} varian · {{ s.terjual }} terjual</p>
                        <p v-if="s.status === 'berlangsung'" class="mt-1 text-xs font-medium text-emerald-700">Berakhir dalam <Countdown :to="s.selesai_at" variant="inline" /></p>
                    </Link>
                    <StatusBadge :tone="s.status_tone" :label="s.status_label" />
                </li>
                <li v-if="!sales.data.length" class="px-4 py-14 text-center text-sm text-slate-500">Belum ada flash sale.</li>
            </ul>
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Nama</th>
                            <th class="px-5 py-3 font-medium">Jadwal</th>
                            <th class="px-5 py-3 text-right font-medium">Varian</th>
                            <th class="px-5 py-3 text-right font-medium">Terjual</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="s in sales.data" :key="s.id" class="hover:bg-slate-50/60">
                            <td class="px-5 py-3 font-medium text-ink"><Link :href="`/admin/flash-sale/${s.id}/edit`" class="hover:text-accent-600">{{ s.nama }}</Link></td>
                            <td class="px-5 py-3 text-slate-600">
                                {{ s.mulai }} <Icon icon="heroicons:arrow-long-right" class="inline h-3.5 w-3.5 text-slate-400" /> {{ s.selesai }}
                                <p v-if="s.status === 'berlangsung'" class="text-xs text-emerald-700">Berakhir dalam <Countdown :to="s.selesai_at" variant="inline" /></p>
                            </td>
                            <td class="px-5 py-3 text-right text-ink">{{ s.jumlah_varian }}</td>
                            <td class="px-5 py-3 text-right text-ink">{{ s.terjual }}</td>
                            <td class="px-5 py-3"><StatusBadge :tone="s.status_tone" :label="s.status_label" /></td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <UiButton :href="`/admin/flash-sale/${s.id}/edit`" variant="ghost" size="sm" icon="heroicons:pencil-square">Edit</UiButton>
                                <UiButton variant="ghost" size="sm" icon="heroicons:trash" class="text-rose-600 hover:bg-rose-50 hover:text-rose-700" @click="hapus(s)">Hapus</UiButton>
                            </td>
                        </tr>
                        <tr v-if="!sales.data.length"><td colspan="6" class="px-5 py-14 text-center text-slate-500">Belum ada flash sale.</td></tr>
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="sales" />
        </section>
    </div>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'

export default { layout: AdminLayout }
</script>
