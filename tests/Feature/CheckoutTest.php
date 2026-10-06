<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Customer + address + one cart line (2 units x 120.000), ready to check out.
     *
     * @return array{0: \App\Models\Product, 1: \App\Models\User, 2: \App\Models\Address}
     */
    private function prepareCheckout(array $variant = ['M', 'Black', 10]): array
    {
        [$size, $color, $stock] = $variant;

        $product = $this->makeProduct(['price' => 120000], [[$size, $color, $stock]]);
        $customer = $this->makeCustomer(false);
        $address = $this->makeAddress($customer);

        CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $product->variants->first()->id,
            'quantity' => 2,
        ]);

        $this->actingAs($customer);

        return [$product, $customer, $address];
    }

    public function test_checkout_requires_a_non_empty_cart(): void
    {
        $this->makeCustomer();

        $this->get('/checkout')->assertRedirect(route('cart.index'));
        $this->post('/checkout', [])->assertRedirect(route('cart.index'));
    }

    public function test_the_checkout_page_shows_subtotal_and_shipping(): void
    {
        $this->prepareCheckout();

        $this->get('/checkout')
            ->assertOk()
            ->assertSee('Rp 240,000')   // subtotal: 2 x 120.000
            ->assertSee('Rp 15,000');   // flat shipping from config/shop.php
    }

    public function test_a_customer_can_place_an_order_with_a_payment_proof(): void
    {
        Storage::fake('payment_proofs');
        [$product, $customer, $address] = $this->prepareCheckout();

        $response = $this->post('/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'bank_transfer',
            'payment_proof' => $this->imageFixture('bukti.png'),
            'notes' => 'Tolong cepat ya',
        ]);

        $order = Order::firstOrFail();

        $response->assertRedirect(route('orders.show', $order));
        $response->assertSessionHas('success');

        $this->assertSame('pending', $order->status);
        $this->assertSame('bank_transfer', $order->payment_method);
        $this->assertSame('Tolong cepat ya', $order->notes);
        $this->assertSame($customer->id, $order->user_id);
        $this->assertSame($address->id, $order->address_id);
        $this->assertSame('Test Customer', $order->recipient_name);

        // Totals are computed server-side: 2 x 120.000 + 15.000 ongkir.
        $this->assertSame('240000.00', (string) $order->subtotal);
        $this->assertSame('15000.00', (string) $order->shipping_cost);
        $this->assertSame('255000.00', (string) $order->total);

        // The order number carries the configured prefix.
        $this->assertStringStartsWith('ORD-', $order->order_number);

        // Stock was claimed and the cart emptied. (Read via a fresh query:
        // the in-memory relation was loaded before the checkout ran.)
        $this->assertSame(8, $product->fresh()->variants->first()->stock);
        $this->assertDatabaseCount('cart_items', 0);

        // Order items snapshot the product details.
        $item = $order->items()->firstOrFail();
        $this->assertSame($product->name, $item->product_name);
        $this->assertSame('M', $item->size);
        $this->assertSame('Black', $item->color);
        $this->assertSame('120000.00', (string) $item->price);
        $this->assertSame(2, $item->quantity);
        $this->assertSame('240000.00', (string) $item->subtotal);

        // The receipt lives on the private disk, under the proofs directory.
        $this->assertNotNull($order->payment_proof);
        $this->assertStringStartsWith('payment-proofs/', $order->payment_proof);
        Storage::disk('payment_proofs')->assertExists($order->payment_proof);

        // The order page renders for its owner.
        $this->get("/pesanan/{$order->id}")->assertOk()->assertSee($order->order_number);
    }

    public function test_disabled_or_unknown_payment_methods_are_rejected(): void
    {
        Storage::fake('payment_proofs');
        config(['shop.payment_methods' => [
            'bank_transfer' => ['label' => 'Bank Transfer', 'type' => 'bank', 'account' => '1111111111', 'account_name' => 'Store Owner', 'enabled' => true],
            'e_wallet' => ['label' => 'E-Wallet', 'type' => 'e-wallet', 'account' => '222222222222', 'account_name' => 'Store Owner', 'enabled' => false],
        ]]);

        [, , $address] = $this->prepareCheckout();

        $this->from('/checkout')->post('/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'e_wallet',
            'payment_proof' => $this->imageFixture(),
        ])->assertSessionHasErrors('payment_method');

        $this->from('/checkout')->post('/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'cod',
            'payment_proof' => $this->imageFixture(),
        ])->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_another_users_address_cannot_be_used(): void
    {
        Storage::fake('payment_proofs');

        $stranger = $this->makeCustomer(false);
        $strangerAddress = $this->makeAddress($stranger);

        [, , $address] = $this->prepareCheckout();

        $this->from('/checkout')->post('/checkout', [
            'address_id' => $strangerAddress->id,
            'payment_method' => 'bank_transfer',
            'payment_proof' => $this->imageFixture(),
        ])->assertSessionHasErrors('address_id');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_payment_proof_is_required(): void
    {
        [, , $address] = $this->prepareCheckout();

        $this->from('/checkout')->post('/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'bank_transfer',
        ])->assertSessionHasErrors('payment_proof');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_an_inactive_product_blocks_checkout_and_leaves_no_order(): void
    {
        Storage::fake('payment_proofs');
        [$product, , $address] = $this->prepareCheckout();

        $product->update(['is_active' => false]);

        $this->from('/checkout')->post('/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'bank_transfer',
            'payment_proof' => $this->imageFixture(),
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame([], Storage::disk('payment_proofs')->allFiles());
    }

    public function test_insufficient_stock_rolls_the_order_back_and_drops_the_upload(): void
    {
        Storage::fake('payment_proofs');

        // The cart holds 2 units, but stock drops to 1 after the cart was filled.
        [$product, , $address] = $this->prepareCheckout();
        $product->variants->first()->update(['stock' => 1]);

        $this->from('/checkout')->post('/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'bank_transfer',
            'payment_proof' => $this->imageFixture(),
        ])->assertSessionHas('error');

        // Nothing may survive the rolled-back transaction.
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame(1, $product->variants->first()->fresh()->stock);
        $this->assertDatabaseHas('cart_items', ['quantity' => 2]);
        $this->assertSame([], Storage::disk('payment_proofs')->allFiles());
    }

    public function test_shipping_cost_comes_from_store_settings(): void
    {
        Storage::fake('payment_proofs');

        // A value saved from Admin > Store Settings overrides config/shop.php.
        Setting::set('shipping_cost', '25000');
        shop()->refresh();

        [, , $address] = $this->prepareCheckout();

        $this->post('/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'bank_transfer',
            'payment_proof' => $this->imageFixture(),
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('25000.00', (string) $order->shipping_cost);
        $this->assertSame('265000.00', (string) $order->total);
    }
}
