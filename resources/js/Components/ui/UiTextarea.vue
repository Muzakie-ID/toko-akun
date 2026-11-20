<script setup>
import { computed, useAttrs } from 'vue'
import UiField from './UiField.vue'
import { controlBase, controlError, nextId } from './controlClasses'

defineOptions({ inheritAttrs: false })

// class/style ke pembungkus (atur lebar dari luar), atribut lain ke elemen form
const attrs = useAttrs()
const rootAttrs = computed(() => ({ class: attrs.class, style: attrs.style }))
const controlAttrs = computed(() => {
    const { class: _c, style: _s, ...rest } = attrs
    return rest
})

const model = defineModel({ type: [String, null], default: '' })
const props = defineProps({
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    rows: { type: [Number, String], default: 4 },
    id: { type: String, default: '' },
    required: { type: Boolean, default: false },
})

const textareaId = computed(() => props.id || nextId('textarea'))
</script>

<template>
    <UiField v-bind="rootAttrs" :label="label" :for="textareaId" :error="error" :hint="hint" :required="required">
        <textarea
            :id="textareaId"
            v-model="model"
            :rows="rows"
            :required="required"
            :aria-invalid="error ? 'true' : undefined"
            v-bind="controlAttrs"
            class="form-textarea px-3.5 py-2.5 text-sm"
            :class="[controlBase, error && controlError]"
        />
    </UiField>
</template>
