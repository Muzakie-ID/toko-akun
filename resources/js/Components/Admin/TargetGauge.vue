<script setup>
import { computed } from 'vue'

const props = defineProps({ persen: { type: Number, required: true } })

// Setengah lingkaran dari 14 ruas; ruas terisi sesuai persen, makin ke kanan makin muda
const SEGMENTS = 14
const segments = computed(() => {
    const filled = Math.round((props.persen / 100) * SEGMENTS)
    return Array.from({ length: SEGMENTS }, (_, i) => {
        const gap = 3
        const start = 180 + (i * 180) / SEGMENTS + gap / 2
        const end = 180 + ((i + 1) * 180) / SEGMENTS - gap / 2
        return { d: arc(start, end), filled: i < filled, opacity: 1 - (i / SEGMENTS) * 0.55 }
    })
})

function point(r, deg) {
    const rad = (deg * Math.PI) / 180
    return [100 + r * Math.cos(rad), 100 + r * Math.sin(rad)]
}

function arc(start, end) {
    const [x1, y1] = point(90, start)
    const [x2, y2] = point(90, end)
    const [x3, y3] = point(58, end)
    const [x4, y4] = point(58, start)
    return `M${x1},${y1} A90,90 0 0 1 ${x2},${y2} L${x3},${y3} A58,58 0 0 0 ${x4},${y4} Z`
}
</script>

<template>
    <div class="relative mx-auto w-full max-w-[260px]">
        <svg viewBox="0 0 200 108" class="w-full" role="img" :aria-label="`Target tercapai ${persen}%`">
            <path
                v-for="(s, i) in segments"
                :key="i"
                :d="s.d"
                :class="s.filled ? 'fill-accent-500' : 'fill-slate-100'"
                :style="s.filled ? { opacity: s.opacity } : null"
                stroke-linejoin="round"
            />
        </svg>
        <div class="absolute inset-x-0 bottom-0 text-center">
            <p class="text-3xl font-semibold tracking-tight text-ink">{{ persen.toLocaleString('id-ID') }}%</p>
            <p class="text-xs text-slate-500">dari target bulan ini</p>
        </div>
    </div>
</template>
