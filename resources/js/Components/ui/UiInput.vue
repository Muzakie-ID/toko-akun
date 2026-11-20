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
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    icon: { type: String, default: '' },
    size: { type: String, default: 'md' },
    id: { type: String, default: '' },
    type: { type: String, default: 'text' },
    required: { type: Boolean, default: false },
})

const inputId = computed(() => props.id || nextId('input'))
</script>

<template>
    <UiField v-bind="rootAttrs" :label="label" :for="inputId" :error="error" :hint="hint" :required="required">
        <div class="relative">
            <Icon v-if="icon" :icon="icon" class="pointer-events-none absolute top-1/2 left-3.5 h-4.5 w-4.5 -translate-y-1/2 text-slate-400" />
            <input
                :id="inputId"
                v-model="model"
                :type="type"
                :required="required"
                :aria-invalid="error ? 'true' : undefined"
                v-bind="controlAttrs"
                class="form-input"
                :class="[controlBase, controlSize[size], icon ? 'pl-10' : 'px-3.5', error && controlError]"
            />
        </div>
    </UiField>
</template>
