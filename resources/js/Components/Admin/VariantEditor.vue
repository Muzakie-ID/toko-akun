<script setup>
import { UiButton, UiCheckbox, UiField, UiInput, UiSelect } from '@/Components/ui'
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
        <UiSelect v-model="form.jenis_akun" label="Tipe akun" :disabled="terkunci" :options="jenisAkunOptions" :error="form.errors.jenis_akun" />

        <UiField class="lg:col-span-2" label="Durasi" :error="form.errors.durasi_hari" :hint="terkunci ? 'Tipe & durasi terkunci karena sudah ada stok.' : ''">
            <div class="flex gap-2">
                <UiInput v-model="form.durasi_jumlah" class="flex-1" type="number" min="1" required :disabled="terkunci" aria-label="Jumlah durasi" />
                <UiSelect v-model="form.durasi_satuan" class="flex-1" :disabled="terkunci" aria-label="Satuan durasi" :options="[{ value: 'hari', label: 'Hari' }, { value: 'bulan', label: 'Bulan' }, { value: 'tahun', label: 'Tahun' }]" />
            </div>
        </UiField>

        <UiInput v-model="form.harga" label="Harga jual (Rp)" type="number" min="0" required :error="form.errors.harga" />
        <UiInput v-model="form.harga_asli" label="Harga coret" type="number" min="0" placeholder="opsional" :error="form.errors.harga_asli" />
        <UiInput v-model="form.harga_reseller" label="Harga reseller" type="number" min="0" placeholder="= harga jual" :error="form.errors.harga_reseller" />

        <div class="flex flex-wrap items-center gap-3 sm:col-span-2 lg:col-span-6">
            <UiCheckbox v-model="form.is_aktif" label="Aktif (tampil di katalog)" />
            <div class="ml-auto flex gap-2">
                <UiButton v-if="variant" variant="ghost" @click="emit('done')">Batal</UiButton>
                <UiButton type="submit" :icon="variant ? 'heroicons:check' : 'heroicons:plus'" :loading="form.processing">{{ variant ? 'Simpan Varian' : 'Tambah Varian' }}</UiButton>
            </div>
        </div>
    </form>
</template>
