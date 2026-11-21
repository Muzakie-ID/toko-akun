<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { UiButton, UiCheckbox, UiChip, UiInput, UiSelect, UiTextarea } from '@/Components/ui'
import RichText from '@/Components/RichText.vue'
import { fillPlaceholders } from '@/lib/richText'

const props = defineProps({
    konten: { type: Object, required: true },
    diperbarui: { type: String, required: true },
    bawaan: { type: Object, required: true },
    ikon: { type: Array, required: true },
    placeholder: { type: Array, required: true },
})

const salin = (value) => JSON.parse(JSON.stringify(value))

const form = useForm({
    intro: props.konten.intro,
    bagian: salin(props.konten.bagian),
    perbarui_tanggal: true,
})

const mode = ref('edit')
const copied = ref(null)

const namaIkon = (icon) => icon.replace('heroicons:', '').replaceAll('-', ' ')
const opsiIkon = computed(() => props.ikon.map((icon) => ({ value: icon, label: namaIkon(icon) })))
const nilaiPlaceholder = computed(() => Object.fromEntries(props.placeholder.map((p) => [p.kode.slice(1, -1), p.nilai])))
const isi = (text) => fillPlaceholders(text, nilaiPlaceholder.value)

const tambah = () => form.bagian.push({ judul: '', icon: props.ikon[0], isi: '' })
const hapus = (i) => {
    if (confirm(`Hapus bagian "${form.bagian[i].judul || i + 1}"?`)) {
        form.bagian.splice(i, 1)
    }
}
const geser = (i, arah) => {
    const j = i + arah
    if (j < 0 || j >= form.bagian.length) return
    const [item] = form.bagian.splice(i, 1)
    form.bagian.splice(j, 0, item)
}
const pulihkan = () => {
    if (confirm('Ganti semua isi editor dengan teks bawaan? Perubahan baru tersimpan setelah kamu menekan Simpan.')) {
        form.intro = props.bawaan.intro
        form.bagian = salin(props.bawaan.bagian)
    }
}
const salinKode = async (kode) => {
    try {
        await navigator.clipboard.writeText(kode)
        copied.value = kode
        setTimeout(() => (copied.value = null), 1500)
    } catch {}
}

const save = () => form.put('/admin/syarat-ketentuan', { preserveScroll: true })
const err = (key) => form.errors[key]
</script>

<template>
    <div class="mx-auto max-w-6xl">
        <Head title="Syarat & Ketentuan" />
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-ink sm:text-2xl">Syarat &amp; Ketentuan</h1>
                <p class="mt-1 text-sm text-slate-500">Terakhir diperbarui {{ diperbarui }}. Tampil di halaman publik dan ditautkan dari halaman daftar.</p>
            </div>
            <UiButton href="/syarat-ketentuan" external variant="secondary" size="sm" icon="heroicons:arrow-top-right-on-square">Lihat halaman</UiButton>
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,1fr)_300px]">
            <form class="min-w-0 space-y-4" @submit.prevent="save">
                <div class="flex gap-2" role="tablist" aria-label="Mode tampilan">
                    <UiChip role="tab" :active="mode === 'edit'" icon="heroicons:pencil-square" @click="mode = 'edit'">Edit</UiChip>
                    <UiChip role="tab" :active="mode === 'pratinjau'" icon="heroicons:eye" @click="mode = 'pratinjau'">Pratinjau</UiChip>
                </div>

                <template v-if="mode === 'edit'">
                    <section class="rounded-2xl bg-white p-4 ring-1 ring-slate-200/70 sm:p-5">
                        <UiTextarea v-model="form.intro" label="Pembuka" rows="3" required maxlength="1000" :error="err('intro')" />
                    </section>

                    <section v-for="(b, i) in form.bagian" :key="i" class="rounded-2xl bg-white p-4 ring-1 ring-slate-200/70 sm:p-5">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-soft text-primary">
                                    <Icon :icon="b.icon" class="h-4 w-4" />
                                </span>
                                Bagian {{ i + 1 }}
                            </h2>
                            <div class="flex items-center gap-1">
                                <UiButton variant="ghost" size="sm" square icon="heroicons:arrow-up" :disabled="i === 0" :label="`Naikkan bagian ${i + 1}`" @click="geser(i, -1)" />
                                <UiButton variant="ghost" size="sm" square icon="heroicons:arrow-down" :disabled="i === form.bagian.length - 1" :label="`Turunkan bagian ${i + 1}`" @click="geser(i, 1)" />
                                <UiButton variant="ghost" size="sm" square icon="heroicons:trash" class="text-rose-600" :disabled="form.bagian.length === 1" :label="`Hapus bagian ${i + 1}`" @click="hapus(i)" />
                            </div>
                        </div>
                        <div class="mt-4 grid gap-4 sm:grid-cols-[minmax(0,1fr)_200px]">
                            <UiInput v-model="b.judul" label="Judul" required maxlength="80" :error="err(`bagian.${i}.judul`)" />
                            <UiSelect v-model="b.icon" label="Ikon" :options="opsiIkon" :error="err(`bagian.${i}.icon`)" />
                            <UiTextarea v-model="b.isi" class="sm:col-span-2" label="Isi" rows="8" required maxlength="3000" :error="err(`bagian.${i}.isi`)" />
                        </div>
                    </section>

                    <UiButton variant="secondary" block icon="heroicons:plus" :disabled="form.bagian.length >= 15" @click="tambah">Tambah bagian</UiButton>
                    <p v-if="err('bagian')" class="text-sm text-rose-600">{{ err('bagian') }}</p>
                </template>

                <article v-else class="rounded-2xl bg-white p-5 text-sm leading-7 text-slate-600 ring-1 ring-slate-200/70 sm:p-8">
                    <p class="whitespace-pre-line">{{ isi(form.intro) }}</p>
                    <section v-for="(b, i) in form.bagian" :key="i" class="mt-8">
                        <h2 class="flex items-center gap-3 text-lg font-bold text-ink">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100">
                                <Icon :icon="b.icon" class="h-5 w-5 text-slate-700" />
                            </span>
                            {{ i + 1 }}. {{ isi(b.judul) }}
                        </h2>
                        <RichText :text="isi(b.isi)" class="mt-3" />
                    </section>
                </article>

                <div class="flex flex-col gap-3 rounded-2xl bg-white p-4 ring-1 ring-slate-200/70 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <UiCheckbox v-model="form.perbarui_tanggal" label="Perbarui tanggal &quot;Terakhir diperbarui&quot;" hint="Matikan bila hanya memperbaiki salah ketik." />
                    <div class="flex gap-2">
                        <UiButton variant="ghost" icon="heroicons:arrow-uturn-left" @click="pulihkan">Isi bawaan</UiButton>
                        <UiButton type="submit" icon="heroicons:check" :loading="form.processing" class="flex-1 sm:flex-none">Simpan</UiButton>
                    </div>
                </div>
            </form>

            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                <section class="rounded-2xl bg-white p-4 ring-1 ring-slate-200/70 sm:p-5">
                    <h2 class="text-sm font-semibold text-ink">Format teks</h2>
                    <ul class="mt-3 space-y-2 text-sm text-slate-600">
                        <li><code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">- teks</code> di awal baris = poin</li>
                        <li><code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">**teks**</code> = tebal</li>
                        <li>Baris kosong = paragraf baru</li>
                    </ul>
                </section>
                <section class="rounded-2xl bg-white p-4 ring-1 ring-slate-200/70 sm:p-5">
                    <h2 class="text-sm font-semibold text-ink">Placeholder</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Terisi otomatis dari Pengaturan. Ketuk untuk menyalin.</p>
                    <ul class="mt-3 space-y-1">
                        <li v-for="p in placeholder" :key="p.kode">
                            <button type="button" class="flex w-full items-start justify-between gap-3 rounded-lg px-2 py-1.5 text-left transition hover:bg-slate-50" @click="salinKode(p.kode)">
                                <span class="min-w-0">
                                    <code class="block text-xs font-semibold text-primary">{{ p.kode }}</code>
                                    <span class="block text-xs text-slate-500">{{ p.label }}: {{ p.nilai }}</span>
                                </span>
                                <Icon :icon="copied === p.kode ? 'heroicons:check' : 'heroicons:clipboard-document'" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                            </button>
                        </li>
                    </ul>
                </section>
            </aside>
        </div>
    </div>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'

export default { layout: AdminLayout }
</script>
