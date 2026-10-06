# Payment

This project does **not** use an online payment gateway. Customers pay by manual
transfer (or any offline method you agree on) and upload a photo/screenshot of the
proof. An admin verifies the proof in the back office.

## Flow

1. Customer checks out and picks a payment method (configured in
   `config/shop.php` → `payment_methods`, optionally overridden in
   **Pengaturan → Metode Pembayaran**).
2. The order is created with status `waiting_payment` and stock is deducted.
3. On the order page the customer uploads a payment proof
   (`Bukti Pembayaran`). The file is stored on the private `payment_proofs` disk —
   it is never publicly accessible; only logged-in admins can view/download it.
4. An admin opens **Pesanan**, reviews the proof and moves the order to
   `paid` / `processing`, or rejects it with a note.

## Order statuses

| Status | Meaning | Stock |
|---|---|---|
| `waiting_payment` | Waiting for the customer's proof | reserved |
| `paid`, `processing`, `shipped` | Confirmed | reserved |
| `completed` | Finished | deducted (one-time) |
| `cancelled` | Cancelled | released |

Stock handling rules:

- Stock is deducted when the order is placed.
- When an order becomes `cancelled`, its reserved/completed quantities are released
  back and `completed_at` is cleared, so re-activating the order does not double-deduct.
- Reverting `completed` → `shipped` also restores the stock that was deducted on completion.
- Stock is never released twice: `completed_at` guards the transition.
- Payment methods are validated against the configured list; arbitrary values are rejected.

## Configuring methods

```env
SHOP_PAYMENT_METHODS=bank_transfer,cod
```

Or edit them in the admin panel (**Pengaturan → Metode Pembayaran**). Labels shown
to customers come from `SHOP_PAYMENT_LABELS` (or the settings page), e.g.
`"Transfer BCA,Transfer Mandiri,COD"`.

The destination account details shown to customers are store settings
(**Pengaturan → Rekening & Pengiriman**), stored in the `settings` table —
no account numbers live in the source code.

## Storage location

Proofs live under `storage/app/private/payment-proofs/` (disk `payment_proofs`).
They are excluded from version control and backups of the public disk are not
needed for them — include `storage/app/private` in your backups.

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
