<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'

defineProps({ status: { type: String, default: '' } })

const form = useForm({ email: '', password: '', remember: false })

const googleUrl = usePage().props.auth?.google_url ?? '/auth/google'

const submit = () => form.post('/login')
</script>

<template>
    <AuthLayout>
        <div v-if="status" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ status }}
        </div>

        <h1 class="text-2xl font-bold text-ink">Masuk ke Akunhaus</h1>
        <p class="mt-1.5 text-sm text-slate-500">Belum punya akun?
            <Link href="/register" class="font-semibold text-ink underline underline-offset-2 hover:text-gold">Daftar</Link>
        </p>

        <!-- Login Google -->
        <a
            :href="googleUrl"
            class="mt-6 flex h-11 w-full items-center justify-center gap-3 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-ink transition hover:bg-slate-50"
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47a5.57 5.57 0 0 1-2.4 3.58v3h3.86c2.26-2.09 3.56-5.17 3.56-8.82Z" />
                <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.86-3c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.29v3.09A11.99 11.99 0 0 0 12 24Z" />
                <path fill="#FBBC05" d="M5.27 14.29A7.2 7.2 0 0 1 4.89 12c0-.8.14-1.57.38-2.29V6.62H1.29a12 12 0 0 0 0 10.76l3.98-3.09Z" />
                <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42A11.98 11.98 0 0 0 12 0 11.99 11.99 0 0 0 1.29 6.62l3.98 3.09C6.22 6.86 8.87 4.75 12 4.75Z" />
            </svg>
            Masuk dengan Google
        </a>

        <div class="my-5 flex items-center gap-3 text-xs text-slate-400">
            <span class="h-px flex-1 bg-slate-200" />
            atau masuk dengan email
            <span class="h-px flex-1 bg-slate-200" />
        </div>

        <form @submit.prevent="submit">
            <label class="block text-sm font-medium text-ink" for="email">Email</label>
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

            <div class="mt-4 flex items-center justify-between">
                <label class="block text-sm font-medium text-ink" for="password">Password</label>
                <!-- ponytail: fitur lupa password di luar scope MVP -->
                <a href="#" class="text-xs font-medium text-slate-500 hover:text-ink">Lupa password?</a>
            </div>
            <input
                id="password"
                v-model="form.password"
                type="password"
                required
                autocomplete="current-password"
                class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-ink transition focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-200 focus:outline-none"
                :class="{ 'border-red-400': form.errors.password }"
            />
            <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>

            <label class="mt-4 flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                <input v-model="form.remember" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-slate-300" />
                Ingat saya
            </label>

            <button
                type="submit"
                :disabled="form.processing"
                class="mt-6 h-11 w-full rounded-xl bg-brand-900 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {{ form.processing ? 'Memproses...' : 'Masuk' }}
            </button>
        </form>
    </AuthLayout>
</template>

<script>
import AuthLayout from '@/Layouts/AuthLayout.vue'

export default { layout: AuthLayout }
</script>
