<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import Navbar from '@/Components/Navbar.vue'
import Footer from '@/Components/Footer.vue'

const flash = computed(() => usePage().props.flash ?? {})
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white">
        <Navbar />
        <div v-if="flash.success || flash.error" class="mx-auto w-full max-w-[1320px] px-4 pt-4 lg:px-15">
            <p v-if="flash.success" class="flex items-center gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200" role="status">
                <Icon icon="heroicons:check-circle" class="h-4 w-4 shrink-0" /> {{ flash.success }}
            </p>
            <p v-if="flash.error" class="flex items-center gap-2 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 ring-1 ring-rose-200" role="alert">
                <Icon icon="heroicons:exclamation-triangle" class="h-4 w-4 shrink-0" /> {{ flash.error }}
            </p>
        </div>
        <main class="flex-1">
            <slot />
        </main>
        <Footer />
    </div>
</template>
