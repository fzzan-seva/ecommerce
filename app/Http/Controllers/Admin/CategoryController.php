<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->orderBy('name')->paginate(20);

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateCategory($request);
        $validated['slug'] = $this->generateSlug($validated['name']);

        Category::create($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $this->validateCategory($request, $category);
        $validated['slug'] = $this->generateSlug($validated['name'], $category);

        $category->update($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category)
    {
        // products.category_id is nullable with ON DELETE SET NULL, so a blind
        // delete would silently detach every product from its category and
        // break the storefront's category filter. Refuse instead.
        if ($category->products()->exists()) {
            return back()->with(
                'error',
                "Kategori \"{$category->name}\" masih digunakan oleh {$category->products()->count()} produk. Pindahkan atau hapus produk tersebut terlebih dahulu."
            );
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil dihapus.');
    }

    /**
     * @return array<string, string>
     */
    private function validateCategory(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')->ignore($category?->id),
            ],
        ]);
    }

    /**
     * Collision-free slug: name, then name-2, name-3, ...
     */
    private function generateSlug(string $name, ?Category $ignore = null): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'kategori';
        }

        $slug = $base;
        $suffix = 1;

        while (
            Category::where('slug', $slug)
                ->when($ignore, fn ($query) => $query->where('id', '!=', $ignore->id))
                ->exists()
        ) {
            $slug = $base.'-'.++$suffix;
        }

        return $slug;
    }
}
