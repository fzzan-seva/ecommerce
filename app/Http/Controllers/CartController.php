<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $items = auth()->user()->cartItems()->with(['product', 'variant'])->get();
        $subtotal = $items->sum(fn ($item) => $item->subtotal());

        return view('shop.cart', compact('items', 'subtotal'));
    }

    public function store(Request $request, Product $product)
    {
        abort_unless($product->is_active, 404);

        $validated = $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $variant = ProductVariant::where('id', $validated['product_variant_id'])
            ->where('product_id', $product->id)
            ->firstOrFail();

        if ($variant->stock < 1) {
            return back()->with('error', 'Stok varian ini habis.');
        }

        $maxQty = min($variant->stock, 99);
        if ($validated['quantity'] > $maxQty) {
            return back()->with('error', 'Stok tidak mencukupi. Tersedia: ' . $variant->stock);
        }

        $item = CartItem::firstOrNew([
            'user_id' => auth()->id(),
            'product_variant_id' => $variant->id,
        ]);

        $item->product_id = $product->id;
        $newQty = ($item->exists ? $item->quantity : 0) + $validated['quantity'];

        if ($newQty > $variant->stock) {
            return back()->with('error', 'Stok tidak mencukupi. Tersedia: ' . $variant->stock);
        }

        $item->quantity = $newQty;
        $item->save();

        return back()->with('success', 'Produk ditambahkan ke keranjang.');
    }

    public function update(Request $request, CartItem $cartItem)
    {
        abort_unless($cartItem->user_id === auth()->id(), 403);

        $cartItem->load('variant');
        $maxStock = $cartItem->variant->stock;

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:' . max(1, $maxStock)],
        ]);

        $cartItem->update(['quantity' => $validated['quantity']]);

        return back()->with('success', 'Keranjang diperbarui.');
    }

    public function destroy(CartItem $cartItem)
    {
        abort_unless($cartItem->user_id === auth()->id(), 403);
        $cartItem->delete();

        return back()->with('success', 'Item dihapus dari keranjang.');
    }
}
