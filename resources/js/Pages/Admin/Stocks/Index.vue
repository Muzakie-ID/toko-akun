<script setup>
import { UiButton, UiDialog, UiInput, UiSearch, UiSelect } from '@/Components/ui'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'
import Pagination from '@/Components/Admin/Pagination.vue'

const props = defineProps({
    stocks: { type: Object, required: true },
    filters: { type: Object, required: true },
    variants: { type: Array, required: true },
})

const filter = reactive({ ...props.filters })
const apply = () => {
    const query = Object.fromEntries(Object.entries(filter).filter(([, v]) => v !== ''))
    router.get('/admin/stok', query, { preserveState: true, preserveScroll: true, replace: true })
}

const editing = ref(null)
const form = useForm({ email: '', password: '', slots: [] })
const shown = ref({})

const edit = (stock) => {
    editing.value = stock
    form.clearErrors()
    form.email = stock.email
    form.password = stock.password
    form.slots = stock.slots.map((s) => ({ id: s.id, nomor: s.nomor, info: s.info ?? '', invoice: s.invoice }))
}

const save = () => {
    form.transform((data) => ({ ...data, slots: data.slots.map((s) => ({ id: s.id, info: s.info || null })) }))
        .put(`/admin/stok/${editing.value.id}`, { preserveScroll: true, onSuccess: () => (editing.value = null) })
}

const hapus = (stock) => {
    if (!confirm(`Hapus stok ${stock.email}?`)) return
    router.delete(`/admin/stok/${stock.id}`, { preserveScroll: true })
}
</script>

<template>
    <div>
        <Head title="Stok Akun" />
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-ink sm:text-2xl">Stok Akun</h1>
                <p class="mt-1 text-sm text-slate-500">Edit kredensial untuk klaim garansi; pembeli langsung melihat data baru.</p>
            </div>
            <UiButton href="/admin/stok/import" icon="heroicons:arrow-up-tray">Input Stok Massal</UiButton>
        </div>

        <section class="mt-5 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70">
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 p-3 sm:gap-3 sm:p-4">
                <UiSelect v-model="filter.varian" class="sm:w-72" aria-label="Varian" placeholder="Semua varian" :options="variants.map((v) => ({ value: String(v.id), label: v.label }))" @change="apply" />
                <UiSelect v-model="filter.status" class="sm:w-48" aria-label="Status stok" placeholder="Semua status" :options="[{ value: 'tersedia', label: 'Masih ada slot' }, { value: 'habis', label: 'Habis terjual' }]" @change="apply" />
                <UiSearch v-model="filter.email" class="sm:min-w-56 sm:flex-1" label="Cari email" placeholder="Cari email lengkap (harus persis)" @search="apply" />
            </div>

            <!-- HP: kartu -->
            <ul class="divide-y divide-slate-100 md:hidden">
                <li v-for="s in stocks.data" :key="s.id" class="px-4 py-3.5">
                    <div class="flex items-start gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-ink">{{ s.produk }}</p>
                            <p class="text-xs text-slate-500">{{ s.varian }} · masuk {{ s.dibuat }}</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="s.slot_terpakai >= s.total_slot ? 'bg-slate-100 text-slate-500' : 'bg-emerald-50 text-emerald-700'">
                            {{ s.slot_terpakai }}/{{ s.total_slot }} {{ s.slot_terpakai >= s.total_slot ? 'habis' : 'tersedia' }}
                        </span>
                    </div>
                    <div class="mt-2 rounded-xl bg-slate-50 px-3 py-2 font-mono text-xs text-ink">
                        <p class="truncate">{{ s.email }}</p>
                        <p class="mt-0.5 flex items-center justify-between gap-2">
                            <span class="truncate">{{ shown[s.id] ? s.password : '••••••••' }}</span>
                            <UiButton variant="ghost" size="sm" square class="-my-2 shrink-0 font-sans" :icon="shown[s.id] ? 'heroicons:eye-slash' : 'heroicons:eye'" :label="shown[s.id] ? 'Sembunyikan password' : 'Lihat password'" @click="shown[s.id] = !shown[s.id]" />
                        </p>
                    </div>
                    <div class="mt-2 flex gap-2">
                        <UiButton variant="secondary" size="sm" class="flex-1" icon="heroicons:pencil-square" @click="edit(s)">Edit kredensial</UiButton>
                        <UiButton v-if="!s.slot_terpakai" variant="danger-soft" size="sm" icon="heroicons:trash" @click="hapus(s)">Hapus</UiButton>
                    </div>
                </li>
                <li v-if="!stocks.data.length" class="px-4 py-14 text-center text-sm text-slate-500">Tidak ada stok yang cocok.</li>
            </ul>
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Produk</th>
                            <th class="px-5 py-3 font-medium">Email</th>
                            <th class="px-5 py-3 font-medium">Password</th>
                            <th class="px-5 py-3 font-medium">Slot</th>
                            <th class="px-5 py-3 font-medium">Masuk</th>
                            <th class="px-5 py-3"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="s in stocks.data" :key="s.id" class="hover:bg-slate-50/60">
                            <td class="px-5 py-3"><p class="font-medium text-ink">{{ s.produk }}</p><p class="text-xs text-slate-500">{{ s.varian }}</p></td>
                            <td class="px-5 py-3 font-mono text-xs text-ink">{{ s.email }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-ink">
                                {{ shown[s.id] ? s.password : '••••••••' }}
                                <UiButton variant="ghost" size="sm" square class="-my-2 ml-1 shrink-0 font-sans" :icon="shown[s.id] ? 'heroicons:eye-slash' : 'heroicons:eye'" :label="shown[s.id] ? 'Sembunyikan password' : 'Lihat password'" @click="shown[s.id] = !shown[s.id]" />
                            </td>
                            <td class="px-5 py-3">
                                <span class="font-medium text-ink">{{ s.slot_terpakai }}/{{ s.total_slot }}</span>
                                <span class="ml-1 text-xs" :class="s.slot_terpakai >= s.total_slot ? 'text-slate-400' : 'text-emerald-600'">{{ s.slot_terpakai >= s.total_slot ? 'habis' : 'tersedia' }}</span>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-slate-500">{{ s.dibuat }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <UiButton variant="ghost" size="sm" icon="heroicons:pencil-square" @click="edit(s)">Edit</UiButton>
                                <UiButton v-if="!s.slot_terpakai" variant="ghost" size="sm" icon="heroicons:trash" class="text-rose-600 hover:bg-rose-50 hover:text-rose-700" @click="hapus(s)">Hapus</UiButton>
                            </td>
                        </tr>
                        <tr v-if="!stocks.data.length"><td colspan="6" class="px-5 py-14 text-center text-slate-500">Tidak ada stok yang cocok.</td></tr>
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="stocks" />
        </section>

        <UiDialog v-if="editing" title="Edit kredensial" :description="`${editing.produk} · ${editing.varian}`" size="lg" @close="editing = null">
            <form id="stok-form" class="space-y-4" @submit.prevent="save">
                <div v-if="editing.slot_terpakai" class="flex items-start gap-2 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800 ring-1 ring-amber-200">
                    <Icon icon="heroicons:exclamation-triangle" class="mt-px h-4 w-4 shrink-0" />
                    Sudah terjual ke {{ editing.slot_terpakai }} pembeli. Perubahan langsung terlihat di halaman pesanan mereka.
                </div>
                <UiInput v-model="form.email" label="Email / username" required :error="form.errors.email" />
                <UiInput v-model="form.password" label="Password" required :error="form.errors.password" />
                <div>
                    <p class="text-sm font-medium text-ink">Info per slot</p>
                    <div v-for="slot in form.slots" :key="slot.id" class="mt-2 flex items-center gap-2">
                        <span class="w-14 shrink-0 text-xs text-slate-500">Slot {{ slot.nomor }}</span>
                        <UiInput v-model="slot.info" size="sm" class="flex-1" :aria-label="`Info slot ${slot.nomor}`" placeholder="mis. Profil 2, PIN 1234" />
                        <span class="w-24 shrink-0 truncate text-[11px]" :class="slot.invoice ? 'text-primary' : 'text-slate-400'">{{ slot.invoice ?? 'belum terjual' }}</span>
                    </div>
                </div>
            </form>
            <template #footer>
                <UiButton variant="ghost" @click="editing = null">Batal</UiButton>
                <UiButton type="submit" form="stok-form" :loading="form.processing">Simpan</UiButton>
            </template>
        </UiDialog>
    </div>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'

export default { layout: AdminLayout }
</script>
