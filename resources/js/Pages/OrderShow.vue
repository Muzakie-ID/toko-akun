<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import StatusBadge from '@/Components/Admin/StatusBadge.vue'
import StarRating from '@/Components/StarRating.vue'
import { UiButton, UiTextarea } from '@/Components/ui'
import { rupiah } from '@/lib/format'

const props = defineProps({
    order: { type: Object, required: true },
    rekening: { type: Array, default: () => [] },
    whatsapp: { type: String, default: '' },
})

const menungguBayar = computed(() => ['menunggu_bayar', 'menunggu_verifikasi'].includes(props.order.status))
const isQris = computed(() => props.order.metode === 'qris')

// Hitung mundur batas bayar + cek status otomatis selama tagihan QRIS aktif
const now = ref(Date.now())
let timer = null
let poller = null
onMounted(() => {
    timer = setInterval(() => (now.value = Date.now()), 1000)
    poller = setInterval(() => {
        if (menungguBayar.value && isQris.value) router.reload({ only: ['order'] })
    }, 5000)
})
onBeforeUnmount(() => {
    clearInterval(timer)
    clearInterval(poller)
})

const sisa = computed(() => {
    if (!props.order.expires_at) return null
    const ms = new Date(props.order.expires_at).getTime() - now.value
    if (ms <= 0) return 'Waktu habis'
    const h = Math.floor(ms / 3_600_000)
    const m = Math.floor((ms % 3_600_000) / 60_000)
    const s = Math.floor((ms % 60_000) / 1000)
    return h > 0 ? `${h} jam ${m} menit` : `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
})

const form = useForm({ bukti: null })
const kirimBukti = () => form.post(`/pesanan/${props.order.invoice}/bukti`, { forceFormData: true, preserveScroll: true, onSuccess: () => form.reset() })

const copied = ref('')
const salin = async (teks, key) => {
    try {
        await navigator.clipboard.writeText(String(teks))
        copied.value = key
        setTimeout(() => (copied.value = ''), 1500)
    } catch {}
}
const shown = ref({})

// Satu form ulasan per item; terbuka otomatis untuk item yang belum diulas
const reviewForms = Object.fromEntries(
    props.order.items.filter((i) => i.bisa_ulas).map((i) => [i.id, useForm({ rating: i.ulasan?.rating ?? 0, komentar: i.ulasan?.komentar ?? '' })]),
)
const editingReview = ref({})
const kirimUlasan = (item) => reviewForms[item.id].post(`/ulasan/${item.id}`, { preserveScroll: true, onSuccess: () => (editingReview.value[item.id] = false) })
const waLink = computed(() => (props.whatsapp ? `https://wa.me/${props.whatsapp.replace(/\D/g, '')}?text=${encodeURIComponent(`Halo admin, saya mau klaim garansi pesanan ${props.order.invoice}`)}` : null))
</script>

<template>
    <section class="mx-auto max-w-[1000px] px-4 py-8 lg:px-15">
        <Head :title="`Pesanan ${order.invoice}`" />
        <Link href="/pesanan" class="-my-2 inline-flex items-center gap-1 py-2 text-sm text-slate-500 hover:text-ink">
            <Icon icon="heroicons:arrow-left" class="h-4 w-4" /> Pesanan Saya
        </Link>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="text-xl font-bold text-ink sm:text-2xl">{{ order.invoice }}</h1>
            <StatusBadge :tone="order.status_tone" :label="order.status_label" />
        </div>
        <p class="mt-1 text-sm text-slate-500">{{ order.tanggal }}</p>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_340px]">
            <div class="space-y-4">
                <!-- Bayar QRIS -->
                <div v-if="isQris && order.status === 'menunggu_bayar'" class="rounded-2xl border border-slate-200 p-5 text-center">
                    <h2 class="text-base font-semibold text-ink">Scan QRIS untuk membayar</h2>
                    <p class="mt-1 text-sm text-slate-500">Bayar <b class="text-ink">tepat</b> sesuai nominal agar terdeteksi otomatis.</p>
                    <img v-if="order.qr_image_url" :src="order.qr_image_url" alt="Kode QRIS pembayaran" class="mx-auto mt-4 h-64 w-64 rounded-xl border border-slate-200 bg-white p-2" />
                    <p class="mt-4 text-3xl font-bold tracking-tight text-ink">{{ rupiah(order.total) }}</p>
                    <UiButton variant="ghost" size="sm" class="mt-1 text-primary" :icon="copied === 'total' ? 'heroicons:check' : 'heroicons:clipboard-document'" @click="salin(order.total, 'total')">{{ copied === 'total' ? 'Tersalin' : 'Salin nominal' }}</UiButton>
                    <p v-if="sisa" class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-sm font-medium text-amber-800">
                        <Icon icon="heroicons:clock" class="h-4 w-4" /> Sisa waktu {{ sisa }}
                    </p>
                    <div class="mt-4 flex flex-col items-center gap-2">
                        <UiButton v-if="order.checkout_url" :href="order.checkout_url" external icon-right="heroicons:arrow-top-right-on-square">Buka halaman bayar</UiButton>
                        <p class="flex items-center gap-1.5 text-xs text-slate-500"><span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500" /> Status diperbarui otomatis setelah kamu bayar</p>
                    </div>
                </div>

                <!-- Transfer manual -->
                <div v-if="!isQris && menungguBayar" class="rounded-2xl border border-slate-200 p-5">
                    <h2 class="text-base font-semibold text-ink">Transfer ke salah satu rekening</h2>
                    <ul class="mt-3 space-y-2">
                        <li v-for="(r, i) in rekening" :key="i" class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-4 py-3">
                            <div>
                                <p class="text-xs font-semibold text-slate-500">{{ r.bank }}</p>
                                <p class="font-mono text-base font-semibold text-ink">{{ r.nomor }}</p>
                                <p class="text-xs text-slate-500">a.n. {{ r.nama }}</p>
                            </div>
                            <UiButton variant="ghost" size="sm" class="-my-2 text-primary" :icon="copied === `rek${i}` ? 'heroicons:check' : 'heroicons:clipboard-document'" @click="salin(r.nomor, `rek${i}`)">{{ copied === `rek${i}` ? 'Tersalin' : 'Salin' }}</UiButton>
                        </li>
                    </ul>
                    <p class="mt-3 text-sm text-slate-600">Total transfer: <b class="text-ink">{{ rupiah(order.total) }}</b><span v-if="order.expires_label"> · batas {{ order.expires_label }}</span></p>

                    <div v-if="order.alasan_tolak" class="mt-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 ring-1 ring-rose-200">Bukti sebelumnya ditolak: {{ order.alasan_tolak }}. Silakan unggah ulang.</div>
                    <div v-else-if="order.status === 'menunggu_verifikasi'" class="mt-4 rounded-xl bg-blue-50 px-4 py-3 text-sm text-blue-800 ring-1 ring-blue-200">Bukti sudah diterima dan sedang diverifikasi. Kamu bisa mengganti bukti bila salah unggah.</div>

                    <form class="mt-4" @submit.prevent="kirimBukti">
                        <label class="flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-300 px-4 py-6 text-center transition hover:border-primary hover:bg-slate-50 focus-within:ring-2 focus-within:ring-primary-ring/50">
                            <Icon icon="heroicons:arrow-up-tray" class="h-6 w-6 text-slate-400" />
                            <span class="text-sm font-medium text-ink">{{ form.bukti ? form.bukti.name : 'Pilih foto bukti transfer' }}</span>
                            <span class="text-xs text-slate-400">JPG, PNG, atau WEBP, maks. 2 MB</span>
                            <input type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="form.bukti = $event.target.files?.[0] ?? null" />
                        </label>
                        <p v-if="form.errors.bukti" class="mt-1 text-xs text-rose-600">{{ form.errors.bukti }}</p>
                        <UiButton type="submit" block class="mt-3" icon="heroicons:paper-airplane" :loading="form.processing" :disabled="!form.bukti">
                            {{ order.ada_bukti ? 'Ganti Bukti' : 'Kirim Bukti' }}
                        </UiButton>
                    </form>
                </div>

                <div v-if="order.status === 'diproses'" class="rounded-2xl bg-violet-50 px-5 py-4 text-sm text-violet-900 ring-1 ring-violet-200">
                    Pembayaran diterima. Sebagian akun sedang disiapkan dan akan muncul di sini secara otomatis.
                </div>
                <div v-if="['kedaluwarsa', 'dibatalkan'].includes(order.status)" class="rounded-2xl bg-slate-50 px-5 py-4 text-sm text-slate-700 ring-1 ring-slate-200">
                    Pesanan ini {{ order.status === 'kedaluwarsa' ? 'kedaluwarsa karena belum dibayar' : 'dibatalkan' }}. Sudah terlanjur bayar? Hubungi admin dengan menyebut nomor invoice.
                </div>

                <!-- Akun -->
                <div class="overflow-hidden rounded-2xl border border-slate-200">
                    <h2 class="px-5 pt-5 text-base font-semibold text-ink">Akun ({{ order.items.length }})</h2>
                    <ul class="mt-3 divide-y divide-slate-100">
                        <li v-for="item in order.items" :key="item.id" class="px-5 py-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-ink">{{ item.produk }}</p>
                                    <p class="text-xs text-slate-500">{{ item.varian }}<span v-if="item.aktif_sampai"> · aktif s/d {{ item.aktif_sampai }}</span></p>
                                </div>
                                <span v-if="!item.kredensial" class="inline-flex items-center gap-1.5 text-xs text-slate-400">
                                    <Icon icon="heroicons:clock" class="h-3.5 w-3.5" /> {{ menungguBayar ? 'Menunggu pembayaran' : 'Belum dikirim' }}
                                </span>
                            </div>
                            <div v-if="item.kredensial" class="mt-3 grid gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-2">
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-slate-500">Email / username</p>
                                    <div class="mt-1 flex items-center gap-2">
                                        <code class="truncate rounded-md bg-white px-2.5 py-1.5 text-sm text-ink ring-1 ring-slate-200">{{ item.kredensial.email }}</code>
                                        <UiButton variant="ghost" size="sm" class="-my-2 text-primary" :icon="copied === `e${item.id}` ? 'heroicons:check' : 'heroicons:clipboard-document'" @click="salin(item.kredensial.email, `e${item.id}`)">{{ copied === `e${item.id}` ? 'Tersalin' : 'Salin' }}</UiButton>
                                    </div>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-slate-500">Password</p>
                                    <div class="mt-1 flex items-center gap-2">
                                        <code class="rounded-md bg-white px-2.5 py-1.5 text-sm text-ink ring-1 ring-slate-200">{{ shown[item.id] ? item.kredensial.password : '••••••••' }}</code>
                                        <UiButton variant="ghost" size="sm" square class="-my-2" :icon="shown[item.id] ? 'heroicons:eye-slash' : 'heroicons:eye'" :label="shown[item.id] ? 'Sembunyikan password' : 'Lihat password'" @click="shown[item.id] = !shown[item.id]" />
                                        <UiButton variant="ghost" size="sm" class="-my-2 text-primary" :icon="copied === `p${item.id}` ? 'heroicons:check' : 'heroicons:clipboard-document'" @click="salin(item.kredensial.password, `p${item.id}`)">{{ copied === `p${item.id}` ? 'Tersalin' : 'Salin' }}</UiButton>
                                    </div>
                                </div>
                                <p v-if="item.kredensial.info" class="text-sm text-ink sm:col-span-2"><span class="text-xs font-medium text-slate-500">Info: </span>{{ item.kredensial.info }}</p>
                            </div>
                            <div v-if="item.bisa_ulas" class="mt-3">
                                <div v-if="item.ulasan && !editingReview[item.id]" class="flex flex-wrap items-center gap-2 text-sm">
                                    <span class="text-xs text-slate-500">Ulasanmu:</span>
                                    <StarRating :model-value="item.ulasan.rating" size="h-3.5 w-3.5" />
                                    <span v-if="item.ulasan.komentar" class="text-slate-600">"{{ item.ulasan.komentar }}"</span>
                                    <UiButton variant="ghost" size="sm" class="-my-2 text-primary" icon="heroicons:pencil-square" @click="editingReview[item.id] = true">Ubah</UiButton>
                                </div>
                                <form v-else class="rounded-xl border border-slate-200 p-3" @submit.prevent="kirimUlasan(item)">
                                    <p class="text-xs font-medium text-slate-600">{{ item.ulasan ? 'Ubah ulasan' : 'Beri ulasan untuk akun ini' }}</p>
                                    <StarRating v-model="reviewForms[item.id].rating" editable size="h-6 w-6" class="mt-1.5" :label="`Rating ${item.produk}`" />
                                    <p v-if="reviewForms[item.id].errors.rating" class="mt-1 text-xs text-rose-600">{{ reviewForms[item.id].errors.rating }}</p>
                                    <UiTextarea v-model="reviewForms[item.id].komentar" class="mt-2" rows="2" maxlength="1000" placeholder="Ceritakan pengalamanmu (opsional)" :aria-label="`Komentar ${item.produk}`" :error="reviewForms[item.id].errors.komentar" />
                                    <UiButton type="submit" size="sm" class="mt-2" :loading="reviewForms[item.id].processing" :disabled="!reviewForms[item.id].rating">Kirim Ulasan</UiButton>
                                </form>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <aside class="h-fit space-y-4">
                <div class="rounded-2xl border border-slate-200 p-5">
                    <h2 class="text-sm font-semibold text-ink">Ringkasan</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between text-slate-600"><dt>Subtotal</dt><dd class="text-ink">{{ rupiah(order.subtotal) }}</dd></div>
                        <div v-if="order.kode_unik" class="flex justify-between text-slate-600"><dt>Kode unik</dt><dd class="text-ink">{{ rupiah(order.kode_unik) }}</dd></div>
                        <div class="flex justify-between border-t border-slate-100 pt-2 font-bold text-ink"><dt>Total</dt><dd>{{ rupiah(order.total) }}</dd></div>
                        <div v-if="order.paid_at" class="flex justify-between text-xs text-slate-500"><dt>Dibayar</dt><dd>{{ order.paid_at }}</dd></div>
                    </dl>
                </div>
                <UiButton v-if="waLink" :href="waLink" external variant="secondary" block>
                    <Icon icon="logos:whatsapp-icon" class="h-4 w-4" /> Hubungi admin / klaim garansi
                </UiButton>
            </aside>
        </div>
    </section>
</template>

<script>
import PublicLayout from '@/Layouts/PublicLayout.vue'

export default { layout: PublicLayout }
</script>
