<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({ paginator: { type: Object, required: true } })

// Link pertama/terakhir dari Laravel berlabel "Previous"/"Next"; nomor halaman di tengah
const pages = computed(() => props.paginator.links.slice(1, -1))
</script>

<template>
    <nav v-if="paginator.last_page > 1" class="flex flex-wrap items-center justify-between gap-3 px-4 py-4 text-sm sm:px-5" aria-label="Halaman">
        <p class="text-slate-500">{{ paginator.from }}–{{ paginator.to }} dari {{ paginator.total }}</p>

        <!-- HP: sebelumnya / berikutnya saja -->
        <div class="flex items-center gap-2 sm:hidden">
            <Link v-if="paginator.prev_page_url" :href="paginator.prev_page_url" preserve-scroll class="flex h-10 items-center gap-1 rounded-xl px-3 font-medium text-ink ring-1 ring-slate-200" aria-label="Halaman sebelumnya">
                <Icon icon="heroicons:chevron-left" class="h-4 w-4" />
            </Link>
            <span class="text-slate-500">{{ paginator.current_page }} / {{ paginator.last_page }}</span>
            <Link v-if="paginator.next_page_url" :href="paginator.next_page_url" preserve-scroll class="flex h-10 items-center gap-1 rounded-xl px-3 font-medium text-ink ring-1 ring-slate-200" aria-label="Halaman berikutnya">
                <Icon icon="heroicons:chevron-right" class="h-4 w-4" />
            </Link>
        </div>

        <!-- Desktop: nomor halaman -->
        <div class="hidden flex-wrap items-center gap-1 sm:flex">
            <Link v-if="paginator.prev_page_url" :href="paginator.prev_page_url" preserve-scroll class="flex h-8 items-center rounded-lg px-2.5 text-slate-600 hover:bg-slate-100"><Icon icon="heroicons:chevron-left" class="mr-0.5 h-4 w-4" />Sebelumnya</Link>
            <template v-for="link in pages" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    class="flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5"
                    :class="link.active ? 'bg-primary font-semibold text-white' : 'text-slate-600 hover:bg-slate-100'"
                    :aria-current="link.active ? 'page' : undefined"
                >{{ link.label }}</Link>
                <span v-else class="flex h-8 min-w-8 items-center justify-center px-2.5 text-slate-300">{{ link.label }}</span>
            </template>
            <Link v-if="paginator.next_page_url" :href="paginator.next_page_url" preserve-scroll class="flex h-8 items-center rounded-lg px-2.5 text-slate-600 hover:bg-slate-100">Berikutnya<Icon icon="heroicons:chevron-right" class="ml-0.5 h-4 w-4" /></Link>
        </div>
    </nav>
</template>
