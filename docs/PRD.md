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

Registrasi terbuka untuk Customer & Reseller (pilih role saat daftar). Karyawan dibuat oleh Admin dari panel.

## 3. Model Data (ERD)

```
users            id, name, email, password, role (admin|customer|reseller|karyawan)
categories       id, nama, slug            (Streaming, AI Tools, Desain, Office, Cloud, Game, Lainnya)
products         id, category_id, nama, slug, deskripsi, banner, logo, garansi_hari,
                 rating, total_terjual, is_aktif, is_populer
product_variants id, product_id, jenis_akun (private|sharing), durasi_hari (bebas: 7, 30, 60, 365, ...),
                 harga, harga_asli, harga_reseller, is_aktif      -- unik per (produk, jenis_akun, durasi_hari)
stocks           id, product_variant_id, email*, email_hash, password*, total_slot, created_by
stock_slots      id, stock_id, nomor (1..total_slot), info*, order_item_id (nullable, ditambah saat fase order)
                 -- slot tersedia jika order_item_id null.   * = terenkripsi
carts            id, user_id, session_token (guest, opsional)
cart_items       id, cart_id, product_variant_id, qty
orders           id, user_id, no_invoice, subtotal, total, status
                 (menunggu_bayar|diproses|selesai|dibatalkan|refund), bukti_bayar_path
order_items      id, order_id, product_variant_id, snapshot_nama, snapshot_harga, qty,
                 qty selalu 1 per baris (1 baris = 1 slot), slot ditautkan via stock_slots.order_item_id
reviews          id, order_item_id, rating (1-5), komentar
wishlists        id, user_id, product_id
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
- **Rating & total_terjual** di produk = agregat denormalisasi (di-update saat review/mutasi order) agar katalog cepat.
- **Stocks** = gudang kredensial, input manual lewat halaman admin (bukan integrasi stok-zaka). Pengiriman otomatis: ambil slot kosong (FIFO per varian, dalam transaksi + lock), tautkan ke `order_item`.
- **Kredensial dibaca live dari stocks** (bukan snapshot) → klaim garansi = admin perbarui email/password di stok, pembeli otomatis melihat kredensial baru.

## 4. Alur Utama

### 4.1 Checkout (Fase 1 — pembayaran manual)
1. Customer pilih produk + tipe akun (private/sharing) + durasi → keranjang, atau "Beli Sekarang" (langsung checkout).
2. Checkout: ringkasan, data kontak, transfer manual → upload bukti bayar → order status `menunggu_bayar`.
3. Karyawan/Admin verifikasi bukti di panel → status `diproses`.
4. Sistem **otomatis**: ambil slot stok kosong (FIFO per varian; produk sharing berbagi kredensial yang sama) → tautkan ke `order_item`.
5. Customer melihat kredensial di halaman "Pesanan Saya" + notifikasi (email/in-app). Order `selesai` setelah semua item terkirim.

### 4.2 Fase 2 — Gateway
Ganti langkah 2–3 dengan payment gateway (QRIS/VA/e-wallet). Webhook `paid` → auto-assign kredensial tanpa verifikasi manual.

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
| Wishlist | — | Produk tersimpan |
| Akun Saya | — | Profil, ganti password |

### Admin/Karyawan (satu panel, beda menu per permission)
| Halaman | Isi |
|---------|-----|
| Dashboard | Statistik: penjualan, order menunggu verifikasi, stok menipis |
| Produk | CRUD produk + varian (tipe akun × durasi) + harga (normal & reseller) (**sudah dibuat**: `/admin/produk`). Tipe & durasi varian terkunci setelah ada stok; produk/varian yang punya stok tidak bisa dihapus, cukup dinonaktifkan |
| Stok Kredensial | Input massal `email\|password\|slot\|info` per varian dengan preview (**sudah dibuat**: `/admin/stok/import`), daftar stok, filter tersedia/terjual |
| Order | Daftar + detail, verifikasi bukti bayar (terima/tolak) → trigger kirim otomatis. Klaim garansi: update kredensial di stok |
| User (Admin only) | Kelola user, jadikan karyawan/reseller |

## 6. Scope

### Fase 1 (MVP)
- Auth + role middleware (customer/reseller/karyawan/admin)
- Katalog: filter kategori/harga/durasi/jenis akun, sort (terpopuler, terbaru, harga), search
- Detail produk, keranjang, beli langsung
- Checkout transfer manual + upload bukti
- Panel admin: produk, stok kredensial, order + verifikasi
- **Kirim kredensial otomatis** saat order disetujui
- Pesanan saya + detail kredensial
- Wishlist, rating & review
- Harga reseller otomatis berlaku untuk role reseller
- Produk sharing (multi-slot) + klaim garansi manual

### Fase 2
- Payment gateway (QRIS/VA), webhook auto-paid
- Notifikasi WhatsApp/Telegram
- Komisi & dashboard reseller (referral)
- Kupon/voucher, flash sale ("Deals" di navbar)

## 7. Keputusan

1. Garansi → **klaim manual**: admin perbarui kredensial di stok, pembeli lihat otomatis.
2. Reseller → **level harga** (lebih murah), aktif sejak register. Multi-level nanti bila perlu.
3. Tipe akun **private/sharing** → pilihan varian yang ditentukan admin; sharing lewat multi-slot dengan info per slot.
5. Durasi bebas dalam hari; stok terpisah per durasi.
4. Review & rating → **masuk MVP**.
