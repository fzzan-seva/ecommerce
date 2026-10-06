<?php

namespace App\Http\Controllers;

use App\Exceptions\StockUnavailableException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

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
        // Single source of truth for shipping: the cart summary, this
        // calculation and the stored order all read config/shop.php.
        $shipping = shop()->shippingCost();

        return view('shop.checkout', compact('items', 'addresses', 'subtotal', 'shipping'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $items = $user->cartItems()->with(['product', 'variant'])->get();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Keranjang kosong.');
        }

        // A product may have been deactivated after it was put in the cart.
        $unavailable = $items->first(fn ($item) => ! $item->product || ! $item->product->is_active);

        if ($unavailable !== null) {
            return back()->with('error', "Produk {$unavailable->product->name} tidak lagi tersedia dan harus dihapus dari keranjang.");
        }

        $enabledMethods = array_keys(shop()->enabledPaymentMethods());

        $validated = $request->validate([
            // Scoped with Rule::exists so another user's address ID is rejected as
            // "invalid" rather than passing validation and then 404ing, which
            // would let an attacker probe which address IDs exist.
            'address_id' => [
                'required',
                Rule::exists('addresses', 'id')->where('user_id', $user->id),
            ],
            'payment_method' => [
                'required',
                Rule::in($enabledMethods),
            ],
            'payment_proof' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $address = $user->addresses()->findOrFail($validated['address_id']);

        // Only store the upload once validation has passed, so a rejected
        // request never leaves a stray file on disk. It goes on the private
        // disk, outside the public/storage symlink, so the receipt is not
        // world-readable by URL.
        $paymentProof = $request->file('payment_proof')->store('payment-proofs', 'payment_proofs');

        // Totals are computed server-side from the database rows; nothing the
        // browser sends (price, subtotal, shipping, total) is ever trusted.
        $subtotal = $items->sum(fn ($item) => $item->subtotal());
        $shipping = shop()->shippingCost();
        $total = $subtotal + $shipping;

        try {
            $order = DB::transaction(function () use ($user, $items, $address, $validated, $paymentProof, $subtotal, $shipping, $total) {
                $order = Order::create([
                    'order_number' => shop()->orderPrefix().'-'.strtoupper(uniqid()),
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
                    // Claim the stock atomically: the WHERE guard makes the check
                    // and the decrement a single statement, so two shoppers racing
                    // for the last item cannot both win. Previously the check ran
                    // outside the transaction and decrement() was unguarded, which
                    // let concurrent checkouts oversell and blow up with a 500.
                    $claimed = ProductVariant::whereKey($item->product_variant_id)
                        ->where('stock', '>=', $item->quantity)
                        ->decrement('stock', $item->quantity);

                    if ($claimed === 0) {
                        throw new StockUnavailableException(
                            "Stok {$item->product->name} ({$item->variant->label()}) tidak mencukupi."
                        );
                    }

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        // Exact variant identity: stock is released back to this
                        // very row on cancel, never to a later look-alike variant.
                        'product_variant_id' => $item->product_variant_id,
                        'product_name' => $item->product->name,
                        'size' => $item->variant->size,
                        'color' => $item->variant->color,
                        'price' => $item->product->price,
                        'quantity' => $item->quantity,
                        'subtotal' => $item->subtotal(),
                    ]);
                }

                $user->cartItems()->delete();

                return $order;
            });
        } catch (StockUnavailableException $e) {
            // The order was rolled back, so drop the now-orphaned upload.
            Storage::disk('payment_proofs')->delete($paymentProof);

            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            // Any other failure also rolls the transaction back — do not leave
            // the receipt behind as an orphaned private file.
            Storage::disk('payment_proofs')->delete($paymentProof);

            throw $e;
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Pesanan berhasil dibuat! Bukti transfer Anda akan diverifikasi oleh admin sebelum pesanan diproses.');
    }
}
