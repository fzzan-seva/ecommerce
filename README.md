# FQueensha — E-Commerce Gamis Laravel

Toko online gamis perempuan dengan desain elegan hitam & emas. Dibangun dengan Laravel 10.

## Fitur

### Pengunjung / User
- Register, login, logout
- Jelajahi katalog gamis (filter kategori & pencarian)
- Checkout dengan transfer (GoPay, DANA, OVO, BSI, Mandiri)
- Kelola alamat pengiriman
- Riwayat pesanan

### Admin
- Dashboard statistik (produk, user, pesanan, pendapatan)
- CRUD produk (tambah, edit, hapus + upload gambar)
- Lihat daftar user & detail akun + alamat user
- Kelola status pesanan

## Akun Demo (setelah seed)

| Role  | Email                 | Password  |
|-------|-----------------------|-----------|
| Admin | admin@fqueensha.com   | password  |
| User  | user@fqueensha.com    | password  |

## Instalasi

```bash
cd fqueensha
composer install
cp .env.example .env   # jika belum ada
php artisan key:generate
```

Atur database di `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fqueensha
DB_USERNAME=root
DB_PASSWORD=
```

Atau gunakan SQLite (default):

```
DB_CONNECTION=sqlite
```

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Buka http://127.0.0.1:8000

## Kontak & Pembayaran

- **Lokasi:** Soreang, Kab. Bandung, Jawa Barat
- **WhatsApp:** 0812-1453-1169
- **Instagram:** [@fi.triyani5625](https://instagram.com/fi.triyani5625)

Metode pembayaran (transfer only):
| Metode | Nomor |
|--------|-------|
| GoPay / DANA / OVO | 0812-1453-1169 |
| Bank BSI | 7233648056 |
| Bank Mandiri | 1340020333638 |

Konfigurasi dapat diubah di `config/fqueensha.php`.

## Struktur URL

- `/` — Beranda toko
- `/login`, `/register` — Autentikasi
- `/admin` — Dashboard admin
- `/keranjang`, `/checkout`, `/pesanan`, `/alamat` — Fitur user (perlu login)

## Tech Stack

- Laravel 10, PHP 8.1+
- Blade templates
- CSS custom (tema hitam #0a0a0a & emas #c9a227)
