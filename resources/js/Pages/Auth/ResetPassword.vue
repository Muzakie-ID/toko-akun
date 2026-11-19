<script setup>
import { Head, useForm } from '@inertiajs/vue3'

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, default: '' },
})

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const submit = () => form.post('/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') })
</script>

<template>
    <Head title="Buat Password Baru" />

    <h1 class="text-2xl font-bold text-ink">Buat Password Baru</h1>
    <p class="mt-1.5 text-sm text-slate-500">Minimal 8 karakter.</p>

    <form class="mt-6" @submit.prevent="submit">
        <label class="block text-sm font-medium text-ink" for="email">Email</label>
        <input
            id="email"
            v-model="form.email"
            type="email"
            required
            autocomplete="email"
            class="mt-1.5 h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm text-ink transition focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-200 focus:outline-none"
            :class="{ 'border-red-400': form.errors.email }"
        />
        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>

        <label class="mt-4 block text-sm font-medium text-ink" for="password">Password Baru</label>
        <input
            id="password"
            v-model="form.password"
            type="password"
            required
            autofocus
            autocomplete="new-password"
            class="mt-1.5 h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm text-ink transition focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-200 focus:outline-none"
            :class="{ 'border-red-400': form.errors.password }"
        />
        <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>

        <label class="mt-4 block text-sm font-medium text-ink" for="password_confirmation">Konfirmasi Password</label>
        <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            required
            autocomplete="new-password"
            class="mt-1.5 h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm text-ink transition focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-200 focus:outline-none"
        />

        <button type="submit" :disabled="form.processing" class="mt-6 h-11 w-full rounded-xl bg-brand-900 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50">
            {{ form.processing ? 'Menyimpan...' : 'Simpan Password' }}
        </button>
    </form>
</template>

<script>
import AuthLayout from '@/Layouts/AuthLayout.vue'

export default { layout: AuthLayout }
</script>
