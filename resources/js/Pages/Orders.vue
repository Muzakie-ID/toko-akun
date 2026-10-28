<script setup>
import { ref } from 'vue'

// ponytail: mock pesanan — ganti dari Controller (order + kredensial live dari stocks) saat backend hidup
const pesanan = ref([
    {
        id: 'INV-20260115-001',
        tanggal: '15 Jan 2026',
        status_label: 'Selesai',
        status_tone: 'emerald',
        total: 45000,
        items: [
            {
                nama: 'Netflix Premium',
                durasi_label: '1 Bulan',
                qty: 1,
                kredensial: { email: 'netflix.acc01@haus.id', password: 'Zn8*kQ2m' },
            },
        ],
    },
    {
        id: 'INV-20260120-004',
        tanggal: '20 Jan 2026',
        status_label: 'Menunggu Verifikasi',
        status_tone: 'amber',
        total: 20000,
        items: [
            {
                nama: 'Canva Pro',
                durasi_label: '1 Tahun',
                qty: 2,
                kredensial: null,
            },
        ],
    },
    {
        id: 'INV-20260122-009',
        tanggal: '22 Jan 2026',
        status_label: 'Diproses',
        status_tone: 'brand',
        total: 25000,
        items: [
            {
                nama: 'Spotify Premium',
                durasi_label: '1 Bulan',
                qty: 1,
                kredensial: { email: 'spotify.acc07@haus.id', password: 'Hr5!pL9x' },
            },
        ],
    },
])

const terbuka = ref(null)
const toggle = (id) => (terbuka.value = terbuka.value === id ? null : id)

const toneClass = {
    emerald: 'bg-emerald-50 text-emerald-700',
    amber: 'bg-amber-50 text-amber-700',
    brand: 'bg-brand-50 text-brand-700',
}

const rupiah = (n) => 'Rp ' + Number(n).toLocaleString('id-ID')
const salin = async (teks) => {
    try {
        await navigator.clipboard.writeText(teks)
    } catch {
        /* clipboard tak tersedia — abaikan */
    }
}
</script>

<template>
    <section class="mx-auto max-w-[1320px] px-4 py-8 lg:px-15">
        <h1 class="text-2xl font-bold text-ink">Pesanan Saya</h1>

        <div class="mt-6 space-y-4">
            <article v-for="p in pesanan" :key="p.id" class="overflow-hidden rounded-2xl border border-slate-200">
                <!-- Header order -->
                <header class="flex flex-wrap items-center gap-x-6 gap-y-2 border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
                    <div>
                        <p class="text-xs text-slate-500">No. Invoice</p>
                        <p class="text-sm font-semibold text-ink">{{ p.id }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Tanggal</p>
                        <p class="text-sm font-medium text-ink">{{ p.tanggal }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Total</p>
                        <p class="text-sm font-bold text-ink">{{ rupiah(p.total) }}</p>
                    </div>
                    <span class="ml-auto inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold" :class="toneClass[p.status_tone]">
                        {{ p.status_label }}
                    </span>
                </header>

                <!-- Item -->
                <div class="divide-y divide-slate-100">
                    <div v-for="item in p.items" :key="item.nama" class="px-5 py-4">
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-base font-black text-brand-600">
                                {{ item.nama.charAt(0) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-ink">{{ item.nama }}</p>
                                <p class="text-xs text-slate-500">{{ item.jenis_akun_label || '' }}{{ item.jenis_akun_label ? ' &bull; ' : '' }}{{ item.durasi_label }} &times; {{ item.qty }}</p>
                            </div>
                            <button
                                v-if="item.kredensial"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-brand-700"
                                @click="toggle(p.id)"
                            >
                                {{ terbuka === p.id ? 'Sembunyikan' : 'Lihat Kredensial' }}
                            </button>
                            <span v-else class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-400">
                                <Icon icon="heroicons:clock" class="h-3.5 w-3.5" />
                                Menunggu verifikasi pembayaran
                            </span>
                        </div>

                        <!-- Panel kredensial -->
                        <div v-if="item.kredensial && terbuka === p.id" class="mt-3 rounded-xl bg-slate-50 p-4">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <p class="text-xs font-medium text-slate-500">Email</p>
                                    <div class="mt-1 flex items-center gap-2">
                                        <code class="truncate rounded-md bg-white px-2.5 py-1.5 text-sm text-ink ring-1 ring-slate-200">{{ item.kredensial.email }}</code>
                                        <button type="button" class="text-xs font-semibold text-brand-600 hover:text-brand-700" @click="salin(item.kredensial.email)">Salin</button>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-slate-500">Password</p>
                                    <div class="mt-1 flex items-center gap-2">
                                        <code class="rounded-md bg-white px-2.5 py-1.5 text-sm text-ink ring-1 ring-slate-200">{{ item.kredensial.password }}</code>
                                        <button type="button" class="text-xs font-semibold text-brand-600 hover:text-brand-700" @click="salin(item.kredensial.password)">Salin</button>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 text-xs text-slate-500">
                                Simpan kredensial ini. Ada masalah akun? Klaim garansi lewat admin &mdash; kredensial baru otomatis muncul di halaman ini.
                            </p>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>

<script>
import PublicLayout from '@/Layouts/PublicLayout.vue'

export default { layout: PublicLayout }
</script>
