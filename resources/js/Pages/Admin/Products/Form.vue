<script setup>
import { UiButton, UiCheckbox, UiField, UiInput, UiSelect, UiTextarea } from '@/Components/ui'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import VariantEditor from '@/Components/Admin/VariantEditor.vue'

const props = defineProps({
    product: { type: Object, default: null },
    categories: { type: Array, required: true },
    variants: { type: Array, required: true },
    jenisAkunOptions: { type: Array, required: true },
})

const form = useForm({
    category_id: props.product?.category_id ?? props.categories[0]?.id ?? '',
    nama: props.product?.nama ?? '',
    slug: props.product?.slug ?? '',
    deskripsi: props.product?.deskripsi ?? '',
    garansi_hari: props.product?.garansi_hari ?? 30,
    is_aktif: props.product?.is_aktif ?? true,
    is_populer: props.product?.is_populer ?? false,
    logo: null,
    hapus_logo: false,
})

const logoPreview = ref(props.product?.logo_url ?? null)
// Setelah disimpan, ganti pratinjau lokal (blob) dengan URL logo dari server
watch(() => props.product?.logo_url, (url) => (logoPreview.value = url ?? null))
const pilihLogo = (event) => {
    const file = event.target.files?.[0] ?? null
    form.logo = file
    form.hapus_logo = false
    logoPreview.value = file ? URL.createObjectURL(file) : props.product?.logo_url ?? null
}
const hapusLogo = () => {
    form.logo = null
    form.hapus_logo = true
    logoPreview.value = null
}

const submit = () => {
    if (props.product) {
        // File hanya terkirim lewat POST multipart; _method=put agar tetap ke route update
        form.transform((data) => ({ ...data, _method: 'put' })).post(`/admin/produk/${props.product.id}`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => form.reset('logo', 'hapus_logo'),
        })
    } else {
        // Setelah dibuat halaman pindah ke mode edit; muat ulang form dari data server kecuali ada error validasi
        form.post('/admin/produk', { preserveState: 'errors', forceFormData: true })
    }
}

const editingId = ref(null)

const rupiah = (n) => (n === null || n === undefined ? '—' : 'Rp ' + Number(n).toLocaleString('id-ID'))

const hapusVarian = (variant) => {
    if (!confirm(`Hapus varian ${variant.jenis_akun_label} ${variant.durasi_label}?`)) return
    router.delete(`/admin/varian/${variant.id}`, { preserveScroll: true })
}
</script>

<template>
    <div class="mx-auto max-w-6xl">
        <Head :title="product ? `Edit ${product.nama}` : 'Tambah Produk'" />

        <Link href="/admin/produk" class="-my-2 inline-flex items-center gap-1 py-2 text-sm text-slate-500 hover:text-ink">
            <Icon icon="heroicons:arrow-left" class="h-4 w-4" />
            Semua produk
        </Link>
        <h1 class="mt-2 text-xl font-semibold tracking-tight text-ink sm:text-2xl">{{ product ? product.nama : 'Tambah Produk' }}</h1>

        <form class="mt-6 grid gap-4 rounded-2xl bg-white p-4 ring-1 ring-slate-200/70 sm:grid-cols-2 sm:p-5" @submit.prevent="submit">
            <UiInput v-model="form.nama" label="Nama produk" required placeholder="Netflix Premium" :error="form.errors.nama" />
            <UiSelect v-model="form.category_id" label="Kategori" required :options="categories.map((c) => ({ value: c.id, label: c.nama }))" :error="form.errors.category_id" />
            <UiInput v-model="form.slug" label="Slug URL" placeholder="otomatis dari nama" :error="form.errors.slug" />
            <UiInput v-model="form.garansi_hari" label="Garansi (hari)" type="number" min="0" required :error="form.errors.garansi_hari" />
            <UiField class="sm:col-span-2" label="Logo" :error="form.errors.logo" hint="PNG/JPG/WEBP persegi, min. 64×64, maks. 1 MB. Tanpa logo, kartu memakai huruf awal.">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-xl bg-slate-50 ring-1 ring-slate-200">
                        <img v-if="logoPreview" :src="logoPreview" alt="Pratinjau logo" class="h-full w-full object-contain" />
                        <Icon v-else icon="heroicons:photo" class="h-6 w-6 text-slate-300" />
                    </div>
                    <label class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl bg-primary-soft px-3 text-xs font-semibold text-primary transition hover:brightness-95 focus-within:ring-2 focus-within:ring-primary-ring">
                        <Icon icon="heroicons:arrow-up-tray" class="h-4 w-4" /> {{ logoPreview ? 'Ganti logo' : 'Unggah logo' }}
                        <input type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="pilihLogo" />
                    </label>
                    <UiButton v-if="logoPreview" variant="ghost" size="sm" icon="heroicons:trash" class="text-rose-600 hover:bg-rose-50 hover:text-rose-700" @click="hapusLogo">Hapus</UiButton>
                </div>
            </UiField>
            <UiTextarea v-model="form.deskripsi" class="sm:col-span-2" label="Deskripsi" rows="4" :error="form.errors.deskripsi" />
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 sm:col-span-2">
                <UiCheckbox v-model="form.is_aktif" label="Aktif (tampil di katalog)" />
                <UiCheckbox v-model="form.is_populer" label="Tandai populer" />
                <UiButton type="submit" class="ml-auto" :loading="form.processing">{{ product ? 'Simpan Produk' : 'Buat Produk' }}</UiButton>
            </div>
        </form>

        <section v-if="product" class="mt-8">
            <h2 class="text-lg font-bold text-ink">Varian</h2>
            <p class="mt-1 text-sm text-slate-500">Setiap varian adalah kombinasi tipe akun dan durasi, dengan harga dan stok sendiri.</p>

            <div class="mt-3 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70">
                <div v-for="variant in variants" :key="variant.id" class="border-b border-slate-100 px-5 py-3.5 last:border-b-0">
                    <VariantEditor
                        v-if="editingId === variant.id"
                        :product-id="product.id"
                        :variant="variant"
                        :jenis-akun-options="jenisAkunOptions"
                        @done="editingId = null"
                    />
                    <div v-else class="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm">
                        <span class="w-40 font-semibold text-ink">{{ variant.jenis_akun_label }} · {{ variant.durasi_label }}</span>
                        <span class="text-ink">{{ rupiah(variant.harga) }}</span>
                        <span class="text-slate-400 line-through">{{ variant.harga_asli ? rupiah(variant.harga_asli) : '' }}</span>
                        <span class="text-xs text-slate-500">Reseller: {{ rupiah(variant.harga_reseller ?? variant.harga) }}</span>
                        <span class="text-xs text-slate-500">{{ variant.jumlah_stok }} stok</span>
                        <span v-if="!variant.is_aktif" class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500">Nonaktif</span>
                        <span class="ml-auto whitespace-nowrap">
                            <UiButton variant="ghost" size="sm" icon="heroicons:pencil-square" @click="editingId = variant.id">Edit</UiButton>
                            <UiButton variant="ghost" size="sm" icon="heroicons:trash" class="text-rose-600 hover:bg-rose-50 hover:text-rose-700" @click="hapusVarian(variant)">Hapus</UiButton>
                        </span>
                    </div>
                </div>
                <p v-if="!variants.length" class="px-5 py-6 text-center text-sm text-slate-500">Belum ada varian. Produk tanpa varian tidak bisa dibeli.</p>
            </div>

            <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white p-4 sm:p-5">
                <h3 class="mb-3 text-sm font-semibold text-ink">Tambah varian</h3>
                <VariantEditor :product-id="product.id" :jenis-akun-options="jenisAkunOptions" />
            </div>
        </section>
    </div>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'

export default { layout: AdminLayout }
</script>
