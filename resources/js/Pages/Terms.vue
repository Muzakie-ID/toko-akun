<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import { UiButton } from '@/Components/ui'
import RichText from '@/Components/RichText.vue'

const props = defineProps({
    intro: { type: String, required: true },
    bagian: { type: Array, required: true },
    diperbarui: { type: String, required: true },
    whatsapp: { type: String, default: null },
})

const waLink = computed(() => (props.whatsapp ? `https://wa.me/${props.whatsapp.replace(/\D/g, '')}` : null))
const anchor = (i) => `bagian-${i + 1}`
</script>

<template>
    <section class="mx-auto max-w-[1320px] px-4 py-8 lg:px-15 lg:py-12">
        <Head title="Syarat & Ketentuan" />

        <header class="max-w-3xl">
            <p class="text-sm font-semibold text-slate-500">Bantuan</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink sm:text-3xl">Syarat &amp; Ketentuan</h1>
            <p class="mt-3 text-sm leading-6 whitespace-pre-line text-slate-600">{{ intro }}</p>
            <p class="mt-3 inline-flex items-center gap-1.5 text-xs text-slate-400">
                <Icon icon="heroicons:calendar" class="h-4 w-4" /> Terakhir diperbarui {{ diperbarui }}
            </p>
        </header>

        <div class="mt-8 grid gap-8 lg:grid-cols-[260px_minmax(0,1fr)] lg:gap-12">
            <nav aria-label="Daftar isi" class="lg:sticky lg:top-24 lg:self-start">
                <details class="group rounded-2xl border border-slate-200 lg:hidden">
                    <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-3 text-sm font-semibold text-ink">
                        Daftar isi
                        <Icon icon="heroicons:chevron-down" class="h-4 w-4 transition group-open:rotate-180" />
                    </summary>
                    <ol class="space-y-0.5 px-2 pb-2">
                        <li v-for="(b, i) in bagian" :key="i">
                            <a :href="`#${anchor(i)}`" class="flex items-center gap-3 rounded-lg px-2 py-2 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-ink">
                                <span class="w-5 text-right text-xs tabular-nums text-slate-400">{{ i + 1 }}</span>
                                {{ b.judul }}
                            </a>
                        </li>
                    </ol>
                </details>
                <ol class="hidden space-y-0.5 lg:block">
                    <li v-for="(b, i) in bagian" :key="i">
                        <a :href="`#${anchor(i)}`" class="flex items-center gap-3 rounded-lg px-2 py-2 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-ink">
                            <span class="w-5 text-right text-xs tabular-nums text-slate-400">{{ i + 1 }}</span>
                            {{ b.judul }}
                        </a>
                    </li>
                </ol>
            </nav>

            <article class="max-w-3xl space-y-10 text-sm leading-7 text-slate-600 [&_h2]:scroll-mt-24">
                <section v-for="(b, i) in bagian" :key="i" :aria-labelledby="anchor(i)">
                    <h2 :id="anchor(i)" class="flex items-center gap-3 text-lg font-bold text-ink">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100">
                            <Icon :icon="b.icon" class="h-5 w-5 text-slate-700" />
                        </span>
                        {{ i + 1 }}. {{ b.judul }}
                    </h2>

                    <RichText :text="b.isi" class="mt-3" />
                </section>

                <aside class="flex flex-col gap-4 rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold text-ink">Ada pertanyaan?</p>
                        <p class="text-sm text-slate-500">Hubungi admin bila ada yang belum jelas sebelum membeli.</p>
                    </div>
                    <UiButton v-if="waLink" :href="waLink" external icon="heroicons:chat-bubble-left-right" class="shrink-0">Chat Admin</UiButton>
                    <UiButton v-else href="/" variant="secondary" class="shrink-0">Kembali ke Toko</UiButton>
                </aside>
            </article>
        </div>
    </section>
</template>

<script>
import PublicLayout from '@/Layouts/PublicLayout.vue'

export default { layout: PublicLayout }
</script>
