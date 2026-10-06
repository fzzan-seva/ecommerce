<?php

namespace Tests;

use App\Models\Address;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Monotonic counter so product names/slugs never collide between tests.
     */
    protected static int $productCounter = 0;

    /**
     * A real PNG/JPEG from tests/fixtures, wrapped as an UploadedFile.
     *
     * GD is not guaranteed on every machine (this suite must run without it),
     * so images are pre-made fixtures instead of UploadedFile::fake()->image().
     */
    protected function imageFixture(string $name = 'pixel.png'): UploadedFile
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $isJpeg = in_array($extension, ['jpg', 'jpeg'], true);

        return new UploadedFile(
            base_path('tests/fixtures/'.($isJpeg ? 'pixel.jpg' : 'pixel.png')),
            $name,
            $isJpeg ? 'image/jpeg' : 'image/png',
            null,
            true,
        );
    }

    /**
     * A file that claims to be an image but is plain text — the validator must
     * reject it, because the `image` rule sniffs the actual bytes.
     */
    protected function fakeImage(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'not-an-image-');
        file_put_contents($path, 'This is not an image, despite the .png name.');

        return new UploadedFile($path, 'fake.png', 'text/plain', null, true);
    }

    /**
     * A product with a deterministic, unique slug plus optional variants.
     *
     * @param  array<int, array{0: string, 1: string, 2: int}>  $variants
     */
    protected function makeProduct(array $attributes = [], array $variants = []): Product
    {
        $n = ++self::$productCounter;

        // The admin UI requires at least one variant, so a product without an
        // explicit list still gets a sensible default.
        if ($variants === []) {
            $variants = [['M', 'Black', 5]];
        }

        $product = Product::create(array_merge([
            'name' => "Test Product {$n}",
            'slug' => "test-product-{$n}",
            'description' => 'A product created by the test suite.',
            'price' => 100000,
            'is_active' => true,
        ], $attributes));

        foreach ($variants as [$size, $color, $stock]) {
            $product->variants()->create([
                'size' => $size,
                'color' => $color,
                'stock' => $stock,
            ]);
        }

        return $product->load('variants');
    }

    protected function makeCustomer(bool $login = true): User
    {
        $user = User::factory()->create();

        if ($login) {
            $this->actingAs($user);
        }

        return $user;
    }

    protected function makeAdmin(bool $login = true): User
    {
        $user = User::factory()->create();

        // 'role' is deliberately not mass-assignable, so set it directly.
        $user->role = 'admin';
        $user->save();

        if ($login) {
            $this->actingAs($user);
        }

        return $user;
    }

    protected function makeAddress(User $user, array $attributes = []): Address
    {
        return $user->addresses()->create(array_merge([
            'label' => 'Rumah',
            'recipient_name' => 'Test Customer',
            'phone' => '081234567890',
            'address_line' => 'Jalan Test No. 1',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'postal_code' => '40111',
            'is_default' => true,
        ], $attributes));
    }
}
