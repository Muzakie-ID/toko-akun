<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import StatusBadge from '@/Components/Admin/StatusBadge.vue'
import { UiButton } from '@/Components/ui'
import { rupiah } from '@/lib/format'

const props = defineProps({
    topup: { type: Object, required: true },
    saldo: { type: Number, required: true },
})

const menunggu = computed(() => props.topup.status === 'menunggu_bayar')
const now = ref(Date.now())
let timer = null
let poller = null
onMounted(() => {
    timer = setInterval(() => (now.value = Date.now()), 1000)
    poller = setInterval(() => menunggu.value && router.reload({ only: ['topup', 'saldo', 'auth'] }), 5000)
})
onBeforeUnmount(() => {
    clearInterval(timer)
    clearInterval(poller)
})

const sisa = computed(() => {
    if (!props.topup.expires_at) return null
    const ms = new Date(props.topup.expires_at).getTime() - now.value
    if (ms <= 0) return 'Waktu habis'
    return `${String(Math.floor(ms / 60_000)).padStart(2, '0')}:${String(Math.floor((ms % 60_000) / 1000)).padStart(2, '0')}`
})
</script>

<template>
    <section class="mx-auto max-w-[560px] px-4 py-8">
        <Head :title="`Top-up ${topup.invoice}`" />
        <Link href="/saldo" class="-my-2 inline-flex items-center gap-1 py-2 text-sm text-slate-500 hover:text-ink">
            <Icon icon="heroicons:arrow-left" class="h-4 w-4" /> Saldo
        </Link>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="text-xl font-bold text-ink sm:text-2xl">Top-up {{ rupiah(topup.jumlah) }}</h1>
            <StatusBadge :tone="topup.status_tone" :label="topup.status_label" />
        </div>
        <p class="mt-1 text-sm text-slate-500">{{ topup.invoice }}</p>

        <div v-if="menunggu" class="mt-6 rounded-2xl border border-slate-200 p-5 text-center">
            <h2 class="text-base font-semibold text-ink">Scan QRIS untuk membayar</h2>
            <p class="mt-1 text-sm text-slate-500">Bayar <b class="text-ink">tepat</b> sesuai nominal agar saldo masuk otomatis.</p>
            <img v-if="topup.qr_image_url" :src="topup.qr_image_url" alt="Kode QRIS top-up" class="mx-auto mt-4 h-64 w-64 rounded-xl border border-slate-200 bg-white p-2" />
            <p class="mt-4 text-3xl font-bold tracking-tight text-ink">{{ rupiah(topup.total) }}</p>
            <p class="text-xs text-slate-500">{{ rupiah(topup.jumlah) }} + kode unik {{ rupiah(topup.kode_unik) }}</p>
            <p v-if="sisa" class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-sm font-medium text-amber-800">
                <Icon icon="heroicons:clock" class="h-4 w-4" /> Sisa waktu {{ sisa }}
            </p>
            <div class="mt-4 flex flex-col items-center gap-2">
                <UiButton v-if="topup.checkout_url" :href="topup.checkout_url" external icon-right="heroicons:arrow-top-right-on-square">Buka halaman bayar</UiButton>
                <p class="flex items-center gap-1.5 text-xs text-slate-500"><span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500" /> Status diperbarui otomatis</p>
            </div>
        </div>

        <div v-else-if="topup.status === 'lunas'" class="mt-6 rounded-2xl bg-emerald-50 p-6 text-center ring-1 ring-emerald-200">
            <Icon icon="heroicons:check-circle" class="mx-auto h-10 w-10 text-emerald-600" />
            <p class="mt-2 text-base font-semibold text-emerald-900">Saldo {{ rupiah(topup.jumlah) }} sudah masuk</p>
            <p class="mt-1 text-sm text-emerald-800">Saldo sekarang {{ rupiah(saldo) }}</p>
            <UiButton href="/" class="mt-4">Belanja Sekarang</UiButton>
        </div>

        <div v-else class="mt-6 rounded-2xl bg-slate-50 p-5 text-sm text-slate-700 ring-1 ring-slate-200">
            Tagihan top-up ini sudah tidak berlaku. Buat top-up baru dari halaman <Link href="/saldo" class="font-semibold underline">Saldo</Link>.
            Sudah terlanjur bayar? Saldo tetap masuk otomatis begitu pembayaran terdeteksi.
        </div>
    </section>
</template>

<script>
import PublicLayout from '@/Layouts/PublicLayout.vue'

export default { layout: PublicLayout }
</script>
