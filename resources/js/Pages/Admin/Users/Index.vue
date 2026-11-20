<script setup>
import { UiButton, UiDialog, UiInput, UiSearch, UiSelect } from '@/Components/ui'
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
            <h1 class="text-xl font-semibold tracking-tight text-ink sm:text-2xl">Pengguna</h1>
            <UiButton icon="heroicons:user-plus" :aria-expanded="showCreate" @click="showCreate = !showCreate"><span class="hidden sm:inline">Buat Akun Staf</span><span class="sm:hidden">Staf Baru</span></UiButton>
        </div>

        <form v-if="showCreate" class="mt-4 grid gap-4 rounded-2xl bg-white p-4 ring-1 ring-slate-200/70 sm:grid-cols-2 sm:p-5 lg:grid-cols-4" @submit.prevent="create">
            <UiInput v-model="form.name" label="Nama" required :error="form.errors.name" />
            <UiInput v-model="form.email" label="Email" type="email" required :error="form.errors.email" />
            <UiInput v-model="form.password" label="Password awal" required minlength="8" :error="form.errors.password" />
            <UiSelect v-model="form.role" label="Role" :options="[{ value: 'karyawan', label: 'Karyawan' }, { value: 'admin', label: 'Admin' }]" :error="form.errors.role" />
            <p class="text-xs text-slate-500 sm:col-span-2 lg:col-span-3">Karyawan bisa kelola produk, stok, dan pesanan. Pengguna &amp; pengaturan hanya untuk admin.</p>
            <UiButton type="submit" :loading="form.processing" class="sm:justify-self-end lg:col-span-1 lg:w-full">Buat Akun</UiButton>
        </form>

        <section class="mt-4 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70">
            <div class="flex flex-wrap gap-2 border-b border-slate-100 p-3 sm:gap-3 sm:p-4">
                <UiSearch v-model="filter.q" class="sm:min-w-60 sm:flex-1" label="Cari pengguna" placeholder="Nama atau email" @search="apply" />
                <UiSelect v-model="filter.role" class="sm:w-44" aria-label="Filter role" placeholder="Semua role" :options="roles" @change="apply" />
            </div>
            <!-- HP: kartu -->
            <ul class="divide-y divide-slate-100 md:hidden">
                <li v-for="u in users.data" :key="u.id" class="px-4 py-3.5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-ink">{{ u.nama }} <Icon v-if="u.via_google" icon="logos:google-icon" class="ml-0.5 inline h-3 w-3" aria-label="Daftar via Google" /></p>
                            <p class="truncate text-xs text-slate-500">{{ u.email }}</p>
                        </div>
                        <span v-if="u.id === me.id" class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="roleTone[u.role]">{{ u.role }} (kamu)</span>
                        <UiSelect v-else :model-value="u.role" size="sm" class="w-32 shrink-0" :aria-label="`Role ${u.nama}`" :options="roles" @change="ubahRole(u, $event.target.value)" />
                    </div>
                    <dl class="mt-2 grid grid-cols-3 gap-2 text-xs">
                        <div><dt class="text-slate-400">Saldo</dt><dd class="font-medium text-ink">{{ rupiah(u.saldo) }}</dd></div>
                        <div><dt class="text-slate-400">Belanja</dt><dd class="font-medium text-ink">{{ rupiah(u.total_belanja) }}</dd></div>
                        <div><dt class="text-slate-400">Pesanan</dt><dd class="font-medium text-ink">{{ u.jumlah_pesanan }}</dd></div>
                    </dl>
                    <UiButton variant="secondary" size="sm" block class="mt-2" icon="heroicons:wallet" @click="bukaSaldo(u)">Ubah saldo</UiButton>
                </li>
                <li v-if="!users.data.length" class="px-4 py-14 text-center text-sm text-slate-500">Tidak ada pengguna yang cocok.</li>
            </ul>
            <div class="hidden overflow-x-auto md:block">
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
                                <UiSelect v-else :model-value="u.role" size="sm" class="w-32" :aria-label="`Role ${u.nama}`" :options="roles" @change="ubahRole(u, $event.target.value)" />
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <span class="text-ink">{{ rupiah(u.saldo) }}</span>
                                <UiButton variant="ghost" size="sm" square icon="heroicons:pencil-square" class="-my-2 ml-1 text-primary" :label="`Ubah saldo ${u.nama}`" @click="bukaSaldo(u)" />
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

        <UiDialog v-if="saldoTarget" title="Penyesuaian saldo" :description="`${saldoTarget.nama} · saldo sekarang ${rupiah(saldoTarget.saldo)}`" @close="saldoTarget = null">
            <form id="saldo-form" class="space-y-4" @submit.prevent="simpanSaldo">
                <UiInput v-model.number="saldoForm.jumlah" label="Jumlah (Rp)" type="number" required placeholder="50000 untuk menambah, -50000 untuk mengurangi" :error="saldoForm.errors.jumlah" />
                <UiInput v-model="saldoForm.keterangan" label="Keterangan" required placeholder="Kompensasi akun bermasalah" :error="saldoForm.errors.keterangan" hint="Tercatat di riwayat saldo pembeli beserta nama admin." />
            </form>
            <template #footer>
                <UiButton variant="ghost" @click="saldoTarget = null">Batal</UiButton>
                <UiButton type="submit" form="saldo-form" :loading="saldoForm.processing">Simpan</UiButton>
            </template>
        </UiDialog>
    </div>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'

export default { layout: AdminLayout }
</script>
