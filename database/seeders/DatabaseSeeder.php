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
        User::create([
            'name' => 'Admin FQueensha',
            'email' => 'admin@fqueensha.com',
            'password' => 'password',
            'role' => 'admin',
            'phone' => '0812-1453-1169',
        ]);

        User::create([
            'name' => 'Siti Aminah',
            'email' => 'user@fqueensha.com',
            'password' => 'password',
            'role' => 'user',
            'phone' => '081298765432',
        ]);

        $categories = [
            ['name' => 'Gamis Daily', 'slug' => 'gamis-daily'],
            ['name' => 'Gamis Premium', 'slug' => 'gamis-premium'],
            ['name' => 'Gamis Syar\'i', 'slug' => 'gamis-syari'],
            ['name' => 'Set Gamis', 'slug' => 'set-gamis'],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }

        $products = [
            [
                'name' => 'Gamis Queensha Elegant Black',
                'category' => 'gamis-premium',
                'price' => 389000,
                'variants' => [
                    ['size' => 'M', 'color' => 'Hitam', 'stock' => 8],
                    ['size' => 'L', 'color' => 'Hitam', 'stock' => 10],
                    ['size' => 'XL', 'color' => 'Hitam', 'stock' => 7],
                ],
            ],
            [
                'name' => 'Gamis Silk Gold Edition',
                'category' => 'gamis-premium',
                'price' => 459000,
                'variants' => [
                    ['size' => 'S', 'color' => 'Emas', 'stock' => 5],
                    ['size' => 'M', 'color' => 'Emas', 'stock' => 6],
                    ['size' => 'L', 'color' => 'Emas', 'stock' => 4],
                ],
            ],
            [
                'name' => 'Gamis Daily Comfort Navy',
                'category' => 'gamis-daily',
                'price' => 249000,
                'variants' => [
                    ['size' => 'M', 'color' => 'Navy', 'stock' => 12],
                    ['size' => 'L', 'color' => 'Navy', 'stock' => 15],
                    ['size' => 'XL', 'color' => 'Navy', 'stock' => 8],
                    ['size' => 'XXL', 'color' => 'Navy', 'stock' => 5],
                ],
            ],
            [
                'name' => 'Gamis Syari Full Length Maroon',
                'category' => 'gamis-syari',
                'price' => 329000,
                'variants' => [
                    ['size' => 'M', 'color' => 'Maroon', 'stock' => 7],
                    ['size' => 'L', 'color' => 'Maroon', 'stock' => 8],
                    ['size' => 'XL', 'color' => 'Maroon', 'stock' => 5],
                ],
            ],
            [
                'name' => 'Set Gamis + Khimar Ivory',
                'category' => 'set-gamis',
                'price' => 499000,
                'variants' => [
                    ['size' => 'S', 'color' => 'Ivory', 'stock' => 4],
                    ['size' => 'M', 'color' => 'Ivory', 'stock' => 5],
                    ['size' => 'L', 'color' => 'Ivory', 'stock' => 3],
                ],
            ],
            [
                'name' => 'Gamis Brokat Hitam Emas',
                'category' => 'gamis-premium',
                'price' => 549000,
                'variants' => [
                    ['size' => 'M', 'color' => 'Hitam-Emas', 'stock' => 4],
                    ['size' => 'L', 'color' => 'Hitam-Emas', 'stock' => 4],
                ],
            ],
            [
                'name' => 'Gamis Polos Dusty Rose',
                'category' => 'gamis-daily',
                'price' => 219000,
                'variants' => [
                    ['size' => 'S', 'color' => 'Dusty Rose', 'stock' => 10],
                    ['size' => 'M', 'color' => 'Dusty Rose', 'stock' => 12],
                    ['size' => 'L', 'color' => 'Dusty Rose', 'stock' => 8],
                    ['size' => 'XL', 'color' => 'Dusty Rose', 'stock' => 5],
                ],
            ],
            [
                'name' => 'Gamis Plisket Premium Olive',
                'category' => 'gamis-daily',
                'price' => 279000,
                'variants' => [
                    ['size' => 'M', 'color' => 'Olive', 'stock' => 8],
                    ['size' => 'L', 'color' => 'Olive', 'stock' => 9],
                    ['size' => 'XL', 'color' => 'Olive', 'stock' => 5],
                ],
            ],
        ];

        foreach ($products as $p) {
            $category = Category::where('slug', $p['category'])->first();
            $product = Product::create([
                'category_id' => $category->id,
                'name' => $p['name'],
                'slug' => Str::slug($p['name']) . '-' . Str::random(4),
                'description' => 'Gamis perempuan berkualitas dari FQueensha. Bahan nyaman, jahitan rapi, dan desain elegan untuk tampil anggun setiap hari.',
                'price' => $p['price'],
                'is_active' => true,
            ]);

            foreach ($p['variants'] as $variant) {
                $product->variants()->create($variant);
            }
        }
    }
}
