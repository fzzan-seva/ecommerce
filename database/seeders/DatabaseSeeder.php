<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Passwords are randomised so a seeded database never ships a guessable
        // credential. The generated values are printed once during seeding.
        $adminPassword = $this->randomPassword();

        $admin = new User([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => $adminPassword,
        ]);
        $admin->role = 'admin';
        $admin->save();

        $customerPassword = $this->randomPassword();

        $customer = new User([
            'name' => 'Demo Customer',
            'email' => 'customer@example.com',
            'password' => $customerPassword,
        ]);
        $customer->role = 'user';
        $customer->save();

        $this->command?->info('Demo credentials (shown once, store them safely):');
        $this->command?->warn('  Admin    : admin@example.com / '.$adminPassword);
        $this->command?->warn('  Customer : customer@example.com / '.$customerPassword);

        $this->seedCatalog();
    }

    private function seedCatalog(): void
    {
        $categories = [
            ['name' => 'Dresses', 'slug' => 'dresses'],
            ['name' => 'Tops', 'slug' => 'tops'],
            ['name' => 'Outerwear', 'slug' => 'outerwear'],
            ['name' => 'Accessories', 'slug' => 'accessories'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $products = [
            [
                'name' => 'Classic Dress',
                'category' => 'dresses',
                'price' => 389000,
                'description' => 'A timeless classic dress cut from a comfortable blend. Easy to style for work or a night out.',
                'variants' => [
                    ['size' => 'M', 'color' => 'Black', 'stock' => 8],
                    ['size' => 'L', 'color' => 'Black', 'stock' => 10],
                    ['size' => 'XL', 'color' => 'Black', 'stock' => 7],
                ],
            ],
            [
                'name' => 'Premium Dress',
                'category' => 'dresses',
                'price' => 459000,
                'description' => 'Our premium dress with a refined finish, soft drape and careful stitching throughout.',
                'variants' => [
                    ['size' => 'S', 'color' => 'Ivory', 'stock' => 5],
                    ['size' => 'M', 'color' => 'Ivory', 'stock' => 6],
                    ['size' => 'L', 'color' => 'Ivory', 'stock' => 4],
                ],
            ],
            [
                'name' => 'Casual Wear',
                'category' => 'tops',
                'price' => 249000,
                'description' => 'An everyday casual top that pairs with anything in your wardrobe.',
                'variants' => [
                    ['size' => 'M', 'color' => 'Navy', 'stock' => 12],
                    ['size' => 'L', 'color' => 'Navy', 'stock' => 15],
                    ['size' => 'XL', 'color' => 'Navy', 'stock' => 8],
                ],
            ],
            [
                'name' => 'Basic Collection',
                'category' => 'tops',
                'price' => 199000,
                'description' => 'A wardrobe staple from the basic collection — simple, versatile and affordable.',
                'variants' => [
                    ['size' => 'S', 'color' => 'White', 'stock' => 10],
                    ['size' => 'M', 'color' => 'White', 'stock' => 14],
                    ['size' => 'L', 'color' => 'White', 'stock' => 9],
                ],
            ],
            [
                'name' => 'Everyday Jacket',
                'category' => 'outerwear',
                'price' => 549000,
                'description' => 'A light jacket for cooler evenings, with a clean cut and practical pockets.',
                'variants' => [
                    ['size' => 'M', 'color' => 'Olive', 'stock' => 6],
                    ['size' => 'L', 'color' => 'Olive', 'stock' => 6],
                    ['size' => 'XL', 'color' => 'Olive', 'stock' => 4],
                ],
            ],
            [
                'name' => 'Essential Scarf',
                'category' => 'accessories',
                'price' => 129000,
                'description' => 'A soft finishing accessory that completes any outfit.',
                'variants' => [
                    ['size' => 'One Size', 'color' => 'Grey', 'stock' => 20],
                    ['size' => 'One Size', 'color' => 'Burgundy', 'stock' => 15],
                ],
            ],
        ];

        foreach ($products as $product) {
            $category = Category::where('slug', $product['category'])->first();

            $model = Product::create([
                'category_id' => $category?->id,
                'name' => $product['name'],
                'slug' => Str::slug($product['name']).'-'.Str::random(4),
                'description' => $product['description'],
                'price' => $product['price'],
                'is_active' => true,
            ]);

            foreach ($product['variants'] as $variant) {
                $model->variants()->create($variant);
            }
        }
    }

    private function randomPassword(): string
    {
        return Str::password(16);
    }
}
