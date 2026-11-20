<script setup>
// Pengatur jumlah (− n +) dengan ikon, dipakai di detail produk dan keranjang.
const props = defineProps({
    modelValue: { type: Number, required: true },
    min: { type: Number, default: 1 },
    max: { type: Number, default: 99 },
    label: { type: String, default: 'jumlah' },
})
const emit = defineEmits(['update:modelValue'])
const set = (v) => v >= props.min && v <= props.max && emit('update:modelValue', v)
</script>

<template>
    <div class="inline-flex items-center gap-1 rounded-xl ring-1 ring-slate-300 ring-inset">
        <button type="button" class="flex h-10 w-10 items-center justify-center rounded-l-xl text-slate-600 hover:bg-slate-50 disabled:opacity-30" :disabled="modelValue <= min" :aria-label="`Kurangi ${label}`" @click="set(modelValue - 1)">
            <Icon icon="heroicons:minus" class="h-4 w-4" />
        </button>
        <span class="w-8 text-center text-sm font-semibold text-ink tabular-nums" aria-live="polite">{{ modelValue }}</span>
        <button type="button" class="flex h-10 w-10 items-center justify-center rounded-r-xl text-slate-600 hover:bg-slate-50 disabled:opacity-30" :disabled="modelValue >= max" :aria-label="`Tambah ${label}`" @click="set(modelValue + 1)">
            <Icon icon="heroicons:plus" class="h-4 w-4" />
        </button>
    </div>
</template>
