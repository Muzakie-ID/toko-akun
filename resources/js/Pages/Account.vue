<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { UiButton, UiInput } from '@/Components/ui'
import { rupiah } from '@/lib/format'

const props = defineProps({ akun: { type: Object, required: true } })

const profil = useForm({ name: props.akun.name, email: props.akun.email })
const password = useForm({ current_password: '', password: '', password_confirmation: '' })

const simpanProfil = () => profil.put('/akun', { preserveScroll: true })
const simpanPassword = () => password.put('/akun/password', { preserveScroll: true, onSuccess: () => password.reset() })
const logout = () => router.post('/logout')
</script>

<template>
    <section class="mx-auto max-w-[880px] px-4 py-8 lg:px-15">
        <Head title="Akun Saya" />
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-ink sm:text-2xl">Akun Saya</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Bergabung {{ akun.bergabung }}
                    <span v-if="akun.role === 'reseller'" class="ml-1 rounded-md bg-amber-50 px-1.5 py-0.5 text-xs font-semibold text-amber-700">Reseller</span>
                </p>
            </div>
            <UiButton variant="secondary" icon="heroicons:arrow-right-start-on-rectangle" @click="logout">Keluar</UiButton>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-3">
            <Link href="/saldo" class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
                <Icon icon="heroicons:wallet" class="h-6 w-6 text-slate-400" />
                <span><span class="block text-sm font-semibold text-ink">Saldo</span><span class="text-xs text-slate-500">{{ rupiah(akun.saldo) }}</span></span>
            </Link>
            <Link href="/pesanan" class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
                <Icon icon="heroicons:shopping-bag" class="h-6 w-6 text-slate-400" />
                <span><span class="block text-sm font-semibold text-ink">Pesanan Saya</span><span class="text-xs text-slate-500">{{ akun.jumlah_pesanan }} pesanan lunas</span></span>
            </Link>
            <Link href="/wishlist" class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
                <Icon icon="heroicons:heart" class="h-6 w-6 text-slate-400" />
                <span><span class="block text-sm font-semibold text-ink">Wishlist</span><span class="text-xs text-slate-500">{{ akun.jumlah_wishlist }} produk tersimpan</span></span>
            </Link>
        </div>

        <form class="mt-6 rounded-2xl border border-slate-200 p-5" @submit.prevent="simpanProfil">
            <h2 class="text-base font-semibold text-ink">Profil</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <UiInput v-model="profil.name" label="Nama" required autocomplete="name" :error="profil.errors.name" />
                <UiInput
                    v-model="profil.email"
                    label="Email"
                    type="email"
                    required
                    autocomplete="email"
                    :error="profil.errors.email"
                    :hint="akun.via_google ? 'Terhubung dengan Google; login Google tetap berfungsi walau email diganti.' : ''"
                />
            </div>
            <UiButton type="submit" class="mt-4" :loading="profil.processing" :disabled="!profil.isDirty">Simpan Profil</UiButton>
        </form>

        <form class="mt-4 rounded-2xl border border-slate-200 p-5" @submit.prevent="simpanPassword">
            <h2 class="text-base font-semibold text-ink">{{ akun.punya_password ? 'Ganti Password' : 'Buat Password' }}</h2>
            <p v-if="!akun.punya_password" class="mt-1 text-sm text-slate-500">Kamu daftar lewat Google. Buat password agar bisa masuk juga dengan email.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <UiInput v-if="akun.punya_password" v-model="password.current_password" label="Password lama" type="password" required autocomplete="current-password" :error="password.errors.current_password" />
                <UiInput v-model="password.password" label="Password baru" type="password" required minlength="8" autocomplete="new-password" :error="password.errors.password" />
                <UiInput v-model="password.password_confirmation" label="Ulangi password baru" type="password" required autocomplete="new-password" />
            </div>
            <UiButton type="submit" class="mt-4" :loading="password.processing">{{ akun.punya_password ? 'Ganti Password' : 'Buat Password' }}</UiButton>
        </form>
    </section>
</template>

<script>
import PublicLayout from '@/Layouts/PublicLayout.vue'

export default { layout: PublicLayout }
</script>
