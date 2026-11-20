<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { brandColors } from '@/lib/brand'
import { rupiah } from '@/lib/format'

const props = defineProps({
    items: { type: Array, required: true },
    metode: { type: Array, required: true },
    saldo: { type: Number, default: 0 },
})

const hargaReseller = computed(() => usePage().props.auth?.user?.harga_reseller)
const subtotal = computed(() => props.items.reduce((t, i) => t + i.harga * i.qty, 0))
const saldoCukup = computed(() => props.saldo >= subtotal.value)
const isDisabled = (m) => m.value === 'saldo' && !saldoCukup.value

const form = useForm({ metode_bayar: (props.metode.find((m) => !isDisabled(m)) ?? {}).value ?? '' })
const jumlahAkun = computed(() => props.items.reduce((t, i) => t + i.qty, 0))
const adaMasalahStok = computed(() => props.items.some((i) => i.qty > i.stok))

const ubahQty = (item, qty) => {
    if (qty < 1 || qty > Math.max(item.stok, 1)) return
    router.patch(`/keranjang/${item.id}`, { qty }, { preserveScroll: true, preserveState: true })
}
const hapus = (item) => router.delete(`/keranjang/${item.id}`, { preserveScroll: true })
const checkout = () => form.post('/checkout')
</script>

<template>
    <section class="mx-auto max-w-[1320px] px-4 py-8 lg:px-15">
        <Head title="Keranjang" />
        <h1 class="text-2xl font-bold text-ink">Keranjang &amp; Checkout</h1>

        <div v-if="items.length" class="mt-6 grid gap-8 lg:grid-cols-[1fr_380px]">
            <div class="space-y-3">
                <article v-for="item in items" :key="item.id" class="flex flex-wrap items-center gap-4 rounded-2xl border p-4" :class="item.qty > item.stok ? 'border-rose-200 bg-rose-50/40' : 'border-slate-200'">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl text-xl font-black" :style="{ background: brandColors(item.nama).bg, color: brandColors(item.nama).fg }">
                        <img v-if="item.logo_url" :src="item.logo_url" alt="" class="h-8 w-8 object-contain" />
                        <template v-else>{{ item.nama.charAt(0) }}</template>
                    </div>
                    <div class="min-w-0 flex-1">
                        <Link :href="`/produk/${item.slug}`" class="text-sm font-semibold text-ink hover:underline">{{ item.nama }}</Link>
                        <p class="mt-0.5 text-xs text-slate-500">{{ item.jenis_akun_label }} &bull; {{ item.durasi_label }}</p>
                        <p class="mt-1 text-sm font-bold text-ink">{{ rupiah(item.harga) }}</p>
                        <p v-if="item.qty > item.stok" class="mt-1 text-xs font-medium text-rose-600">{{ item.stok ? `Stok tinggal ${item.stok}, kurangi jumlahnya.` : 'Stok habis, hapus dari keranjang.' }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="h-8 w-8 rounded-lg border border-slate-200 text-slate-600 disabled:opacity-40" :disabled="item.qty <= 1" :aria-label="`Kurangi ${item.nama}`" @click="ubahQty(item, item.qty - 1)">&minus;</button>
                        <span class="w-8 text-center text-sm font-semibold text-ink" aria-live="polite">{{ item.qty }}</span>
                        <button type="button" class="h-8 w-8 rounded-lg border border-slate-200 text-slate-600 disabled:opacity-40" :disabled="item.qty >= item.stok" :aria-label="`Tambah ${item.nama}`" @click="ubahQty(item, item.qty + 1)">+</button>
                    </div>
                    <div class="w-24 text-right text-sm font-bold text-ink">{{ rupiah(item.harga * item.qty) }}</div>
                    <button type="button" class="text-slate-400 transition hover:text-rose-500" :aria-label="`Hapus ${item.nama}`" @click="hapus(item)">
                        <Icon icon="heroicons:trash" class="h-5 w-5" />
                    </button>
                </article>
                <p class="text-xs text-slate-500">Setiap akun dikirim terpisah. Beli 2 = dapat 2 akun/slot berbeda.</p>
            </div>

            <form class="space-y-4" @submit.prevent="checkout">
                <div class="rounded-2xl border border-slate-200 p-5">
                    <h2 class="text-sm font-semibold text-ink">Metode Pembayaran</h2>
                    <div v-if="metode.length" class="mt-3 space-y-2" role="radiogroup">
                        <label v-for="m in metode" :key="m.value" class="flex gap-3 rounded-xl border p-3 transition" :class="[form.metode_bayar === m.value ? 'border-ink bg-slate-50 ring-1 ring-ink' : 'border-slate-200 hover:border-slate-400', isDisabled(m) ? 'cursor-not-allowed opacity-60' : 'cursor-pointer']">
                            <input v-model="form.metode_bayar" type="radio" name="metode" :value="m.value" :disabled="isDisabled(m)" class="mt-0.5 h-4 w-4 text-brand-600" />
                            <span>
                                <span class="flex items-center gap-1.5 text-sm font-semibold text-ink">
                                    <Icon :icon="{ qris: 'heroicons:qr-code', saldo: 'heroicons:wallet' }[m.value] ?? 'heroicons:building-library'" class="h-4 w-4" /> {{ m.label }}
                                    <span v-if="m.value === 'saldo'" class="font-normal text-slate-500">· {{ rupiah(saldo) }}</span>
                                </span>
                                <span class="mt-0.5 block text-xs leading-5 text-slate-500">{{ m.keterangan }}</span>
                                <span v-if="isDisabled(m)" class="mt-1 block text-xs font-medium text-rose-600">
                                    Kurang {{ rupiah(subtotal - saldo) }}. <Link href="/saldo" class="underline">Top-up</Link>
                                </span>
                            </span>
                        </label>
                    </div>
                    <p v-else class="mt-2 text-sm text-rose-600">Pembayaran sedang tidak tersedia. Hubungi admin.</p>
                    <p v-if="form.errors.metode_bayar" class="mt-2 text-xs text-rose-600">{{ form.errors.metode_bayar }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200 p-5">
                    <h2 class="text-sm font-semibold text-ink">Ringkasan</h2>
                    <p v-if="hargaReseller" class="mt-2 flex items-center gap-1.5 rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-800">
                        <Icon icon="heroicons:sparkles" class="h-3.5 w-3.5" /> Harga reseller diterapkan
                    </p>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between text-slate-600"><dt>Subtotal ({{ jumlahAkun }} akun)</dt><dd class="font-medium text-ink">{{ rupiah(subtotal) }}</dd></div>
                        <div v-if="form.metode_bayar === 'qris'" class="flex justify-between text-xs text-slate-500"><dt>Kode unik QRIS</dt><dd>+ Rp 1–999</dd></div>
                        <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-ink"><dt>Total</dt><dd>{{ rupiah(subtotal) }}</dd></div>
                    </dl>
                    <ul v-if="form.errors.checkout" class="mt-3 space-y-1 text-xs text-rose-600">
                        <li>{{ form.errors.checkout }}</li>
                    </ul>
                    <button type="submit" :disabled="form.processing || !metode.length || adaMasalahStok" class="mt-4 h-11 w-full rounded-xl bg-brand-600 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-40">
                        {{ form.processing ? 'Membuat pesanan…' : 'Bayar Sekarang' }}
                    </button>
                </div>
            </form>
        </div>

        <div v-else class="mt-16 flex flex-col items-center text-center">
            <Icon icon="heroicons:shopping-cart" class="h-12 w-12 text-slate-300" />
            <p class="mt-4 text-sm font-semibold text-ink">Keranjang masih kosong</p>
            <p class="mt-1 text-sm text-slate-500">Yuk pilih produk premium favoritmu.</p>
            <Link href="/" class="mt-4 inline-flex h-10 items-center rounded-xl bg-brand-600 px-5 text-sm font-semibold text-white hover:bg-brand-700">Lihat Produk</Link>
        </div>
    </section>
</template>

<script>
import PublicLayout from '@/Layouts/PublicLayout.vue'

export default { layout: PublicLayout }
</script>
