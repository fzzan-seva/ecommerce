<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $items = $user->cartItems()->with(['product', 'variant'])->get();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Keranjang kosong.');
        }

        $addresses = $user->addresses()->get();
        $subtotal = $items->sum(fn ($item) => $item->subtotal());
        $shipping = 15000;

        return view('shop.checkout', compact('items', 'addresses', 'subtotal', 'shipping'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $items = $user->cartItems()->with(['product', 'variant'])->get();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Keranjang kosong.');
        }

        $validated = $request->validate([
            'address_id' => ['required', 'exists:addresses,id'],
            'payment_method' => ['required', 'in:' . implode(',', array_keys(config('fqueensha.payment_methods')))],
            'payment_proof' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $paymentProof = $request->file('payment_proof') ? $request->file('payment_proof')->store('payment-proofs', 'public') : null;

        $address = $user->addresses()->findOrFail($validated['address_id']);
        $subtotal = $items->sum(fn ($item) => $item->subtotal());
        $shipping = 15000;
        $total = $subtotal + $shipping;

        foreach ($items as $item) {
            if ($item->quantity > $item->variant->stock) {
                return back()->with('error', "Stok {$item->product->name} ({$item->variant->label()}) tidak mencukupi.");
            }
        }

        DB::transaction(function () use ($user, $items, $address, $validated, $paymentProof, $subtotal, $shipping, $total) {
            $order = Order::create([
                'order_number' => 'FQ-' . strtoupper(uniqid()),
                'user_id' => $user->id,
                'address_id' => $address->id,
                'recipient_name' => $address->recipient_name,
                'phone' => $address->phone,
                'shipping_address' => $address->fullAddress(),
                'subtotal' => $subtotal,
                'shipping_cost' => $shipping,
                'total' => $total,
                'status' => 'pending',
                'payment_method' => $validated['payment_method'],
                'payment_proof' => $paymentProof,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'size' => $item->variant->size,
                    'color' => $item->variant->color,
                    'price' => $item->product->price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal(),
                ]);

                $item->variant->decrement('stock', $item->quantity);
            }

            $user->cartItems()->delete();
        });

        $order = $user->orders()->latest()->first();

        return redirect()->route('orders.show', $order)->with('success', 'Pesanan berhasil dibuat! Bukti transfer Anda akan diverifikasi oleh admin sebelum pesanan diproses.');
    }
}
