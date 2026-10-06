<?php

namespace Tests\Feature;

use App\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_add_a_variant_to_the_cart(): void
    {
        $product = $this->makeProduct([], [['M', 'Black', 10]]);
        $this->makeCustomer();

        $this->post('/keranjang/'.$product->slug, [
            'product_variant_id' => $product->variants->first()->id,
            'quantity' => 2,
        ])->assertRedirect();

        $this->assertDatabaseHas('cart_items', [
            'user_id' => auth()->id(),
            'product_id' => $product->id,
            'product_variant_id' => $product->variants->first()->id,
            'quantity' => 2,
        ]);
    }

    public function test_adding_the_same_variant_twice_merges_the_quantities(): void
    {
        $product = $this->makeProduct([], [['M', 'Black', 10]]);
        $this->makeCustomer();

        $this->post('/keranjang/'.$product->slug, [
            'product_variant_id' => $product->variants->first()->id,
            'quantity' => 2,
        ])->assertRedirect();

        $this->post('/keranjang/'.$product->slug, [
            'product_variant_id' => $product->variants->first()->id,
            'quantity' => 3,
        ])->assertRedirect();

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', ['quantity' => 5]);
    }

    public function test_quantity_cannot_exceed_the_available_stock(): void
    {
        $product = $this->makeProduct([], [['M', 'Black', 3]]);
        $variantId = $product->variants->first()->id;
        $this->makeCustomer();

        $this->from('/produk/'.$product->slug)
            ->post('/keranjang/'.$product->slug, [
                'product_variant_id' => $variantId,
                'quantity' => 5,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('cart_items', 0);

        // Adding up to the limit works; one more must fail again.
        $this->post('/keranjang/'.$product->slug, [
            'product_variant_id' => $variantId,
            'quantity' => 3,
        ])->assertRedirect();

        $this->from('/produk/'.$product->slug)
            ->post('/keranjang/'.$product->slug, [
                'product_variant_id' => $variantId,
                'quantity' => 1,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('cart_items', ['quantity' => 3]);
    }

    public function test_a_variant_from_another_product_is_rejected(): void
    {
        $productA = $this->makeProduct([], [['M', 'Black', 5]]);
        $productB = $this->makeProduct([], [['M', 'Black', 5]]);
        $this->makeCustomer();

        $this->post('/keranjang/'.$productA->slug, [
            'product_variant_id' => $productB->variants->first()->id,
            'quantity' => 1,
        ])->assertNotFound();

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_out_of_stock_variants_cannot_be_added(): void
    {
        $product = $this->makeProduct([], [['M', 'Black', 0]]);
        $this->makeCustomer();

        $this->from(route('products.show', $product))
            ->post('/keranjang/'.$product->slug, [
                'product_variant_id' => $product->variants->first()->id,
                'quantity' => 1,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_the_cart_page_shows_items_and_totals(): void
    {
        $product = $this->makeProduct(['name' => 'Cart Product', 'price' => 50000], [['M', 'Black', 5]]);
        $customer = $this->makeCustomer(false);

        CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $product->variants->first()->id,
            'quantity' => 2,
        ]);

        $this->actingAs($customer);

        // Subtotal 100.000 + flat shipping 15.000 (config/shop.php default).
        $this->get('/keranjang')
            ->assertOk()
            ->assertSee('Cart Product')
            ->assertSee('Rp 100,000')
            ->assertSee('Rp 15,000');
    }

    public function test_a_customer_can_update_and_remove_their_own_cart_items(): void
    {
        $product = $this->makeProduct([], [['M', 'Black', 10]]);
        $customer = $this->makeCustomer(false);

        $item = CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $product->variants->first()->id,
            'quantity' => 1,
        ]);

        $this->actingAs($customer);

        $this->patch("/keranjang/{$item->id}", ['quantity' => 4])->assertRedirect();
        $this->assertSame(4, $item->fresh()->quantity);

        $this->delete("/keranjang/{$item->id}")->assertRedirect();
        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_updating_quantity_beyond_stock_or_below_one_is_rejected(): void
    {
        $product = $this->makeProduct([], [['M', 'Black', 10]]);
        $customer = $this->makeCustomer(false);

        $item = CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $product->variants->first()->id,
            'quantity' => 1,
        ]);

        $this->actingAs($customer);

        $this->from('/keranjang')
            ->patch("/keranjang/{$item->id}", ['quantity' => 50])
            ->assertSessionHasErrors('quantity');

        $this->from('/keranjang')
            ->patch("/keranjang/{$item->id}", ['quantity' => 0])
            ->assertSessionHasErrors('quantity');

        $this->from('/keranjang')
            ->patch("/keranjang/{$item->id}", ['quantity' => 'abc'])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(1, $item->fresh()->quantity);
    }

    public function test_a_customer_cannot_touch_another_customers_cart_item(): void
    {
        $product = $this->makeProduct([], [['M', 'Black', 10]]);
        $owner = $this->makeCustomer(false);
        $other = $this->makeCustomer(false);

        $item = CartItem::create([
            'user_id' => $owner->id,
            'product_id' => $product->id,
            'product_variant_id' => $product->variants->first()->id,
            'quantity' => 1,
        ]);

        $this->actingAs($other)
            ->patch("/keranjang/{$item->id}", ['quantity' => 5])
            ->assertForbidden();

        $this->actingAs($other)
            ->delete("/keranjang/{$item->id}")
            ->assertForbidden();

        $this->assertSame(1, $item->fresh()->quantity);
    }
}
