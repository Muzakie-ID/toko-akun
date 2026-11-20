<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { UiButton, UiInput } from '@/Components/ui'

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, default: '' },
})

const form = useForm({ token: props.token, email: props.email, password: '', password_confirmation: '' })
const submit = () => form.post('/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') })
</script>

<template>
    <div>
        <Head title="Buat Password Baru" />
        <h1 class="text-2xl font-bold text-ink">Buat Password Baru</h1>
        <p class="mt-1.5 text-sm text-slate-500">Minimal 8 karakter.</p>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
            <UiInput v-model="form.email" label="Email" type="email" required autocomplete="email" :error="form.errors.email" />
            <UiInput v-model="form.password" label="Password Baru" type="password" required autofocus autocomplete="new-password" :error="form.errors.password" />
            <UiInput v-model="form.password_confirmation" label="Konfirmasi Password" type="password" required autocomplete="new-password" />
            <UiButton type="submit" block :loading="form.processing">Simpan Password</UiButton>
        </form>
    </div>
</template>

<script>
import AuthLayout from '@/Layouts/AuthLayout.vue'

export default { layout: AuthLayout }
</script>
