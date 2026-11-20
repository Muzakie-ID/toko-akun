<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import { rupiah } from '@/lib/format'

const props = defineProps({
    sale: { type: Object, default: null },
    variants: { type: Array, required: true },
})

const pad = (n) => String(n).padStart(2, '0')
const local = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
const inHours = (h) => { const d = new Date(); d.setMinutes(0, 0, 0); d.setHours(d.getHours() + h); return local(d) }

const form = useForm({
    nama: props.sale?.nama ?? 'Flash Sale',
    mulai_at: props.sale?.mulai_at ?? inHours(1),
    selesai_at: props.sale?.selesai_at ?? inHours(25),
    is_aktif: props.sale?.is_aktif ?? true,
    items: props.sale?.items.map((i) => ({ ...i })) ?? [],
})

const variantById = computed(() => Object.fromEntries(props.variants.map((v) => [v.id, v])))
const tambah = () => form.items.push({ product_variant_id: '', harga_sale: '', kuota: '', terjual: 0 })
const hapus = (i) => form.items.splice(i, 1)
const diskon = (item) => {
    const v = variantById.value[item.product_variant_id]
    return v && item.harga_sale !== '' && Number(item.harga_sale) < v.harga ? Math.round((1 - item.harga_sale / v.harga) * 100) : null
}

const submit = () => {
    const transform = (data) => ({ ...data, items: data.items.map(({ terjual, ...i }) => ({ ...i, kuota: i.kuota === '' ? null : i.kuota })) })
    if (props.sale) form.transform(transform).put(`/admin/flash-sale/${props.sale.id}`, { preserveScroll: true })
    else form.transform(transform).post('/admin/flash-sale', { preserveState: 'errors' })
}
const hapusSale = () => confirm('Hapus flash sale ini?') && router.delete(`/admin/flash-sale/${props.sale.id}`)
const err = (k) => form.errors[k]
</script>

<template>
    <div class="mx-auto max-w-4xl">
        <Head :title="sale ? sale.nama : 'Buat Flash Sale'" />
        <Link href="/admin/flash-sale" class="-my-2 inline-flex items-center gap-1 py-2 text-sm text-slate-500 hover:text-ink">
            <Icon icon="heroicons:arrow-left" class="h-4 w-4" /> Semua flash sale
        </Link>
        <h1 class="mt-2 text-xl font-semibold tracking-tight text-ink sm:text-2xl">{{ sale ? sale.nama : 'Buat Flash Sale' }}</h1>

        <form class="mt-5 space-y-4" @submit.prevent="submit">
            <section class="grid gap-4 rounded-2xl bg-white p-4 ring-1 ring-slate-200/70 sm:grid-cols-3 sm:p-5">
                <div class="sm:col-span-3">
                    <label class="text-sm font-medium text-ink" for="fs-nama">Nama</label>
                    <input id="fs-nama" v-model="form.nama" type="text" required maxlength="80" placeholder="Flash Sale Gajian" class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                    <p v-if="err('nama')" class="mt-1 text-xs text-rose-600">{{ err('nama') }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-ink" for="fs-mulai">Mulai</label>
                    <input id="fs-mulai" v-model="form.mulai_at" type="datetime-local" required class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                    <p v-if="err('mulai_at')" class="mt-1 text-xs text-rose-600">{{ err('mulai_at') }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-ink" for="fs-selesai">Selesai</label>
                    <input id="fs-selesai" v-model="form.selesai_at" type="datetime-local" required class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                    <p v-if="err('selesai_at')" class="mt-1 text-xs text-rose-600">{{ err('selesai_at') }}</p>
                </div>
                <label class="flex items-center gap-2 text-sm font-medium text-ink sm:mt-7">
                    <input v-model="form.is_aktif" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-accent-600" />
                    Aktif
                </label>
                <p class="text-xs text-slate-500 sm:col-span-3">Waktu dalam WIB. Banner &amp; hitung mundur muncul di beranda pembeli selama sale berlangsung.</p>
            </section>

            <section class="rounded-2xl bg-white p-4 ring-1 ring-slate-200/70 sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-ink">Produk sale</h2>
                    <button type="button" class="inline-flex h-10 items-center gap-1.5 rounded-xl px-3 text-sm font-medium text-accent-700 ring-1 ring-accent-200 hover:bg-accent-50" @click="tambah">
                        <Icon icon="heroicons:plus" class="h-4 w-4" /> Tambah varian
                    </button>
                </div>
                <p v-if="err('items')" class="mt-2 text-xs text-rose-600">{{ err('items') }}</p>

                <ul class="mt-3 space-y-3">
                    <li v-for="(item, i) in form.items" :key="i" class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200/70">
                        <div class="grid gap-3 sm:grid-cols-[1fr_150px_120px_auto] sm:items-start">
                            <div>
                                <label class="text-xs font-medium text-slate-600" :for="`fs-v-${i}`">Varian</label>
                                <select :id="`fs-v-${i}`" v-model="item.product_variant_id" required class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none">
                                    <option value="" disabled>Pilih varian…</option>
                                    <option v-for="v in variants" :key="v.id" :value="v.id">{{ v.label }}</option>
                                </select>
                                <p v-if="variantById[item.product_variant_id]" class="mt-1 text-[11px] text-slate-500">
                                    Harga normal {{ rupiah(variantById[item.product_variant_id].harga) }} · stok {{ variantById[item.product_variant_id].stok }}
                                    <span v-if="item.terjual"> · {{ item.terjual }} terjual di sale ini</span>
                                </p>
                                <p v-if="err(`items.${i}.product_variant_id`)" class="mt-1 text-xs text-rose-600">{{ err(`items.${i}.product_variant_id`) }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-slate-600" :for="`fs-h-${i}`">Harga sale (Rp)</label>
                                <input :id="`fs-h-${i}`" v-model.number="item.harga_sale" type="number" min="0" required class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                                <p v-if="diskon(item)" class="mt-1 text-[11px] font-semibold text-rose-600">-{{ diskon(item) }}%</p>
                                <p v-if="err(`items.${i}.harga_sale`)" class="mt-1 text-xs text-rose-600">{{ err(`items.${i}.harga_sale`) }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-slate-600" :for="`fs-k-${i}`">Kuota</label>
                                <input :id="`fs-k-${i}`" v-model.number="item.kuota" type="number" min="1" placeholder="Tanpa batas" class="mt-1.5 h-10 w-full rounded-xl bg-white px-3 text-sm text-ink ring-1 ring-slate-200 focus:ring-2 focus:ring-accent-300 focus:outline-none" />
                                <p v-if="err(`items.${i}.kuota`)" class="mt-1 text-xs text-rose-600">{{ err(`items.${i}.kuota`) }}</p>
                            </div>
                            <button type="button" class="flex h-10 items-center justify-center gap-1.5 rounded-xl px-3 text-sm text-rose-600 hover:bg-rose-50 sm:mt-6 sm:w-10 sm:px-0" :aria-label="`Hapus baris ${i + 1}`" @click="hapus(i)">
                                <Icon icon="heroicons:trash" class="h-4 w-4" /><span class="sm:hidden">Hapus</span>
                            </button>
                        </div>
                    </li>
                    <li v-if="!form.items.length" class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">
                        Belum ada varian. Klik "Tambah varian".
                    </li>
                </ul>
                <p class="mt-3 text-xs text-slate-500">Kuota dihitung per akun. Pesanan yang batal/kedaluwarsa mengembalikan kuota. Reseller tetap dapat harga termurah antara harga reseller dan harga sale.</p>
            </section>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <button v-if="sale" type="button" class="h-11 rounded-xl px-4 text-sm font-medium text-rose-600 hover:bg-rose-50" @click="hapusSale">Hapus flash sale</button>
                <button type="submit" :disabled="form.processing" class="ml-auto h-11 rounded-xl bg-accent-600 px-6 text-sm font-semibold text-white hover:bg-accent-700 disabled:opacity-50">
                    {{ sale ? 'Simpan' : 'Buat Flash Sale' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'

export default { layout: AdminLayout }
</script>
