<?php

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_variants')) {
            return;
        }

        if (! Schema::hasColumn('cart_items', 'product_variant_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('order_items', 'size')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->string('size', 20)->nullable()->after('product_name');
                $table->string('color', 50)->nullable()->after('size');
            });
        }

        if (Schema::hasColumn('products', 'stock')) {
            foreach (Product::all() as $product) {
                $sizes = $this->parseSizes($product->size);
                $colors = $this->parseColors($product->color);
                $stockPerVariant = max(1, (int) floor($product->stock / max(1, count($sizes) * count($colors))));

                foreach ($sizes as $size) {
                    foreach ($colors as $color) {
                        ProductVariant::firstOrCreate(
                            ['product_id' => $product->id, 'size' => $size, 'color' => $color],
                            ['stock' => $stockPerVariant]
                        );
                    }
                }
            }

            foreach (DB::table('cart_items')->whereNull('product_variant_id')->get() as $item) {
                $variant = ProductVariant::where('product_id', $item->product_id)->first();
                if ($variant) {
                    DB::table('cart_items')->where('id', $item->id)->update([
                        'product_variant_id' => $variant->id,
                    ]);
                }
            }

            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn(['stock', 'size', 'color']);
            });
        }

        if (Schema::hasColumn('cart_items', 'product_id')) {
            try {
                Schema::table('cart_items', function (Blueprint $table) {
                    $table->dropUnique(['user_id', 'product_id']);
                });
            } catch (\Throwable $e) {
                // Index may not exist on fresh installs.
            }
        }

        if (Schema::hasColumn('cart_items', 'product_variant_id')) {
            try {
                Schema::table('cart_items', function (Blueprint $table) {
                    $table->unique(['user_id', 'product_variant_id']);
                });
            } catch (\Throwable $e) {
                // Index may already exist.
            }
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stock')->default(0);
            $table->string('size')->nullable();
            $table->string('color')->nullable();
        });

        foreach (Product::with('variants')->get() as $product) {
            $product->update([
                'stock' => $product->variants->sum('stock'),
                'size' => $product->variants->pluck('size')->unique()->implode('-'),
                'color' => $product->variants->pluck('color')->unique()->first(),
            ]);
        }

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'product_variant_id']);
            $table->unique(['user_id', 'product_id']);
            $table->dropConstrainedForeignId('product_variant_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['size', 'color']);
        });
    }

    private function parseSizes(?string $size): array
    {
        if (! $size) {
            return ['All Size'];
        }

        $parts = preg_split('/[\s,\-\/]+/', $size, -1, PREG_SPLIT_NO_EMPTY);

        return $parts ?: ['All Size'];
    }

    private function parseColors(?string $color): array
    {
        if (! $color) {
            return ['Default'];
        }

        return [$color];
    }
};
