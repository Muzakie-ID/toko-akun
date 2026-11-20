<script setup>
import { UiButton, UiCheckbox, UiDialog, UiInput, UiTextarea } from '@/Components/ui'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import StatusBadge from '@/Components/Admin/StatusBadge.vue'
import { rupiah } from '@/lib/format'

const props = defineProps({
    order: { type: Object, required: true },
    aksi: { type: Object, required: true },
})

const base = `/admin/pesanan/${props.order.invoice}`
const dialog = ref(null)
const reasonForm = useForm({ alasan: '', catatan: '', ke_saldo: props.order.metode_value === 'saldo' })
const noteForm = useForm({ catatan_admin: props.order.catatan_admin ?? '' })
const shown = ref({})

const post = (path, confirmText) => {
    if (confirmText && !confirm(confirmText)) return
    router.post(`${base}/${path}`, {}, { preserveScroll: true })
}

const openDialog = (type) => {
    reasonForm.reset()
    reasonForm.ke_saldo = props.order.metode_value === 'saldo'
    reasonForm.clearErrors()
    dialog.value = type
}

const dialogs = {
    tolak: { title: 'Tolak bukti bayar', field: 'alasan', label: 'Alasan (ditampilkan ke pembeli)', placeholder: 'Nominal tidak sesuai / bukti tidak terbaca', button: 'Tolak Bukti', path: 'tolak' },
    batal: { title: 'Batalkan pesanan', field: 'catatan', label: 'Alasan pembatalan', placeholder: 'Permintaan pembeli', button: 'Batalkan Pesanan', path: 'batal' },
    refund: { title: 'Tandai refund', field: 'catatan', label: 'Catatan refund', placeholder: 'Dana dikembalikan via BCA, 20 Nov', button: 'Tandai Refund', path: 'refund' },
}

const submitDialog = () => {
    const d = dialogs[dialog.value]
    reasonForm.post(`${base}/${d.path}`, { preserveScroll: true, onSuccess: () => (dialog.value = null) })
}

const salin = (teks) => navigator.clipboard?.writeText(teks).catch(() => {})
</script>

<template>
    <div class="mx-auto max-w-6xl">
        <Head :title="order.invoice" />

        <Link href="/admin/pesanan" class="-my-2 inline-flex items-center gap-1 py-2 text-sm text-slate-500 hover:text-ink">
            <Icon icon="heroicons:arrow-left" class="h-4 w-4" /> Semua pesanan
        </Link>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="text-xl font-semibold tracking-tight text-ink sm:text-2xl">{{ order.invoice }}</h1>
            <StatusBadge :tone="order.status_tone" :label="order.status_label" />
        </div>
        <p class="mt-1 text-sm text-slate-500">Dibuat {{ order.tanggal }} · {{ order.metode }}</p>

        <!-- Peringatan -->
        <div v-if="order.perlu_cek" class="mt-4 flex gap-2 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 ring-1 ring-rose-200" role="alert">
            <Icon icon="heroicons:exclamation-triangle" class="mt-0.5 h-4 w-4 shrink-0" />
            <span v-if="order.late_paid_at">Pembayaran masuk {{ order.late_paid_at }}, setelah tagihan tidak berlaku. Pilih <b>Proses Pesanan</b> untuk mengirim akun, atau <b>Refund</b>.</span>
            <span v-else>Ada pembayaran dengan nominal berbeda. Cek catatan admin di bawah.</span>
        </div>

        <!-- Aksi -->
        <div class="mt-5 flex flex-wrap gap-2">
            <UiButton v-if="aksi.verifikasi" icon="heroicons:check-badge" @click="post('verifikasi', 'Tandai pembayaran diterima dan kirim akun ke pembeli?')">Terima Pembayaran</UiButton>
            <UiButton v-if="aksi.prosesBayarTelat" icon="heroicons:check-badge" @click="post('verifikasi', 'Proses pesanan dan kirim akun ke pembeli?')">Proses Pesanan</UiButton>
            <UiButton v-if="aksi.kirimUlang" icon="heroicons:arrow-path" @click="post('kirim-ulang')">Kirim Akun yang Kurang</UiButton>
            <UiButton v-if="aksi.tolak" variant="danger-soft" icon="heroicons:x-circle" @click="openDialog('tolak')">Tolak Bukti</UiButton>
            <UiButton v-if="aksi.batal" variant="secondary" icon="heroicons:no-symbol" @click="openDialog('batal')">Batalkan</UiButton>
            <UiButton v-if="aksi.refund" variant="secondary" icon="heroicons:receipt-refund" @click="openDialog('refund')">Tandai Refund</UiButton>
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-[1fr_340px]">
            <div class="space-y-4">
                <!-- Item & kredensial -->
                <section class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70">
                    <h2 class="px-5 pt-5 text-base font-semibold text-ink">Akun dipesan ({{ order.items.length }})</h2>
                    <ul class="mt-3 divide-y divide-slate-100">
                        <li v-for="item in order.items" :key="item.id" class="px-5 py-4">
                            <div class="flex flex-wrap items-start gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-ink">{{ item.produk }}</p>
                                    <p class="text-xs text-slate-500">{{ item.varian }} · {{ rupiah(item.harga) }}</p>
                                </div>
                                <span v-if="item.terkirim" class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200 ring-inset">Terkirim · aktif s/d {{ item.aktif_sampai }}</span>
                                <span v-else class="rounded-full bg-slate-50 px-2.5 py-0.5 text-xs font-medium text-slate-500 ring-1 ring-slate-200 ring-inset">Belum terkirim</span>
                            </div>
                            <div v-if="item.kredensial" class="mt-3 grid gap-2 rounded-xl bg-slate-50 p-3 text-sm sm:grid-cols-[1fr_1fr_auto]">
                                <div class="min-w-0">
                                    <p class="text-xs text-slate-500">Email</p>
                                    <p class="truncate font-mono text-ink">{{ item.kredensial.email }}</p>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-slate-500">Password</p>
                                    <p class="font-mono text-ink">{{ shown[item.id] ? item.kredensial.password : '••••••••' }}</p>
                                </div>
                                <div class="flex items-end gap-1">
                                    <UiButton variant="ghost" size="sm" :icon="shown[item.id] ? 'heroicons:eye-slash' : 'heroicons:eye'" @click="shown[item.id] = !shown[item.id]">{{ shown[item.id] ? 'Sembunyikan' : 'Lihat' }}</UiButton>
                                    <UiButton :href="`/admin/stok?email=${encodeURIComponent(item.kredensial.email)}`" variant="ghost" size="sm" class="text-primary" icon="heroicons:arrow-path-rounded-square">Ganti (garansi)</UiButton>
                                </div>
                                <p v-if="item.kredensial.info" class="text-xs text-slate-600 sm:col-span-3">Info slot: <span class="font-medium text-ink">{{ item.kredensial.info }}</span></p>
                            </div>
                        </li>
                    </ul>
                </section>

                <!-- Bukti bayar -->
                <section v-if="order.metode_value === 'manual'" class="rounded-2xl bg-white p-5 ring-1 ring-slate-200/70">
                    <h2 class="text-base font-semibold text-ink">Bukti transfer</h2>
                    <p v-if="order.alasan_tolak" class="mt-2 text-sm text-rose-600">Ditolak sebelumnya: {{ order.alasan_tolak }}</p>
                    <a v-if="order.ada_bukti" :href="`${base}/bukti`" target="_blank" rel="noopener" class="mt-3 block overflow-hidden rounded-xl ring-1 ring-slate-200">
                        <img :src="`${base}/bukti`" alt="Bukti transfer pembeli" class="max-h-[420px] w-full bg-slate-50 object-contain" />
                    </a>
                    <p v-else class="mt-2 text-sm text-slate-500">Pembeli belum mengunggah bukti.</p>
                </section>
            </div>

            <div class="space-y-4">
                <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200/70">
                    <h2 class="text-base font-semibold text-ink">Pembayaran</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="text-ink">{{ rupiah(order.subtotal) }}</dd></div>
                        <div v-if="order.kode_unik" class="flex justify-between"><dt class="text-slate-500">Kode unik QRIS</dt><dd class="text-ink">{{ rupiah(order.kode_unik) }}</dd></div>
                        <div class="flex justify-between border-t border-slate-100 pt-2 font-semibold"><dt class="text-ink">Total dibayar</dt><dd class="text-ink">{{ rupiah(order.total) }}</dd></div>
                        <div v-if="order.paid_at" class="flex justify-between"><dt class="text-slate-500">Lunas</dt><dd class="text-ink">{{ order.paid_at }}</dd></div>
                        <div v-else-if="order.expires_at" class="flex justify-between"><dt class="text-slate-500">Batas bayar</dt><dd class="text-ink">{{ order.expires_at }}</dd></div>
                        <div v-if="order.gateway_id" class="flex justify-between gap-3"><dt class="text-slate-500">ID qris-muz</dt><dd class="truncate font-mono text-xs text-ink">{{ order.gateway_id }}</dd></div>
                    </dl>
                </section>

                <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200/70">
                    <h2 class="text-base font-semibold text-ink">Pembeli</h2>
                    <p class="mt-3 text-sm font-medium text-ink">{{ order.pembeli }} <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[11px] text-slate-500 capitalize">{{ order.pembeli_role }}</span></p>
                    <p class="flex items-center gap-2 text-sm text-slate-500">
                        {{ order.pembeli_email }}
                        <UiButton variant="ghost" size="sm" square class="-my-2 text-primary" icon="heroicons:clipboard-document" label="Salin email pembeli" @click="salin(order.pembeli_email)" />
                    </p>
                </section>

                <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200/70">
                    <h2 class="text-base font-semibold text-ink">Catatan admin</h2>
                    <form class="mt-3" @submit.prevent="noteForm.put(`${base}/catatan`, { preserveScroll: true })">
                        <UiTextarea v-model="noteForm.catatan_admin" rows="5" aria-label="Catatan admin" :error="noteForm.errors.catatan_admin" />
                        <UiButton type="submit" size="sm" class="mt-2" :loading="noteForm.processing" :disabled="!noteForm.isDirty">Simpan Catatan</UiButton>
                    </form>
                </section>
            </div>
        </div>

        <!-- Dialog alasan -->
        <UiDialog v-if="dialog" :title="dialogs[dialog].title" @close="dialog = null">
            <form id="alasan-form" class="space-y-3" @submit.prevent="submitDialog">
                <UiInput v-model="reasonForm[dialogs[dialog].field]" :label="dialogs[dialog].label" required autofocus :placeholder="dialogs[dialog].placeholder" :error="reasonForm.errors[dialogs[dialog].field]" />
                <UiCheckbox
                    v-if="dialog === 'refund'"
                    v-model="reasonForm.ke_saldo"
                    :disabled="order.metode_value === 'saldo'"
                    :label="`Kembalikan ${rupiah(order.total)} ke saldo pembeli`"
                    :hint="order.metode_value === 'saldo' ? 'Pesanan dibayar dengan saldo, jadi selalu dikembalikan ke saldo.' : 'Bila tidak dicentang, kembalikan dana di luar sistem.'"
                />
            </form>
            <template #footer>
                <UiButton variant="ghost" @click="dialog = null">Batal</UiButton>
                <UiButton type="submit" form="alasan-form" variant="danger" :loading="reasonForm.processing">{{ dialogs[dialog].button }}</UiButton>
            </template>
        </UiDialog>
    </div>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'

export default { layout: AdminLayout }
</script>
