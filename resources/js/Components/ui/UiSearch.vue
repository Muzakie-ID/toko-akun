<script setup>
// Kolom cari dengan ikon; kirim (enter) memicu event `search`.
import { computed, useAttrs } from 'vue'

defineOptions({ inheritAttrs: false })

const attrs = useAttrs()
const controlAttrs = computed(() => {
    const { class: _c, style: _s, ...rest } = attrs
    return rest
})

const model = defineModel({ type: [String, null], default: '' })
defineProps({
    placeholder: { type: String, default: 'Cari…' },
    label: { type: String, default: 'Cari' },
    size: { type: String, default: 'md' },
    tone: { type: String, default: 'muted' }, // muted (abu) | plain (putih)
})
const emit = defineEmits(['search'])
</script>

<template>
    <form role="search" class="w-full" :class="$attrs.class" :style="$attrs.style" @submit.prevent="emit('search', model)">
        <label
            class="flex w-full items-center gap-2.5 rounded-xl px-3.5 transition focus-within:ring-2 focus-within:ring-primary-ring/50"
            :class="[
                size === 'sm' ? 'h-9' : size === 'lg' ? 'h-12 rounded-2xl' : 'h-11',
                tone === 'plain' ? 'border border-slate-300 bg-white' : 'bg-slate-100',
            ]"
        >
            <Icon icon="heroicons:magnifying-glass" class="h-4.5 w-4.5 shrink-0 text-slate-400" />
            <input
                v-model="model"
                type="search"
                :aria-label="label"
                :placeholder="placeholder"
                v-bind="controlAttrs"
                class="w-full min-w-0 border-0 bg-transparent p-0 text-sm text-ink placeholder:text-slate-400 focus:ring-0 focus:outline-none"
            />
        </label>
    </form>
</template>
