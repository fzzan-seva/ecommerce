<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'address_id',
        'recipient_name',
        'phone',
        'shipping_address',
        'subtotal',
        'shipping_cost',
        'total',
        'status',
        'payment_method',
        'payment_proof',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Pembayaran',
            'paid' => 'Dibayar',
            'processing' => 'Diproses',
            'shipped' => 'Dikirim',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => $this->status,
        };
    }

    public function formattedTotal(): string
    {
        return price($this->total);
    }

    public function paymentMethodLabel(): string
    {
        $method = shop()->paymentMethod($this->payment_method);

        if ($method !== null) {
            return $method['label'];
        }

        // The method may have been renamed or disabled since the order was
        // placed — keep old orders readable.
        return ucfirst(str_replace('_', ' ', (string) $this->payment_method));
    }

    public function paymentAccount(): ?string
    {
        $method = shop()->paymentMethod($this->payment_method);

        return $method !== null && $method['account'] !== '' ? $method['account'] : null;
    }

    public function paymentAccountName(): ?string
    {
        $method = shop()->paymentMethod($this->payment_method);

        return $method !== null && $method['account_name'] !== '' ? $method['account_name'] : null;
    }

    public function paymentProofUrl(): ?string
    {
        // Routed through an authorised controller instead of the public
        // storage symlink, so receipts are not world-readable.
        return $this->payment_proof ? route('payment-proofs.show', $this) : null;
    }
}
