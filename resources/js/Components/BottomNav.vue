<script setup>
// Navigasi bawah untuk pembeli di HP (referensi: Home / Saved / Cart / Account).
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const cartCount = computed(() => page.props.cart_count ?? 0)
const path = computed(() => page.url.split('?')[0].split('#')[0])

const items = computed(() => [
    { label: 'Beranda', href: '/', icon: 'heroicons:home', iconActive: 'heroicons:home-solid', match: (p) => p === '/' || p.startsWith('/produk') },
    { label: 'Wishlist', href: '/wishlist', icon: 'heroicons:heart', iconActive: 'heroicons:heart-solid', match: (p) => p.startsWith('/wishlist') },
    { label: 'Keranjang', href: '/keranjang', icon: 'heroicons:shopping-cart', iconActive: 'heroicons:shopping-cart-solid', match: (p) => p.startsWith('/keranjang'), badge: cartCount.value },
    { label: 'Pesanan', href: '/pesanan', icon: 'heroicons:shopping-bag', iconActive: 'heroicons:shopping-bag-solid', match: (p) => p.startsWith('/pesanan') },
    user.value
        ? { label: 'Akun', href: '/akun', icon: 'heroicons:user', iconActive: 'heroicons:user-solid', match: (p) => p.startsWith('/akun') || p.startsWith('/saldo') }
        : { label: 'Masuk', href: '/login', icon: 'heroicons:user', iconActive: 'heroicons:user-solid', match: () => false },
])
</script>

<template>
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur md:hidden" aria-label="Navigasi utama">
        <ul class="grid grid-cols-5">
            <li v-for="item in items" :key="item.href">
                <Link
                    :href="item.href"
                    class="flex h-16 flex-col items-center justify-center gap-0.5 text-[11px] font-medium transition"
                    :class="item.match(path) ? 'text-ink' : 'text-slate-400'"
                    :aria-current="item.match(path) ? 'page' : undefined"
                >
                    <span class="relative">
                        <Icon :icon="item.match(path) ? item.iconActive : item.icon" class="h-6 w-6" />
                        <span v-if="item.badge" class="absolute -top-1 -right-2 flex h-4 min-w-4 items-center justify-center rounded-full bg-gold px-1 text-[10px] font-bold text-white">{{ item.badge }}</span>
                    </span>
                    {{ item.label }}
                </Link>
            </li>
        </ul>
    </nav>
</template>
