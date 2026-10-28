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
products         id, category_id, nama, slug, banner, logo, jenis_akun (personal|private|sharing|upgrade),
                 harga_asli, harga, rating, total_terjual, garansi_hari, is_aktif, is_populer
product_variants id, product_id, durasi_bulan (1|3|6|12), harga, harga_asli, is_aktif
stocks           id, product_variant_id, email, password, catatan,
                 total_slot, slot_terpakai   -- tersedia jika slot_terpakai < total_slot
carts            id, user_id, session_token (guest, opsional)
cart_items       id, cart_id, product_variant_id, qty
orders           id, user_id, no_invoice, subtotal, total, status
                 (menunggu_bayar|diproses|selesai|dibatalkan|refund), bukti_bayar_path
order_items      id, order_id, product_variant_id, snapshot_nama, snapshot_harga, qty,
                 stock_id (nullable, set saat kredensial terkirim)
reviews          id, order_item_id, rating (1-5), komentar
wishlists        id, user_id, product_id
```

Catatan:
- **Harga reseller**: kolom `harga_reseller` pada `product_variants` (null = harga sama). Reseller = satu level harga dulu; kalau nanti butuh beberapa tingkat, naikkan ke tabel `reseller_levels`.
- **Produk sharing**: satu kredensial dijual ke banyak pembeli via `total_slot` > 1 (mis. slot profil Netflix). Produk personal = `total_slot` 1. Satu mekanisme untuk keduanya.
- **Rating & total_terjual** di produk = agregat denormalisasi (di-update saat review/mutasi order) agar katalog cepat.
- **Stocks** = gudang kredensial, input manual lewat halaman admin (bukan integrasi stok-zaka). Pengiriman otomatis: ambil baris ber-slot kosong (FIFO), isi 1 slot, tautkan ke `order_item`.
- **Kredensial dibaca live dari stocks** (bukan snapshot) → klaim garansi = admin perbarui email/password di stok, pembeli otomatis melihat kredensial baru.

## 4. Alur Utama

### 4.1 Checkout (Fase 1 — pembayaran manual)
1. Customer pilih produk + durasi → keranjang, atau "Beli Sekarang" (langsung checkout).
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
| Produk | CRUD produk + varian durasi + harga (normal & reseller) |
| Stok Kredensial | Mass-input kredensial per varian, status, filter stok tersedia/terjual |
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
3. Produk **sharing** → didukung lewat multi-slot pada stok.
4. Review & rating → **masuk MVP**.
