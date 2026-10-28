<script setup>
import Navbar from '@/Components/Navbar.vue'
import Footer from '@/Components/Footer.vue'
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white">
        <Navbar />
        <main class="flex-1">
            <slot />
        </main>
        <Footer />
    </div>
</template>
