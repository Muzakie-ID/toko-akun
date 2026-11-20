<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { UiButton, UiInput } from '@/Components/ui'

defineProps({ status: { type: String, default: '' } })

const form = useForm({ email: '' })
const submit = () => form.post('/lupa-password')
</script>

<template>
    <div>
        <Head title="Lupa Password" />
        <h1 class="text-2xl font-bold text-ink">Lupa Password</h1>
        <p class="mt-1.5 text-sm leading-6 text-slate-500">
            Masukkan email akunmu, kami kirim link untuk membuat password baru.
            Daftar lewat Google dan belum punya password? Pakai halaman ini juga.
        </p>

        <div v-if="status" class="mt-5 flex items-start gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200" role="status">
            <Icon icon="heroicons:check-circle" class="mt-0.5 h-4 w-4 shrink-0" />{{ status }}
        </div>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
            <UiInput v-model="form.email" label="Email" type="email" required autofocus autocomplete="email" :error="form.errors.email" />
            <UiButton type="submit" block :loading="form.processing">Kirim Link Reset</UiButton>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500">
            Ingat password?
            <Link href="/login" class="font-semibold text-ink underline underline-offset-2 hover:text-gold">Kembali masuk</Link>
        </p>
    </div>
</template>

<script>
import AuthLayout from '@/Layouts/AuthLayout.vue'

export default { layout: AuthLayout }
</script>
