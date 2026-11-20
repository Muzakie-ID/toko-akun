<script setup>
import { UiButton, UiInput, UiSelect, UiTextarea } from '@/Components/ui'
import { Head, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    variants: { type: Array, required: true },
    preview: { type: Array, default: null },
    result: { type: Object, default: null },
})

const form = useForm({
    product_variant_id: props.variants[0]?.id ?? '',
    slot_default: 4,
    akun: '',
    aksi: 'preview',
})

const selectedVariant = computed(() => props.variants.find((v) => v.id === Number(form.product_variant_id)))
const isSharing = computed(() => selectedVariant.value?.jenis_akun === 'sharing')

const lineCount = computed(() => form.akun.split(/\r?\n/).filter((l) => l.trim() !== '').length)
const validCount = computed(() => props.preview?.filter((r) => r.errors.length === 0).length ?? 0)
const invalidCount = computed(() => (props.preview?.length ?? 0) - validCount.value)

const submit = (aksi) => {
    form.aksi = aksi
    form
        .transform((data) => ({ ...data, slot_default: isSharing.value ? data.slot_default : null }))
        .post('/admin/stok/import', {
            preserveScroll: true,
            preserveState: true,
            onSuccess: (page) => {
                if (aksi !== 'simpan') return
                // Sisakan hanya baris yang ditolak agar admin bisa langsung memperbaikinya
                const rejected = new Set((page.props.result?.ditolak ?? []).map((row) => row.baris))
                form.akun = form.akun
                    .split(/\r?\n/)
                    .filter((_, i) => rejected.has(i + 1))
                    .join('\n')
            },
        })
}

const placeholder = computed(() =>
    isSharing.value
        ? 'netflix1@mail.com|pass123|4|Profil 1 PIN 1111;Profil 2 PIN 2222;Profil 3 PIN 3333;Profil 4 PIN 4444\nnetflix2@mail.com|pass456||Profil bebas, jangan ganti password'
        : 'akun1@mail.com|pass123\nakun2@mail.com|pass456|1|Login via web, bukan app',
)
</script>

<template>
    <div class="mx-auto max-w-6xl">
        <Head title="Input Stok Massal" />

        <h1 class="text-xl font-semibold tracking-tight text-ink sm:text-2xl">Input Stok Massal</h1>
        <p class="mt-1 text-sm text-slate-500">Tempel daftar akun, cek preview, lalu simpan. Kredensial disimpan terenkripsi.</p>

        <div
            v-if="result"
            class="mt-6 rounded-xl border px-4 py-3 text-sm"
            :class="result.ditolak.length ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-emerald-200 bg-emerald-50 text-emerald-800'"
            role="status"
        >
            <p class="font-semibold">
                {{ result.tersimpan }} akun tersimpan<span v-if="result.ditolak.length">, {{ result.ditolak.length }} ditolak &mdash; baris yang ditolak disisakan di kolom di bawah untuk diperbaiki</span>.
            </p>
            <ul v-if="result.ditolak.length" class="mt-2 list-inside list-disc text-xs text-rose-700">
                <li v-for="row in result.ditolak" :key="row.baris">Baris {{ row.baris }} ({{ row.email || '-' }}): {{ row.errors.join(' ') }}</li>
            </ul>
        </div>

        <div v-if="!variants.length" class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Belum ada varian produk. Buat produk &amp; varian dulu.
        </div>

        <form v-else class="mt-6 grid gap-6 lg:grid-cols-[1fr_300px]" @submit.prevent="submit('preview')">
            <div class="space-y-4 rounded-2xl bg-white ring-1 ring-slate-200/70 p-5">
                <UiSelect
                    v-model="form.product_variant_id"
                    label="Varian produk"
                    :options="variants.map((v) => ({ value: v.id, label: `${v.produk} — ${v.jenis_akun_label} — ${v.durasi_label} (${v.jumlah_stok} stok)` }))"
                    :error="form.errors.product_variant_id"
                />

                <UiInput
                    v-if="isSharing"
                    v-model="form.slot_default"
                    class="max-w-40"
                    label="Slot default"
                    type="number"
                    min="2"
                    max="50"
                    hint="Dipakai untuk baris yang kolom slot-nya kosong."
                    :error="form.errors.slot_default"
                />

                <div>
                    <div class="mb-1.5 flex items-baseline justify-between">
                        <label for="akun" class="text-sm font-medium text-ink">Daftar akun</label>
                        <span class="text-xs text-slate-400">{{ lineCount }} baris</span>
                    </div>
                    <UiTextarea id="akun" v-model="form.akun" rows="12" spellcheck="false" class="[&_textarea]:font-mono" :placeholder="placeholder" :error="form.errors.akun" />
                </div>

                <div class="flex flex-wrap gap-2.5">
                    <UiButton type="submit" variant="secondary" icon="heroicons:eye" :loading="form.processing && form.aksi === 'preview'" :disabled="!lineCount">Cek Preview</UiButton>
                    <UiButton icon="heroicons:check" :loading="form.processing && form.aksi === 'simpan'" :disabled="!preview || !validCount" @click="submit('simpan')">
                        Simpan {{ preview ? validCount : '' }} Akun Valid
                    </UiButton>
                </div>
            </div>

            <aside class="h-fit rounded-2xl bg-white ring-1 ring-slate-200/70 p-5 text-sm">
                <h2 class="font-semibold text-ink">Format per baris</h2>
                <code class="mt-2 block rounded-lg bg-slate-50 px-3 py-2 text-xs text-ink ring-1 ring-slate-200">email|password|slot|info</code>
                <ul class="mt-3 space-y-1.5 text-xs leading-5 text-slate-600">
                    <li><b>slot</b> & <b>info</b> boleh dikosongkan di akun private.</li>
                    <li v-if="isSharing"><b>Sharing:</b> info wajib. Pisahkan info tiap slot dengan <code>;</code>. Satu info saja = berlaku untuk semua slot.</li>
                    <li v-else><b>Private:</b> slot selalu 1.</li>
                    <li>Password tidak boleh mengandung <code>|</code>.</li>
                    <li>Baris kosong &amp; diawali <code>#</code> diabaikan.</li>
                </ul>
            </aside>
        </form>

        <section v-if="preview" class="mt-8">
            <h2 class="text-lg font-bold text-ink">Preview</h2>
            <p class="mt-1 text-sm text-slate-500">
                <span class="font-semibold text-emerald-700">{{ validCount }} valid</span>
                <span v-if="invalidCount"> &middot; <span class="font-semibold text-rose-600">{{ invalidCount }} error</span> (tidak akan disimpan)</span>
            </p>
            <ul class="mt-3 divide-y divide-slate-100 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70 md:hidden">
                <li v-for="row in preview" :key="row.baris" class="px-4 py-3" :class="row.errors.length ? 'bg-rose-50/60' : ''">
                    <div class="flex items-start justify-between gap-3">
                        <p class="min-w-0 truncate font-mono text-xs text-ink"><span class="font-sans text-slate-400">#{{ row.baris }}</span> {{ row.email || '—' }}</p>
                        <span class="shrink-0 text-xs" :class="row.errors.length ? 'text-rose-700' : 'font-semibold text-emerald-700'">{{ row.errors.length ? 'Error' : 'OK' }}</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Slot {{ row.slot ?? '—' }}<span v-if="row.info.length"> · {{ row.info.join('; ') }}</span></p>
                    <p v-if="row.errors.length" class="mt-1 text-xs text-rose-700">{{ row.errors.join(' ') }}</p>
                </li>
            </ul>
            <div class="mt-3 hidden overflow-x-auto rounded-2xl bg-white ring-1 ring-slate-200/70 md:block">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5 font-medium">Baris</th>
                            <th class="px-4 py-2.5 font-medium">Email</th>
                            <th class="px-4 py-2.5 font-medium">Slot</th>
                            <th class="px-4 py-2.5 font-medium">Info</th>
                            <th class="px-4 py-2.5 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="row in preview" :key="row.baris" :class="row.errors.length ? 'bg-rose-50/60' : ''">
                            <td class="px-4 py-2.5 text-slate-500">{{ row.baris }}</td>
                            <td class="px-4 py-2.5 font-mono text-xs text-ink">{{ row.email || '—' }}</td>
                            <td class="px-4 py-2.5 text-ink">{{ row.slot ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-xs text-slate-600">
                                <ol v-if="row.info.length > 1" class="list-inside list-decimal">
                                    <li v-for="(info, i) in row.info" :key="i">{{ info }}</li>
                                </ol>
                                <span v-else>{{ row.info[0] ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-xs">
                                <span v-if="!row.errors.length" class="font-semibold text-emerald-700">OK</span>
                                <span v-else class="text-rose-700">{{ row.errors.join(' ') }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'

export default { layout: AdminLayout }
</script>
