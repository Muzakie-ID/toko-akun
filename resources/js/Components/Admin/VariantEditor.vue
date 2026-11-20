<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    productId: { type: Number, required: true },
    variant: { type: Object, default: null },
    jenisAkunOptions: { type: Array, required: true },
})

const emit = defineEmits(['done'])

const satuanHari = { hari: 1, bulan: 30, tahun: 365 }

// Pecah durasi_hari jadi jumlah + satuan agar admin bisa isi "2 Bulan" atau "7 Hari"
const pecahDurasi = (hari) => {
    if (!hari) return { jumlah: 1, satuan: 'bulan' }
    if (hari % 365 === 0) return { jumlah: hari / 365, satuan: 'tahun' }
    if (hari % 30 === 0) return { jumlah: hari / 30, satuan: 'bulan' }
    return { jumlah: hari, satuan: 'hari' }
}

const durasiAwal = pecahDurasi(props.variant?.durasi_hari)

const form = useForm({
    jenis_akun: props.variant?.jenis_akun ?? props.jenisAkunOptions[0].value,
    durasi_jumlah: durasiAwal.jumlah,
    durasi_satuan: durasiAwal.satuan,
    harga: props.variant?.harga ?? '',
    harga_asli: props.variant?.harga_asli ?? '',
    harga_reseller: props.variant?.harga_reseller ?? '',
    is_aktif: props.variant?.is_aktif ?? true,
})

const terkunci = computed(() => (props.variant?.jumlah_stok ?? 0) > 0)
const durasiHari = computed(() => Number(form.durasi_jumlah) * satuanHari[form.durasi_satuan])

const submit = () => {
    const kosongJadiNull = (v) => (v === '' || v === null ? null : Number(v))
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            if (!props.variant) form.reset('harga', 'harga_asli', 'harga_reseller')
            emit('done')
        },
    }

    form.transform((data) => ({
        jenis_akun: data.jenis_akun,
        durasi_hari: durasiHari.value,
        harga: kosongJadiNull(data.harga),
        harga_asli: kosongJadiNull(data.harga_asli),
        harga_reseller: kosongJadiNull(data.harga_reseller),
        is_aktif: data.is_aktif,
    }))

    if (props.variant) {
        form.put(`/admin/varian/${props.variant.id}`, options)
    } else {
        form.post(`/admin/produk/${props.productId}/varian`, options)
    }
}
</script>

<template>
    <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6" @submit.prevent="submit">
        <div class="lg:col-span-1">
            <label class="text-xs font-medium text-slate-600" :for="`jenis-${variant?.id ?? 'baru'}`">Tipe akun</label>
            <select :id="`jenis-${variant?.id ?? 'baru'}`" v-model="form.jenis_akun" :disabled="terkunci" class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-ink focus:border-slate-400 focus:ring-2 focus:ring-slate-200 focus:outline-none">
                <option v-for="opt in jenisAkunOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <p v-if="form.errors.jenis_akun" class="mt-1 text-xs text-red-600">{{ form.errors.jenis_akun }}</p>
        </div>

        <div class="lg:col-span-2">
            <span class="text-xs font-medium text-slate-600">Durasi</span>
            <div class="flex gap-2">
                <input v-model="form.durasi_jumlah" type="number" min="1" required :disabled="terkunci" aria-label="Jumlah durasi" class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-ink focus:border-slate-400 focus:ring-2 focus:ring-slate-200 focus:outline-none" />
                <select v-model="form.durasi_satuan" :disabled="terkunci" aria-label="Satuan durasi" class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-ink focus:border-slate-400 focus:ring-2 focus:ring-slate-200 focus:outline-none">
                    <option value="hari">Hari</option>
                    <option value="bulan">Bulan</option>
                    <option value="tahun">Tahun</option>
                </select>
            </div>
            <p v-if="form.errors.durasi_hari" class="mt-1 text-xs text-red-600">{{ form.errors.durasi_hari }}</p>
            <p v-else-if="terkunci" class="mt-1 text-xs text-slate-400">Tipe & durasi terkunci karena sudah ada stok.</p>
        </div>

        <div>
            <label class="text-xs font-medium text-slate-600" :for="`harga-${variant?.id ?? 'baru'}`">Harga jual (Rp)</label>
            <input :id="`harga-${variant?.id ?? 'baru'}`" v-model="form.harga" type="number" min="0" required class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-ink focus:border-slate-400 focus:ring-2 focus:ring-slate-200 focus:outline-none" />
            <p v-if="form.errors.harga" class="mt-1 text-xs text-red-600">{{ form.errors.harga }}</p>
        </div>

        <div>
            <label class="text-xs font-medium text-slate-600" :for="`asli-${variant?.id ?? 'baru'}`">Harga coret</label>
            <input :id="`asli-${variant?.id ?? 'baru'}`" v-model="form.harga_asli" type="number" min="0" placeholder="opsional" class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-ink focus:border-slate-400 focus:ring-2 focus:ring-slate-200 focus:outline-none" />
            <p v-if="form.errors.harga_asli" class="mt-1 text-xs text-red-600">{{ form.errors.harga_asli }}</p>
        </div>

        <div>
            <label class="text-xs font-medium text-slate-600" :for="`reseller-${variant?.id ?? 'baru'}`">Harga reseller</label>
            <input :id="`reseller-${variant?.id ?? 'baru'}`" v-model="form.harga_reseller" type="number" min="0" placeholder="= harga jual" class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-ink focus:border-slate-400 focus:ring-2 focus:ring-slate-200 focus:outline-none" />
            <p v-if="form.errors.harga_reseller" class="mt-1 text-xs text-red-600">{{ form.errors.harga_reseller }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3 sm:col-span-2 lg:col-span-6">
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input v-model="form.is_aktif" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                Aktif (tampil di katalog)
            </label>
            <div class="ml-auto flex gap-2">
                <button v-if="variant" type="button" class="h-9 rounded-lg px-3 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="emit('done')">Batal</button>
                <button type="submit" :disabled="form.processing" class="h-9 rounded-lg bg-accent-600 px-4 text-sm font-semibold text-white hover:bg-accent-700 disabled:opacity-50">
                    {{ variant ? 'Simpan Varian' : 'Tambah Varian' }}
                </button>
            </div>
        </div>
    </form>
</template>
