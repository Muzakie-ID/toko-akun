<script setup>
import { Link } from '@inertiajs/vue3'

const year = new Date().getFullYear()

const columns = [
    {
        title: 'Produk',
        links: [
            { label: 'Semua Produk', href: '/' },
            { label: 'Streaming', href: '/?kategori=streaming' },
            { label: 'AI Tools', href: '/?kategori=ai-tools' },
            { label: 'Deals', href: '/#terlaris' },
        ],
    },
    {
        title: 'Bantuan',
        links: [
            { label: 'Cara Order', href: '/#cara-order' },
            { label: 'Kebijakan Garansi', href: '/#garansi' },
            { label: 'FAQ', href: '/#faq' },
        ],
    },
    {
        title: 'Akun',
        links: [
            { label: 'Masuk', href: '/login' },
            { label: 'Daftar', href: '/register' },
            { label: 'Pesanan Saya', href: '/pesanan' },
        ],
    },
]
</script>

<template>
    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto grid max-w-[1320px] gap-10 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4 lg:px-15">
            <div>
                <Link href="/" class="flex items-center gap-2">
                    <Icon icon="heroicons:building-storefront-solid" class="h-6 w-6 text-brand-900" />
                    <span class="text-lg font-bold tracking-tight text-ink">akunhaus</span>
                </Link>
                <p class="mt-3 max-w-xs text-sm leading-6 text-slate-500">
                    Toko akun premium terpercaya. Kredensial dikirim otomatis setelah pembayaran terverifikasi.
                </p>
            </div>

            <nav v-for="col in columns" :key="col.title" :aria-label="col.title">
                <h3 class="text-sm font-semibold text-ink">{{ col.title }}</h3>
                <ul class="mt-3 space-y-2">
                    <li v-for="link in col.links" :key="link.label">
                        <Link :href="link.href" class="text-sm text-slate-500 transition hover:text-brand-600">{{ link.label }}</Link>
                    </li>
                </ul>
            </nav>
        </div>
        <div class="border-t border-slate-100 py-5 text-center text-xs text-slate-400">
            &copy; {{ year }} Akunhaus. Semua hak dilindungi.
        </div>
    </footer>
</template>
