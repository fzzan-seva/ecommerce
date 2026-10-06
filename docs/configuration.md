# Configuration

Store behaviour is configured in two layers. For **every** key the resolution
order is:

1. **Database** — values saved from **Admin → Pengaturan Toko** (win)
2. **`config/shop.php`** — fed by the `SHOP_*` variables in `.env`

So `.env` provides the defaults for a fresh install, and the admin panel
overrides them at runtime without redeploying. In code, always read values
through the helpers instead of touching config or the database directly:

```php
shop()->name();          // store name
shop()->shippingCost();  // flat shipping rate
price(150000);           // "Rp 150,000" — the one price formatter
```

Both helpers are defined in `app/helpers.php` and resolve through the
`App\Support\Shop` singleton (`app/Support/Shop.php`).

## Environment variables (`.env`)

### Application

| Variable | Default | Notes |
|---|---|---|
| `APP_NAME` | `Laravel E-Commerce` | Fallback store name when `SHOP_NAME` is empty |
| `APP_URL` | `http://localhost` | Used to build absolute URLs — set to your real domain |
| `APP_DEBUG` | `true` locally | **Must be `false` in production** |
| `APP_ENV` | `local` | `production` in production |

### Store identity

| Variable | Default | Notes |
|---|---|---|
| `SHOP_NAME` | `Laravel E-Commerce` | Store name in titles, header, footer, e-mails |
| `SHOP_TAGLINE` | `Your online store` | Shown in the footer |
| `SHOP_DESCRIPTION` | `A modern online store powered by Laravel` | Meta description |
| `SHOP_LOGO` | *(empty)* | Path on the public disk; empty falls back to text branding |
| `SHOP_PHONE` | *(empty)* | Empty hides the contact block |
| `SHOP_EMAIL` | *(empty)* | Empty hides it |
| `SHOP_ADDRESS` | *(empty)* | Empty hides it |

### Social links

Empty values hide the link entirely.

| Variable | Format | Notes |
|---|---|---|
| `SHOP_WHATSAPP` | Digits only, international — e.g. `6281234567890` | Builds the `https://wa.me/<digits>` link |
| `SHOP_INSTAGRAM` | Handle or full URL — e.g. `mystore` | Rendered as `instagram.com/mystore` unless it starts with `http` |
| `SHOP_FACEBOOK` | Handle or full URL | Same rule as Instagram |

### Commerce

| Variable | Default | Notes |
|---|---|---|
| `SHOP_CURRENCY` | `IDR` | Currency code |
| `SHOP_CURRENCY_SYMBOL` | `Rp` | Symbol used by `price()` |
| `SHOP_CURRENCY_DECIMALS` | `0` | `0` → `Rp 150,000`; `2` → `Rp 150,000.00` |
| `SHOP_SHIPPING_COST` | `15000` | **Flat rate** used by the cart summary, the checkout calculation and the value stored on every order — the three always agree |

### Orders

| Variable | Default | Notes |
|---|---|---|
| `SHOP_ORDER_PREFIX` | `ORD` | Every order number looks like `ORD-651F2A9C0B3D4` |

### Payment methods

Payment methods are **not** defined in `.env` — manage them from
**Admin → Pengaturan Toko** so real account numbers never end up in a git
repository. The defaults in `config/shop.php` are placeholders
(`0000000000` / "Store Owner"). Each method supports:

```php
'method_key' => [
    'label'        => 'Bank Transfer',   // shown at checkout
    'type'         => 'bank',            // bank | e-wallet | anything you like
    'account'      => '0000000000',      // account number / phone number
    'account_name' => 'Store Owner',     // "a.n. ..." shown next to the account
    'enabled'      => true,              // disabled methods are hidden and rejected
],
```

Method keys must match `^[a-z0-9_]+$` and are stored in the orders table
(`VARCHAR(20)`), so keep them short and stable — renaming a key makes old
orders fall back to a plain-text label, which is handled gracefully.

## Admin panel: Pengaturan Toko

The settings page (`/admin/pengaturan`) edits the same keys in four sections:

1. **Identitas Toko** — name, tagline, description, phone, email, address, logo
2. **Komisi & Pengiriman** — currency, symbol, decimals, shipping cost
3. **Media Sosial** — WhatsApp, Instagram, Facebook
4. **Metode Pembayaran** — add/remove/edit transfer accounts, toggle `enabled`

Notes:

- Saving clears the per-request cache, so changes apply to the very next request.
- The logo upload replaces the previous file; **remove_logo** deletes it.
- Shipping cost changes affect only **future** carts and orders — existing
  orders keep the `shipping_cost` that was stored at checkout time.

## Currency formatting

Every price in the application goes through `price()`:

```php
price(150000);                       // "Rp 150,000"
// with SHOP_CURRENCY_SYMBOL="$", SHOP_CURRENCY_DECIMALS=2:
price(150000);                       // "$ 150,000.00"
```

Prices are stored as `DECIMAL(10,2)` in the database and formatted only for
display.

## Changing configuration safely

- `.env` changes require the config cache to be refreshed:
  `php artisan config:clear` (or `php artisan optimize` in production).
- Admin-panel changes need no artisan command at all.
