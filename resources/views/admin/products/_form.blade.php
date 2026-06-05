<form method="POST" action="{{ $action }}" enctype="multipart/form-data" id="product-form">
    @csrf
    @if(isset($method)) @method($method) @endif

    <div class="form-group">
        <label>Nama Produk</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $product->name ?? '') }}" required>
    </div>
    <div class="form-group">
        <label>Kategori</label>
        <select name="category_id" class="form-control">
            <option value="">— Pilih —</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Deskripsi</label>
        <textarea name="description" class="form-control" rows="4">{{ old('description', $product->description ?? '') }}</textarea>
    </div>
    <div class="form-group">
        <label>Harga (Rp)</label>
        <input type="number" name="price" class="form-control" value="{{ old('price', $product->price ?? '') }}" min="0" required>
    </div>

    <div class="form-group">
        <label>Stok per Ukuran & Warna</label>
        <div id="variant-rows" class="variant-rows">
            @php
                $oldVariants = old('variants');
                $variants = $oldVariants ?? (isset($product) ? $product->variants->map(fn($v) => ['size' => $v->size, 'color' => $v->color, 'stock' => $v->stock])->toArray() : [['size' => '', 'color' => '', 'stock' => 0]]);
            @endphp
            @foreach($variants as $i => $variant)
                <div class="variant-row">
                    <input type="text" name="variants[{{ $i }}][size]" class="form-control" placeholder="Ukuran (M)" value="{{ $variant['size'] ?? '' }}" required>
                    <input type="text" name="variants[{{ $i }}][color]" class="form-control" placeholder="Warna" value="{{ $variant['color'] ?? '' }}" required>
                    <input type="number" name="variants[{{ $i }}][stock]" class="form-control" placeholder="Stok" min="0" value="{{ $variant['stock'] ?? 0 }}" required>
                    <button type="button" class="btn btn-danger btn-sm variant-remove" onclick="removeVariantRow(this)">×</button>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-outline btn-sm mt-1" onclick="addVariantRow()">+ Tambah Varian</button>
    </div>

    <div class="form-group">
        <label>Gambar</label>
        <input type="file" name="image" class="form-control" accept="image/*">
        @if(isset($product) && $product->image)
            <img src="{{ $product->image_url }}" alt="" style="max-width:120px;margin-top:0.5rem;border-radius:4px">
        @endif
    </div>
    <div class="form-check">
        <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}>
        <label for="is_active">Produk aktif (tampil di toko)</label>
    </div>
    <button type="submit" class="btn btn-gold mt-2">Simpan</button>
    <a href="{{ route('admin.products.index') }}" class="btn btn-outline mt-2">Batal</a>
</form>

<script>
let variantIndex = {{ count($variants) }};
function addVariantRow() {
    const row = document.createElement('div');
    row.className = 'variant-row';
    row.innerHTML = `
        <input type="text" name="variants[${variantIndex}][size]" class="form-control" placeholder="Ukuran (M)" required>
        <input type="text" name="variants[${variantIndex}][color]" class="form-control" placeholder="Warna" required>
        <input type="number" name="variants[${variantIndex}][stock]" class="form-control" placeholder="Stok" min="0" value="0" required>
        <button type="button" class="btn btn-danger btn-sm variant-remove" onclick="removeVariantRow(this)">×</button>
    `;
    document.getElementById('variant-rows').appendChild(row);
    variantIndex++;
}
function removeVariantRow(btn) {
    const rows = document.querySelectorAll('.variant-row');
    if (rows.length > 1) btn.closest('.variant-row').remove();
}
</script>
