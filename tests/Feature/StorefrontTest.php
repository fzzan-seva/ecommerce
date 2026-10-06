<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_homepage_shows_active_products_only(): void
    {
        $this->makeProduct(['name' => 'Visible Product']);
        $this->makeProduct(['name' => 'Hidden Product', 'is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Visible Product')
            ->assertDontSee('Hidden Product', false);
    }

    public function test_search_filters_products_by_name_and_description(): void
    {
        $this->makeProduct(['name' => 'Linen Dress', 'description' => 'Made of linen.']);
        $this->makeProduct(['name' => 'Denim Jacket', 'description' => 'Made of denim.']);
        $this->makeProduct(['name' => 'Silk Scarf', 'description' => 'Not matching at all.']);

        $this->get('/?q=linen')
            ->assertOk()
            ->assertSee('Linen Dress')
            ->assertDontSee('Denim Jacket', false)
            ->assertDontSee('Silk Scarf', false);

        $this->get('/?q=denim')
            ->assertOk()
            ->assertSee('Denim Jacket')
            ->assertDontSee('Linen Dress', false);
    }

    public function test_inactive_products_never_appear_in_search_results(): void
    {
        $this->makeProduct(['name' => 'Secret Item', 'is_active' => false]);

        $this->get('/?q=secret')->assertOk()->assertDontSee('Secret Item', false);
    }

    public function test_the_category_filter_only_shows_matching_products(): void
    {
        $dresses = Category::create(['name' => 'Dresses', 'slug' => 'dresses']);
        $tops = Category::create(['name' => 'Tops', 'slug' => 'tops']);

        $this->makeProduct(['name' => 'Summer Dress', 'category_id' => $dresses->id]);
        $this->makeProduct(['name' => 'Cotton Top', 'category_id' => $tops->id]);

        $this->get('/?category=dresses')
            ->assertOk()
            ->assertSee('Summer Dress')
            ->assertDontSee('Cotton Top', false);

        // An unknown category yields an empty result set, not an error.
        $this->get('/?category=does-not-exist')->assertOk();
    }

    public function test_a_product_page_shows_price_variants_and_the_cart_form(): void
    {
        $product = $this->makeProduct(['name' => 'Detail Product', 'price' => 75000], [
            ['M', 'Black', 3],
            ['L', 'Black', 5],
        ]);

        // Only signed-in shoppers see the add-to-cart form.
        $this->makeCustomer();

        $this->get('/produk/'.$product->slug)
            ->assertOk()
            ->assertSee('Detail Product')
            ->assertSee('Rp 75,000')
            ->assertSee(route('cart.store', $product));
    }

    public function test_related_products_stay_within_the_same_category_and_capped_at_four(): void
    {
        $category = Category::create(['name' => 'Outerwear', 'slug' => 'outerwear']);

        $product = $this->makeProduct(['name' => 'Main Product', 'category_id' => $category->id]);

        // Five more active products in the same category: only four may show.
        foreach (range(1, 5) as $i) {
            $this->makeProduct(['name' => "Related {$i}", 'category_id' => $category->id]);
        }

        $this->makeProduct(['name' => 'Other Category Product']);

        $content = $this->get('/produk/'.$product->slug)->assertOk()->getContent();

        $shown = collect(range(1, 5))->filter(fn ($i) => str_contains($content, "Related {$i}"));

        $this->assertCount(4, $shown);
        $this->assertStringNotContainsString('Other Category Product', $content);
    }

    public function test_store_settings_branding_reaches_the_storefront(): void
    {
        Setting::set('name', 'Brand From Database');
        shop()->refresh();

        $this->makeProduct();

        $this->get('/')->assertOk()->assertSee('Brand From Database');
    }
}
