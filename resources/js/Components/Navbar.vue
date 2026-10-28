<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const page = usePage()
const cartCount = computed(() => page.props.cart_count ?? 0)
const user = computed(() => page.props.auth?.user)
const mobileOpen = ref(false)

const navLinks = [
    { label: 'Kategori', href: '/#katalog', hasChevron: true },
    { label: 'Deals', href: '/#terlaris' },
    { label: 'Cara Order', href: '/#cara-order' },
]
</script>

<template>
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
        <nav class="mx-auto flex h-[72px] max-w-[1320px] items-center gap-6 px-4 lg:px-15">
            <!-- Logo -->
            <Link href="/" class="flex shrink-0 items-center gap-2">
                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-brand-600 text-[13px] font-bold text-white">A</span>
                <span class="text-lg font-bold tracking-tight text-ink">akunhaus</span>
            </Link>

            <!-- Nav links (desktop) -->
            <div class="hidden items-center gap-1 md:flex">
                <Link
                    v-for="link in navLinks"
                    :key="link.label"
                    :href="link.href"
                    class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-ink"
                >
                    {{ link.label }}
                    <svg v-if="link.hasChevron" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </Link>
            </div>

            <!-- Search -->
            <div class="ml-auto hidden min-w-0 flex-1 justify-center lg:flex">
                <label class="flex h-10 w-full max-w-[420px] items-center gap-2.5 rounded-xl border border-slate-200 bg-slate-50 px-4 transition focus-within:border-brand-400 focus-within:bg-white focus-within:ring-2 focus-within:ring-brand-100">
                    <svg class="h-4.5 w-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input
                        type="search"
                        placeholder="Cari produk, misalnya &quot;Netflix&quot; atau &quot;ChatGPT&quot;"
                        class="w-full bg-transparent text-sm text-ink placeholder:text-slate-400 focus:outline-none"
                    />
                </label>
            </div>

            <!-- Actions -->
            <div class="ml-auto flex shrink-0 items-center gap-1 lg:ml-0">
                <template v-if="user">
                    <Link href="/pesanan" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-ink">
                        <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        <span class="hidden sm:inline">Akun Saya</span>
                    </Link>
                </template>
                <template v-else>
                    <Link href="/login" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-ink sm:block">
                        Masuk
                    </Link>
                    <Link href="/register" class="hidden rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-brand-700 sm:block">
                        Daftar
                    </Link>
                </template>

                <Link href="/keranjang" class="relative flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-ink" aria-label="Keranjang">
                    <span class="relative">
                        <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                        <span
                            v-if="cartCount > 0"
                            class="absolute -top-1.5 -right-2 flex h-4.5 min-w-4.5 items-center justify-center rounded-full bg-brand-600 px-1 text-[10px] font-bold text-white"
                        >{{ cartCount }}</span>
                    </span>
                    <span class="hidden sm:inline">Keranjang</span>
                </Link>

                <!-- Mobile menu toggle -->
                <button
                    class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 md:hidden"
                    @click="mobileOpen = !mobileOpen"
                    aria-label="Menu"
                >
                    <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path v-if="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        <path v-else stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </nav>

        <!-- Mobile panel -->
        <div v-if="mobileOpen" class="border-t border-slate-200 px-4 py-3 md:hidden">
            <label class="mb-2 flex h-10 items-center gap-2.5 rounded-xl border border-slate-200 bg-slate-50 px-4">
                <svg class="h-4.5 w-4.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="search" placeholder="Cari produk..." class="w-full bg-transparent text-sm focus:outline-none" />
            </label>
            <Link v-for="link in navLinks" :key="link.label" :href="link.href" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                {{ link.label }}
            </Link>
        </div>
    </header>
</template>
