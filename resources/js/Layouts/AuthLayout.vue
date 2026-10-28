<script setup>
// Shell bersama halaman auth: kolom kiri branding, kolom kanan form
</script>

<template>
    <div class="flex min-h-screen">
        <!-- Panel branding (desktop) -->
        <aside class="relative hidden w-[45%] flex-col justify-between overflow-hidden bg-cream p-10 lg:flex">
            <a href="/" class="flex items-center gap-2">
                <Icon icon="heroicons:building-storefront-solid" class="h-6 w-6 text-brand-900" />
                <span class="text-lg font-bold tracking-tight text-ink">akunhaus</span>
            </a>

            <div>
                <h2 class="text-3xl leading-tight font-extrabold tracking-tight text-ink">
                    Langganan Premium,<br />
                    <span class="text-gold">Lebih Hemat</span>
                </h2>
                <p class="mt-3 text-sm text-slate-600">Proses otomatis &bull; Pengiriman instan &bull; Bantuan pelanggan</p>
            </div>

            <p class="text-xs text-slate-500">&copy; 2026 Akunhaus</p>
        </aside>

        <!-- Panel form -->
        <main class="flex flex-1 items-center justify-center bg-slate-50 px-4 py-12">
            <div class="w-full max-w-sm">
                <slot />
            </div>
        </main>
    </div>
</template>
