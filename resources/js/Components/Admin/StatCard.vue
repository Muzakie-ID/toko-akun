<script setup>
import { computed } from 'vue'
import { angka, rupiah } from '@/lib/format'

const props = defineProps({ stat: { type: Object, required: true } })

const nilai = computed(() => (props.stat.format === 'rupiah' ? rupiah(props.stat.nilai) : angka(props.stat.nilai)))
const naik = computed(() => (props.stat.pertumbuhan ?? 0) >= 0)
</script>

<template>
    <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200/70 sm:p-5">
        <div class="flex items-center gap-2.5">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent-50 text-accent-600">
                <Icon :icon="stat.icon" class="h-4 w-4" />
            </span>
            <span class="min-w-0 truncate text-sm font-medium text-ink">{{ stat.label }}</span>
        </div>
        <p class="mt-3 truncate text-xl leading-none font-semibold tracking-tight text-ink sm:mt-4 sm:text-[26px]" :title="nilai">{{ nilai }}</p>
        <p class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
            <span
                v-if="stat.pertumbuhan !== null"
                class="inline-flex items-center gap-0.5 rounded-md px-1.5 py-0.5 font-semibold"
                :class="naik ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
            >
                <Icon :icon="naik ? 'heroicons:arrow-up' : 'heroicons:arrow-down'" class="h-3 w-3" />
                {{ Math.abs(stat.pertumbuhan).toLocaleString('id-ID') }}%
            </span>
            <span v-else class="rounded-md bg-slate-100 px-1.5 py-0.5 font-semibold text-slate-500">baru</span>
            <span class="hidden sm:inline">dari bulan lalu</span>
        </p>
    </div>
</template>
