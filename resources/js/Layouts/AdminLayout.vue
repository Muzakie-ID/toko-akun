<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const flash = computed(() => page.props.flash ?? {})
const badges = computed(() => page.props.adminBadges ?? {})
const isAdmin = computed(() => user.value?.role === 'admin')
const drawerOpen = ref(false)
const search = ref('')

watch(() => page.url, () => (drawerOpen.value = false))

const sections = computed(() => [
    {
        title: 'Menu Utama',
        items: [
            { label: 'Dashboard', href: '/admin', icon: 'heroicons:squares-2x2', exact: true },
            { label: 'Pesanan', href: '/admin/pesanan', icon: 'heroicons:shopping-bag', badge: badges.value.pesanan },
            { label: 'Top-up Saldo', href: '/admin/topup', icon: 'heroicons:wallet' },
            { label: 'Produk', href: '/admin/produk', icon: 'heroicons:cube' },
            { label: 'Kategori', href: '/admin/kategori', icon: 'heroicons:tag' },
            { label: 'Stok Akun', href: '/admin/stok', icon: 'heroicons:key', exactPrefix: '/admin/stok', exclude: '/admin/stok/import', badge: badges.value.menungguStok, badgeTone: 'amber' },
            { label: 'Input Stok Massal', href: '/admin/stok/import', icon: 'heroicons:arrow-up-tray' },
            { label: 'Ulasan', href: '/admin/ulasan', icon: 'heroicons:star' },
        ],
    },
    ...(isAdmin.value
        ? [{
            title: 'Toko',
            items: [
                { label: 'Pengguna', href: '/admin/pengguna', icon: 'heroicons:users' },
                { label: 'Pengaturan', href: '/admin/pengaturan', icon: 'heroicons:cog-6-tooth' },
            ],
        }]
        : []),
])

const path = computed(() => page.url.split('?')[0])
const isActive = (item) => {
    if (item.exact) return path.value === item.href
    if (item.exclude && path.value.startsWith(item.exclude)) return false
    return path.value === item.href || path.value.startsWith(item.href + '/')
}

const cariPesanan = () => router.get('/admin/pesanan', search.value ? { q: search.value } : {})
const logout = () => router.post('/logout')
</script>

<template>
    <div class="min-h-screen bg-admin-bg p-0 lg:p-4">
        <div class="flex min-h-screen gap-4 lg:min-h-[calc(100vh-2rem)]">
            <!-- Overlay mobile -->
            <div v-if="drawerOpen" class="fixed inset-0 z-40 bg-slate-900/30 lg:hidden" @click="drawerOpen = false" />

            <!-- Sidebar -->
            <aside
                class="fixed inset-y-0 left-0 z-50 flex w-64 shrink-0 flex-col bg-white p-4 transition-transform lg:sticky lg:top-4 lg:z-auto lg:h-[calc(100vh-2rem)] lg:translate-x-0 lg:rounded-2xl lg:ring-1 lg:ring-slate-200/70"
                :class="drawerOpen ? 'translate-x-0 shadow-xl' : '-translate-x-full'"
            >
                <Link href="/admin" class="flex items-center gap-2.5 px-2 py-1.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-accent-400 to-accent-600 text-white shadow-sm">
                        <Icon icon="heroicons:building-storefront-solid" class="h-5 w-5" />
                    </span>
                    <span class="text-lg font-semibold tracking-tight text-ink">akunhaus</span>
                </Link>

                <nav class="mt-6 flex-1 space-y-6 overflow-y-auto" aria-label="Menu admin">
                    <div v-for="section in sections" :key="section.title">
                        <p class="px-3 text-xs font-medium text-slate-400">{{ section.title }}</p>
                        <ul class="mt-2 space-y-0.5">
                            <li v-for="item in section.items" :key="item.href" class="relative">
                                <span v-if="isActive(item)" class="absolute top-1.5 bottom-1.5 -left-4 w-1 rounded-r-full bg-accent-600" aria-hidden="true" />
                                <Link
                                    :href="item.href"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm transition"
                                    :class="isActive(item) ? 'bg-accent-50 font-semibold text-ink' : 'text-slate-600 hover:bg-slate-50 hover:text-ink'"
                                    :aria-current="isActive(item) ? 'page' : undefined"
                                >
                                    <Icon :icon="item.icon" class="h-[18px] w-[18px]" :class="isActive(item) ? 'text-accent-600' : 'text-slate-400'" />
                                    <span class="flex-1">{{ item.label }}</span>
                                    <span
                                        v-if="item.badge"
                                        class="flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[11px] font-semibold text-white"
                                        :class="item.badgeTone === 'amber' ? 'bg-amber-500' : 'bg-accent-600'"
                                    >{{ item.badge }}</span>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </nav>

                <div class="mt-4 rounded-2xl bg-gradient-to-br from-accent-50 to-white p-4 ring-1 ring-accent-100">
                    <p class="flex items-center gap-1.5 text-sm font-semibold text-ink">
                        <Icon icon="heroicons:sparkles" class="h-4 w-4 text-accent-600" />
                        Toko kamu
                    </p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Lihat tampilan katalog seperti yang dilihat pembeli.</p>
                    <Link href="/" class="mt-3 flex h-8 items-center justify-center rounded-lg bg-accent-600 text-xs font-semibold text-white hover:bg-accent-700">
                        Buka Toko
                    </Link>
                </div>
            </aside>

            <!-- Konten -->
            <div class="flex min-w-0 flex-1 flex-col gap-4">
                <header class="sticky top-0 z-30 flex h-16 items-center gap-3 bg-white px-4 lg:top-4 lg:rounded-2xl lg:ring-1 lg:ring-slate-200/70">
                    <button type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Buka menu" @click="drawerOpen = true">
                        <Icon icon="heroicons:bars-3" class="h-5 w-5" />
                    </button>
                    <form class="hidden max-w-md flex-1 md:block" role="search" @submit.prevent="cariPesanan">
                        <label class="flex h-10 items-center gap-2 rounded-xl bg-slate-50 px-3 ring-1 ring-slate-200/70 focus-within:ring-accent-300">
                            <Icon icon="heroicons:magnifying-glass" class="h-4 w-4 text-slate-400" />
                            <input v-model="search" type="search" placeholder="Cari no. invoice atau nama pembeli…" aria-label="Cari pesanan" class="w-full bg-transparent text-sm text-ink placeholder:text-slate-400 focus:outline-none" />
                        </label>
                    </form>
                    <div class="ml-auto flex items-center gap-2">
                        <Link href="/admin/pesanan?status=menunggu_verifikasi" class="relative flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 ring-1 ring-slate-200/70 hover:bg-slate-50" aria-label="Pesanan perlu verifikasi">
                            <Icon icon="heroicons:bell" class="h-5 w-5" />
                            <span v-if="badges.pesanan" class="absolute top-2 right-2.5 h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white" />
                        </Link>
                        <div class="flex items-center gap-2.5 pl-1">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-accent-100 text-sm font-semibold text-accent-700">
                                {{ user?.name?.charAt(0)?.toUpperCase() }}
                            </span>
                            <span class="hidden text-sm leading-tight sm:block">
                                <span class="block font-semibold text-ink">{{ user?.name }}</span>
                                <span class="block text-xs text-slate-500 capitalize">{{ user?.role }}</span>
                            </span>
                        </div>
                        <button type="button" class="flex h-10 w-10 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-50 hover:text-ink" aria-label="Keluar" @click="logout">
                            <Icon icon="heroicons:arrow-right-start-on-rectangle" class="h-5 w-5" />
                        </button>
                    </div>
                </header>

                <main class="flex-1 px-4 pb-8 lg:px-0">
                    <div v-if="flash.success" class="mb-4 flex items-start gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200" role="status">
                        <Icon icon="heroicons:check-circle" class="mt-0.5 h-4 w-4 shrink-0" />
                        {{ flash.success }}
                    </div>
                    <div v-if="flash.error" class="mb-4 flex items-start gap-2 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 ring-1 ring-rose-200" role="alert">
                        <Icon icon="heroicons:exclamation-triangle" class="mt-0.5 h-4 w-4 shrink-0" />
                        {{ flash.error }}
                    </div>
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
