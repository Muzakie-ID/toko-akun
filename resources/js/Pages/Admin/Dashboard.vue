<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import RevenueChart from '@/Components/Admin/RevenueChart.vue'
import StatCard from '@/Components/Admin/StatCard.vue'
import StatusBadge from '@/Components/Admin/StatusBadge.vue'
import TargetGauge from '@/Components/Admin/TargetGauge.vue'
import { rupiah } from '@/lib/format'

const props = defineProps({
    stats: { type: Array, required: true },
    chart: { type: Object, required: true },
    target: { type: Object, required: true },
    perluTindakan: { type: Object, required: true },
    pesananTerbaru: { type: Array, required: true },
    stokMenipis: { type: Array, required: true },
})

const user = computed(() => usePage().props.auth?.user)
const periode = ref('bulanan')
const totalPeriode = computed(() => props.chart[periode.value].reduce((t, d) => t + d.nilai, 0))
const hariIni = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })

const tindakan = computed(() => [
    { label: 'Bukti bayar perlu dicek', jumlah: props.perluTindakan.verifikasi, href: '/admin/pesanan?status=menunggu_verifikasi', icon: 'heroicons:document-check', tone: 'text-accent-600 bg-accent-50' },
    { label: 'Pesanan menunggu stok', jumlah: props.perluTindakan.menungguStok, href: '/admin/pesanan?status=diproses', icon: 'heroicons:clock', tone: 'text-amber-600 bg-amber-50' },
    { label: 'Bayar setelah kedaluwarsa', jumlah: props.perluTindakan.bayarTelat, href: '/admin/pesanan?perlu_cek=1', icon: 'heroicons:exclamation-triangle', tone: 'text-rose-600 bg-rose-50' },
])
</script>

<template>
    <div>
        <Head title="Dashboard" />

        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-ink sm:text-2xl">Selamat datang, {{ user?.name?.split(' ')[0] }}!</h1>
                <p class="mt-1 text-sm text-slate-500 capitalize">{{ hariIni }}</p>
            </div>
            <Link href="/admin/pesanan" class="hidden h-10 items-center gap-2 rounded-xl bg-white px-4 text-sm font-medium text-ink ring-1 ring-slate-200 hover:bg-slate-50 sm:inline-flex">
                <Icon icon="heroicons:shopping-bag" class="h-4 w-4" />
                Semua Pesanan
            </Link>
        </div>

        <!-- Kartu statistik -->
        <div class="mt-5 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            <StatCard v-for="stat in stats" :key="stat.label" :stat="stat" />
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-[1fr_340px]">
            <!-- Chart pendapatan -->
            <section class="rounded-2xl bg-white p-4 ring-1 ring-slate-200/70 sm:p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-ink">Pendapatan</h2>
                        <p class="mt-2 text-2xl font-semibold tracking-tight text-ink sm:text-3xl">{{ rupiah(totalPeriode) }}</p>
                        <p class="text-xs text-slate-500">{{ periode === 'bulanan' ? '12 bulan terakhir' : '14 hari terakhir' }}</p>
                    </div>
                    <div class="flex rounded-full bg-slate-100 p-1 text-sm" role="tablist">
                        <button
                            v-for="p in [{ v: 'harian', l: 'Harian' }, { v: 'bulanan', l: 'Bulanan' }]"
                            :key="p.v"
                            type="button"
                            role="tab"
                            :aria-selected="periode === p.v"
                            class="rounded-full px-4 py-2 font-medium transition sm:py-1.5"
                            :class="periode === p.v ? 'bg-ink text-white shadow-sm' : 'text-slate-600'"
                            @click="periode = p.v"
                        >{{ p.l }}</button>
                    </div>
                </div>
                <div class="mt-6">
                    <RevenueChart :data="chart[periode]" />
                </div>
            </section>

            <!-- Target + perlu tindakan -->
            <div class="flex flex-col gap-4">
                <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200/70">
                    <h2 class="text-base font-semibold text-ink">Target Bulan Ini</h2>
                    <div class="mt-4"><TargetGauge :persen="target.persen" /></div>
                    <div class="mt-5 flex items-end justify-between text-sm">
                        <div>
                            <p class="text-xs text-slate-500">Tercapai</p>
                            <p class="font-semibold text-ink">{{ rupiah(target.tercapai) }}</p>
                        </div>
                        <p class="text-xs text-slate-500">Target <span class="text-sm font-semibold text-ink">{{ rupiah(target.target) }}</span></p>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-accent-500" :style="{ width: `${target.persen}%` }" />
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200/70">
                    <h2 class="text-base font-semibold text-ink">Perlu Tindakan</h2>
                    <ul class="mt-3 space-y-1">
                        <li v-for="t in tindakan" :key="t.label">
                            <Link :href="t.href" class="flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-slate-50">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg" :class="t.tone"><Icon :icon="t.icon" class="h-4 w-4" /></span>
                                <span class="flex-1 text-sm text-slate-600">{{ t.label }}</span>
                                <span class="text-sm font-semibold" :class="t.jumlah ? 'text-ink' : 'text-slate-300'">{{ t.jumlah }}</span>
                            </Link>
                        </li>
                    </ul>
                </section>
            </div>
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-[1fr_340px]">
            <!-- Pesanan terbaru -->
            <section class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70">
                <div class="flex items-center justify-between px-4 pt-4 pb-2 sm:px-5 sm:pt-5 sm:pb-3">
                    <h2 class="text-base font-semibold text-ink">Pesanan Terbaru</h2>
                    <Link href="/admin/pesanan" class="-my-2 py-2 text-sm font-medium text-accent-600 hover:underline">Lihat semua</Link>
                </div>
                <!-- HP: kartu -->
                <ul class="divide-y divide-slate-100 md:hidden">
                    <li v-for="o in pesananTerbaru" :key="o.invoice">
                        <Link :href="`/admin/pesanan/${o.invoice}`" class="flex items-start gap-3 px-4 py-3 active:bg-slate-50">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ink">{{ o.pembeli }}</p>
                                <p class="truncate text-xs text-slate-500">{{ o.produk }} · {{ o.jumlah_item }} akun</p>
                                <p class="mt-0.5 text-[11px] text-slate-400">{{ o.invoice }} · {{ o.tanggal }}</p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1">
                                <span class="text-sm font-semibold text-ink">{{ rupiah(o.total) }}</span>
                                <StatusBadge :tone="o.status_tone" :label="o.status_label" />
                            </div>
                        </Link>
                    </li>
                    <li v-if="!pesananTerbaru.length" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada pesanan.</li>
                </ul>
                <div class="hidden overflow-x-auto px-5 pb-4 md:block">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs text-slate-500">
                            <tr class="bg-slate-50">
                                <th class="rounded-l-lg px-3 py-2.5 font-medium">Invoice</th>
                                <th class="px-3 py-2.5 font-medium">Pembeli</th>
                                <th class="px-3 py-2.5 font-medium">Produk</th>
                                <th class="px-3 py-2.5 font-medium">Status</th>
                                <th class="rounded-r-lg px-3 py-2.5 text-right font-medium">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="o in pesananTerbaru" :key="o.invoice" class="hover:bg-slate-50/60">
                                <td class="px-3 py-3"><Link :href="`/admin/pesanan/${o.invoice}`" class="font-medium text-ink hover:text-accent-600">{{ o.invoice }}</Link><p class="text-xs text-slate-400">{{ o.tanggal }}</p></td>
                                <td class="px-3 py-3 text-ink">{{ o.pembeli }}</td>
                                <td class="px-3 py-3 text-slate-600">{{ o.produk }} <span class="text-xs text-slate-400">· {{ o.jumlah_item }} akun</span></td>
                                <td class="px-3 py-3"><StatusBadge :tone="o.status_tone" :label="o.status_label" /></td>
                                <td class="px-3 py-3 text-right font-medium text-ink">{{ rupiah(o.total) }}</td>
                            </tr>
                            <tr v-if="!pesananTerbaru.length"><td colspan="5" class="px-3 py-10 text-center text-slate-500">Belum ada pesanan.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Stok menipis -->
            <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200/70">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-ink">Stok Menipis</h2>
                    <Link href="/admin/stok/import" class="-my-2 py-2 text-sm font-medium text-accent-600 hover:underline">Tambah stok</Link>
                </div>
                <ul class="mt-3 divide-y divide-slate-100">
                    <li v-for="s in stokMenipis" :key="s.id" class="flex items-center gap-3 py-2.5">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-ink">{{ s.produk }}</p>
                            <p class="text-xs text-slate-500">{{ s.varian }}</p>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="s.tersedia === 0 ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700'">
                            {{ s.tersedia === 0 ? 'Habis' : `${s.tersedia} slot` }}
                        </span>
                    </li>
                    <li v-if="!stokMenipis.length" class="py-6 text-center text-sm text-slate-500">Semua varian aman.</li>
                </ul>
            </section>
        </div>
    </div>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'

export default { layout: AdminLayout }
</script>
