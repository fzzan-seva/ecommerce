# Payment

This project does **not** use an online payment gateway. Customers pay by manual
transfer (or any offline method you agree on) and upload a photo/screenshot of the
proof with their order. An admin verifies the proof in the back office.

## Flow

1. Customer checks out and picks a payment method (defaults from
   `config/shop.php` → `payment_methods`, overridden at runtime from
   **Pengaturan → Metode Pembayaran**).
2. The receipt upload is part of the checkout form (required,
   JPEG/PNG/WebP up to 2 MB). The file goes straight to the private
   `payment_proofs` disk — it is never publicly accessible; only the owning
   customer and admins can view it.
3. The order is created with status `pending` and stock is claimed atomically.
4. An admin opens **Pesanan**, reviews the receipt and moves the order to
   `paid`, `processing`, and so on.

The receipt is uploaded **once, with the order** — there is no self-service
re-upload endpoint. If an upload is unusable, the admin contacts the customer
out of band and records the result by changing the status.

## Order statuses

| Status | Meaning | Stock |
|---|---|---|
| `pending` | Order placed, receipt awaiting verification | claimed |
| `paid`, `processing`, `shipped` | Confirmed / in progress | claimed |
| `completed` | Finished | claimed |
| `cancelled` | Cancelled | released back to the variants |

Stock handling rules:

- Stock is claimed once, atomically, when the order is placed.
- Moving an order **to** `cancelled` (from any other status) releases its
  quantities back to the exact variant rows the order consumed.
- Moving an order **out of** `cancelled` claims those quantities again, and the
  panel refuses with a clear message if stock has run out in the meantime — the
  order simply stays cancelled.
- Transitions never repeat: setting the same status twice is a no-op, so stock
  is released or claimed exactly once per transition.
- Payment methods are validated against the configured, enabled list; arbitrary
  values posted to the checkout are rejected.

## Configuring methods

Payment methods are **not** environment variables — account numbers do not
belong in `.env` or in a git repository. Manage them from
**Pengaturan → Metode Pembayaran** (stored in the `settings` table), or adjust
the placeholders in `config/shop.php` for a fresh install.

Each method has a key (`^[a-z0-9_]+$`, max 20 characters), label, type,
account number, account name and an `enabled` toggle. Disabled methods are
hidden from checkout **and** rejected by validation.

The destination account details shown to customers therefore come from the
database settings — no account numbers live in the source code.

## Storage location

Proofs live under `storage/app/private/payment-proofs/` (disk `payment_proofs`).
They are excluded from version control and are not part of the public
`storage` symlink — include `storage/app/private` in your backups.

If you have receipts from an older version that were stored on the public disk,
migrate them with:

```bash
php artisan proofs:relocate            # move files
php artisan proofs:relocate --dry-run  # preview only
```

## Adding a real gateway later

Keep the manual flow as a fallback and add a gateway behind the same order
statuses. Nothing in the checkout code assumes manual payment — you only need
a new payment provider, a webhook/controller to confirm payment, and to flip
the order to `paid`.
