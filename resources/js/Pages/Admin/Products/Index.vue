<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import Pagination from '@/Components/Admin/Pagination.vue'

const props = defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, required: true },
})

const search = ref(props.filters.q)

const submitSearch = () => {
    router.get('/admin/produk', search.value ? { q: search.value } : {}, { preserveState: true, replace: true })
}

const hapus = (product) => {
    if (!confirm(`Hapus produk "${product.nama}" beserta semua variannya?`)) return
    router.delete(`/admin/produk/${product.id}`, { preserveScroll: true })
}
</script>

<template>
    <div class="mx-auto max-w-6xl">
        <Head title="Produk" />

        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-semibold tracking-tight text-ink sm:text-2xl">Produk</h1>
            <Link href="/admin/produk/create" class="inline-flex h-10 items-center gap-1.5 rounded-xl bg-accent-600 px-4 text-sm font-semibold text-white transition hover:bg-accent-700">
                <Icon icon="heroicons:plus" class="h-4 w-4" />
                Tambah Produk
            </Link>
        </div>

        <form class="mt-5" role="search" @submit.prevent="submitSearch">
            <label class="flex h-10 w-full items-center sm:max-w-sm gap-2 rounded-xl border border-slate-300 bg-white px-3 focus-within:ring-2 focus-within:ring-slate-200">
                <Icon icon="heroicons:magnifying-glass" class="h-4 w-4 text-slate-400" />
                <input v-model="search" type="search" placeholder="Cari nama produk…" aria-label="Cari produk" class="w-full bg-transparent text-sm text-ink focus:outline-none" />
            </label>
        </form>

        <div class="mt-4 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70">
            <!-- HP: kartu -->
            <ul class="divide-y divide-slate-100 md:hidden">
                <li v-for="product in products.data" :key="product.id" class="flex items-center gap-3 px-4 py-3">
                    <Link :href="`/admin/produk/${product.id}/edit`" class="min-w-0 flex-1">
                        <p class="flex items-center gap-1.5 text-sm font-semibold text-ink">
                            <span class="truncate">{{ product.nama }}</span>
                            <span v-if="product.is_populer" class="shrink-0 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">Populer</span>
                        </p>
                        <p class="text-xs text-slate-500">{{ product.kategori }} · {{ product.jumlah_varian }} varian · {{ product.jumlah_stok }} stok</p>
                        <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="product.is_aktif ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'">{{ product.is_aktif ? 'Aktif' : 'Nonaktif' }}</span>
                    </Link>
                    <button type="button" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-rose-600 hover:bg-rose-50" :aria-label="`Hapus ${product.nama}`" @click="hapus(product)">
                        <Icon icon="heroicons:trash" class="h-5 w-5" />
                    </button>
                </li>
                <li v-if="!products.data.length" class="px-4 py-10 text-center text-sm text-slate-500">
                    {{ filters.q ? `Tidak ada produk yang cocok dengan "${filters.q}".` : 'Belum ada produk.' }}
                </li>
            </ul>
            <div class="hidden overflow-x-auto md:block">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs text-slate-500">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Nama</th>
                        <th class="px-4 py-2.5 font-medium">Kategori</th>
                        <th class="px-4 py-2.5 text-right font-medium">Varian</th>
                        <th class="px-4 py-2.5 text-right font-medium">Stok</th>
                        <th class="px-4 py-2.5 font-medium">Status</th>
                        <th class="px-4 py-2.5"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="product in products.data" :key="product.id">
                        <td class="px-4 py-3 font-semibold text-ink">
                            <Link :href="`/admin/produk/${product.id}/edit`" class="hover:underline">{{ product.nama }}</Link>
                            <span v-if="product.is_populer" class="ml-1.5 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">Populer</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ product.kategori }}</td>
                        <td class="px-4 py-3 text-right text-ink" :class="{ 'text-rose-600': !product.jumlah_varian }">{{ product.jumlah_varian }}</td>
                        <td class="px-4 py-3 text-right text-ink">{{ product.jumlah_stok }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="product.is_aktif ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                                {{ product.is_aktif ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <Link :href="`/admin/produk/${product.id}/edit`" class="text-xs font-semibold text-ink hover:underline">Edit</Link>
                            <button type="button" class="ml-3 text-xs font-semibold text-rose-600 hover:underline" @click="hapus(product)">Hapus</button>
                        </td>
                    </tr>
                    <tr v-if="!products.data.length">
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                            {{ filters.q ? `Tidak ada produk yang cocok dengan "${filters.q}".` : 'Belum ada produk.' }}
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
            <Pagination :paginator="products" />
        </div>
    </div>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'

export default { layout: AdminLayout }
</script>
