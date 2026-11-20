<script setup>
// Label + kontrol + petunjuk/error dengan format yang sama di semua form.
defineProps({
    label: { type: String, default: '' },
    for: { type: String, default: '' },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    required: { type: Boolean, default: false },
})
</script>

<template>
    <div>
        <label v-if="label" :for="$props.for" class="mb-1.5 block text-sm font-medium text-ink">
            {{ label }}<span v-if="required" class="text-rose-500" aria-hidden="true"> *</span>
        </label>
        <slot />
        <p v-if="error" class="mt-1 flex items-start gap-1 text-xs text-rose-600" role="alert">
            <Icon icon="heroicons:exclamation-circle" class="mt-px h-3.5 w-3.5 shrink-0" />{{ error }}
        </p>
        <p v-else-if="hint" class="mt-1 text-xs text-slate-500">{{ hint }}</p>
    </div>
</template>
