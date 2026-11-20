<script setup>
import { UiButton, UiCheckbox, UiInput, UiSelect } from '@/Components/ui'
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
                <UiInput v-model="form.nama" class="sm:col-span-3" label="Nama" required maxlength="80" placeholder="Flash Sale Gajian" :error="err('nama')" />
                <UiInput v-model="form.mulai_at" label="Mulai" type="datetime-local" required :error="err('mulai_at')" />
                <UiInput v-model="form.selesai_at" label="Selesai" type="datetime-local" required :error="err('selesai_at')" />
                <UiCheckbox v-model="form.is_aktif" label="Aktif" class="font-medium sm:mt-7" />
                <p class="text-xs text-slate-500 sm:col-span-3">Waktu dalam WIB. Banner &amp; hitung mundur muncul di beranda pembeli selama sale berlangsung.</p>
            </section>

            <section class="rounded-2xl bg-white p-4 ring-1 ring-slate-200/70 sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-ink">Produk sale</h2>
                    <UiButton variant="soft" size="sm" icon="heroicons:plus" @click="tambah">Tambah varian</UiButton>
                </div>
                <p v-if="err('items')" class="mt-2 text-xs text-rose-600">{{ err('items') }}</p>

                <ul class="mt-3 space-y-3">
                    <li v-for="(item, i) in form.items" :key="i" class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200/70">
                        <div class="grid gap-3 sm:grid-cols-[1fr_150px_130px_auto] sm:items-start">
                            <UiSelect
                                v-model="item.product_variant_id"
                                label="Varian"
                                required
                                placeholder="Pilih varian…"
                                :options="variants.map((v) => ({ value: v.id, label: v.label }))"
                                :error="err(`items.${i}.product_variant_id`)"
                                :hint="variantById[item.product_variant_id] ? `Harga normal ${rupiah(variantById[item.product_variant_id].harga)} · stok ${variantById[item.product_variant_id].stok}${item.terjual ? ` · ${item.terjual} terjual di sale ini` : ''}` : ''"
                            />
                            <UiInput
                                v-model.number="item.harga_sale"
                                label="Harga sale (Rp)"
                                type="number"
                                min="0"
                                required
                                :error="err(`items.${i}.harga_sale`)"
                                :hint="diskon(item) ? `Diskon ${diskon(item)}%` : ''"
                            />
                            <UiInput v-model.number="item.kuota" label="Kuota" type="number" min="1" placeholder="Tanpa batas" :error="err(`items.${i}.kuota`)" />
                            <UiButton variant="ghost" icon="heroicons:trash" class="text-rose-600 hover:bg-rose-50 hover:text-rose-700 sm:mt-7 sm:w-11 sm:px-0" :label="`Hapus baris ${i + 1}`" @click="hapus(i)">
                                <span class="sm:hidden">Hapus</span>
                            </UiButton>
                        </div>
                    </li>
                    <li v-if="!form.items.length" class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">
                        Belum ada varian. Klik "Tambah varian".
                    </li>
                </ul>
                <p class="mt-3 text-xs text-slate-500">Kuota dihitung per akun. Pesanan yang batal/kedaluwarsa mengembalikan kuota. Reseller tetap dapat harga termurah antara harga reseller dan harga sale.</p>
            </section>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <UiButton v-if="sale" variant="danger-soft" icon="heroicons:trash" @click="hapusSale">Hapus flash sale</UiButton>
                <UiButton type="submit" class="ml-auto" :icon="sale ? 'heroicons:check' : 'heroicons:bolt'" :loading="form.processing">{{ sale ? 'Simpan' : 'Buat Flash Sale' }}</UiButton>
            </div>
        </form>
    </div>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'

export default { layout: AdminLayout }
</script>
