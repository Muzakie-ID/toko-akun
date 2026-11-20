<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

defineProps({ categories: { type: Array, required: true } })

const createForm = useForm({ nama: '' })
const editForm = useForm({ nama: '' })
const editingId = ref(null)

const create = () => createForm.post('/admin/kategori', { preserveScroll: true, onSuccess: () => createForm.reset() })
const startEdit = (c) => {
    editingId.value = c.id
    editForm.nama = c.nama
    editForm.clearErrors()
}
const update = () => editForm.put(`/admin/kategori/${editingId.value}`, { preserveScroll: true, onSuccess: () => (editingId.value = null) })
const hapus = (c) => confirm(`Hapus kategori ${c.nama}?`) && router.delete(`/admin/kategori/${c.id}`, { preserveScroll: true })
</script>

<template>
    <div class="mx-auto max-w-3xl">
        <Head title="Kategori" />
        <h1 class="text-2xl font-semibold tracking-tight text-ink">Kategori</h1>
        <p class="mt-1 text-sm text-slate-500">Dipakai untuk filter di katalog.</p>

        <form class="mt-5 flex gap-2 rounded-2xl bg-white p-4 ring-1 ring-slate-200/70" @submit.prevent="create">
            <div class="flex-1">
                <input v-model="createForm.nama" type="text" required placeholder="Nama kategori baru, mis. Musik" aria-label="Nama kategori baru" class="h-10 w-full rounded-xl px-3 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                <p v-if="createForm.errors.slug || createForm.errors.nama" class="mt-1 text-xs text-rose-600">{{ createForm.errors.slug || createForm.errors.nama }}</p>
            </div>
            <button type="submit" :disabled="createForm.processing" class="h-10 rounded-xl bg-accent-600 px-4 text-sm font-semibold text-white hover:bg-accent-700 disabled:opacity-50">Tambah</button>
        </form>

        <ul class="mt-4 divide-y divide-slate-100 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70">
            <li v-for="c in categories" :key="c.id" class="flex items-center gap-3 px-5 py-3">
                <form v-if="editingId === c.id" class="flex flex-1 items-start gap-2" @submit.prevent="update">
                    <div class="flex-1">
                        <input v-model="editForm.nama" type="text" required aria-label="Nama kategori" class="h-9 w-full rounded-lg px-3 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                        <p v-if="editForm.errors.slug || editForm.errors.nama" class="mt-1 text-xs text-rose-600">{{ editForm.errors.slug || editForm.errors.nama }}</p>
                    </div>
                    <button type="submit" class="h-9 rounded-lg bg-accent-600 px-3 text-xs font-semibold text-white">Simpan</button>
                    <button type="button" class="h-9 rounded-lg px-3 text-xs font-medium text-slate-600 hover:bg-slate-100" @click="editingId = null">Batal</button>
                </form>
                <template v-else>
                    <span class="flex-1 font-medium text-ink">{{ c.nama }}</span>
                    <span class="text-xs text-slate-500">{{ c.products_count }} produk</span>
                    <button type="button" class="text-xs font-semibold text-accent-600 hover:underline" @click="startEdit(c)">Edit</button>
                    <button type="button" class="text-xs font-semibold text-rose-600 hover:underline disabled:opacity-30" :disabled="c.products_count > 0" :title="c.products_count ? 'Masih dipakai produk' : ''" @click="hapus(c)">Hapus</button>
                </template>
            </li>
            <li v-if="!categories.length" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada kategori.</li>
        </ul>
    </div>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'

export default { layout: AdminLayout }
</script>
