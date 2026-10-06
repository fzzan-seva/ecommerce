# Laravel E-Commerce

A complete, production-ready e-commerce application built with **Laravel 10** and **Blade**.
It ships with a responsive storefront (product variants, categories, search, cart, checkout),
a manual bank / e-wallet payment flow with receipt verification, customer order history,
and a full admin panel — all white-labelled, so you rename the store, set your own payment
accounts and shipping rate from the admin panel, and start selling.

## Features

### Storefront
- Product catalogue with categories, live search and category filtering
- Products with size / colour variants and per-variant stock levels
- Shopping cart, shipping address book and checkout
- Manual payment flow: the customer uploads a photo of the transfer receipt,
  the admin verifies it before the order is processed
- Order history with status tracking and receipt viewing
- Elegant, fully responsive dark & gold design

### Admin panel (`/admin`)
- Dashboard: revenue, pending orders, low-stock alerts, recent orders
- Product management with variants, images and activation toggle
- Category management (deletion is blocked while products still use the category)
- Order management with a status workflow and automatic stock bookkeeping
- Store settings — identity, currency, shipping cost, social links and payment
  methods — editable at runtime from the browser, no code changes required
- Customer directory with order overview

### Under the hood
- Single source of truth for store configuration: `config/shop.php` (`.env` `SHOP_*`
  variables) with database overrides from **Admin → Pengaturan Toko**
- Payment receipts stored on a **private disk** and served only to their owner or an admin
- Prices, totals and stock are always computed server-side; stock is claimed atomically
  at checkout so concurrent buyers cannot oversell
- PHPUnit test suite (78 tests, 349 assertions) covering auth, cart, checkout,
  orders, payment proofs, settings and the storefront

## Requirements

- PHP 8.1 or newer (developed and tested on PHP 8.5)
- Composer 2
- Node.js 18+ and npm (only needed if you use the included Vite pipeline)
- MySQL 8 / MariaDB 10.4+

## Quick start

```bash
git clone <your-repository-url> laravel-ecommerce
cd laravel-ecommerce
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your database credentials (`DB_*`). Then:

```bash
php artisan migrate --seed
php artisan storage:link
```

The seeder creates two demo accounts and prints their **randomly generated**
passwords **once** — store them safely:

```
Demo credentials (shown once, store them safely):
  Admin    : admin@example.com / <random password>
  Customer : customer@example.com / <random password>
```

Optionally build the front-end pipeline (the storefront itself runs without it —
styling ships as the plain stylesheet `public/css/app.css`):

```bash
npm ci
npm run build
```

Serve the application:

```bash
php artisan serve
```

Open `http://127.0.0.1:8000`, sign in as the admin and open **Pengaturan Toko**
(store settings) to set your store name, payment accounts and shipping rate.
Full walkthrough: [docs/installation.md](docs/installation.md).

## Configuration

All store-level configuration is environment-driven and can be overridden at
runtime from the admin panel (database values win over `.env` values):

| `.env` variable | Purpose |
|---|---|
| `SHOP_NAME`, `SHOP_TAGLINE`, `SHOP_DESCRIPTION` | Store identity |
| `SHOP_LOGO`, `SHOP_PHONE`, `SHOP_EMAIL`, `SHOP_ADDRESS` | Contact details |
| `SHOP_WHATSAPP`, `SHOP_INSTAGRAM`, `SHOP_FACEBOOK` | Social links (empty = hidden) |
| `SHOP_CURRENCY`, `SHOP_CURRENCY_SYMBOL`, `SHOP_CURRENCY_DECIMALS` | Price formatting |
| `SHOP_SHIPPING_COST` | Flat shipping rate used by cart, checkout and orders |
| `SHOP_ORDER_PREFIX` | Prefix for generated order numbers (default `ORD`) |

Payment methods (labels, account numbers, enabled/disabled) are managed from
**Admin → Pengaturan Toko** — never commit real account numbers to `.env`.
Details: [docs/configuration.md](docs/configuration.md).

## Documentation

| Guide | What it covers |
|---|---|
| [docs/installation.md](docs/installation.md) | Requirements, install, seeding, first login |
| [docs/configuration.md](docs/configuration.md) | Every `SHOP_*` variable and the admin settings panel |
| [docs/admin-guide.md](docs/admin-guide.md) | Using the admin panel day to day |
| [docs/payment.md](docs/payment.md) | The manual payment & receipt verification flow |
| [docs/deployment.md](docs/deployment.md) | Deploying to a VPS or shared hosting |
| [docs/troubleshooting.md](docs/troubleshooting.md) | Common problems and how to fix them |

## Testing

```bash
php artisan test
```

The suite uses PHPUnit only (no Pest). It runs against the database configured
in `.env` and uses `RefreshDatabase` — **it will wipe that database**, so point
`DB_DATABASE` at a dedicated test database before running it. Test images are
pre-made fixtures in `tests/fixtures/`, so the suite runs without the GD extension.

## Notes for integrators

- The storefront/admin interface copy is in Indonesian; all user-facing strings
  live in `resources/views/` and are plain Blade — translate or rebrand freely.
- Product images and logos are stored on the `public` disk (run
  `php artisan storage:link`). Payment receipts live on the private
  `payment_proofs` disk and are **never** publicly reachable.
- `php artisan proofs:relocate` moves any receipts that predate the private-disk
  change; use `--dry-run` to preview.
- Prices flow through the `price()` helper and store identity through `shop()`
  (see `app/helpers.php` and `app/Support/Shop.php`) — never hard-code either.

## License

This source code is released under the [MIT License](LICENSE).

It is sold and distributed as a developer template: you may use it to build and
sell your own end products without restriction. You may **not** redistribute or
resell the template itself, in whole or in part, as a competing template or
source-code product.
