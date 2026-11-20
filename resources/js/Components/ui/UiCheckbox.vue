<script setup>
// Checkbox dengan label; v-model boolean, atau array bila diberi `value`.
import { computed, useAttrs } from 'vue'

defineOptions({ inheritAttrs: false })

const attrs = useAttrs()
const controlAttrs = computed(() => {
    const { class: _c, style: _s, ...rest } = attrs
    return rest
})

const model = defineModel({ type: [Boolean, Array], default: false })
defineProps({
    value: { type: [String, Number, Boolean], default: undefined },
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
})
</script>

<template>
    <label class="flex min-h-9 cursor-pointer items-center gap-2.5 text-sm text-slate-700" :class="[$attrs.class, { 'cursor-not-allowed opacity-60': disabled }]" :style="$attrs.style">
        <input
            v-model="model"
            type="checkbox"
            :value="value"
            :disabled="disabled"
            v-bind="controlAttrs"
            class="form-checkbox h-5 w-5 shrink-0 rounded-md border-slate-300 text-primary transition focus:ring-2 focus:ring-primary-ring/50 focus:ring-offset-0"
        />
        <span class="min-w-0 flex-1">
            <slot>{{ label }}</slot>
            <span v-if="hint" class="block text-xs text-slate-500">{{ hint }}</span>
        </span>
        <slot name="end" />
    </label>
</template>
