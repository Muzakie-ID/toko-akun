<script setup>
// Bintang 1–5. Mode input bila `editable`, dengan radiogroup agar bisa dipakai keyboard.
const model = defineModel({ type: Number, default: 0 })

defineProps({
    editable: { type: Boolean, default: false },
    size: { type: String, default: 'h-4 w-4' },
    label: { type: String, default: 'Rating' },
})
</script>

<template>
    <div v-if="editable" class="flex gap-1" role="radiogroup" :aria-label="label">
        <button
            v-for="n in 5"
            :key="n"
            type="button"
            role="radio"
            :aria-checked="model === n"
            :aria-label="`${n} bintang`"
            class="rounded transition hover:scale-110 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none"
            @click="model = n"
        >
            <Icon icon="heroicons:star-solid" :class="[size, n <= model ? 'text-amber-400' : 'text-slate-200']" />
        </button>
    </div>
    <div v-else class="flex gap-0.5" :aria-label="`${model} dari 5 bintang`" role="img">
        <Icon v-for="n in 5" :key="n" icon="heroicons:star-solid" :class="[size, n <= model ? 'text-amber-400' : 'text-slate-200']" />
    </div>
</template>
