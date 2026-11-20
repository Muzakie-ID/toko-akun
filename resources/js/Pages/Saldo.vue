<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import { rupiah } from '@/lib/format'

const props = defineProps({
    saldo: { type: Number, required: true },
    reseller: { type: Object, required: true },
    topup: { type: Object, required: true },
    menunggu: { type: Array, required: true },
    mutasi: { type: Object, required: true },
})

const form = useForm({ jumlah: '' })
const presets = [50000, 100000, 500000, 1000000]
const kurangKeReseller = computed(() => Math.max(props.reseller.batas - props.saldo, 0))
const persen = computed(() => Math.min(100, Math.round((props.saldo / Math.max(props.reseller.batas, 1)) * 100)))

const topupKeReseller = () => (form.jumlah = Math.max(kurangKeReseller.value, props.topup.min))
const kirim = () => form.post('/saldo/topup')
</script>

<template>
    <section class="mx-auto max-w-[1000px] px-4 py-8 lg:px-15">
        <Head title="Saldo" />
        <h1 class="text-xl font-bold text-ink sm:text-2xl">Saldo</h1>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl bg-ink p-5 text-white">
                <p class="text-sm text-white/70">Saldo saat ini</p>
                <p class="mt-1 text-3xl font-bold tracking-tight">{{ rupiah(saldo) }}</p>
                <p class="mt-3 text-xs text-white/70">Pakai saldo saat checkout: lunas seketika, akun langsung terkirim.</p>
            </div>

            <div class="rounded-2xl border p-5" :class="reseller.aktif ? 'border-amber-300 bg-amber-50' : 'border-slate-200'">
                <p class="flex items-center gap-1.5 text-sm font-semibold text-ink">
                    <Icon icon="heroicons:sparkles" class="h-4 w-4 text-amber-600" />
                    {{ reseller.aktif ? 'Harga reseller aktif' : 'Harga reseller' }}
                </p>
                <template v-if="reseller.permanen">
                    <p class="mt-2 text-sm text-slate-600">Akunmu berstatus reseller dari admin, harga reseller selalu berlaku.</p>
                </template>
                <template v-else-if="reseller.otomatis">
                    <p class="mt-2 text-sm text-slate-600">
                        Harga reseller berlaku selama saldo minimal <b class="text-ink">{{ rupiah(reseller.batas) }}</b> (dicek saat checkout, sebelum saldo dipotong).
                    </p>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-white ring-1 ring-amber-200" role="progressbar" :aria-valuenow="persen" aria-valuemin="0" aria-valuemax="100" aria-label="Saldo menuju batas reseller">
                        <div class="h-full rounded-full bg-amber-500" :style="{ width: `${persen}%` }" />
                    </div>
                    <p v-if="kurangKeReseller > 0" class="mt-2 text-xs text-slate-600">
                        Kurang {{ rupiah(kurangKeReseller) }}.
                        <button v-if="topup.tersedia" type="button" class="-my-2 inline-block py-2 font-semibold text-brand-600 underline" @click="topupKeReseller">Isi nominalnya</button>
                    </p>
                </template>
                <p v-else class="mt-2 text-sm text-slate-600">Harga reseller hanya untuk akun yang ditetapkan admin.</p>
            </div>
        </div>

        <!-- Top-up -->
        <form class="mt-4 rounded-2xl border border-slate-200 p-5" @submit.prevent="kirim">
            <h2 class="text-base font-semibold text-ink">Top-up via QRIS</h2>
            <template v-if="topup.tersedia">
                <div class="mt-3 flex flex-wrap gap-2">
                    <button v-for="p in presets" :key="p" type="button" class="h-10 rounded-full border px-4 text-sm font-medium transition" :class="Number(form.jumlah) === p ? 'border-ink bg-ink text-white' : 'border-slate-200 text-slate-600 hover:border-slate-400'" @click="form.jumlah = p">
                        {{ rupiah(p) }}
                    </button>
                </div>
                <div class="mt-3 flex flex-wrap items-start gap-3">
                    <div class="w-full max-w-xs">
                        <label for="jumlah" class="sr-only">Nominal top-up</label>
                        <input id="jumlah" v-model.number="form.jumlah" type="number" :min="topup.min" :max="topup.max" step="1000" required placeholder="Nominal lain" class="h-11 w-full rounded-xl border border-slate-300 px-3.5 text-sm focus:border-slate-400 focus:ring-2 focus:ring-slate-200 focus:outline-none" />
                        <p v-if="form.errors.jumlah" class="mt-1 text-xs text-rose-600">{{ form.errors.jumlah }}</p>
                        <p v-else class="mt-1 text-xs text-slate-500">{{ rupiah(topup.min) }} – {{ rupiah(topup.max) }}. Kode unik QRIS (Rp1–999) tidak masuk saldo.</p>
                    </div>
                    <button type="submit" :disabled="form.processing || !form.jumlah" class="h-11 rounded-xl bg-brand-600 px-6 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-40">
                        {{ form.processing ? 'Membuat tagihan…' : 'Top-up Sekarang' }}
                    </button>
                </div>
            </template>
            <p v-else class="mt-2 text-sm text-slate-500">Top-up sedang tidak tersedia. Coba lagi nanti.</p>

            <ul v-if="menunggu.length" class="mt-4 space-y-2">
                <li v-for="t in menunggu" :key="t.invoice">
                    <Link :href="`/saldo/topup/${t.invoice}`" class="flex items-center justify-between rounded-xl bg-amber-50 px-4 py-2.5 text-sm text-amber-900 ring-1 ring-amber-200 hover:bg-amber-100">
                        <span>Top-up {{ rupiah(t.total) }} menunggu pembayaran (s/d {{ t.expires_label }})</span>
                        <span class="font-semibold">Bayar &rarr;</span>
                    </Link>
                </li>
            </ul>
        </form>

        <!-- Riwayat -->
        <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
            <h2 class="px-5 pt-5 text-base font-semibold text-ink">Riwayat saldo</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-500">
                        <tr>
                            <th class="px-5 py-2.5 font-medium">Tanggal</th>
                            <th class="px-5 py-2.5 font-medium">Keterangan</th>
                            <th class="px-5 py-2.5 text-right font-medium">Jumlah</th>
                            <th class="px-5 py-2.5 text-right font-medium">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="m in mutasi.data" :key="m.id">
                            <td class="px-5 py-3 whitespace-nowrap text-slate-500">{{ m.tanggal }}</td>
                            <td class="px-5 py-3"><span class="text-ink">{{ m.keterangan }}</span> <span class="text-xs text-slate-400">· {{ m.tipe }}</span></td>
                            <td class="px-5 py-3 text-right font-semibold whitespace-nowrap" :class="m.jumlah >= 0 ? 'text-emerald-700' : 'text-rose-600'">{{ m.jumlah >= 0 ? '+' : '−' }}{{ rupiah(Math.abs(m.jumlah)) }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap text-ink">{{ rupiah(m.saldo_akhir) }}</td>
                        </tr>
                        <tr v-if="!mutasi.data.length"><td colspan="4" class="px-5 py-10 text-center text-slate-500">Belum ada riwayat saldo.</td></tr>
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="mutasi" />
        </div>
    </section>
</template>

<script>
import PublicLayout from '@/Layouts/PublicLayout.vue'

export default { layout: PublicLayout }
</script>
