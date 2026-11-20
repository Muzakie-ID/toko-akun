<script setup>
defineOptions({ inheritAttrs: false })

const model = defineModel({ type: [String, Number, null], default: null })
defineProps({
    value: { type: [String, Number], required: true },
    disabled: { type: Boolean, default: false },
})
</script>

<template>
    <input
        v-model="model"
        type="radio"
        :value="value"
        :disabled="disabled"
        v-bind="$attrs"
        class="form-radio h-5 w-5 shrink-0 border-slate-300 text-primary transition focus:ring-2 focus:ring-primary-ring/50 focus:ring-offset-0 disabled:opacity-50"
    />
</template>
