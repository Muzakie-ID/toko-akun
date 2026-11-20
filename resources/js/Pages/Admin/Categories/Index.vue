<script setup>
import { UiButton, UiInput } from '@/Components/ui'
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
        <h1 class="text-xl font-semibold tracking-tight text-ink sm:text-2xl">Kategori</h1>
        <p class="mt-1 text-sm text-slate-500">Dipakai untuk filter di katalog.</p>

        <form class="mt-5 flex items-start gap-2 rounded-2xl bg-white p-4 ring-1 ring-slate-200/70" @submit.prevent="create">
            <UiInput v-model="createForm.nama" class="flex-1" required placeholder="Nama kategori baru, mis. Musik" aria-label="Nama kategori baru" :error="createForm.errors.slug || createForm.errors.nama" />
            <UiButton type="submit" icon="heroicons:plus" :loading="createForm.processing">Tambah</UiButton>
        </form>

        <ul class="mt-4 divide-y divide-slate-100 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70">
            <li v-for="c in categories" :key="c.id" class="flex items-center gap-3 px-5 py-3">
                <form v-if="editingId === c.id" class="flex flex-1 items-start gap-2" @submit.prevent="update">
                    <UiInput v-model="editForm.nama" size="sm" class="flex-1" required aria-label="Nama kategori" :error="editForm.errors.slug || editForm.errors.nama" />
                    <UiButton type="submit" size="sm" :loading="editForm.processing">Simpan</UiButton>
                    <UiButton variant="ghost" size="sm" @click="editingId = null">Batal</UiButton>
                </form>
                <template v-else>
                    <span class="flex-1 font-medium text-ink">{{ c.nama }}</span>
                    <span class="text-xs text-slate-500">{{ c.products_count }} produk</span>
                    <UiButton variant="ghost" size="sm" icon="heroicons:pencil-square" @click="startEdit(c)">Edit</UiButton>
                    <UiButton variant="ghost" size="sm" icon="heroicons:trash" class="text-rose-600 hover:bg-rose-50 hover:text-rose-700" :disabled="c.products_count > 0" :title="c.products_count ? 'Masih dipakai produk' : ''" @click="hapus(c)">Hapus</UiButton>
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
