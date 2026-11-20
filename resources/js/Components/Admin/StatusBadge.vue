<script setup>
defineProps({
    tone: { type: String, default: 'slate' },
    label: { type: String, required: true },
})

const tones = {
    amber: 'bg-amber-50 text-amber-700 ring-amber-200',
    blue: 'bg-accent-50 text-accent-700 ring-accent-200',
    violet: 'bg-violet-50 text-violet-700 ring-violet-200',
    emerald: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    slate: 'bg-slate-50 text-slate-500 ring-slate-200',
    rose: 'bg-rose-50 text-rose-700 ring-rose-200',
}
</script>

<template>
    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset" :class="tones[tone] ?? tones.slate">
        {{ label }}
    </span>
</template>
