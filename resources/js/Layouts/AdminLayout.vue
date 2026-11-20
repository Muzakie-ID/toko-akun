<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const user = computed(() => page.props.auth?.user)

const flash = computed(() => page.props.flash ?? {})

const menu = [
    { label: 'Produk', href: '/admin/produk', icon: 'heroicons:cube' },
    { label: 'Input Stok Massal', href: '/admin/stok/import', icon: 'heroicons:arrow-up-tray' },
]
</script>

<template>
    <div class="flex min-h-screen bg-slate-50">
        <aside class="hidden w-60 shrink-0 flex-col border-r border-slate-200 bg-white lg:flex">
            <Link href="/" class="flex h-16 items-center gap-2 border-b border-slate-200 px-5">
                <Icon icon="heroicons:building-storefront-solid" class="h-6 w-6 text-brand-900" />
                <span class="text-lg font-bold tracking-tight text-ink">akunhaus</span>
                <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-500 uppercase">Admin</span>
            </Link>
            <nav class="flex flex-col gap-1 p-3">
                <Link
                    v-for="item in menu"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition"
                    :class="page.url.startsWith(item.href) ? 'bg-slate-100 text-ink' : 'text-slate-600 hover:bg-slate-50 hover:text-ink'"
                >
                    <Icon :icon="item.icon" class="h-4.5 w-4.5" />
                    {{ item.label }}
                </Link>
            </nav>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 lg:px-8">
                <Link href="/" class="text-lg font-bold tracking-tight text-ink lg:hidden">akunhaus</Link>
                <span class="ml-auto text-sm text-slate-600">{{ user?.name }} &middot; <span class="capitalize">{{ user?.role }}</span></span>
            </header>
            <nav class="flex gap-1 overflow-x-auto border-b border-slate-200 bg-white px-4 py-2 lg:hidden">
                <Link
                    v-for="item in menu"
                    :key="item.href"
                    :href="item.href"
                    class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium"
                    :class="page.url.startsWith(item.href) ? 'bg-slate-100 text-ink' : 'text-slate-600'"
                >
                    {{ item.label }}
                </Link>
            </nav>
            <main class="flex-1 px-4 py-8 lg:px-8">
                <div v-if="flash.success" class="mx-auto mb-6 max-w-5xl rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                    {{ flash.success }}
                </div>
                <div v-if="flash.error" class="mx-auto mb-6 max-w-5xl rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                    {{ flash.error }}
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
