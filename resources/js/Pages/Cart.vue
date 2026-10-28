<script setup>
import { computed, ref } from 'vue'

// ponytail: mock keranjang & checkout — state lokal, ganti ke Inertia form saat backend hidup
const items = ref([
    { id: 1, nama: 'Netflix Premium', durasi_label: '1 Bulan', jenis_akun_label: 'Personal', harga: 25000, qty: 1 },
    { id: 5, nama: 'Canva Pro', durasi_label: '1 Tahun', jenis_akun_label: 'Sharing', harga: 10000, qty: 2 },
])

const buktiBayar = ref(null)
const fileName = ref('')

const subtotal = computed(() => items.value.reduce((t, i) => t + i.harga * i.qty, 0))
const total = computed(() => subtotal.value)

const rupiah = (n) => 'Rp ' + Number(n).toLocaleString('id-ID')

const tambah = (item) => item.qty++
const kurang = (item) => {
    if (item.qty > 1) item.qty--
}
const hapus = (item) => {
    items.value = items.value.filter((i) => i.id !== item.id)
}
const pilihFile = (e) => {
    const f = e.target.files?.[0]
    if (f) fileName.value = f.name
}
</script>

<template>
    <section class="mx-auto max-w-[1320px] px-4 py-8 lg:px-15">
        <h1 class="text-2xl font-bold text-ink">Keranjang &amp; Checkout</h1>

        <div v-if="items.length" class="mt-6 grid gap-8 lg:grid-cols-[1fr_380px]">
            <!-- Item list -->
            <div class="space-y-3">
                <article
                    v-for="item in items"
                    :key="item.id"
                    class="flex flex-wrap items-center gap-4 rounded-2xl border border-slate-200 p-4"
                >
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-xl font-black text-brand-600">
                        {{ item.nama.charAt(0) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-sm font-semibold text-ink">{{ item.nama }}</h2>
                        <p class="mt-0.5 text-xs text-slate-500">{{ item.jenis_akun_label }} &bull; {{ item.durasi_label }}</p>
                        <p class="mt-1 text-sm font-bold text-brand-600">{{ rupiah(item.harga) }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="h-8 w-8 rounded-lg border border-slate-200 text-slate-600 hover:border-brand-400 hover:text-brand-600" :aria-label="`Kurangi ${item.nama}`" @click="kurang(item)">&minus;</button>
                        <span class="w-8 text-center text-sm font-semibold text-ink" aria-live="polite">{{ item.qty }}</span>
                        <button type="button" class="h-8 w-8 rounded-lg border border-slate-200 text-slate-600 hover:border-brand-400 hover:text-brand-600" :aria-label="`Tambah ${item.nama}`" @click="tambah(item)">+</button>
                    </div>
                    <div class="w-24 text-right text-sm font-bold text-ink">{{ rupiah(item.harga * item.qty) }}</div>
                    <button type="button" class="text-slate-400 transition hover:text-rose-500" :aria-label="`Hapus ${item.nama}`" @click="hapus(item)">
                        <Icon icon="heroicons:trash" class="h-5 w-5" />
                    </button>
                </article>
            </div>

            <!-- Ringkasan + pembayaran -->
            <aside class="space-y-4">
                <div class="rounded-2xl border border-slate-200 p-5">
                    <h2 class="text-sm font-semibold text-ink">Ringkasan Pesanan</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between text-slate-600">
                            <dt>Subtotal ({{ items.reduce((t, i) => t + i.qty, 0) }} item)</dt>
                            <dd class="font-medium text-ink">{{ rupiah(subtotal) }}</dd>
                        </div>
                        <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-ink">
                            <dt>Total</dt>
                            <dd>{{ rupiah(total) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-slate-200 p-5">
                    <h2 class="text-sm font-semibold text-ink">Pembayaran Manual</h2>
                    <p class="mt-1.5 text-xs leading-5 text-slate-500">
                        Transfer ke <span class="font-semibold text-ink">BCA 1234567890 a.n. Akunhaus</span>,
                        lalu unggah bukti pembayaran. Kredensial dikirim otomatis setelah diverifikasi.
                    </p>
                    <label
                        class="mt-3 flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 px-4 py-6 text-center transition hover:border-brand-400 hover:bg-brand-50/50"
                    >
                        <Icon icon="heroicons:arrow-up-tray" class="h-6 w-6 text-slate-400" />
                        <span class="text-sm font-medium text-brand-600">Unggah Bukti Bayar</span>
                        <span v-if="fileName" class="text-xs text-slate-500">{{ fileName }}</span>
                        <span v-else class="text-xs text-slate-400">PNG, JPG maks. 2MB</span>
                        <input type="file" accept="image/png,image/jpeg" class="sr-only" @change="pilihFile" />
                    </label>
                    <button
                        type="button"
                        :disabled="!buktiBayar && !fileName"
                        class="mt-3 h-11 w-full rounded-xl bg-brand-600 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        Kirim Pesanan
                    </button>
                </div>
            </aside>
        </div>

        <!-- Kosong -->
        <div v-else class="mt-16 flex flex-col items-center text-center">
            <Icon icon="heroicons:shopping-cart" class="h-12 w-12 text-slate-300" />
            <p class="mt-4 text-sm font-semibold text-ink">Keranjang masih kosong</p>
            <p class="mt-1 text-sm text-slate-500">Yuk pilih produk premium favoritmu.</p>
            <a href="/" class="mt-4 inline-flex h-10 items-center rounded-xl bg-brand-600 px-5 text-sm font-semibold text-white hover:bg-brand-700">
                Lihat Produk
            </a>
        </div>
    </section>
</template>

<script>
import PublicLayout from '@/Layouts/PublicLayout.vue'

export default { layout: PublicLayout }
</script>
