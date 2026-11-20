<script setup>
import { computed, useAttrs } from 'vue'
import UiField from './UiField.vue'
import { controlBase, controlError, controlSize, nextId } from './controlClasses'

defineOptions({ inheritAttrs: false })

// class/style ke pembungkus (atur lebar dari luar), atribut lain ke elemen form
const attrs = useAttrs()
const rootAttrs = computed(() => ({ class: attrs.class, style: attrs.style }))
const controlAttrs = computed(() => {
    const { class: _c, style: _s, ...rest } = attrs
    return rest
})

const model = defineModel({ type: [String, Number, null], default: '' })
const props = defineProps({
    // [{ value, label }]; atau isi <option> lewat slot
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: '' },
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    size: { type: String, default: 'md' },
    id: { type: String, default: '' },
    required: { type: Boolean, default: false },
})

const selectId = computed(() => props.id || nextId('select'))
</script>

<template>
    <UiField v-bind="rootAttrs" :label="label" :for="selectId" :error="error" :hint="hint" :required="required">
        <select
            :id="selectId"
            v-model="model"
            :required="required"
            :aria-invalid="error ? 'true' : undefined"
            v-bind="controlAttrs"
            class="form-select pr-10 pl-3.5"
            :class="[controlBase, controlSize[size], error && controlError]"
        >
            <option v-if="placeholder" value="" :disabled="required">{{ placeholder }}</option>
            <slot>
                <option v-for="o in options" :key="o.value" :value="o.value">{{ o.label }}</option>
            </slot>
        </select>
    </UiField>
</template>
