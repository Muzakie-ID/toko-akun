<script setup>
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
                <h1 class="text-2xl font-semibold tracking-tight text-ink">Stok Akun</h1>
                <p class="mt-1 text-sm text-slate-500">Edit kredensial untuk klaim garansi; pembeli langsung melihat data baru.</p>
            </div>
            <Link href="/admin/stok/import" class="inline-flex h-10 items-center gap-2 rounded-xl bg-accent-600 px-4 text-sm font-semibold text-white hover:bg-accent-700">
                <Icon icon="heroicons:arrow-up-tray" class="h-4 w-4" /> Input Stok Massal
            </Link>
        </div>

        <section class="mt-5 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70">
            <form class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4" @submit.prevent="apply">
                <select v-model="filter.varian" aria-label="Varian" class="h-10 max-w-full min-w-56 rounded-xl bg-white px-3 text-sm ring-1 ring-slate-200 focus:outline-none" @change="apply">
                    <option value="">Semua varian</option>
                    <option v-for="v in variants" :key="v.id" :value="String(v.id)">{{ v.label }}</option>
                </select>
                <select v-model="filter.status" aria-label="Status stok" class="h-10 rounded-xl bg-white px-3 text-sm ring-1 ring-slate-200 focus:outline-none" @change="apply">
                    <option value="">Semua status</option>
                    <option value="tersedia">Masih ada slot</option>
                    <option value="habis">Habis terjual</option>
                </select>
                <label class="flex h-10 min-w-56 flex-1 items-center gap-2 rounded-xl bg-slate-50 px-3 ring-1 ring-slate-200/70">
                    <Icon icon="heroicons:magnifying-glass" class="h-4 w-4 text-slate-400" />
                    <input v-model="filter.email" type="search" placeholder="Cari email lengkap (terenkripsi, harus persis)" aria-label="Cari email" class="w-full bg-transparent text-sm focus:outline-none" />
                </label>
            </form>

            <div class="overflow-x-auto">
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
                                <button type="button" class="ml-2 font-sans text-xs font-semibold text-accent-600" @click="shown[s.id] = !shown[s.id]">{{ shown[s.id] ? 'Tutup' : 'Lihat' }}</button>
                            </td>
                            <td class="px-5 py-3">
                                <span class="font-medium text-ink">{{ s.slot_terpakai }}/{{ s.total_slot }}</span>
                                <span class="ml-1 text-xs" :class="s.slot_terpakai >= s.total_slot ? 'text-slate-400' : 'text-emerald-600'">{{ s.slot_terpakai >= s.total_slot ? 'habis' : 'tersedia' }}</span>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-slate-500">{{ s.dibuat }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <button type="button" class="text-xs font-semibold text-accent-600 hover:underline" @click="edit(s)">Edit</button>
                                <button v-if="!s.slot_terpakai" type="button" class="ml-3 text-xs font-semibold text-rose-600 hover:underline" @click="hapus(s)">Hapus</button>
                            </td>
                        </tr>
                        <tr v-if="!stocks.data.length"><td colspan="6" class="px-5 py-14 text-center text-slate-500">Tidak ada stok yang cocok.</td></tr>
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="stocks" />
        </section>

        <div v-if="editing" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" role="dialog" aria-modal="true" aria-label="Edit kredensial" @click.self="editing = null">
            <form class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-5 shadow-xl" @submit.prevent="save">
                <h2 class="text-lg font-semibold text-ink">Edit kredensial</h2>
                <p class="text-sm text-slate-500">{{ editing.produk }} · {{ editing.varian }}</p>
                <div v-if="editing.slot_terpakai" class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800 ring-1 ring-amber-200">
                    Sudah terjual ke {{ editing.slot_terpakai }} pembeli. Perubahan langsung terlihat di halaman pesanan mereka.
                </div>
                <label class="mt-4 block text-sm font-medium text-ink" for="stok-email">Email / username</label>
                <input id="stok-email" v-model="form.email" type="text" required class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
                <label class="mt-3 block text-sm font-medium text-ink" for="stok-password">Password</label>
                <input id="stok-password" v-model="form.password" type="text" required class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                <p v-if="form.errors.password" class="mt-1 text-xs text-rose-600">{{ form.errors.password }}</p>

                <p class="mt-4 text-sm font-medium text-ink">Info per slot</p>
                <div v-for="slot in form.slots" :key="slot.id" class="mt-2 flex items-center gap-2">
                    <span class="w-14 shrink-0 text-xs text-slate-500">Slot {{ slot.nomor }}</span>
                    <input v-model="slot.info" type="text" :aria-label="`Info slot ${slot.nomor}`" placeholder="mis. Profil 2, PIN 1234" class="h-9 w-full rounded-lg px-3 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                    <span class="w-28 shrink-0 truncate text-[11px]" :class="slot.invoice ? 'text-accent-600' : 'text-slate-400'">{{ slot.invoice ?? 'belum terjual' }}</span>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="editing = null">Batal</button>
                    <button type="submit" :disabled="form.processing" class="h-10 rounded-xl bg-accent-600 px-4 text-sm font-semibold text-white hover:bg-accent-700 disabled:opacity-50">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'

export default { layout: AdminLayout }
</script>
