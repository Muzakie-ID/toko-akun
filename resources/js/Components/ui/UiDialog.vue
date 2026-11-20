<script setup>
// Dialog modal seragam: latar gelap, panel putih, judul, tombol tutup, isi, footer. Tutup dengan Esc / klik latar.
import { onBeforeUnmount, onMounted } from 'vue'

const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    size: { type: String, default: 'md' }, // md | lg
})
const emit = defineEmits(['close'])

const onKey = (e) => e.key === 'Escape' && emit('close')
onMounted(() => document.addEventListener('keydown', onKey))
onBeforeUnmount(() => document.removeEventListener('keydown', onKey))
</script>

<template>
    <!-- Tidak di-teleport agar tetap di dalam .theme-admin (warna utama admin) -->
    <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/40 p-0 sm:items-center sm:p-4" @click.self="emit('close')">
            <div
                role="dialog"
                aria-modal="true"
                :aria-label="title"
                class="max-h-[90vh] w-full overflow-y-auto rounded-t-3xl bg-white p-5 shadow-xl sm:rounded-2xl"
                :class="size === 'lg' ? 'sm:max-w-lg' : 'sm:max-w-md'"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-ink">{{ title }}</h2>
                        <p v-if="description" class="mt-0.5 text-sm text-slate-500">{{ description }}</p>
                    </div>
                    <button type="button" class="-mt-1 -mr-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100" aria-label="Tutup" @click="emit('close')">
                        <Icon icon="heroicons:x-mark" class="h-5 w-5" />
                    </button>
                </div>
                <div class="mt-4"><slot /></div>
                <div v-if="$slots.footer" class="mt-5 flex flex-wrap justify-end gap-2"><slot name="footer" /></div>
            </div>
    </div>
</template>
