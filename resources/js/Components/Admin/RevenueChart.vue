<script setup>
import { computed, ref } from 'vue'
import { rupiah, rupiahRingkas } from '@/lib/format'

const props = defineProps({ data: { type: Array, required: true } })

const max = computed(() => {
    const peak = Math.max(...props.data.map((d) => d.nilai), 0)
    if (peak === 0) return 1
    const magnitude = 10 ** Math.floor(Math.log10(peak))
    return Math.ceil(peak / magnitude) * magnitude
})
const ticks = computed(() => [4, 3, 2, 1, 0].map((i) => (max.value / 4) * i))
const highlighted = computed(() => props.data.length - 1)
const hovered = ref(null)
const active = computed(() => hovered.value ?? highlighted.value)
</script>

<template>
    <div class="flex gap-2 sm:gap-3">
        <div class="flex h-56 shrink-0 flex-col justify-between pb-6 text-right text-[10px] whitespace-nowrap text-slate-400 sm:text-[11px]" aria-hidden="true">
            <span v-for="t in ticks" :key="t">{{ rupiahRingkas(t) }}</span>
        </div>
        <div class="relative flex-1">
            <div class="pointer-events-none absolute inset-x-0 top-0 bottom-6 flex flex-col justify-between" aria-hidden="true">
                <span v-for="t in ticks" :key="t" class="border-t border-dashed border-slate-200" />
            </div>
            <ul class="relative flex h-56 items-end gap-1 sm:gap-3" role="list">
                <li
                    v-for="(d, i) in data"
                    :key="d.label"
                    class="flex h-full flex-1 flex-col items-center justify-end"
                    @mouseenter="hovered = i"
                    @mouseleave="hovered = null"
                >
                    <div class="relative flex w-full flex-1 items-end justify-center pb-1">
                        <span
                            v-if="active === i"
                            class="absolute z-10 -translate-y-full rounded-md bg-primary px-2 py-1 text-[11px] font-semibold whitespace-nowrap text-white shadow"
                            :class="i >= data.length - 2 ? 'right-0' : i <= 1 ? 'left-0' : ''"
                            :style="{ bottom: `${Math.max(d.nilai / max * 100, 2)}%` }"
                        >{{ rupiah(d.nilai) }}</span>
                        <span
                            class="w-full max-w-9 rounded-full transition-all"
                            :class="active === i ? 'bg-gradient-to-b from-accent-500 to-accent-300' : 'bar-striped'"
                            :style="{ height: `${Math.max(d.nilai / max * 100, 3)}%` }"
                            :aria-label="`${d.label}: ${rupiah(d.nilai)}`"
                        />
                    </div>
                    <span class="h-6 pt-1.5 text-[10px] text-slate-500 sm:text-[11px]" :class="{ 'font-semibold text-ink': active === i }">{{ d.label }}</span>
                </li>
            </ul>
        </div>
    </div>
</template>
