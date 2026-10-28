<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' })

const googleUrl = usePage().props.auth?.google_url ?? '/auth/google'

const submit = () => form.post('/register')
</script>

<template>
    <h1 class="text-2xl font-bold text-ink">Buat Akun Baru</h1>
        <p class="mt-1.5 text-sm text-slate-500">Sudah punya akun?
            <Link href="/login" class="font-semibold text-ink underline underline-offset-2 hover:text-gold">Masuk</Link>
        </p>

        <!-- Daftar via Google -->
        <a
            :href="googleUrl"
            class="mt-6 flex h-11 w-full items-center justify-center gap-3 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-ink transition hover:bg-slate-50"
        >
        <Icon icon="logos:google-icon" class="h-5 w-5" aria-hidden="true" />
        Daftar dengan Google
    </a>

        <div class="my-5 flex items-center gap-3 text-xs text-slate-400">
            <span class="h-px flex-1 bg-slate-200" />
            atau daftar dengan email
            <span class="h-px flex-1 bg-slate-200" />
        </div>

        <form @submit.prevent="submit">
            <label class="block text-sm font-medium text-ink" for="name">Nama Lengkap</label>
            <input
                id="name"
                v-model="form.name"
                type="text"
                required
                autocomplete="name"
                class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-ink transition focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-200 focus:outline-none"
                :class="{ 'border-red-400': form.errors.name }"
            />
            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>

            <label class="mt-4 block text-sm font-medium text-ink" for="email">Email</label>
            <input
                id="email"
                v-model="form.email"
                type="email"
                required
                autocomplete="email"
                class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-ink transition focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-200 focus:outline-none"
                :class="{ 'border-red-400': form.errors.email }"
            />
            <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>

            <label class="mt-4 block text-sm font-medium text-ink" for="password">Password</label>
            <input
                id="password"
                v-model="form.password"
                type="password"
                required
                autocomplete="new-password"
                class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-ink transition focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-200 focus:outline-none"
                :class="{ 'border-red-400': form.errors.password }"
            />
            <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
            <p v-else class="mt-1 text-xs text-slate-400">Minimal 8 karakter.</p>

            <label class="mt-4 block text-sm font-medium text-ink" for="password_confirmation">Konfirmasi Password</label>
            <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                required
                autocomplete="new-password"
                class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-ink transition focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-200 focus:outline-none"
            />

            <button
                type="submit"
                :disabled="form.processing"
                class="mt-6 h-11 w-full rounded-xl bg-brand-900 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {{ form.processing ? 'Memproses...' : 'Daftar' }}
            </button>

            <p class="mt-4 text-center text-xs leading-5 text-slate-400">
                Dengan mendaftar, kamu menyetujui
                <a href="#" class="underline hover:text-ink">Syarat &amp; Ketentuan</a> dan
                <a href="#" class="underline hover:text-ink">Kebijakan Privasi</a> kami.
            </p>
        </form>
</template>

<script>
import AuthLayout from '@/Layouts/AuthLayout.vue'

export default { layout: AuthLayout }
</script>
