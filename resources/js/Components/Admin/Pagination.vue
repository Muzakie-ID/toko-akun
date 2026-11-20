<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({ paginator: { type: Object, required: true } })
</script>

<template>
    <nav v-if="paginator.last_page > 1" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 text-sm" aria-label="Halaman">
        <p class="text-slate-500">{{ paginator.from }}–{{ paginator.to }} dari {{ paginator.total }}</p>
        <div class="flex flex-wrap gap-1">
            <template v-for="link in paginator.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    class="flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5"
                    :class="link.active ? 'bg-accent-600 font-semibold text-white' : 'text-slate-600 hover:bg-slate-100'"
                ><span v-html="link.label" /></Link>
                <span v-else class="flex h-8 min-w-8 items-center justify-center px-2.5 text-slate-300" v-html="link.label" />
            </template>
        </div>
    </nav>
</template>
