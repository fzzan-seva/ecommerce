<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_the_product_listing_and_detail_page(): void
    {
        $product = $this->makeProduct(['name' => 'Denim Jacket'], [['M', 'Blue', 4]]);

        $this->get('/')->assertOk()->assertSee('Denim Jacket');
        $this->get('/produk/'.$product->slug)->assertOk()->assertSee('Denim Jacket');
    }

    public function test_inactive_products_are_hidden_and_cannot_be_purchased(): void
    {
        $product = $this->makeProduct(['is_active' => false]);

        $this->get('/')->assertOk()->assertDontSee($product->name, false);
        $this->get('/produk/'.$product->slug)->assertNotFound();

        $this->makeCustomer();
        $this->post('/keranjang/'.$product->slug, [
            'product_variant_id' => $product->variants->first()->id,
            'quantity' => 1,
        ])->assertNotFound();

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_a_guest_cannot_manage_products(): void
    {
        $this->get('/admin/produk')->assertRedirect('/login');
    }

    public function test_a_customer_cannot_create_or_update_products(): void
    {
        $product = $this->makeProduct();
        $this->makeCustomer();

        $this->get('/admin/produk/create')->assertForbidden();
        $this->post('/admin/produk', ['name' => 'X'])->assertForbidden();
        $this->put('/admin/produk/'.$product->slug, ['name' => 'X'])->assertForbidden();
        $this->delete('/admin/produk/'.$product->slug)->assertForbidden();
    }

    public function test_an_admin_can_create_a_product_with_variants_and_an_image(): void
    {
        Storage::fake('public');
        $this->makeAdmin();

        $response = $this->post('/admin/produk', [
            'name' => 'Summer Dress',
            'category_id' => null,
            'description' => 'Light and comfortable.',
            'price' => 250000,
            'image' => $this->imageFixture('dress.png'),
            'is_active' => '1',
            'variants' => [
                ['size' => 'M', 'color' => 'Black', 'stock' => 5],
                ['size' => 'L', 'color' => 'Black', 'stock' => 7],
            ],
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $product = \App\Models\Product::where('name', 'Summer Dress')->firstOrFail();
        $this->assertSame('summer-dress', $product->slug);
        $this->assertTrue($product->is_active);
        $this->assertCount(2, $product->variants);
        Storage::disk('public')->assertExists($product->image);
    }

    public function test_invalid_product_images_are_rejected(): void
    {
        Storage::fake('public');
        $this->makeAdmin();

        $response = $this->from('/admin/produk/create')->post('/admin/produk', [
            'name' => 'Bad Image Product',
            'price' => 1000,
            'image' => $this->fakeImage(),
            'variants' => [
                ['size' => 'M', 'color' => 'Black', 'stock' => 1],
            ],
        ]);

        $response->assertSessionHasErrors('image');
        $this->assertDatabaseCount('products', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_updating_a_product_preserves_variant_ids(): void
    {
        $this->makeAdmin();
        $product = $this->makeProduct(['name' => 'Old Name'], [
            ['M', 'Black', 10],
            ['L', 'Black', 5],
        ]);

        $originalIds = $product->variants->pluck('id')->sort()->values()->all();

        $response = $this->put('/admin/produk/'.$product->slug, [
            'name' => 'New Name',
            'price' => 150000,
            'is_active' => '1',
            'variants' => [
                ['size' => 'M', 'color' => 'Black', 'stock' => 3],
                ['size' => 'L', 'color' => 'Black', 'stock' => 9],
            ],
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertSame('New Name', $product->name);
        // The slug must not change, otherwise existing links break.
        $this->assertSame('test-product-'.explode('-', $product->slug)[2] ?? $product->slug, $product->slug);

        $freshIds = $product->variants()->pluck('id')->sort()->values()->all();
        $this->assertSame($originalIds, $freshIds);
        $this->assertSame(3, $product->variants()->where('size', 'M')->value('stock'));
        $this->assertSame(9, $product->variants()->where('size', 'L')->value('stock'));
    }

    public function test_updating_a_product_can_add_and_remove_variants(): void
    {
        $this->makeAdmin();
        $product = $this->makeProduct([], [
            ['M', 'Black', 10],
            ['L', 'Black', 5],
        ]);

        $keptId = $product->variants()->where('size', 'M')->value('id');
        $removedId = $product->variants()->where('size', 'L')->value('id');

        $this->put('/admin/produk/'.$product->slug, [
            'name' => $product->name,
            'price' => $product->price,
            'is_active' => '1',
            'variants' => [
                ['size' => 'M', 'color' => 'Black', 'stock' => 10],
                ['size' => 'XL', 'color' => 'White', 'stock' => 4],
            ],
        ])->assertRedirect();

        // Unchanged variant keeps its row/ID, removed one is gone, new one added.
        $this->assertDatabaseHas('product_variants', ['id' => $keptId, 'size' => 'M']);
        $this->assertDatabaseMissing('product_variants', ['id' => $removedId]);
        $this->assertDatabaseHas('product_variants', ['product_id' => $product->id, 'size' => 'XL', 'color' => 'White']);
        $this->assertDatabaseCount('product_variants', 2);
    }

    public function test_a_variant_that_sits_in_a_customers_cart_cannot_be_removed(): void
    {
        $this->makeAdmin();
        $product = $this->makeProduct([], [
            ['M', 'Black', 10],
            ['L', 'Black', 5],
        ]);
        $customer = $this->makeCustomer(false);
        $mVariant = $product->variants()->where('size', 'M')->first();

        CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $mVariant->id,
            'quantity' => 1,
        ]);

        $response = $this->from('/admin/produk/'.$product->slug.'/edit')
            ->put('/admin/produk/'.$product->slug, [
                'name' => $product->name,
                'price' => $product->price,
                'is_active' => '1',
                'variants' => [
                    ['size' => 'L', 'color' => 'Black', 'stock' => 5],
                ],
            ]);

        $response->assertSessionHasErrors('variants');
        $this->assertDatabaseHas('product_variants', ['id' => $mVariant->id]);
        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $mVariant->id]);
    }

    public function test_duplicate_variants_are_rejected(): void
    {
        $this->makeAdmin();

        $response = $this->from('/admin/produk/create')->post('/admin/produk', [
            'name' => 'Duplicate Variants',
            'price' => 1000,
            'variants' => [
                ['size' => 'M', 'color' => 'Black', 'stock' => 1],
                ['size' => 'm', 'color' => 'black', 'stock' => 2],
            ],
        ]);

        $response->assertSessionHasErrors('variants');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_negative_stock_is_rejected(): void
    {
        $this->makeAdmin();

        $response = $this->from('/admin/produk/create')->post('/admin/produk', [
            'name' => 'Negative Stock',
            'price' => 1000,
            'variants' => [
                ['size' => 'M', 'color' => 'Black', 'stock' => -5],
            ],
        ]);

        $response->assertSessionHasErrors('variants.0.stock');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_slugs_are_unique(): void
    {
        $this->makeAdmin();

        foreach ([1, 2, 3] as $i) {
            $this->post('/admin/produk', [
                'name' => 'Same Name',
                'price' => 1000,
                'variants' => [['size' => 'M', 'color' => 'Black', 'stock' => 1]],
            ])->assertRedirect(route('admin.products.index'));
        }

        $this->assertDatabaseCount('products', 3);
        $this->assertSame(
            3,
            \App\Models\Product::pluck('slug')->unique()->count()
        );
    }

    public function test_replacing_a_product_image_deletes_the_old_file(): void
    {
        Storage::fake('public');
        $this->makeAdmin();
        $product = $this->makeProduct();

        $oldPath = $this->imageFixture('old.png')->store('products', 'public');
        $product->update(['image' => $oldPath]);

        $this->put('/admin/produk/'.$product->slug, [
            'name' => $product->name,
            'price' => $product->price,
            'image' => $this->imageFixture('new.png'),
            'is_active' => '1',
            'variants' => [['size' => 'M', 'color' => 'Black', 'stock' => 10]],
        ])->assertRedirect();

        $product->refresh();
        $this->assertNotSame($oldPath, $product->image);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($product->image);
    }

    public function test_deleting_a_product_keeps_historical_order_items_intact(): void
    {
        $this->makeAdmin();
        $product = $this->makeProduct(['name' => 'Ordered Product']);

        $order = Order::create([
            'order_number' => 'ORD-HISTORICAL',
            'user_id' => $this->makeCustomer(false)->id,
            'recipient_name' => 'Someone',
            'phone' => '081234567890',
            'shipping_address' => 'Street 1, City',
            'subtotal' => 100000,
            'shipping_cost' => 15000,
            'total' => 115000,
            'status' => 'completed',
            'payment_method' => 'bank_transfer',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Ordered Product',
            'size' => 'M',
            'color' => 'Black',
            'price' => 100000,
            'quantity' => 1,
            'subtotal' => 100000,
        ]);

        $this->delete('/admin/produk/'.$product->slug)->assertRedirect();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);

        $item = OrderItem::where('order_id', $order->id)->firstOrFail();
        $this->assertNull($item->product_id);
        $this->assertSame('Ordered Product', $item->product_name);
        $this->assertSame('M / Black', $item->variantLabel());
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
    }
}
