<script setup>
// Tombol seragam. href = Link Inertia (atau <a> bila external). Ikon dari Iconify (heroicons:*).
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
    variant: { type: String, default: 'primary' }, // primary | secondary | ghost | danger | danger-soft | soft | link | white
    size: { type: String, default: 'md' }, // sm | md | lg
    href: { type: String, default: '' },
    external: { type: Boolean, default: false }, // <a target=_blank> (situs lain)
    native: { type: Boolean, default: false }, // <a> biasa, mis. jangkar #katalog
    type: { type: String, default: 'button' },
    icon: { type: String, default: '' },
    iconRight: { type: String, default: '' },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    block: { type: Boolean, default: false },
    square: { type: Boolean, default: false }, // tombol ikon saja
    label: { type: String, default: '' }, // aria-label untuk tombol ikon saja
})

const variants = {
    primary: 'bg-primary text-white hover:bg-primary-hover',
    secondary: 'bg-white text-ink ring-1 ring-inset ring-slate-300 hover:bg-slate-50',
    soft: 'bg-primary-soft text-primary hover:brightness-95',
    ghost: 'text-slate-600 hover:bg-slate-100 hover:text-ink',
    danger: 'bg-rose-600 text-white hover:bg-rose-700',
    'danger-soft': 'text-rose-600 ring-1 ring-inset ring-rose-200 hover:bg-rose-50',
    link: 'text-primary underline-offset-2 hover:underline',
    white: 'bg-white text-ink hover:bg-slate-100',
}

const sizes = {
    sm: 'h-9 gap-1.5 px-3 text-xs',
    md: 'h-11 gap-2 px-5 text-sm',
    lg: 'h-12 gap-2 px-6 text-sm',
}
const squareSizes = { sm: 'h-9 w-9', md: 'h-11 w-11', lg: 'h-12 w-12' }
const iconSizes = { sm: 'h-4 w-4', md: 'h-4.5 w-4.5', lg: 'h-5 w-5' }

const classes = computed(() => [
    'inline-flex shrink-0 items-center justify-center rounded-xl font-semibold whitespace-nowrap transition',
    'focus-visible:ring-2 focus-visible:ring-primary-ring focus-visible:ring-offset-2 focus-visible:outline-none',
    'disabled:cursor-not-allowed disabled:opacity-40 aria-disabled:pointer-events-none aria-disabled:opacity-40',
    variants[props.variant] ?? variants.primary,
    props.variant === 'link' ? 'h-auto px-0 py-1.5' : props.square ? squareSizes[props.size] : sizes[props.size],
    props.block && 'w-full',
])

const isDisabled = computed(() => props.disabled || props.loading)
</script>

<template>
    <a v-if="href && (external || native)" :href="href" :target="external ? '_blank' : undefined" :rel="external ? 'noopener' : undefined" :class="classes" :aria-label="label || undefined" v-bind="$attrs">
        <Icon v-if="icon" :icon="icon" :class="iconSizes[size]" />
        <slot />
        <Icon v-if="iconRight" :icon="iconRight" :class="iconSizes[size]" />
    </a>
    <Link v-else-if="href" :href="href" :class="classes" :aria-label="label || undefined" :aria-disabled="isDisabled || undefined" v-bind="$attrs">
        <Icon v-if="icon" :icon="icon" :class="iconSizes[size]" />
        <slot />
        <Icon v-if="iconRight" :icon="iconRight" :class="iconSizes[size]" />
    </Link>
    <button v-else :type="type" :class="classes" :disabled="isDisabled" :aria-label="label || undefined" :aria-busy="loading || undefined" v-bind="$attrs">
        <Icon v-if="loading" icon="heroicons:arrow-path" :class="[iconSizes[size], 'animate-spin']" />
        <Icon v-else-if="icon" :icon="icon" :class="iconSizes[size]" />
        <slot />
        <Icon v-if="iconRight" :icon="iconRight" :class="iconSizes[size]" />
    </button>
</template>
