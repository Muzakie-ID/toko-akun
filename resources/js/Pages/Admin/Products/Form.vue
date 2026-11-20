<script setup>
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

        <form class="mt-6 grid gap-4 rounded-2xl bg-white ring-1 ring-slate-200/70 p-5 sm:grid-cols-2" @submit.prevent="submit">
            <div>
                <label for="nama" class="text-sm font-medium text-ink">Nama produk</label>
                <input id="nama" v-model="form.nama" type="text" required placeholder="Netflix Premium" class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-ink focus:border-slate-400 focus:ring-2 focus:ring-slate-200 focus:outline-none" />
                <p v-if="form.errors.nama" class="mt-1 text-xs text-red-600">{{ form.errors.nama }}</p>
            </div>
            <div>
                <label for="kategori" class="text-sm font-medium text-ink">Kategori</label>
                <select id="kategori" v-model="form.category_id" required class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-ink focus:border-slate-400 focus:ring-2 focus:ring-slate-200 focus:outline-none">
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.nama }}</option>
                </select>
                <p v-if="form.errors.category_id" class="mt-1 text-xs text-red-600">{{ form.errors.category_id }}</p>
            </div>
            <div>
                <label for="slug" class="text-sm font-medium text-ink">Slug URL</label>
                <input id="slug" v-model="form.slug" type="text" placeholder="otomatis dari nama" class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-ink focus:border-slate-400 focus:ring-2 focus:ring-slate-200 focus:outline-none" />
                <p v-if="form.errors.slug" class="mt-1 text-xs text-red-600">{{ form.errors.slug }}</p>
            </div>
            <div>
                <label for="garansi" class="text-sm font-medium text-ink">Garansi (hari)</label>
                <input id="garansi" v-model="form.garansi_hari" type="number" min="0" required class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-ink focus:border-slate-400 focus:ring-2 focus:ring-slate-200 focus:outline-none" />
                <p v-if="form.errors.garansi_hari" class="mt-1 text-xs text-red-600">{{ form.errors.garansi_hari }}</p>
            </div>
            <div class="sm:col-span-2">
                <span class="text-sm font-medium text-ink">Logo</span>
                <div class="mt-1.5 flex flex-wrap items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-xl bg-slate-50 ring-1 ring-slate-200">
                        <img v-if="logoPreview" :src="logoPreview" alt="Pratinjau logo" class="h-full w-full object-contain" />
                        <Icon v-else icon="heroicons:photo" class="h-6 w-6 text-slate-300" />
                    </div>
                    <label class="inline-flex h-9 cursor-pointer items-center gap-2 rounded-lg px-3 text-sm font-medium text-accent-700 ring-1 ring-accent-200 hover:bg-accent-50">
                        <Icon icon="heroicons:arrow-up-tray" class="h-4 w-4" /> {{ logoPreview ? 'Ganti logo' : 'Unggah logo' }}
                        <input type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="pilihLogo" />
                    </label>
                    <button v-if="logoPreview" type="button" class="text-sm font-medium text-rose-600 hover:underline" @click="hapusLogo">Hapus</button>
                    <p class="text-xs text-slate-500">PNG/JPG/WEBP persegi, min. 64×64, maks. 1 MB. Tanpa logo, kartu memakai huruf awal.</p>
                </div>
                <p v-if="form.errors.logo" class="mt-1 text-xs text-red-600">{{ form.errors.logo }}</p>
            </div>
            <div class="sm:col-span-2">
                <label for="deskripsi" class="text-sm font-medium text-ink">Deskripsi</label>
                <textarea id="deskripsi" v-model="form.deskripsi" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-ink focus:border-slate-400 focus:ring-2 focus:ring-slate-200 focus:outline-none" />
                <p v-if="form.errors.deskripsi" class="mt-1 text-xs text-red-600">{{ form.errors.deskripsi }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-5 sm:col-span-2">
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input v-model="form.is_aktif" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                    Aktif (tampil di katalog)
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input v-model="form.is_populer" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                    Tandai populer
                </label>
                <button type="submit" :disabled="form.processing" class="ml-auto h-10 rounded-xl bg-accent-600 px-5 text-sm font-semibold text-white hover:bg-accent-700 disabled:opacity-50">
                    {{ product ? 'Simpan Produk' : 'Buat Produk' }}
                </button>
            </div>
        </form>

        <section v-if="product" class="mt-8">
            <h2 class="text-lg font-bold text-ink">Varian</h2>
            <p class="mt-1 text-sm text-slate-500">Setiap varian = tipe akun &times; durasi, dengan harga dan stok sendiri.</p>

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
                        <span class="w-40 font-semibold text-ink">{{ variant.jenis_akun_label }} &middot; {{ variant.durasi_label }}</span>
                        <span class="text-ink">{{ rupiah(variant.harga) }}</span>
                        <span class="text-slate-400 line-through">{{ variant.harga_asli ? rupiah(variant.harga_asli) : '' }}</span>
                        <span class="text-xs text-slate-500">Reseller: {{ rupiah(variant.harga_reseller ?? variant.harga) }}</span>
                        <span class="text-xs text-slate-500">{{ variant.jumlah_stok }} stok</span>
                        <span v-if="!variant.is_aktif" class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500">Nonaktif</span>
                        <span class="ml-auto whitespace-nowrap">
                            <button type="button" class="-my-1 inline-flex min-h-9 items-center px-2 text-xs font-semibold text-ink hover:underline" @click="editingId = variant.id">Edit</button>
                            <button type="button" class="-my-1 inline-flex min-h-9 items-center px-2 text-xs font-semibold text-rose-600 hover:underline" @click="hapusVarian(variant)">Hapus</button>
                        </span>
                    </div>
                </div>
                <p v-if="!variants.length" class="px-5 py-6 text-center text-sm text-slate-500">Belum ada varian. Produk tanpa varian tidak bisa dibeli.</p>
            </div>

            <div class="mt-4 rounded-2xl border border-dashed border-accent-200 bg-white p-5">
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
