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
                <svg class="h-6 w-6 text-brand-900" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <!-- Mark angular "A" menyilang -->
                    <path d="M12 2 1 22h5.2L12 11.4 17.8 22H23L12 2Zm-1.9 14h3.8L12 13.2 10.1 16Z" />
                </svg>
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
                    <Icon v-if="link.hasChevron" icon="heroicons:chevron-down" class="h-3.5 w-3.5" />
                </Link>
            </div>

            <!-- Search -->
            <div class="ml-auto hidden min-w-0 flex-1 justify-center lg:flex">
                <label class="flex h-10 w-full max-w-[420px] items-center gap-2.5 rounded-full border border-slate-200 bg-slate-50 px-4 transition focus-within:border-slate-400 focus-within:bg-white focus-within:ring-2 focus-within:ring-slate-200">
                    <Icon icon="heroicons:magnifying-glass" class="h-4.5 w-4.5 shrink-0 text-slate-400" />
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
                        <Icon icon="heroicons:user" class="h-5 w-5" />
                        <span class="hidden sm:inline">{{ user.name.split(' ')[0] }}</span>
                    </Link>
                    <!-- Logout: POST via form agar CSRF terkirim -->
                    <form method="POST" action="/logout">
                        <input type="hidden" name="_token" :value="$page.props.csrf_token ?? ''" />
                        <button type="submit" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-ink" aria-label="Keluar">
                            <Icon icon="heroicons:arrow-right-start-on-rectangle" class="h-5 w-5" />
                        </button>
                    </form>
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
                        <Icon icon="heroicons:shopping-cart" class="h-5 w-5" />
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
                    <Icon :icon="mobileOpen ? 'heroicons:x-mark' : 'heroicons:bars-3'" class="h-5 w-5" />
                </button>
            </div>
        </nav>

        <!-- Mobile panel -->
        <div v-if="mobileOpen" class="border-t border-slate-200 px-4 py-3 md:hidden">
            <label class="mb-2 flex h-10 items-center gap-2.5 rounded-xl border border-slate-200 bg-slate-50 px-4">
                <Icon icon="heroicons:magnifying-glass" class="h-4.5 w-4.5 text-slate-400" />
                <input type="search" placeholder="Cari produk..." class="w-full bg-transparent text-sm focus:outline-none" />
            </label>
            <Link v-for="link in navLinks" :key="link.label" :href="link.href" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                {{ link.label }}
            </Link>
        </div>
    </header>
</template>
