<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { UiButton, UiCheckbox, UiInput } from '@/Components/ui'

defineProps({ status: { type: String, default: '' } })

const form = useForm({ email: '', password: '', remember: false })
const googleUrl = usePage().props.auth?.google_url ?? '/auth/google'
const submit = () => form.post('/login', { onFinish: () => form.reset('password') })
</script>

<template>
    <div>
        <Head title="Masuk" />
        <div v-if="status" class="mb-4 flex items-start gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200" role="status">
            <Icon icon="heroicons:check-circle" class="mt-0.5 h-4 w-4 shrink-0" />{{ status }}
        </div>

        <h1 class="text-2xl font-bold text-ink">Masuk ke Akunhaus</h1>
        <p class="mt-1.5 text-sm text-slate-500">
            Belum punya akun?
            <Link href="/register" class="font-semibold text-ink underline underline-offset-2 hover:text-gold">Daftar</Link>
        </p>

        <UiButton :href="googleUrl" external variant="secondary" block class="mt-6" target="_self">
            <Icon icon="logos:google-icon" class="h-5 w-5" aria-hidden="true" /> Masuk dengan Google
        </UiButton>

        <div class="my-5 flex items-center gap-3 text-xs text-slate-400">
            <span class="h-px flex-1 bg-slate-200" /> atau masuk dengan email <span class="h-px flex-1 bg-slate-200" />
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <UiInput v-model="form.email" label="Email" type="email" required autocomplete="email" :error="form.errors.email" />
            <div>
                <UiInput v-model="form.password" label="Password" type="password" required autocomplete="current-password" :error="form.errors.password" />
                <div class="mt-1 text-right">
                    <Link href="/lupa-password" class="inline-block py-1.5 text-xs font-medium text-slate-500 hover:text-ink">Lupa password?</Link>
                </div>
            </div>
            <UiCheckbox v-model="form.remember" label="Ingat saya" />
            <UiButton type="submit" block :loading="form.processing">Masuk</UiButton>
        </form>
    </div>
</template>

<script>
import AuthLayout from '@/Layouts/AuthLayout.vue'

export default { layout: AuthLayout }
</script>
