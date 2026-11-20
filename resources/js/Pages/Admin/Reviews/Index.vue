<script setup>
import { UiButton, UiSelect } from '@/Components/ui'
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import StarRating from '@/Components/StarRating.vue'

const props = defineProps({
    reviews: { type: Object, required: true },
    filters: { type: Object, required: true },
})

const filter = reactive({ ...props.filters })
const apply = () => router.get('/admin/ulasan', Object.fromEntries(Object.entries(filter).filter(([, v]) => v !== '')), { preserveState: true, replace: true })
const toggle = (r) => router.post(`/admin/ulasan/${r.id}/tampil`, {}, { preserveScroll: true })
const hapus = (r) => confirm(`Hapus ulasan dari ${r.pembeli}?`) && router.delete(`/admin/ulasan/${r.id}`, { preserveScroll: true })
</script>

<template>
    <div>
        <Head title="Ulasan" />
        <h1 class="text-xl font-semibold tracking-tight text-ink sm:text-2xl">Ulasan</h1>
        <p class="mt-1 text-sm text-slate-500">Sembunyikan ulasan spam atau kasar; rating produk dihitung ulang otomatis.</p>

        <section class="mt-5 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70">
            <div class="grid grid-cols-2 gap-2 border-b border-slate-100 p-3 sm:flex sm:gap-3 sm:p-4">
                <UiSelect v-model="filter.rating" class="sm:w-44" aria-label="Filter bintang" placeholder="Semua bintang" :options="[5, 4, 3, 2, 1].map((n) => ({ value: String(n), label: `${n} bintang` }))" @change="apply" />
                <UiSelect v-model="filter.tampil" class="sm:w-44" aria-label="Filter tampil" placeholder="Semua" :options="[{ value: 'ya', label: 'Ditampilkan' }, { value: 'tidak', label: 'Disembunyikan' }]" @change="apply" />
            </div>
            <ul class="divide-y divide-slate-100">
                <li v-for="r in reviews.data" :key="r.id" class="flex flex-wrap gap-x-4 gap-y-1 px-4 py-4 sm:px-5" :class="{ 'bg-slate-50/70': !r.is_tampil }">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <StarRating :model-value="r.rating" size="h-3.5 w-3.5" />
                            <Link :href="`/produk/${r.produk_slug}`" class="text-sm font-semibold text-ink hover:text-accent-600">{{ r.produk }}</Link>
                            <span v-if="!r.is_tampil" class="rounded-full bg-slate-200 px-2 py-0.5 text-[11px] font-medium text-slate-600">Disembunyikan</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-600">{{ r.komentar || '—' }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ r.pembeli }} · <Link :href="`/admin/pesanan/${r.invoice}`" class="hover:text-accent-600">{{ r.invoice }}</Link> · {{ r.tanggal }}</p>
                    </div>
                    <div class="-ml-2 flex items-start gap-1 text-xs font-semibold whitespace-nowrap sm:ml-0">
                        <UiButton variant="ghost" size="sm" :icon="r.is_tampil ? 'heroicons:eye-slash' : 'heroicons:eye'" @click="toggle(r)">{{ r.is_tampil ? 'Sembunyikan' : 'Tampilkan' }}</UiButton>
                        <UiButton variant="ghost" size="sm" icon="heroicons:trash" class="text-rose-600 hover:bg-rose-50 hover:text-rose-700" @click="hapus(r)">Hapus</UiButton>
                    </div>
                </li>
                <li v-if="!reviews.data.length" class="px-5 py-14 text-center text-sm text-slate-500">Belum ada ulasan.</li>
            </ul>
            <Pagination :paginator="reviews" />
        </section>
    </div>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'

export default { layout: AdminLayout }
</script>
