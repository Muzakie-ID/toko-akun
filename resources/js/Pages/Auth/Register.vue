<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { UiButton, UiInput } from '@/Components/ui'

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' })
const googleUrl = usePage().props.auth?.google_url ?? '/auth/google'
const submit = () => form.post('/register')
</script>

<template>
    <div>
        <Head title="Daftar" />
        <h1 class="text-2xl font-bold text-ink">Buat Akun Baru</h1>
        <p class="mt-1.5 text-sm text-slate-500">
            Sudah punya akun?
            <Link href="/login" class="font-semibold text-ink underline underline-offset-2 hover:text-gold">Masuk</Link>
        </p>

        <UiButton :href="googleUrl" external variant="secondary" block class="mt-6" target="_self">
            <Icon icon="logos:google-icon" class="h-5 w-5" aria-hidden="true" /> Daftar dengan Google
        </UiButton>

        <div class="my-5 flex items-center gap-3 text-xs text-slate-400">
            <span class="h-px flex-1 bg-slate-200" /> atau daftar dengan email <span class="h-px flex-1 bg-slate-200" />
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <UiInput v-model="form.name" label="Nama Lengkap" required autocomplete="name" :error="form.errors.name" />
            <UiInput v-model="form.email" label="Email" type="email" required autocomplete="email" :error="form.errors.email" />
            <UiInput v-model="form.password" label="Password" type="password" required autocomplete="new-password" :error="form.errors.password" hint="Minimal 8 karakter." />
            <UiInput v-model="form.password_confirmation" label="Konfirmasi Password" type="password" required autocomplete="new-password" />
            <UiButton type="submit" block :loading="form.processing">Daftar</UiButton>
            <p class="text-center text-xs leading-5 text-slate-400">
                Dengan mendaftar, kamu menyetujui <Link href="/syarat-ketentuan" class="font-medium text-slate-600 underline underline-offset-2 hover:text-ink">syarat &amp; ketentuan serta kebijakan privasi</Link> Akunhaus.
            </p>
        </form>
    </div>
</template>

<script>
import AuthLayout from '@/Layouts/AuthLayout.vue'

export default { layout: AuthLayout }
</script>
