<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category', 'variants')->latest()->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateProduct($request);

        // Stored before the transaction only because a rejected request must
        // not leave a file behind — the catch below removes it if anything
        // fails after this point.
        $image = $request->hasFile('image')
            ? $request->file('image')->store('products', 'public')
            : null;

        try {
            $product = DB::transaction(function () use ($request, $validated, $image) {
                $attributes = collect($validated)->except('variants')->all();
                $attributes['slug'] = $this->generateSlug($validated['name']);
                $attributes['is_active'] = $request->boolean('is_active', true);

                if ($image !== null) {
                    $attributes['image'] = $image;
                }

                $product = Product::create($attributes);
                $this->syncVariants($product, $validated['variants']);

                return $product;
            });
        } catch (\Throwable $e) {
            if ($image !== null) {
                Storage::disk('public')->delete($image);
            }

            throw $e;
        }

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();
        $product->load('variants');

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $this->validateProduct($request);

        $image = $request->hasFile('image')
            ? $request->file('image')->store('products', 'public')
            : null;
        $oldImage = $product->image;

        try {
            DB::transaction(function () use ($request, $validated, $product, $image) {
                $attributes = collect($validated)->except('variants')->all();
                // The slug never changes on update, so shared links and
                // historical references to this product keep working.
                $attributes['slug'] = $product->slug;
                $attributes['is_active'] = $request->boolean('is_active');

                if ($image !== null) {
                    $attributes['image'] = $image;
                }

                $product->update($attributes);
                $this->syncVariants($product, $validated['variants']);
            });
        } catch (\Throwable $e) {
            if ($image !== null) {
                Storage::disk('public')->delete($image);
            }

            throw $e;
        }

        // Replace the old picture only after the new one is safely in place.
        if ($image !== null && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $image = $product->image;

        // Order items keep a denormalised snapshot (name, size, colour, price),
        // so deleting the product never corrupts historical orders — the
        // product_id on those rows is nullable and is set to NULL.
        $product->delete();

        if ($image) {
            Storage::disk('public')->delete($image);
        }

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProduct(Request $request): array
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.size' => ['required', 'string', 'max:20'],
            'variants.*.color' => ['required', 'string', 'max:50'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
        ]);

        $validated['variants'] = $this->normalizeVariants($validated['variants']);

        return $validated;
    }

    /**
     * Trim/case-normalise the submitted rows and reject duplicates up front,
     * so a duplicate (size, colour) never reaches the database's unique index.
     *
     * @return array<int, array{size: string, color: string, stock: int}>
     */
    private function normalizeVariants(array $variants): array
    {
        $normalized = [];

        foreach ($variants as $variant) {
            $size = strtoupper(trim($variant['size']));
            $color = trim($variant['color']);

            if ($size === '' || $color === '') {
                throw ValidationException::withMessages([
                    'variants' => 'Setiap varian harus memiliki ukuran dan warna.',
                ]);
            }

            $key = $size.'|'.mb_strtolower($color);

            if (isset($normalized[$key])) {
                throw ValidationException::withMessages([
                    'variants' => "Varian {$size} / {$color} terinput lebih dari sekali.",
                ]);
            }

            $normalized[$key] = [
                'size' => $size,
                'color' => $color,
                'stock' => max(0, (int) $variant['stock']),
            ];
        }

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'variants' => 'Minimal satu varian wajib diisi.',
            ]);
        }

        return array_values($normalized);
    }

    /**
     * Diff-based variant sync.
     *
     * The old implementation deleted every variant and re-created them, which
     * changed all variant IDs on each save. That silently wiped other users'
     * cart rows (cart_items.product_variant_id cascades on delete) and broke
     * the (product_id, size, colour) match that stock restoration uses when a
     * cancelled order is reactivated.
     *
     * Now unchanged variants keep their ID, only stock is updated, and a
     * variant that is still in somebody's cart cannot be removed.
     *
     * @param  array<int, array{size: string, color: string, stock: int}>  $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        $existing = $product->variants()->get()->keyBy(fn ($variant) => $this->variantKey($variant->size, $variant->color));

        $keptIds = [];

        foreach ($variants as $variant) {
            $key = $this->variantKey($variant['size'], $variant['color']);

            if ($existing->has($key)) {
                $current = $existing->get($key);

                if ($current->stock !== $variant['stock']) {
                    $current->update(['stock' => $variant['stock']]);
                }

                $keptIds[] = $current->id;

                continue;
            }

            $product->variants()->create($variant);
        }

        $removedIds = $existing->pluck('id')->diff($keptIds)->values();

        if ($removedIds->isEmpty()) {
            return;
        }

        $inCarts = CartItem::whereIn('product_variant_id', $removedIds)->count();

        if ($inCarts > 0) {
            throw ValidationException::withMessages([
                'variants' => 'Varian yang dihapus masih ada di keranjang pembeli. Minta pembeli menghapusnya terlebih dahulu, atau biarkan varian tetap ada.',
            ]);
        }

        $product->variants()->whereIn('id', $removedIds)->delete();
    }

    private function variantKey(string $size, string $color): string
    {
        return strtoupper(trim($size)).'|'.mb_strtolower(trim($color));
    }

    /**
     * Collision-free slug: name, then name-2, name-3, ...
     */
    private function generateSlug(string $name, ?Product $ignore = null): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'produk';
        }

        $slug = $base;
        $suffix = 1;

        while (
            Product::where('slug', $slug)
                ->when($ignore, fn ($query) => $query->where('id', '!=', $ignore->id))
                ->exists()
        ) {
            $slug = $base.'-'.++$suffix;
        }

        return $slug;
    }
}
