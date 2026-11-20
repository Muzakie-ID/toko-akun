<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import { rupiah } from '@/lib/format'

const props = defineProps({
    akun: { type: Object, required: true },
    menujuReseller: { type: Object, default: null },
})

const persenReseller = computed(() =>
    props.menujuReseller ? Math.min(100, Math.round((props.menujuReseller.total_belanja / props.menujuReseller.batas) * 100)) : 0,
)

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
                <h1 class="text-2xl font-bold text-ink">Akun Saya</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Bergabung {{ akun.bergabung }}
                    <span v-if="akun.role === 'reseller'" class="ml-1 rounded-md bg-amber-50 px-1.5 py-0.5 text-xs font-semibold text-amber-700">Reseller</span>
                </p>
            </div>
            <button type="button" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 px-4 text-sm font-medium text-slate-600 hover:bg-slate-50" @click="logout">
                <Icon icon="heroicons:arrow-right-start-on-rectangle" class="h-4 w-4" /> Keluar
            </button>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <Link href="/pesanan" class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
                <Icon icon="heroicons:shopping-bag" class="h-6 w-6 text-slate-400" />
                <span><span class="block text-sm font-semibold text-ink">Pesanan Saya</span><span class="text-xs text-slate-500">{{ akun.jumlah_pesanan }} pesanan lunas</span></span>
            </Link>
            <Link href="/wishlist" class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
                <Icon icon="heroicons:heart" class="h-6 w-6 text-slate-400" />
                <span><span class="block text-sm font-semibold text-ink">Wishlist</span><span class="text-xs text-slate-500">{{ akun.jumlah_wishlist }} produk tersimpan</span></span>
            </Link>
        </div>

        <div v-if="menujuReseller" class="mt-4 rounded-2xl border border-amber-200 bg-amber-50/60 p-5">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="flex items-center gap-1.5 text-sm font-semibold text-ink">
                    <Icon icon="heroicons:sparkles" class="h-4 w-4 text-amber-600" /> Menuju Reseller
                </h2>
                <p class="text-sm text-slate-600"><b class="text-ink">{{ rupiah(menujuReseller.total_belanja) }}</b> / {{ rupiah(menujuReseller.batas) }}</p>
            </div>
            <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-white ring-1 ring-amber-200" role="progressbar" :aria-valuenow="persenReseller" aria-valuemin="0" aria-valuemax="100" aria-label="Progres menuju reseller">
                <div class="h-full rounded-full bg-amber-500 transition-all" :style="{ width: `${persenReseller}%` }" />
            </div>
            <p class="mt-2 text-xs text-slate-600">
                Belanja {{ rupiah(Math.max(menujuReseller.batas - menujuReseller.total_belanja, 0)) }} lagi untuk otomatis jadi Reseller dan dapat harga lebih murah.
            </p>
        </div>

        <form class="mt-6 rounded-2xl border border-slate-200 p-5" @submit.prevent="simpanProfil">
            <h2 class="text-base font-semibold text-ink">Profil</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="text-sm font-medium text-ink">Nama</label>
                    <input id="name" v-model="profil.name" type="text" required autocomplete="name" class="mt-1.5 h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm text-ink focus:border-slate-400 focus:ring-2 focus:ring-slate-200 focus:outline-none" />
                    <p v-if="profil.errors.name" class="mt-1 text-xs text-rose-600">{{ profil.errors.name }}</p>
                </div>
                <div>
                    <label for="email" class="text-sm font-medium text-ink">Email</label>
                    <input id="email" v-model="profil.email" type="email" required autocomplete="email" class="mt-1.5 h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm text-ink focus:border-slate-400 focus:ring-2 focus:ring-slate-200 focus:outline-none" />
                    <p v-if="profil.errors.email" class="mt-1 text-xs text-rose-600">{{ profil.errors.email }}</p>
                    <p v-else-if="akun.via_google" class="mt-1 text-xs text-slate-500">Terhubung dengan Google; login Google tetap berfungsi walau email diganti.</p>
                </div>
            </div>
            <button type="submit" :disabled="profil.processing || !profil.isDirty" class="mt-4 h-10 rounded-xl bg-brand-600 px-5 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-40">Simpan Profil</button>
        </form>

        <form class="mt-4 rounded-2xl border border-slate-200 p-5" @submit.prevent="simpanPassword">
            <h2 class="text-base font-semibold text-ink">{{ akun.punya_password ? 'Ganti Password' : 'Buat Password' }}</h2>
            <p v-if="!akun.punya_password" class="mt-1 text-sm text-slate-500">Kamu daftar lewat Google. Buat password agar bisa masuk juga dengan email.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div v-if="akun.punya_password">
                    <label for="current_password" class="text-sm font-medium text-ink">Password lama</label>
                    <input id="current_password" v-model="password.current_password" type="password" required autocomplete="current-password" class="mt-1.5 h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm text-ink focus:border-slate-400 focus:ring-2 focus:ring-slate-200 focus:outline-none" />
                    <p v-if="password.errors.current_password" class="mt-1 text-xs text-rose-600">{{ password.errors.current_password }}</p>
                </div>
                <div>
                    <label for="password" class="text-sm font-medium text-ink">Password baru</label>
                    <input id="password" v-model="password.password" type="password" required minlength="8" autocomplete="new-password" class="mt-1.5 h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm text-ink focus:border-slate-400 focus:ring-2 focus:ring-slate-200 focus:outline-none" />
                    <p v-if="password.errors.password" class="mt-1 text-xs text-rose-600">{{ password.errors.password }}</p>
                </div>
                <div>
                    <label for="password_confirmation" class="text-sm font-medium text-ink">Ulangi password baru</label>
                    <input id="password_confirmation" v-model="password.password_confirmation" type="password" required autocomplete="new-password" class="mt-1.5 h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm text-ink focus:border-slate-400 focus:ring-2 focus:ring-slate-200 focus:outline-none" />
                </div>
            </div>
            <button type="submit" :disabled="password.processing" class="mt-4 h-10 rounded-xl bg-brand-600 px-5 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-40">{{ akun.punya_password ? 'Ganti Password' : 'Buat Password' }}</button>
        </form>
    </section>
</template>

<script>
import PublicLayout from '@/Layouts/PublicLayout.vue'

export default { layout: PublicLayout }
</script>
