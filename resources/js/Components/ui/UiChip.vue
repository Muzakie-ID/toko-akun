<script setup>
// Chip/pil yang bisa aktif (filter, kategori, nominal, tab status). Tinggi & warna aktif seragam.
defineProps({
    active: { type: Boolean, default: false },
    icon: { type: String, default: '' },
    count: { type: [Number, String], default: null },
    size: { type: String, default: 'md' }, // sm | md
    role: { type: String, default: '' }, // mis. 'tab'
})
</script>

<template>
    <button
        type="button"
        :role="role || undefined"
        :aria-pressed="role ? undefined : active"
        :aria-selected="role === 'tab' ? active : undefined"
        class="inline-flex shrink-0 items-center gap-1.5 rounded-full font-medium whitespace-nowrap transition focus-visible:ring-2 focus-visible:ring-primary-ring focus-visible:outline-none"
        :class="[
            size === 'sm' ? 'h-9 px-3.5 text-sm' : 'h-10 px-4 text-sm',
            active ? 'bg-primary text-white' : 'bg-white text-slate-600 ring-1 ring-inset ring-slate-200 hover:ring-slate-400 hover:text-ink',
        ]"
    >
        <Icon v-if="icon" :icon="icon" class="h-4 w-4" />
        <slot />
        <span v-if="count !== null" class="opacity-60">{{ count }}</span>
    </button>
</template>
