<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_create_a_category_with_a_collision_free_slug(): void
    {
        $this->makeAdmin();

        $this->post('/admin/kategori', ['name' => 'Tote Bags'])
            ->assertRedirect(route('admin.categories.index'));

        // A different name that slugifies identically must not collide.
        $this->post('/admin/kategori', ['name' => 'Tote  Bags'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseCount('categories', 2);
        $this->assertSame(
            ['tote-bags', 'tote-bags-2'],
            Category::orderBy('id')->pluck('slug')->all(),
        );
    }

    public function test_category_name_is_required_and_must_be_unique(): void
    {
        $this->makeAdmin();
        Category::create(['name' => 'Tops', 'slug' => 'tops']);

        $this->from(route('admin.categories.create'))
            ->post('/admin/kategori', ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->from(route('admin.categories.create'))
            ->post('/admin/kategori', ['name' => 'Tops'])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_renaming_a_category_updates_its_slug(): void
    {
        $this->makeAdmin();
        $category = Category::create(['name' => 'Dresses', 'slug' => 'dresses']);

        $this->put("/admin/kategori/{$category->id}", ['name' => 'Dresses & Skirts'])
            ->assertRedirect(route('admin.categories.index'));

        $category->refresh();
        $this->assertSame('Dresses & Skirts', $category->name);
        $this->assertSame('dresses-skirts', $category->slug);
    }

    public function test_a_category_with_products_cannot_be_deleted(): void
    {
        $this->makeAdmin();
        $category = Category::create(['name' => 'Outerwear', 'slug' => 'outerwear']);
        $this->makeProduct(['category_id' => $category->id]);

        $response = $this->from(route('admin.categories.index'))
            ->delete("/admin/kategori/{$category->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_a_category_can_be_deleted_once_its_products_are_moved(): void
    {
        $this->makeAdmin();
        $category = Category::create(['name' => 'Accessories', 'slug' => 'accessories']);
        $product = $this->makeProduct(['category_id' => $category->id]);

        $this->put("/admin/produk/{$product->slug}", [
            'name' => $product->name,
            'price' => $product->price,
            'category_id' => null,
            'is_active' => '1',
            'variants' => [['size' => 'M', 'color' => 'Black', 'stock' => 10]],
        ])->assertRedirect(route('admin.products.index'));

        $this->delete("/admin/kategori/{$category->id}")
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
