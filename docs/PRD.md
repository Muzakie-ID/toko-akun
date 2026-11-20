# PRD — Akunhaus (Toko Akun Premium)

Stack: Laravel 12 + Inertia.js (Vue 3) + MySQL + Tailwind CSS.
Desain referensi: Figma `BsqTdAihsH3tdPewNiivAW`.

## 1. Ringkasan

Marketplace jual-beli akun premium (Netflix, Spotify, ChatGPT, Canva, dst). Customer memilih produk + durasi, bayar, lalu kredensial akun **dikirim otomatis** oleh sistem dari stok yang dikelola admin.

## 2. Peran (Roles)

| Role | Akses |
|------|-------|
| **Customer** | Register/login, beli, keranjang, wishlist, riwayat pesanan, terima kredensial |
| **Reseller** | Semua kemampuan Customer + **harga level reseller** (lebih murah) di katalog & checkout. Reseller = level harga, bukan toko terpisah |
| **Karyawan** | Panel admin **terbatas**: kelola produk, stok kredensial, verifikasi pembayaran. Tidak bisa kelola user/role/pengaturan |
| **Admin** | Semua akses: user & role, produk, stok, order, laporan, pengaturan |

Registrasi selalu sebagai Customer. Customer **otomatis naik menjadi Reseller** begitu total belanja lunasnya ≥ batas di Pengaturan (default Rp1.000.000); admin juga bisa mengubah role manual. Karyawan dibuat oleh Admin dari panel.

## 3. Model Data (ERD)

```
users            id, name, email, password, role (admin|customer|reseller|karyawan)
categories       id, nama, slug            (Streaming, AI Tools, Desain, Office, Cloud, Game, Lainnya)
products         id, category_id, nama, slug, deskripsi, banner, logo, garansi_hari,
                 rating, total_terjual, is_aktif, is_populer
product_variants id, product_id, jenis_akun (private|sharing), durasi_hari (bebas: 7, 30, 60, 365, ...),
                 harga, harga_asli, harga_reseller, is_aktif      -- unik per (produk, jenis_akun, durasi_hari)
stocks           id, product_variant_id, email*, email_hash, password*, total_slot, created_by
stock_slots      id, stock_id, nomor (1..total_slot), info*, order_item_id (nullable, unik)
                 -- slot tersedia jika order_item_id null.   * = terenkripsi
cart_items       id, user_id, product_variant_id, qty      -- keranjang hanya untuk user login
orders           id, user_id, no_invoice, metode_bayar (qris|manual), status, subtotal, kode_unik, total,
                 gateway_id, checkout_url, qr_image_url, qris_payload, expires_at, paid_at, late_paid_at,
                 bukti_bayar_path, alasan_tolak, catatan_admin
                 status: menunggu_bayar|menunggu_verifikasi|diproses|selesai|dibatalkan|kedaluwarsa|refund
order_items      id, order_id, product_variant_id, snapshot_produk, snapshot_jenis_akun, snapshot_durasi_hari,
                 harga, delivered_at, aktif_sampai   -- 1 baris = 1 akun/slot (qty dipecah saat checkout)
settings         key, value   -- pengaturan toko & pembayaran; API key & webhook secret terenkripsi
webhook_events   event_id (unik), type, merchant_ref   -- dedup webhook qris-muz
reviews          id, order_item_id (unik), user_id, product_id, rating (1-5), komentar, is_tampil
wishlists        id, user_id, product_id (unik per user)
```

Catatan:
- **Harga reseller**: kolom `harga_reseller` pada `product_variants` (null = harga sama). Reseller = satu level harga dulu; kalau nanti butuh beberapa tingkat, naikkan ke tabel `reseller_levels`.
- **Tipe akun (private/sharing)** = pilihan varian setelah produk, ditentukan admin. Pembeli memilih produk → tipe → durasi. Harga per kombinasi.
- **Durasi** disimpan dalam hari (`durasi_hari`) agar bebas: 7 Hari, 1 Bulan (30), 2 Bulan (60), 1 Tahun (365). Label dihitung otomatis.
- **Stok terikat ke varian** (produk + tipe + durasi), jadi stok 1 Bulan dan 2 Bulan diinput terpisah.
- **Private** = `total_slot` 1. **Sharing** = `total_slot` ≥ 2, satu kredensial dibagi ke beberapa pembeli. Tiap slot punya `info` sendiri (mis. "Profil 2, PIN 1234"). Satu mekanisme untuk keduanya.
- **Input stok massal**: admin pilih varian lalu tempel satu akun per baris dengan format `email|password|slot|info`.
  - `slot` per baris; kosong = pakai "slot default" di form. Private selalu 1.
  - `info`: opsional untuk private, **wajib** untuk sharing. Info per slot dipisah `;`; satu info saja = berlaku untuk semua slot.
  - Ada preview sebelum simpan. Baris error (format, duplikat dalam batch, sudah ada di stok varian) tidak disimpan dan disisakan di form untuk diperbaiki. Maks 1000 baris per input. Password tidak boleh mengandung `|`.
- **Kredensial terenkripsi** di DB (email, password, info slot). `email_hash` (HMAC) dipakai untuk cek duplikat.
- **Rating, jumlah_ulasan & total_terjual** di produk = agregat denormalisasi (dihitung ulang saat ulasan dibuat/diubah/disembunyikan/dihapus dan saat akun terkirim) agar katalog cepat.
- **Ulasan**: hanya pemilik pesanan `selesai`, satu per akun yang sudah terkirim (bisa diubah). Nama pengulas disamarkan ("Budi S."). Admin/karyawan bisa menyembunyikan atau menghapus.
- **Logo produk**: upload PNG/JPG/WEBP (min. 64×64, maks. 1 MB, SVG ditolak) ke disk `public`; tanpa logo kartu memakai huruf awal. Butuh `php artisan storage:link` dan `APP_URL` yang benar.
- **Stocks** = gudang kredensial, input manual lewat halaman admin (bukan integrasi stok-zaka). Pengiriman otomatis: ambil slot kosong (FIFO per varian, dalam transaksi + lock), tautkan ke `order_item`.
- **Kredensial dibaca live dari stocks** (bukan snapshot) → klaim garansi = admin perbarui email/password di stok, pembeli otomatis melihat kredensial baru.

## 4. Alur Utama

### 4.1 Checkout
1. Customer pilih produk + tipe akun (private/sharing) + durasi → keranjang, atau "Beli Sekarang". Harga & stok dihitung ulang di server; reseller dapat `harga_reseller`.
2. Pilih metode bayar (yang aktif di Pengaturan):
   - **QRIS (qris-muz)**: sistem membuat tagihan di gateway qris-muz milik owner (`merchant_ref` = no invoice). Total = `final_amount` (subtotal + kode unik). Halaman pesanan menampilkan QR + hitung mundur dan memperbarui status otomatis.
   - **Transfer manual**: pembeli transfer ke rekening di Pengaturan lalu unggah bukti → `menunggu_verifikasi` → karyawan/admin terima atau tolak (dengan alasan, pembeli bisa unggah ulang).
3. Lunas (webhook `transaction.paid` dengan nominal cocok, atau verifikasi manual) → `diproses` → sistem **otomatis** ambil slot kosong (FIFO per varian, transaksi + lock) → `selesai` + email ke pembeli.
4. Stok kurang → pesanan tetap `diproses` dan otomatis dilengkapi saat stok varian itu diinput.
5. Tidak dibayar sampai batas waktu → `kedaluwarsa` (webhook `transaction.expired` atau command `orders:expire` tiap 5 menit).
6. Uang masuk setelah kedaluwarsa/dibatalkan (`transaction.late_paid`) → ditandai "perlu dicek"; admin memilih Proses Pesanan atau Refund.

### 4.2 Integrasi qris-muz
- Pengaturan admin: URL API, API key proyek, webhook secret (disimpan terenkripsi, write-only), masa berlaku tagihan, tes koneksi (`GET /v1/status`).
- Webhook: `POST /webhooks/qrismuz`, verifikasi `HMAC-SHA256(secret, timestamp + "." + body)` + toleransi 5 menit, dedup `X-Event-Id`. Nominal tidak cocok tidak melunasi pesanan, hanya dicatat untuk admin.
- Membatalkan pesanan QRIS dari panel ikut membatalkan tagihan di gateway.

## 5. Halaman (Inertia)

### Customer
| Halaman | Figma | Isi |
|---------|-------|-----|
| Beranda + Katalog | `10:3` | Navbar (search, keranjang+badge, Akun Saya), hero, filter chips, sidebar filter (kategori, slider harga, durasi, jenis akun, reset), sort, grid produk, "Paling Laris" |
| — komponen kartu | `7:2` | `ProductCard`: banner, wishlist, judul, badge jenis+durasi, rating+terjual, harga coret+diskon %, tombol Beli |
| — kartu laris | `7:25` | `ProductCardSmall`: thumb, nama, harga, rating+terjual |
| Detail Produk | (belum ada desain) | Banner, varian durasi, jenis akun, stok tersedia, review, tombol Beli (`4:8`) |
| Keranjang & Checkout | (belum ada desain) | Item, ubah qty, upload bukti bayar |
| Pesanan Saya | (belum ada desain) | Daftar order, status, **lihat kredensial** (copy email/pass) |
| Wishlist | — | **Sudah dibuat** (`/wishlist`): ikon hati di kartu & detail produk |
| Akun Saya | — | **Sudah dibuat** (`/akun`): ubah nama/email, ganti password (user Google bisa membuat password tanpa password lama) |

### Admin/Karyawan (satu panel, beda menu per permission)
| Halaman | Isi |
|---------|-----|
| Dashboard | **Sudah dibuat** (`/admin`): pendapatan/pesanan/akun terjual/pembeli baru + pertumbuhan vs periode sama bulan lalu, chart pendapatan bulanan/harian, gauge target bulanan, perlu tindakan, pesanan terbaru, stok menipis |
| Produk | CRUD produk + varian (tipe akun × durasi) + harga (normal & reseller) (**sudah dibuat**: `/admin/produk`). Tipe & durasi varian terkunci setelah ada stok; produk/varian yang punya stok tidak bisa dihapus, cukup dinonaktifkan |
| Stok Kredensial | Input massal `email\|password\|slot\|info` per varian dengan preview (**sudah dibuat**: `/admin/stok/import`), daftar stok, filter tersedia/terjual |
| Order | **Sudah dibuat** (`/admin/pesanan`): tab status, cari, filter; detail dengan kredensial & bukti; terima/tolak, kirim akun yang kurang, proses bayar telat, batal, refund, catatan |
| Stok Akun | **Sudah dibuat** (`/admin/stok`): daftar + slot terjual per invoice, edit kredensial & info slot (klaim garansi), hapus bila belum terjual |
| Kategori | **Sudah dibuat** (`/admin/kategori`) |
| Ulasan | **Sudah dibuat** (`/admin/ulasan`): filter bintang/tampil, sembunyikan, hapus |
| User (Admin only) | **Sudah dibuat** (`/admin/pengguna`): ubah role, buat akun karyawan/admin |
| Pengaturan (Admin only) | **Sudah dibuat** (`/admin/pengaturan`): toko & target, QRIS qris-muz, rekening transfer manual |

## 6. Scope

### Fase 1 (MVP)
- Auth + role middleware (customer/reseller/karyawan/admin)
- Katalog: filter kategori/harga/durasi/jenis akun, sort (terpopuler, terbaru, harga), search
- Detail produk, keranjang, beli langsung
- Checkout QRIS qris-muz (otomatis) + transfer manual dengan upload bukti
- Panel admin: produk, stok kredensial, order + verifikasi
- **Kirim kredensial otomatis** saat order disetujui
- Pesanan saya + detail kredensial
- Wishlist, rating & review
- Harga reseller otomatis berlaku untuk role reseller
- Produk sharing (multi-slot) + klaim garansi manual

### Fase 2
- Notifikasi WhatsApp/Telegram
- Komisi & dashboard reseller (referral)
- Kupon/voucher, flash sale ("Deals" di navbar)

## 7. Keputusan

1. Garansi → **klaim manual**: admin perbarui kredensial di stok, pembeli lihat otomatis.
2. Reseller → **level harga** (lebih murah), didapat otomatis saat total belanja lunas ≥ Rp1.000.000 (bisa diubah/dimatikan di Pengaturan). Dicek setiap pesanan lunas + command harian `resellers:promote`; tidak pernah diturunkan otomatis. Multi-level nanti bila perlu.
3. Tipe akun **private/sharing** → pilihan varian yang ditentukan admin; sharing lewat multi-slot dengan info per slot.
5. Durasi bebas dalam hari; stok terpisah per durasi.
4. Review & rating → **masuk MVP**.
