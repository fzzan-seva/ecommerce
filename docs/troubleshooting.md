# Troubleshooting

## General

### The site shows a 500 error

1. Set `APP_DEBUG=true` temporarily to see the real error (never leave it on in production).
2. Check `storage/logs/laravel.log`.
3. Usually it is file permissions:

   ```bash
   chown -R www-data:www-data storage bootstrap/cache
   ```

4. Clear caches:

   ```bash
   php artisan optimize:clear
   ```

### Styles/scripts are missing (unstyled pages)

```bash
php artisan storage:link      # public/storage must exist for images
ls -la public/css/app.css     # pre-built CSS must be present
```

If you edited `resources/css`, rebuild: `npm ci && npm rebuild esbuild && npm run build`.

### Changes to `.env` or `config/*.php` have no effect

```bash
php artisan config:clear
php artisan config:cache
```

### Views do not update / old markup appears

Compiled views are cached in `storage/framework/views`:

```bash
php artisan view:clear
```

### A route returns 404 after adding one

```bash
php artisan route:clear && php artisan route:cache
```

## Store settings

### I changed a value in admin but the site still shows the old one

Settings saved in **Pengaturan** are stored in the `settings` table and
**override** `config/shop.php`. Clear caches:

```bash
php artisan cache:clear && php artisan config:clear
```

To go back to the `.env` value, delete the row (admin: reset the field, or):

```bash
php artisan tinker
\DB::table('settings')->where('key', 'currency_symbol')->delete();
```

### Prices show `Rp` / wrong currency although I set something else

Currency formatting goes through `price()` and reads, in order:
`settings` table → `config/shop.php` → hard-coded default. Check
`SHOP_CURRENCY_SYMBOL` / `SHOP_CURRENCY_DECIMALS` in `.env`, then look for an
override row in `settings`.

## Media & payment proofs

### Product images do not appear

- `php artisan storage:link` must succeed.
- The `public` disk root comes from `config/filesystems.php`
  (`storage/app/public` by default); product images are stored there.
- Re-upload the image; a broken file may have been stored.

### A customer cannot re-upload a payment proof

The receipt is uploaded once, as part of the checkout form — the storefront has
no second upload endpoint by design. To accept a different receipt, the admin
contacts the customer and records the outcome through the order status.

### Payment proofs are publicly accessible

They must not be. They live on the private `payment_proofs` disk
(`storage/app/private/payment-proofs`). If you migrated from an older version:

```bash
php artisan proofs:relocate --dry-run
php artisan proofs:relocate
```

## Database & tests

### `php artisan test` fails to connect

Tests use the connection configured in `phpunit.xml` (by default the same as
`.env`). Make sure the DB exists and credentials are right.

### ⚠️ The tests wiped my development database

Tests run with `RefreshDatabase`, which executes `migrate:fresh` on the
configured database — **it truncates all tables**. Use a dedicated test database:

```env
DB_DATABASE_TEST=ecommerce_test
```

…and point `phpunit.xml` `<env name="DB_DATABASE" value="…"/>` at it, or simply
expect to re-seed afterwards:

```bash
php artisan migrate:fresh --seed
```

### Images in tests fail: "Unable to create image"

This environment has no GD extension. The test suite already uses static
fixture images (`tests/fixtures/*`) instead of `UploadedFile::fake()->image()`,
so tests pass without GD.

## Composer / npm

### `npm run build` fails with an esbuild error

npm ≥ 10 blocks post-install scripts, so esbuild's binary is missing:

```bash
npm rebuild esbuild
npm run build
```

Or reinstall: `rm -rf node_modules && npm ci && npm rebuild esbuild`.

### `composer install` fails on `ext-…`

Install the missing PHP extension, e.g. `sudo apt install php8.2-mysql php8.2-mbstring php8.2-xml php8.2-bcmath`.

## Deployment

### `No application encryption key` error

```bash
php artisan key:generate
```

### `Target class [shop] does not exist`

Run `composer dump-autoload` — the `shop()` helper lives in `app/helpers.php`,
which is registered under `autoload.files`.

### Admin panel redirects me to login every time

Session cookies are being rejected: set `SESSION_DRIVER=database`,
`SESSION_SECURE_COOKIE=true` only over HTTPS, and check that `APP_URL` matches
the URL in the browser (scheme + domain).

## Getting a clean slate

```bash
php artisan optimize:clear
rm -rf storage/framework/views/*
php artisan migrate:fresh --seed
```
