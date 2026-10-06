# Admin Guide

The admin panel lives at `/admin` and is restricted to accounts with the
`admin` role. Everything below is reachable from the sidebar.

## Creating the first admin

A fresh install without seeding has no admin account. Create one with tinker:

```bash
php artisan tinker
>>> $u = new \App\Models\User(['name' => 'Owner', 'email' => 'me@example.com', 'password' => Illuminate\Support\Facades\Hash::make('a-strong-password')]);
>>> $u->role = 'admin'; $u->save();
```

`role` is deliberately **not** mass-assignable — it must be set directly (or
via the seeder), so a crafted registration request can never escalate itself.

## Dashboard (`/admin`)

- **Stat cards** — total products, customers, orders, pending orders, revenue
  (revenue counts `paid`, `processing`, `shipped` and `completed` orders).
- **Pesanan Terbaru** — last eight orders, click through to manage them.
- **Stok Menipis** — variants with fewer than 5 units; restock or deactivate.

## Produk (Products)

- **Create** — name, category, description, price, image (JPEG/PNG/WEBP up to
  2 MB) and at least one variant (size + colour + stock). Slugs are generated
  automatically and are collision-free (`summer-dress`, `summer-dress-2`, …).
- **Edit** — the slug **never changes** on update, so shared product links keep
  working. Variants are synced by (size, colour):
  - unchanged variants keep their database ID (carts referencing them stay intact);
  - only the stock number is updated for matched variants;
  - a variant that is still in somebody's cart **cannot be removed** — the panel
    asks you to let the shopper remove it first, or to keep the variant.
- **Activation toggle** — inactive products disappear from the storefront,
  search, category pages and cannot be added to carts; existing orders are
  unaffected.
- **Image replacement** — the new file is stored first; the old file is deleted
  only after the save succeeds, so a failed upload never loses the old picture.
- **Delete** — historical order items keep their denormalised snapshot
  (name, size, colour, price); the product link on those rows becomes NULL and
  past orders remain readable.

## Kategori (Categories)

- Names must be unique; slugs are generated and de-duplicated automatically.
- **Safe deletion** — a category that still has products cannot be deleted; the
  panel explains how many products are using it. Move those products to another
  category (or remove their category) first.
- Deleting an empty category is immediate and safe.

## Pesanan (Orders)

Status flow:

```
pending → paid → processing → shipped → completed
   ↘_______________ cancelled ______________↗
```

- **pending** — order placed; the customer has uploaded a receipt that still
  needs verification.
- **paid** — receipt verified (or payment confirmed offline).
- **cancelled** — **stock is returned** to the variants automatically.
- Re-activating a cancelled order (`cancelled → paid`) **claims the stock
  again**; if stock has run out in the meantime the panel refuses with a clear
  message and the order stays cancelled.
- Setting the same status twice is a harmless no-op.
- Invalid statuses are rejected; only the six statuses above exist.
- Orders whose product was deleted can still be cancelled — the stock lookup
  simply skips the missing product.

## Pengguna (Customers)

Read-only directory: name, role, e-mail, phone, registered address book and
order history. Passwords are never displayed; reset a password with tinker if
a customer loses access (see [installation.md](installation.md#4-database)).

## Pengaturan Toko (Store settings)

Four sections — store identity, commerce (currency/shipping), social links and
payment methods. See [configuration.md](configuration.md) for every field and
how database values override `.env`. Highlights:

- Payment method accounts are edited here, never in `.env`.
- Methods toggled off disappear from checkout immediately and are rejected by
  validation even if posted manually.
- Logo upload replaces the old file; **remove logo** deletes it.
- Changes apply to the next request — no cache flush or deploy needed.

## Day-to-day tips

- Update stock from the product edit page (per variant), or reactivate a
  cancelled order to return units automatically.
- Use the dashboard's low-stock list as a restock checklist.
- After changing payment accounts, place a test order as the demo customer to
  confirm the new details show at checkout.
