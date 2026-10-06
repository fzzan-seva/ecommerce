# Installation

This guide takes a fresh clone of the template to a running store with demo data.

## 1. Requirements

| Dependency | Version |
|---|---|
| PHP | 8.1+ (tested on PHP 8.5) |
| PHP extensions | `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `xml`, `ctype`, `json` (standard on almost every host) |
| Composer | 2.x |
| MySQL / MariaDB | MySQL 8.0+ or MariaDB 10.4+ |
| Node.js + npm | 18+ (optional — only for the Vite asset pipeline) |

The `gd` extension is **not** required.

## 2. Clone and install dependencies

```bash
git clone <your-repository-url> laravel-ecommerce
cd laravel-ecommerce
composer install
```

## 3. Environment file

```bash
cp .env.example .env
php artisan key:generate
```

Then edit `.env` and set at minimum:

```dotenv
APP_NAME="My Store"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

All store-specific defaults (`SHOP_*`) are already present in `.env.example`
with safe placeholder values — see
[configuration.md](configuration.md). Everything can also be changed later from
the admin panel without touching `.env`.

## 4. Database

```bash
php artisan migrate --seed
```

This runs every migration and the demo seeder, which creates:

- two demo users —
  - **Admin:** `admin@example.com`
  - **Customer:** `customer@example.com`
- generic categories (`Dresses`, `Tops`, `Outerwear`, `Accessories`)
- six demo products with variants and stock

The seeder prints each account's **randomly generated password exactly once**:

```
Demo credentials (shown once, store them safely):
  Admin    : admin@example.com / <16-character random password>
  Customer : customer@example.com / <16-character random password>
```

There are no default passwords anywhere in the codebase — if you lose the
printed credentials, reset the password with tinker:

```bash
php artisan tinker
>>> \App\Models\User::where('email', 'admin@example.com')->first()->forceFill(['password' => Illuminate\Support\Facades\Hash::make('New-Secure-Password')])->save();
```

To start without demo data, run `php artisan migrate` instead and create the
first admin user as described in [admin-guide.md](admin-guide.md#creating-the-first-admin).

## 5. Storage link

Product images and the store logo are stored on the `public` disk and need the
standard symlink:

```bash
php artisan storage:link
```

Payment receipts are stored on the **private** `payment_proofs` disk
(`storage/app/private/payment-proofs/`) and deliberately have **no** public link.

## 6. Front-end assets (optional)

The storefront styling ships as a plain stylesheet (`public/css/app.css`) — the
store runs without any Node step. A Vite pipeline is included for integrators who
want to modernise the front end:

```bash
npm ci
npm run build
```

If your package manager blocks dependency install scripts (npm 11+ does by
default for some packages), run `npm rebuild esbuild` before building.

## 7. Serve the application

```bash
php artisan serve
```

Visit `http://127.0.0.1:8000`, log in with the seeded admin account and open
**Pengaturan Toko** to set your store name, payment accounts and shipping rate.

For production deployment (HTTPS, caching, permissions, cron), continue with
[deployment.md](deployment.md).

## Verifying the installation

| Check | Expected result |
|---|---|
| Homepage | Storefront loads with demo products |
| `/login` | Seeded admin can sign in |
| `/admin` | Dashboard shows statistics |
| **Admin → Pengaturan Toko** | Settings save and take effect immediately |
| `/checkout` (as customer, with an address) | Order can be placed with a receipt upload |

If anything fails, see [troubleshooting.md](troubleshooting.md).
