<script setup>
// Hitung mundur ke waktu tertentu (ISO 8601). Saat habis, emit 'selesai' agar halaman bisa memuat ulang.
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
    to: { type: String, required: true },
    variant: { type: String, default: 'boxes' }, // boxes | inline
})
const emit = defineEmits(['selesai'])

const now = ref(Date.now())
let timer = null
onMounted(() => {
    timer = setInterval(() => {
        now.value = Date.now()
        if (sisaMs.value <= 0) {
            clearInterval(timer)
            emit('selesai')
        }
    }, 1000)
})
onBeforeUnmount(() => clearInterval(timer))

const sisaMs = computed(() => Math.max(0, new Date(props.to).getTime() - now.value))
const parts = computed(() => {
    const total = Math.floor(sisaMs.value / 1000)
    const hari = Math.floor(total / 86400)
    const jam = Math.floor((total % 86400) / 3600)
    return {
        hari,
        jam: String(jam).padStart(2, '0'),
        menit: String(Math.floor((total % 3600) / 60)).padStart(2, '0'),
        detik: String(total % 60).padStart(2, '0'),
    }
})
const label = computed(() => `${parts.value.hari ? `${parts.value.hari} hari ` : ''}${parts.value.jam}:${parts.value.menit}:${parts.value.detik}`)
</script>

<template>
    <span v-if="variant === 'inline'" class="font-mono tabular-nums" role="timer" :aria-label="`Berakhir dalam ${label}`">{{ label }}</span>
    <span v-else class="inline-flex items-center gap-1" role="timer" :aria-label="`Berakhir dalam ${label}`">
        <template v-if="parts.hari">
            <span class="flex min-w-8 flex-col items-center rounded-md bg-white px-1.5 py-1 text-ink"><span class="text-sm font-bold tabular-nums">{{ parts.hari }}</span><span class="text-[9px] text-slate-500">HARI</span></span>
        </template>
        <span v-for="(v, k) in { JAM: parts.jam, MNT: parts.menit, DTK: parts.detik }" :key="k" class="flex min-w-8 flex-col items-center rounded-md bg-white px-1.5 py-1 text-ink">
            <span class="text-sm font-bold tabular-nums">{{ v }}</span><span class="text-[9px] text-slate-500">{{ k }}</span>
        </span>
    </span>
</template>
