<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A pending order with one line item (3 units x 50.000), created directly
     * in the database so the test does not depend on the checkout flow.
     */
    private function makeOrder(array $attributes = [], int $quantity = 3): Order
    {
        $product = $this->makeProduct(['price' => 50000], [['M', 'Black', 10]]);
        $customer = $this->makeCustomer(false);

        $order = Order::create(array_merge([
            'order_number' => 'ORD-'.strtoupper(uniqid()),
            'user_id' => $customer->id,
            'recipient_name' => 'Test Customer',
            'phone' => '081234567890',
            'shipping_address' => 'Jalan Test No. 1, Bandung, Jawa Barat 40111',
            'subtotal' => 150000,
            'shipping_cost' => 15000,
            'total' => 165000,
            'status' => 'pending',
            'payment_method' => 'bank_transfer',
        ], $attributes));

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'size' => 'M',
            'color' => 'Black',
            'price' => 50000,
            'quantity' => $quantity,
            'subtotal' => 50000 * $quantity,
        ]);

        return $order;
    }

    /**
     * A real order placed through the checkout flow (2 units x 50.000), so its
     * line item records the exact variant id it consumed.
     */
    private function placeOrder(int $quantity = 2): Order
    {
        Storage::fake('payment_proofs');

        $product = $this->makeProduct(['price' => 50000], [['M', 'Black', 10]]);
        $customer = $this->makeCustomer(false);
        $address = $this->makeAddress($customer);

        CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $product->variants->first()->id,
            'quantity' => $quantity,
        ]);

        $this->actingAs($customer);

        $this->post('/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'bank_transfer',
            'payment_proof' => $this->imageFixture(),
        ])->assertRedirect();

        return Order::firstOrFail();
    }

    public function test_a_customer_sees_their_own_orders_only(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($order->user)
            ->get('/pesanan')
            ->assertOk()
            ->assertSee($order->order_number);

        $this->actingAs($order->user)
            ->get("/pesanan/{$order->id}")
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Menunggu Pembayaran');

        // Another customer cannot open the order page.
        $other = $this->makeCustomer(false);
        $this->actingAs($other)->get("/pesanan/{$order->id}")->assertForbidden();
    }

    public function test_an_admin_can_walk_an_order_through_its_statuses(): void
    {
        $this->makeAdmin();
        $order = $this->makeOrder();

        foreach (['paid', 'processing', 'shipped', 'completed'] as $status) {
            $this->patch("/admin/pesanan/{$order->id}/status", ['status' => $status])
                ->assertRedirect();

            $this->assertSame($status, $order->fresh()->status);
        }

        // Setting the same status twice is a friendly no-op, not an error.
        $this->patch("/admin/pesanan/{$order->id}/status", ['status' => 'completed'])
            ->assertSessionHas('success');

        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_an_invalid_status_is_rejected(): void
    {
        $this->makeAdmin();
        $order = $this->makeOrder();

        $this->from(route('admin.orders.show', $order))
            ->patch("/admin/pesanan/{$order->id}/status", ['status' => 'shipped-it'])
            ->assertSessionHasErrors('status');

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_cancelling_an_order_returns_the_stock(): void
    {
        $this->makeAdmin();
        $order = $this->makeOrder([], 3);

        $variant = $order->items()->first()->product->variants->first();
        $this->assertSame(10, $variant->stock);

        $this->patch("/admin/pesanan/{$order->id}/status", ['status' => 'cancelled'])
            ->assertRedirect();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(13, $variant->fresh()->stock);
    }

    public function test_reactivating_a_cancelled_order_claims_the_stock_again(): void
    {
        $this->makeAdmin();
        $order = $this->makeOrder(['status' => 'cancelled'], 3);

        $variant = $order->items()->first()->product->variants->first();

        $this->patch("/admin/pesanan/{$order->id}/status", ['status' => 'paid'])
            ->assertRedirect();

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(7, $variant->fresh()->stock);
    }

    public function test_reactivating_fails_when_the_stock_is_gone(): void
    {
        $this->makeAdmin();
        $order = $this->makeOrder(['status' => 'cancelled'], 3);

        $variant = $order->items()->first()->product->variants->first();
        $variant->update(['stock' => 2]);

        $this->from(route('admin.orders.show', $order))
            ->patch("/admin/pesanan/{$order->id}/status", ['status' => 'paid'])
            ->assertSessionHas('error');

        // The order stays cancelled and the stock is untouched.
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(2, $variant->fresh()->stock);
    }

    public function test_an_order_whose_product_was_deleted_can_still_be_cancelled(): void
    {
        $this->makeAdmin();
        $order = $this->makeOrder();

        $item = $order->items()->firstOrFail();
        $item->product()->delete(); // the denormalised snapshot must survive

        $this->patch("/admin/pesanan/{$order->id}/status", ['status' => 'cancelled'])
            ->assertRedirect();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertNull($item->fresh()->product_id);
        $this->assertSame($item->product_name, $item->fresh()->product_name);
    }

    public function test_a_customer_cannot_change_order_statuses(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($order->user)
            ->patch("/admin/pesanan/{$order->id}/status", ['status' => 'paid'])
            ->assertForbidden();

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_a_checkout_order_records_the_variant_it_consumed_and_cancelling_returns_the_stock(): void
    {
        $order = $this->placeOrder(2);
        $this->makeAdmin();

        $item = $order->items()->firstOrFail();
        $variant = ProductVariant::find($item->product_variant_id);

        $this->assertNotNull($variant);
        $this->assertSame('M', $variant->size);
        $this->assertSame(8, $variant->stock); // 10 claimed at checkout

        $this->patch("/admin/pesanan/{$order->id}/status", ['status' => 'cancelled'])
            ->assertRedirect();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(10, $variant->fresh()->stock);
    }

    public function test_cancelling_and_reactivating_skip_a_variant_that_was_deleted_and_recreated(): void
    {
        $order = $this->placeOrder(2);
        $this->makeAdmin();

        $item = $order->items()->firstOrFail();
        $deletedId = $item->product_variant_id;

        // The admin removes the variant (the cart is empty, so this is allowed)
        // and later creates an identical M / Black variant with a fresh count.
        $product = Product::findOrFail($item->product_id);
        $product->variants()->delete();
        $recreated = $product->variants()->create(['size' => 'M', 'color' => 'Black', 'stock' => 10]);

        $this->patch("/admin/pesanan/{$order->id}/status", ['status' => 'cancelled'])
            ->assertRedirect();

        $this->assertSame('cancelled', $order->fresh()->status);
        // The look-alike row never participated in this order's stock claim, so
        // it must not gain phantom units.
        $this->assertSame(10, $recreated->fresh()->stock);
        // The historical line keeps the identity of the variant it consumed.
        $this->assertSame($deletedId, $order->items()->firstOrFail()->product_variant_id);

        // Re-activating must not silently take units off the new row either.
        $this->patch("/admin/pesanan/{$order->id}/status", ['status' => 'paid'])
            ->assertRedirect();

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(10, $recreated->fresh()->stock);
    }
}
