<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import { rupiah } from '@/lib/format'

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, required: true },
    roles: { type: Array, required: true },
})

const me = usePage().props.auth.user
const filter = reactive({ ...props.filters })
const apply = () => router.get('/admin/pengguna', Object.fromEntries(Object.entries(filter).filter(([, v]) => v !== '')), { preserveState: true, replace: true })

const roleTone = { admin: 'bg-accent-50 text-accent-700', karyawan: 'bg-violet-50 text-violet-700', reseller: 'bg-amber-50 text-amber-700', customer: 'bg-slate-100 text-slate-600' }

const ubahRole = (user, role) => {
    if (role === user.role) return
    if (!confirm(`Ubah role ${user.nama} menjadi ${role}?`)) {
        router.reload({ only: ['users'] })
        return
    }
    router.put(`/admin/pengguna/${user.id}/role`, { role }, { preserveScroll: true, onError: () => router.reload({ only: ['users'] }) })
}

const saldoTarget = ref(null)
const saldoForm = useForm({ jumlah: '', keterangan: '' })
const bukaSaldo = (u) => {
    saldoForm.reset()
    saldoForm.clearErrors()
    saldoTarget.value = u
}
const simpanSaldo = () => saldoForm.post(`/admin/pengguna/${saldoTarget.value.id}/saldo`, { preserveScroll: true, onSuccess: () => (saldoTarget.value = null) })

const showCreate = ref(false)
const form = useForm({ name: '', email: '', password: '', role: 'karyawan' })
const create = () => form.post('/admin/pengguna', { preserveScroll: true, onSuccess: () => { form.reset(); showCreate.value = false } })
</script>

<template>
    <div>
        <Head title="Pengguna" />
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-semibold tracking-tight text-ink">Pengguna</h1>
            <button type="button" class="inline-flex h-10 items-center gap-2 rounded-xl bg-accent-600 px-4 text-sm font-semibold text-white hover:bg-accent-700" @click="showCreate = !showCreate">
                <Icon icon="heroicons:user-plus" class="h-4 w-4" /> Buat Akun Staf
            </button>
        </div>

        <form v-if="showCreate" class="mt-4 grid gap-3 rounded-2xl bg-white p-5 ring-1 ring-slate-200/70 sm:grid-cols-2 lg:grid-cols-5" @submit.prevent="create">
            <div>
                <label class="text-xs font-medium text-slate-600" for="u-nama">Nama</label>
                <input id="u-nama" v-model="form.name" type="text" required class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600" for="u-email">Email</label>
                <input id="u-email" v-model="form.email" type="email" required class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600" for="u-pass">Password awal</label>
                <input id="u-pass" v-model="form.password" type="text" required minlength="8" class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                <p v-if="form.errors.password" class="mt-1 text-xs text-rose-600">{{ form.errors.password }}</p>
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600" for="u-role">Role</label>
                <select id="u-role" v-model="form.role" class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none">
                    <option value="karyawan">Karyawan</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" :disabled="form.processing" class="h-10 w-full rounded-xl bg-ink text-sm font-semibold text-white disabled:opacity-50">Buat Akun</button>
            </div>
            <p class="text-xs text-slate-500 sm:col-span-2 lg:col-span-5">Karyawan bisa kelola produk, stok, dan pesanan. Pengguna & pengaturan hanya untuk admin.</p>
        </form>

        <section class="mt-4 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70">
            <form class="flex flex-wrap gap-3 border-b border-slate-100 p-4" @submit.prevent="apply">
                <label class="flex h-10 min-w-60 flex-1 items-center gap-2 rounded-xl bg-slate-50 px-3 ring-1 ring-slate-200/70">
                    <Icon icon="heroicons:magnifying-glass" class="h-4 w-4 text-slate-400" />
                    <input v-model="filter.q" type="search" placeholder="Nama atau email" aria-label="Cari pengguna" class="w-full bg-transparent text-sm focus:outline-none" />
                </label>
                <select v-model="filter.role" aria-label="Filter role" class="h-10 rounded-xl bg-white px-3 text-sm ring-1 ring-slate-200 focus:outline-none" @change="apply">
                    <option value="">Semua role</option>
                    <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                </select>
            </form>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Nama</th>
                            <th class="px-5 py-3 font-medium">Role</th>
                            <th class="px-5 py-3 text-right font-medium">Saldo</th>
                            <th class="px-5 py-3 text-right font-medium">Pesanan</th>
                            <th class="px-5 py-3 text-right font-medium">Total belanja</th>
                            <th class="px-5 py-3 font-medium">Bergabung</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="u in users.data" :key="u.id">
                            <td class="px-5 py-3">
                                <p class="font-medium text-ink">{{ u.nama }} <Icon v-if="u.via_google" icon="logos:google-icon" class="ml-1 inline h-3 w-3" aria-label="Daftar via Google" /></p>
                                <p class="text-xs text-slate-500">{{ u.email }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <span v-if="u.id === me.id" class="rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="roleTone[u.role]">{{ u.role }} (kamu)</span>
                                <select v-else :value="u.role" :aria-label="`Role ${u.nama}`" class="h-8 rounded-lg border-0 px-2 text-xs font-medium capitalize ring-1 ring-slate-200 focus:outline-none" :class="roleTone[u.role]" @change="ubahRole(u, $event.target.value)">
                                    <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                                </select>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <span class="text-ink">{{ rupiah(u.saldo) }}</span>
                                <button type="button" class="ml-2 text-xs font-semibold text-accent-600 hover:underline" @click="bukaSaldo(u)">Ubah</button>
                            </td>
                            <td class="px-5 py-3 text-right text-ink">{{ u.jumlah_pesanan }}</td>
                            <td class="px-5 py-3 text-right text-ink">{{ rupiah(u.total_belanja) }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ u.bergabung }}</td>
                        </tr>
                        <tr v-if="!users.data.length"><td colspan="6" class="px-5 py-14 text-center text-slate-500">Tidak ada pengguna yang cocok.</td></tr>
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="users" />
        </section>

        <div v-if="saldoTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" role="dialog" aria-modal="true" aria-label="Penyesuaian saldo" @click.self="saldoTarget = null">
            <form class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl" @submit.prevent="simpanSaldo">
                <h2 class="text-lg font-semibold text-ink">Penyesuaian saldo</h2>
                <p class="text-sm text-slate-500">{{ saldoTarget.nama }} · saldo sekarang {{ rupiah(saldoTarget.saldo) }}</p>
                <label class="mt-4 block text-sm font-medium text-ink" for="s-jumlah">Jumlah (Rp)</label>
                <input id="s-jumlah" v-model.number="saldoForm.jumlah" type="number" required placeholder="50000 untuk menambah, -50000 untuk mengurangi" class="mt-1.5 h-10 w-full rounded-xl px-3 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                <p v-if="saldoForm.errors.jumlah" class="mt-1 text-xs text-rose-600">{{ saldoForm.errors.jumlah }}</p>
                <label class="mt-3 block text-sm font-medium text-ink" for="s-ket">Keterangan</label>
                <input id="s-ket" v-model="saldoForm.keterangan" type="text" required placeholder="Kompensasi akun bermasalah" class="mt-1.5 h-10 w-full rounded-xl px-3 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                <p v-if="saldoForm.errors.keterangan" class="mt-1 text-xs text-rose-600">{{ saldoForm.errors.keterangan }}</p>
                <p class="mt-3 text-xs text-slate-500">Tercatat di riwayat saldo pembeli beserta nama admin.</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="saldoTarget = null">Batal</button>
                    <button type="submit" :disabled="saldoForm.processing" class="h-10 rounded-xl bg-accent-600 px-4 text-sm font-semibold text-white hover:bg-accent-700 disabled:opacity-50">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'

export default { layout: AdminLayout }
</script>
