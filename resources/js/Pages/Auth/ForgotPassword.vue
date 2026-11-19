<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

defineProps({ status: { type: String, default: '' } })

const form = useForm({ email: '' })

const submit = () => form.post('/lupa-password')
</script>

<template>
    <Head title="Lupa Password" />

    <h1 class="text-2xl font-bold text-ink">Lupa Password</h1>
    <p class="mt-1.5 text-sm leading-6 text-slate-500">
        Masukkan email akunmu, kami kirim link untuk membuat password baru.
        Daftar lewat Google dan belum punya password? Pakai halaman ini juga.
    </p>

    <div v-if="status" class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" role="status">
        {{ status }}
    </div>

    <form class="mt-6" @submit.prevent="submit">
        <label class="block text-sm font-medium text-ink" for="email">Email</label>
        <input
            id="email"
            v-model="form.email"
            type="email"
            required
            autofocus
            autocomplete="email"
            class="mt-1.5 h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm text-ink transition focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-200 focus:outline-none"
            :class="{ 'border-red-400': form.errors.email }"
        />
        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>

        <button type="submit" :disabled="form.processing" class="mt-6 h-11 w-full rounded-xl bg-brand-900 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50">
            {{ form.processing ? 'Mengirim...' : 'Kirim Link Reset' }}
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        Ingat password?
        <Link href="/login" class="font-semibold text-ink underline underline-offset-2 hover:text-gold">Kembali masuk</Link>
    </p>
</template>

<script>
import AuthLayout from '@/Layouts/AuthLayout.vue'

export default { layout: AuthLayout }
</script>
