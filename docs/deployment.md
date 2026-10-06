# Deployment

## Requirements

- PHP 8.1+ with `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`,
  `json`, `bcmath`, `fileinfo` (check with `php -m`)
- MySQL 5.7+ / MariaDB 10.3+
- Composer 2
- Node 18+ and npm (only needed to rebuild front-end assets)
- A web server: Nginx, Apache, or a control panel (cPanel, CyberPanel, …)

## 1. Get the code

```bash
git clone <your-repo-url> shop
cd shop
composer install --no-dev --optimize-autoloader
```

## 2. Environment

```bash
cp .env.example .env
php artisan key:generate
```

Essential `.env` values:

```env
APP_NAME="My Store"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

DB_HOST=127.0.0.1
DB_DATABASE=shop
DB_USERNAME=shop_user
DB_PASSWORD=<strong-password>

SESSION_SECURE_COOKIE=true
```

`APP_DEBUG` **must** be `false` in production, otherwise stack traces (including
credentials) are shown to visitors.

If you serve the shop from a sub-folder, set `ASSET_URL=https://example.com/shop`.

## 3. Database

```bash
php artisan migrate --force
php artisan db:seed --class=DemoSeeder --force   # optional demo data; skip on a real store
```

For the first admin account either run the demo seeder (it prints a random
password once) or create a user yourself and hash the password:

```bash
php artisan tinker
echo \Illuminate\Support\Facades\Hash::make('your-password');
```

Then insert an `is_admin` user, or promote an existing one:

```bash
php artisan tinker
\App\Models\User::where('email', 'you@example.com')->update(['is_admin' => 1]);
```

## 4. Front-end assets

Pre-built CSS ships in `public/css/app.css`, so the shop works without Node.
If you change anything under `resources/`:

```bash
npm ci
npm rebuild esbuild   # needed when npm blocks install scripts
npm run build
```

## 5. Permissions

The web user needs write access to `storage/` and `bootstrap/cache/`:

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

On shared hosting the owner is usually you — then `chmod -R 775 storage bootstrap/cache`
is enough. Never make `storage/app/private` world-readable: payment proofs live there.

Create the symbolic link for public uploads (product images):

```bash
php artisan storage:link
```

## 6. Web server

Point the document root at `public/`. Nginx example:

```nginx
server {
    server_name example.com;
    root /var/www/shop/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

Apache works out of the box thanks to `public/.htaccess`; make sure
`mod_rewrite` is enabled.

## 7. HTTPS

Use Certbot / Let's Encrypt (or your host's panel) and force HTTPS:

```env
APP_URL=https://example.com
SESSION_SECURE_COOKIE=true
```

## 8. Scheduled tasks & queues

There are no mandatory cron jobs. If you later add queued jobs, enable them:

```cron
* * * * * cd /var/www/shop && php artisan queue:work --sleep=3 --tries=3 >> /dev/null 2>&1
```

## 9. Optimization

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Re-run these after every deploy. To wipe them:

```bash
php artisan optimize:clear
```

## 10. Backups

Back up:

- the database (`mysqldump` or your panel's backup tool)
- `storage/app/public` (product images)
- `storage/app/private` (payment proofs)
- `.env`

## Deploy script (typical)

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm rebuild esbuild && npm run build   # only if resources/ changed
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan storage:link
```

## Shared hosting notes

- Everything must be readable from the project root; keep `vendor/` and `.env`
  outside `public/` (they already are).
- If `storage:link` fails (some panels disable symlinks), copy instead:
  `cp -r storage/app/public/* public/storage/` and re-run after each upload.
- If artisan cannot find the DB, make sure `.env` is picked up:
  `php artisan config:clear && php artisan config:cache`.
