<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { UiButton } from '@/Components/ui'

const props = defineProps({
    toko: { type: Object, required: true },
    pembayaran: { type: Object, required: true },
    topup: { type: Object, required: true },
    reseller: { type: Object, required: true },
    diperbarui: { type: String, required: true },
})

const rupiah = (n) => 'Rp' + Number(n).toLocaleString('id-ID')
const waLink = computed(() => (props.toko.whatsapp ? `https://wa.me/${props.toko.whatsapp.replace(/\D/g, '')}` : null))

const bagian = [
    { id: 'umum', judul: 'Ketentuan umum', icon: 'heroicons:document-text' },
    { id: 'akun', judul: 'Akun pengguna', icon: 'heroicons:user-circle' },
    { id: 'produk', judul: 'Produk & tipe akun', icon: 'heroicons:key' },
    { id: 'pembayaran', judul: 'Pemesanan & pembayaran', icon: 'heroicons:credit-card' },
    { id: 'saldo', judul: 'Saldo & harga reseller', icon: 'heroicons:wallet' },
    { id: 'garansi', judul: 'Garansi', icon: 'heroicons:shield-check' },
    { id: 'refund', judul: 'Pembatalan & refund', icon: 'heroicons:receipt-refund' },
    { id: 'larangan', judul: 'Larangan', icon: 'heroicons:no-symbol' },
    { id: 'privasi', judul: 'Kebijakan privasi', icon: 'heroicons:lock-closed' },
    { id: 'perubahan', judul: 'Perubahan ketentuan', icon: 'heroicons:arrow-path' },
]
</script>

<template>
    <section class="mx-auto max-w-[1320px] px-4 py-8 lg:px-15 lg:py-12">
        <Head title="Syarat & Ketentuan" />

        <header class="max-w-3xl">
            <p class="text-sm font-semibold text-slate-500">Bantuan</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink sm:text-3xl">Syarat &amp; Ketentuan</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Halaman ini mengatur penggunaan layanan {{ toko.nama }}, termasuk kebijakan privasi. Mohon dibaca sebelum mendaftar atau
                berbelanja. Dengan membuat akun atau melakukan pembelian, kamu dianggap telah membaca dan menyetujui seluruh ketentuan di bawah.
            </p>
            <p class="mt-3 inline-flex items-center gap-1.5 text-xs text-slate-400">
                <Icon icon="heroicons:calendar" class="h-4 w-4" /> Terakhir diperbarui {{ diperbarui }}
            </p>
        </header>

        <div class="mt-8 grid gap-8 lg:grid-cols-[260px_minmax(0,1fr)] lg:gap-12">
            <nav aria-label="Daftar isi" class="lg:sticky lg:top-24 lg:self-start">
                <details class="group rounded-2xl border border-slate-200 lg:hidden">
                    <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-3 text-sm font-semibold text-ink">
                        Daftar isi
                        <Icon icon="heroicons:chevron-down" class="h-4 w-4 transition group-open:rotate-180" />
                    </summary>
                    <ol class="space-y-0.5 px-2 pb-2">
                        <li v-for="(b, i) in bagian" :key="b.id">
                            <a :href="`#${b.id}`" class="flex items-center gap-3 rounded-lg px-2 py-2 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-ink">
                                <span class="w-5 text-right text-xs tabular-nums text-slate-400">{{ i + 1 }}</span>
                                {{ b.judul }}
                            </a>
                        </li>
                    </ol>
                </details>
                <ol class="hidden space-y-0.5 lg:block">
                    <li v-for="(b, i) in bagian" :key="b.id">
                        <a :href="`#${b.id}`" class="flex items-center gap-3 rounded-lg px-2 py-2 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-ink">
                            <span class="w-5 text-right text-xs tabular-nums text-slate-400">{{ i + 1 }}</span>
                            {{ b.judul }}
                        </a>
                    </li>
                </ol>
            </nav>

            <article class="max-w-3xl space-y-10 text-sm leading-7 text-slate-600 [&_h2]:scroll-mt-24 [&_li]:pl-1 [&_ol]:list-decimal [&_ol]:space-y-1.5 [&_ol]:pl-5 [&_strong]:font-semibold [&_strong]:text-ink [&_ul]:list-disc [&_ul]:space-y-1.5 [&_ul]:pl-5">
                <section v-for="(b, i) in bagian" :key="b.id" :aria-labelledby="b.id">
                    <h2 :id="b.id" class="flex items-center gap-3 text-lg font-bold text-ink">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100">
                            <Icon :icon="b.icon" class="h-5 w-5 text-slate-700" />
                        </span>
                        {{ i + 1 }}. {{ b.judul }}
                    </h2>

                    <div class="mt-3 space-y-3">
                        <template v-if="b.id === 'umum'">
                            <p>{{ toko.nama }} adalah toko daring yang menjual akses akun layanan digital premium (streaming, AI tools, desain, dan lainnya) dengan durasi tertentu.</p>
                            <ul>
                                <li>{{ toko.nama }} adalah penjual independen dan <strong>tidak berafiliasi</strong> dengan pemilik layanan yang akunnya dijual.</li>
                                <li>Nama dan merek layanan yang tercantum adalah milik pemiliknya masing-masing.</li>
                                <li>Semua harga dalam Rupiah dan sudah termasuk biaya yang tampil saat checkout.</li>
                            </ul>
                        </template>

                        <template v-else-if="b.id === 'akun'">
                            <ul>
                                <li>Pembelian, saldo, dan wishlist memerlukan akun. Data pendaftaran harus benar dan email harus aktif.</li>
                                <li>Kamu bertanggung jawab menjaga kerahasiaan password akun {{ toko.nama }}. Aktivitas dari akunmu dianggap dilakukan olehmu.</li>
                                <li>Kami dapat membekukan akun yang terindikasi penipuan atau penyalahgunaan.</li>
                            </ul>
                        </template>

                        <template v-else-if="b.id === 'produk'">
                            <p>Setiap produk dijual dalam varian berupa <strong>tipe akun</strong> dan <strong>durasi</strong> (misalnya 7 hari atau 1 bulan).</p>
                            <ul>
                                <li><strong>Private</strong>: satu akun dipakai sendiri oleh pembeli.</li>
                                <li><strong>Sharing</strong>: satu akun dipakai bersama beberapa pembeli, masing-masing mendapat slot/profil sendiri. Jangan memakai, mengganti, atau menghapus slot milik pembeli lain.</li>
                                <li>Dilarang mengganti email, password, PIN, nama profil, atau paket akun yang dibeli, kecuali diizinkan dalam info tambahan pesanan.</li>
                                <li>Masa aktif dihitung sejak kredensial dikirim, bukan sejak akun pertama kali dipakai.</li>
                                <li>Selalu ikuti petunjuk di <strong>info tambahan</strong> yang menyertai kredensial.</li>
                            </ul>
                        </template>

                        <template v-else-if="b.id === 'pembayaran'">
                            <ul>
                                <li>Harga dan ketersediaan stok dihitung ulang oleh sistem saat checkout. Harga yang berlaku adalah harga di halaman pembayaran.</li>
                                <li v-if="pembayaran.qris">
                                    <strong>QRIS</strong>: bayar sesuai nominal yang tertera, termasuk kode unik, dalam <strong>{{ pembayaran.qris_menit }} menit</strong>. Pembayaran terverifikasi otomatis dan akun langsung dikirim.
                                </li>
                                <li v-if="pembayaran.manual">
                                    <strong>Transfer manual</strong>: unggah bukti transfer dalam <strong>{{ pembayaran.manual_jam }} jam</strong>. Akun dikirim setelah admin memverifikasi bukti.
                                </li>
                                <li><strong>Saldo</strong>: pesanan langsung lunas dan akun langsung dikirim.</li>
                                <li>Pesanan yang tidak dibayar sampai batas waktu otomatis <strong>kedaluwarsa</strong>. Bila uang masuk setelah itu, admin akan memproses pesanan atau mengembalikan dana ke saldo.</li>
                                <li>Bila stok habis setelah pembayaran, pesanan berstatus <strong>diproses</strong> dan akun dikirim setelah stok tersedia, atau dana dikembalikan.</li>
                                <li>Harga <strong>flash sale</strong> hanya berlaku selama jadwal sale dan sisa kuota per akun. Pembelian melebihi kuota dikenakan harga normal.</li>
                            </ul>
                        </template>

                        <template v-else-if="b.id === 'saldo'">
                            <ul>
                                <li>Saldo diisi lewat top-up QRIS, minimal <strong>{{ rupiah(topup.min) }}</strong> dan maksimal <strong>{{ rupiah(topup.max) }}</strong> per transaksi. Kode unik tidak ikut masuk saldo.</li>
                                <li>Saldo hanya dapat dipakai untuk berbelanja di {{ toko.nama }} dan <strong>tidak dapat ditarik tunai</strong> atau dipindahkan ke pengguna lain.</li>
                                <li>Setiap perubahan saldo tercatat di riwayat saldo, termasuk penyesuaian oleh admin.</li>
                                <li v-if="reseller.otomatis">
                                    <strong>Harga reseller</strong> berlaku otomatis selama saldo saat ini minimal <strong>{{ rupiah(reseller.batas) }}</strong>. Status dicek ulang saat checkout. Bila saldo turun di bawah batas, harga kembali normal.
                                </li>
                                <li v-else>Harga reseller hanya berlaku untuk akun yang ditetapkan sebagai reseller oleh admin.</li>
                            </ul>
                        </template>

                        <template v-else-if="b.id === 'garansi'">
                            <ul>
                                <li>Masa garansi tertera di halaman masing-masing produk. Produk tanpa keterangan garansi tidak bergaransi.</li>
                                <li>Garansi berlaku bila akun tidak bisa dipakai karena kendala dari sisi kami, misalnya password salah atau langganan berhenti sebelum masa aktif habis.</li>
                                <li>Klaim diajukan dari halaman detail pesanan dengan menyertakan nomor invoice dan bukti kendala (tangkapan layar).</li>
                                <li>Bentuk penggantian: perbaikan atau penggantian kredensial (langsung tampil di halaman pesanan). Bila penggantian tidak memungkinkan, admin dapat mengembalikan dana ke saldo.</li>
                                <li><strong>Garansi gugur</strong> bila pembeli melanggar ketentuan di bagian 3 atau 8, misalnya mengganti password atau memakai slot orang lain.</li>
                            </ul>
                        </template>

                        <template v-else-if="b.id === 'refund'">
                            <ul>
                                <li>Pesanan yang <strong>belum dibayar</strong> dapat dibatalkan kapan saja.</li>
                                <li>Pesanan yang <strong>sudah dikirim</strong> tidak dapat dibatalkan atau ditukar, kecuali lewat klaim garansi.</li>
                                <li>Refund atas pesanan yang dibayar dengan saldo dikembalikan ke saldo. Refund pesanan lain dikembalikan ke saldo atau ke rekening pembeli sesuai kesepakatan dengan admin.</li>
                                <li>Pembayaran yang nominalnya tidak sesuai tidak melunasi pesanan secara otomatis dan akan dicek admin.</li>
                            </ul>
                        </template>

                        <template v-else-if="b.id === 'larangan'">
                            <p>Pengguna dilarang:</p>
                            <ul>
                                <li>Menjual ulang, menyewakan, atau membagikan kredensial yang dibeli tanpa izin tertulis dari kami.</li>
                                <li>Memakai akun untuk kegiatan yang melanggar hukum atau ketentuan layanan pemilik akun.</li>
                                <li>Melakukan penipuan pembayaran, chargeback tanpa dasar, atau memanfaatkan celah sistem.</li>
                            </ul>
                            <p>Pelanggaran dapat berakibat pembatalan garansi, pembekuan akun, dan pembekuan saldo.</p>
                        </template>

                        <template v-else-if="b.id === 'privasi'">
                            <p>Kami hanya mengumpulkan data yang dibutuhkan untuk menjalankan toko:</p>
                            <ul>
                                <li><strong>Data akun</strong>: nama, email, dan password (disimpan dalam bentuk hash; tidak ada password bila masuk dengan Google).</li>
                                <li><strong>Data transaksi</strong>: pesanan, pembayaran, bukti transfer, riwayat saldo, wishlist, dan ulasan.</li>
                            </ul>
                            <p>Data dipakai untuk memproses pesanan, mengirim kredensial, menangani garansi, dan mencegah penipuan.</p>
                            <ul>
                                <li>Kredensial akun yang dibeli disimpan terenkripsi dan hanya tampil untuk pembelinya.</li>
                                <li>Data pembayaran QRIS diproses oleh penyedia pembayaran. Kami tidak menyimpan data kartu atau rekening e-wallet.</li>
                                <li>Kami <strong>tidak menjual</strong> data pribadi kepada pihak lain.</li>
                                <li>Ulasan yang kamu tulis tampil publik dengan nama akunmu.</li>
                                <li>Kamu dapat mengubah data profil di halaman Akun Saya, atau meminta penghapusan akun melalui admin.</li>
                            </ul>
                        </template>

                        <template v-else-if="b.id === 'perubahan'">
                            <p>
                                Ketentuan ini dapat diperbarui sewaktu-waktu. Tanggal pembaruan tertera di bagian atas halaman. Ketentuan yang berlaku
                                untuk sebuah pesanan adalah ketentuan pada saat pesanan dibuat.
                            </p>
                        </template>
                    </div>
                </section>

                <aside class="flex flex-col gap-4 rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold text-ink">Ada pertanyaan?</p>
                        <p class="text-sm text-slate-500">Hubungi admin bila ada yang belum jelas sebelum membeli.</p>
                    </div>
                    <UiButton v-if="waLink" :href="waLink" external icon="heroicons:chat-bubble-left-right" class="shrink-0">Chat Admin</UiButton>
                    <UiButton v-else href="/" variant="secondary" class="shrink-0">Kembali ke Toko</UiButton>
                </aside>
            </article>
        </div>
    </section>
</template>

<script>
import PublicLayout from '@/Layouts/PublicLayout.vue'

export default { layout: PublicLayout }
</script>
