<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\StockUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('user')->latest()->paginate(20);

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'items.product']);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,paid,processing,shipped,completed,cancelled'],
        ]);

        $to = $validated['status'];
        $from = $order->status;

        if ($to === $from) {
            return back()->with('success', 'Status pesanan tidak berubah.');
        }

        $restoring = $to === 'cancelled' && $from !== 'cancelled';
        $reclaiming = $from === 'cancelled' && $to !== 'cancelled';

        try {
            DB::transaction(function () use ($order, $to, $restoring, $reclaiming) {
                $order->loadMissing('items');

                if ($reclaiming) {
                    // Re-activating a cancelled order puts its units back on the
                    // shelf, so the stock has to be claimed again — otherwise the
                    // shop can oversell the same garment.
                    foreach ($order->items as $item) {
                        $variantId = $this->resolveVariantId($item);

                        if ($variantId === null) {
                            continue;
                        }

                        $claimed = ProductVariant::whereKey($variantId)
                            ->where('stock', '>=', $item->quantity)
                            ->decrement('stock', $item->quantity);

                        if ($claimed === 0) {
                            throw new StockUnavailableException(
                                "Stok {$item->product_name} ({$item->variantLabel()}) tidak cukup untuk mengaktifkan pesanan ini."
                            );
                        }
                    }
                }

                $order->update(['status' => $to]);

                // Stock is only ever deducted at checkout, so it has to come back
                // on cancel. Without this, every cancelled order silently lost
                // inventory for good.
                if ($restoring) {
                    foreach ($order->items as $item) {
                        $variantId = $this->resolveVariantId($item);

                        if ($variantId !== null) {
                            ProductVariant::whereKey($variantId)->increment('stock', $item->quantity);
                        }
                    }
                }
            });
        } catch (StockUnavailableException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Status pesanan diperbarui.');
    }

    /**
     * Resolve the variant row an order line's stock belongs to.
     *
     * New orders carry the variant id recorded at checkout, which is decisive:
     * it keeps cancel/reactivate bookkeeping on the exact row the order
     * consumed, even if an identical (size, colour) variant was deleted and
     * re-created since. If that row no longer exists the line is skipped —
     * there is nothing to return the units to.
     *
     * Older rows (placed before this column existed) fall back to matching the
     * denormalised (product_id, size, colour) snapshot.
     */
    private function resolveVariantId($item): ?int
    {
        if ($item->product_variant_id !== null) {
            return ProductVariant::whereKey($item->product_variant_id)->value('id');
        }

        if (! $item->product_id) {
            return null;
        }

        return ProductVariant::where('product_id', $item->product_id)
            ->where('size', $item->size)
            ->where('color', $item->color)
            ->value('id');
    }
}
