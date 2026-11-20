<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

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
            <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200/70">
                <h2 class="text-base font-semibold text-ink">Toko</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="text-sm font-medium text-ink" for="s-nama">Nama toko</label>
                        <input id="s-nama" v-model="form.store_nama" type="text" required class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                        <p v-if="err('store_nama')" class="mt-1 text-xs text-rose-600">{{ err('store_nama') }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-ink" for="s-wa">WhatsApp admin</label>
                        <input id="s-wa" v-model="form.store_whatsapp" type="tel" placeholder="62812..." class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                        <p v-if="err('store_whatsapp')" class="mt-1 text-xs text-rose-600">{{ err('store_whatsapp') }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-ink" for="s-target">Target penjualan / bulan (Rp)</label>
                        <input id="s-target" v-model="form.store_target_bulanan" type="number" min="0" required class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                        <p v-if="err('store_target_bulanan')" class="mt-1 text-xs text-rose-600">{{ err('store_target_bulanan') }}</p>
                    </div>
                </div>
            </section>

            <!-- QRIS qris-muz -->
            <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200/70">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="flex items-center gap-2 text-base font-semibold text-ink">
                            QRIS otomatis (qris-muz)
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="qrisReady ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'">{{ qrisReady ? 'Aktif' : 'Belum aktif' }}</span>
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">Tagihan dibuat di gateway qris-muz; pesanan lunas dan akun terkirim otomatis lewat webhook.</p>
                    </div>
                    <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-ink">
                        <input v-model="form.qris_enabled" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-accent-600" />
                        Aktifkan
                    </label>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium text-ink" for="q-url">URL API gateway</label>
                        <input id="q-url" v-model="form.qris_base_url" type="url" placeholder="https://api.qris-muz.domainmu.id" class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                        <p v-if="err('qris_base_url')" class="mt-1 text-xs text-rose-600">{{ err('qris_base_url') }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-ink" for="q-key">API key proyek</label>
                        <input id="q-key" v-model="form.qris_api_key" type="password" autocomplete="off" :placeholder="secrets.qris_api_key ? 'Tersimpan — kosongkan bila tidak diganti' : 'qm_live_...'" class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                        <p class="mt-1 text-xs text-slate-500">Dari dashboard qris-muz → Proyek. Disimpan terenkripsi.</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-ink" for="q-secret">Webhook secret</label>
                        <input id="q-secret" v-model="form.qris_webhook_secret" type="password" autocomplete="off" :placeholder="secrets.qris_webhook_secret ? 'Tersimpan — kosongkan bila tidak diganti' : 'whsec_...'" class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                        <p class="mt-1 text-xs text-slate-500">Untuk memastikan webhook benar dari qris-muz.</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-ink" for="q-exp">Masa berlaku tagihan (menit)</label>
                        <input id="q-exp" v-model="form.qris_expiry_min" type="number" min="5" max="1440" required class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                        <p v-if="err('qris_expiry_min')" class="mt-1 text-xs text-rose-600">{{ err('qris_expiry_min') }}</p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-ink">URL webhook (isi di proyek qris-muz)</span>
                        <div class="mt-1.5 flex h-10 items-center gap-2 rounded-xl bg-slate-50 px-3 ring-1 ring-slate-200">
                            <code class="flex-1 truncate text-xs text-ink">{{ webhookUrl }}</code>
                            <button type="button" class="text-xs font-semibold text-accent-600" @click="salinWebhook">{{ copied ? 'Tersalin' : 'Salin' }}</button>
                        </div>
                    </div>
                </div>
                <button type="button" :disabled="testing || !qrisReady" class="mt-4 inline-flex h-9 items-center gap-2 rounded-lg px-3 text-sm font-medium text-accent-700 ring-1 ring-accent-200 hover:bg-accent-50 disabled:opacity-40" :title="qrisReady ? '' : 'Simpan URL, API key, dan secret dulu'" @click="tesKoneksi">
                    <Icon icon="heroicons:signal" class="h-4 w-4" /> {{ testing ? 'Mengecek…' : 'Tes koneksi' }}
                </button>
            </section>

            <!-- Transfer manual -->
            <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200/70">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-ink">Transfer manual</h2>
                        <p class="mt-1 text-sm text-slate-500">Pembeli transfer lalu unggah bukti; admin/karyawan memverifikasi di halaman Pesanan.</p>
                    </div>
                    <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-ink">
                        <input v-model="form.manual_enabled" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-accent-600" />
                        Aktifkan
                    </label>
                </div>
                <div class="mt-4 space-y-2">
                    <div v-for="(r, i) in form.manual_rekening" :key="i" class="grid gap-2 sm:grid-cols-[140px_1fr_1fr_auto]">
                        <input v-model="r.bank" type="text" required placeholder="BCA" :aria-label="`Bank ${i + 1}`" class="h-10 rounded-xl px-3 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                        <input v-model="r.nomor" type="text" required placeholder="Nomor rekening" :aria-label="`Nomor rekening ${i + 1}`" class="h-10 rounded-xl px-3 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                        <input v-model="r.nama" type="text" required placeholder="Atas nama" :aria-label="`Atas nama ${i + 1}`" class="h-10 rounded-xl px-3 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                        <button type="button" class="h-10 rounded-xl px-3 text-sm text-rose-600 hover:bg-rose-50" :aria-label="`Hapus rekening ${i + 1}`" @click="hapusRekening(i)">
                            <Icon icon="heroicons:trash" class="h-4 w-4" />
                        </button>
                    </div>
                    <p v-if="err('manual_rekening')" class="text-xs text-rose-600">{{ err('manual_rekening') }}</p>
                    <button v-if="form.manual_rekening.length < 5" type="button" class="inline-flex h-9 items-center gap-1.5 rounded-lg px-3 text-sm font-medium text-accent-700 hover:bg-accent-50" @click="tambahRekening">
                        <Icon icon="heroicons:plus" class="h-4 w-4" /> Tambah rekening
                    </button>
                    <p v-if="form.manual_enabled && !form.manual_rekening.length" class="text-xs text-amber-700">Tambahkan minimal satu rekening agar transfer manual tampil di checkout.</p>
                </div>
                <div class="mt-4 max-w-xs">
                    <label class="text-sm font-medium text-ink" for="m-batas">Batas waktu transfer (jam)</label>
                    <input id="m-batas" v-model="form.manual_batas_jam" type="number" min="1" max="168" required class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                </div>
            </section>

            <!-- Saldo & reseller -->
            <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200/70">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-ink">Saldo &amp; harga reseller</h2>
                        <p class="mt-1 text-sm text-slate-500">Top-up saldo lewat QRIS. Pembeli dengan saldo saat ini &ge; batas mendapat harga reseller (dicek saat checkout, sebelum saldo dipotong).</p>
                    </div>
                    <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-ink">
                        <input v-model="form.reseller_otomatis" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-accent-600" />
                        Harga reseller berdasarkan saldo
                    </label>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="text-sm font-medium text-ink" for="r-min">Minimal saldo reseller (Rp)</label>
                        <input id="r-min" v-model="form.reseller_min_saldo" type="number" min="1000" step="1000" required class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                        <p v-if="err('reseller_min_saldo')" class="mt-1 text-xs text-rose-600">{{ err('reseller_min_saldo') }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-ink" for="t-min">Minimal top-up (Rp)</label>
                        <input id="t-min" v-model="form.topup_min" type="number" min="1000" step="1000" required class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                        <p v-if="err('topup_min')" class="mt-1 text-xs text-rose-600">{{ err('topup_min') }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-ink" for="t-max">Maksimal top-up (Rp)</label>
                        <input id="t-max" v-model="form.topup_max" type="number" min="1000" step="1000" required class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                        <p v-if="err('topup_max')" class="mt-1 text-xs text-rose-600">{{ err('topup_max') }}</p>
                    </div>
                </div>
                <p class="mt-3 text-xs text-slate-500">Top-up hanya tersedia bila QRIS aktif. Akun dengan role reseller (diatur di Pengguna) selalu mendapat harga reseller.</p>
            </section>

            <div class="sticky bottom-4 flex justify-end">
                <button type="submit" :disabled="form.processing" class="h-11 rounded-xl bg-accent-600 px-6 text-sm font-semibold text-white shadow-lg shadow-accent-600/20 hover:bg-accent-700 disabled:opacity-50">
                    {{ form.processing ? 'Menyimpan…' : 'Simpan Pengaturan' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'

export default { layout: AdminLayout }
</script>
