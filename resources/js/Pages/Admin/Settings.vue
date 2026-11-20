<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { UiButton, UiCheckbox, UiInput } from '@/Components/ui'

const props = defineProps({
    settings: { type: Object, required: true },
    secrets: { type: Object, required: true },
    webhookUrl: { type: String, required: true },
    qrisReady: { type: Boolean, required: true },
})

const form = useForm({ ...props.settings, qris_api_key: '', qris_webhook_secret: '' })
const testing = ref(false)
const copied = ref(false)

const save = () => form.put('/admin/pengaturan', { preserveScroll: true, onSuccess: () => form.reset('qris_api_key', 'qris_webhook_secret') })
const tambahRekening = () => form.manual_rekening.push({ bank: '', nomor: '', nama: '' })
const hapusRekening = (i) => form.manual_rekening.splice(i, 1)
const tesKoneksi = () => router.post('/admin/pengaturan/tes-qris', {}, { preserveScroll: true, onStart: () => (testing.value = true), onFinish: () => (testing.value = false) })
const salinWebhook = async () => {
    try {
        await navigator.clipboard.writeText(props.webhookUrl)
        copied.value = true
        setTimeout(() => (copied.value = false), 1500)
    } catch {}
}

const err = (key) => form.errors[key]
</script>

<template>
    <div class="mx-auto max-w-4xl">
        <Head title="Pengaturan" />
        <h1 class="text-xl font-semibold tracking-tight text-ink sm:text-2xl">Pengaturan</h1>

        <form class="mt-5 space-y-4" @submit.prevent="save">
            <!-- Toko -->
            <section class="rounded-2xl bg-white p-4 ring-1 ring-slate-200/70 sm:p-5">
                <h2 class="text-base font-semibold text-ink">Toko</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <UiInput v-model="form.store_nama" label="Nama toko" required :error="err('store_nama')" />
                    <UiInput v-model="form.store_whatsapp" label="WhatsApp admin" type="tel" placeholder="62812..." :error="err('store_whatsapp')" />
                    <UiInput v-model="form.store_target_bulanan" label="Target penjualan / bulan (Rp)" type="number" min="0" required :error="err('store_target_bulanan')" />
                </div>
            </section>

            <!-- QRIS qris-muz -->
            <section class="rounded-2xl bg-white p-4 ring-1 ring-slate-200/70 sm:p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="flex items-center gap-2 text-base font-semibold text-ink">
                            QRIS otomatis (qris-muz)
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="qrisReady ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'">{{ qrisReady ? 'Aktif' : 'Belum aktif' }}</span>
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">Tagihan dibuat di gateway qris-muz; pesanan lunas dan akun terkirim otomatis lewat webhook.</p>
                    </div>
                    <UiCheckbox v-model="form.qris_enabled" label="Aktifkan" class="font-medium" />
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <UiInput v-model="form.qris_base_url" class="sm:col-span-2" label="URL API gateway" type="url" placeholder="https://api.qris-muz.domainmu.id" :error="err('qris_base_url')" />
                    <UiInput
                        v-model="form.qris_api_key"
                        label="API key proyek"
                        type="password"
                        autocomplete="off"
                        :placeholder="secrets.qris_api_key ? 'Tersimpan — kosongkan bila tidak diganti' : 'qm_live_...'"
                        hint="Ambil di dashboard qris-muz, menu Proyek. Disimpan terenkripsi."
                        :error="err('qris_api_key')"
                    />
                    <UiInput
                        v-model="form.qris_webhook_secret"
                        label="Webhook secret"
                        type="password"
                        autocomplete="off"
                        :placeholder="secrets.qris_webhook_secret ? 'Tersimpan — kosongkan bila tidak diganti' : 'whsec_...'"
                        hint="Untuk memastikan webhook benar dari qris-muz."
                        :error="err('qris_webhook_secret')"
                    />
                    <UiInput v-model="form.qris_expiry_min" label="Masa berlaku tagihan (menit)" type="number" min="5" max="1440" required :error="err('qris_expiry_min')" />
                    <div>
                        <span class="mb-1.5 block text-sm font-medium text-ink">URL webhook (isi di proyek qris-muz)</span>
                        <div class="flex h-11 items-center gap-2 rounded-xl bg-slate-50 pr-1 pl-3.5 ring-1 ring-slate-200 ring-inset">
                            <code class="flex-1 truncate text-xs text-ink">{{ webhookUrl }}</code>
                            <UiButton variant="ghost" size="sm" class="text-primary" :icon="copied ? 'heroicons:check' : 'heroicons:clipboard-document'" @click="salinWebhook">{{ copied ? 'Tersalin' : 'Salin' }}</UiButton>
                        </div>
                    </div>
                </div>
                <UiButton variant="soft" size="sm" class="mt-4" icon="heroicons:signal" :loading="testing" :disabled="!qrisReady" :title="qrisReady ? '' : 'Simpan URL, API key, dan secret dulu'" @click="tesKoneksi">
                    Tes koneksi
                </UiButton>
            </section>

            <!-- Transfer manual -->
            <section class="rounded-2xl bg-white p-4 ring-1 ring-slate-200/70 sm:p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-ink">Transfer manual</h2>
                        <p class="mt-1 text-sm text-slate-500">Pembeli transfer lalu unggah bukti; admin/karyawan memverifikasi di halaman Pesanan.</p>
                    </div>
                    <UiCheckbox v-model="form.manual_enabled" label="Aktifkan" class="font-medium" />
                </div>
                <div class="mt-4 space-y-3">
                    <div v-for="(r, i) in form.manual_rekening" :key="i" class="grid gap-2 rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200/70 sm:grid-cols-[140px_1fr_1fr_auto] sm:bg-transparent sm:p-0 sm:ring-0">
                        <UiInput v-model="r.bank" required placeholder="BCA" :aria-label="`Bank ${i + 1}`" :error="err(`manual_rekening.${i}.bank`)" />
                        <UiInput v-model="r.nomor" required placeholder="Nomor rekening" :aria-label="`Nomor rekening ${i + 1}`" :error="err(`manual_rekening.${i}.nomor`)" />
                        <UiInput v-model="r.nama" required placeholder="Atas nama" :aria-label="`Atas nama ${i + 1}`" :error="err(`manual_rekening.${i}.nama`)" />
                        <UiButton variant="ghost" square icon="heroicons:trash" class="text-rose-600 hover:bg-rose-50 hover:text-rose-700" :label="`Hapus rekening ${i + 1}`" @click="hapusRekening(i)" />
                    </div>
                    <p v-if="err('manual_rekening')" class="text-xs text-rose-600">{{ err('manual_rekening') }}</p>
                    <UiButton v-if="form.manual_rekening.length < 5" variant="soft" size="sm" icon="heroicons:plus" @click="tambahRekening">Tambah rekening</UiButton>
                    <p v-if="form.manual_enabled && !form.manual_rekening.length" class="text-xs text-amber-700">Tambahkan minimal satu rekening agar transfer manual tampil di checkout.</p>
                </div>
                <UiInput v-model="form.manual_batas_jam" class="mt-4 max-w-xs" label="Batas waktu transfer (jam)" type="number" min="1" max="168" required :error="err('manual_batas_jam')" />
            </section>

            <!-- Saldo & reseller -->
            <section class="rounded-2xl bg-white p-4 ring-1 ring-slate-200/70 sm:p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-ink">Saldo &amp; harga reseller</h2>
                        <p class="mt-1 text-sm text-slate-500">Top-up saldo lewat QRIS. Pembeli dengan saldo saat ini &ge; batas mendapat harga reseller (dicek saat checkout, sebelum saldo dipotong).</p>
                    </div>
                    <UiCheckbox v-model="form.reseller_otomatis" label="Harga reseller berdasarkan saldo" class="font-medium" />
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <UiInput v-model="form.reseller_min_saldo" label="Minimal saldo reseller (Rp)" type="number" min="1000" step="1000" required :error="err('reseller_min_saldo')" />
                    <UiInput v-model="form.topup_min" label="Minimal top-up (Rp)" type="number" min="1000" step="1000" required :error="err('topup_min')" />
                    <UiInput v-model="form.topup_max" label="Maksimal top-up (Rp)" type="number" min="1000" step="1000" required :error="err('topup_max')" />
                </div>
                <p class="mt-3 text-xs text-slate-500">Top-up hanya tersedia bila QRIS aktif. Akun dengan role reseller (diatur di Pengguna) selalu mendapat harga reseller.</p>
            </section>

            <div class="sticky bottom-4 flex justify-end">
                <UiButton type="submit" size="lg" class="shadow-lg shadow-primary/20" icon="heroicons:check" :loading="form.processing">Simpan Pengaturan</UiButton>
            </div>
        </form>
    </div>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'

export default { layout: AdminLayout }
</script>
