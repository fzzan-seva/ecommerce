@extends('layouts.app')

@section('title', $product->name)

@section('content')
<div class="product-detail">
    <div>
        <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
    </div>
    <div>
        <h1>{{ $product->name }}</h1>
        @if($product->category)
            <p class="text-muted sans">{{ $product->category->name }}</p>
        @endif
        <div class="price sans">{{ $product->formattedPrice() }}</div>
        <p class="sans mb-2">{{ $product->description }}</p>
        <p class="sans text-muted">Total stok: <strong id="total-stock">{{ $product->totalStock() }}</strong> pcs</p>

        @auth
            @if(!auth()->user()->isAdmin())
                @if($product->variants->isNotEmpty() && $product->totalStock() > 0)
                    <form action="{{ route('cart.store', $product) }}" method="POST" class="mt-2 sans" id="add-to-cart-form">
                        @csrf
                        <input type="hidden" name="product_variant_id" id="variant-id" value="">

                        <div class="form-group">
                            <label>Ukuran</label>
                            <div class="option-pills" id="size-options">
                                @foreach($product->availableSizes() as $size)
                                    <button type="button" class="option-pill" data-size="{{ $size }}">{{ $size }}</button>
                                @endforeach
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Warna</label>
                            <div class="option-pills" id="color-options">
                                @foreach($product->availableColors() as $color)
                                    <button type="button" class="option-pill" data-color="{{ $color }}">{{ $color }}</button>
                                @endforeach
                            </div>
                        </div>

                        <p class="stock-info" id="variant-stock-info">Pilih ukuran dan warna</p>

                        <div class="flex gap-2 items-center mt-2 add-cart-row">
                            <label>Jumlah:</label>
                            <input type="number" name="quantity" id="quantity" value="1" min="1" max="1" class="form-control qty-input" disabled>
                            <button type="submit" class="btn btn-gold" id="add-cart-btn" disabled>+ Keranjang</button>
                        </div>
                    </form>
                @else
                    <p class="alert alert-error sans mt-2">Stok habis</p>
                @endif
            @endif
        @else
            <a href="{{ route('login') }}" class="btn btn-gold mt-2">Login untuk Beli</a>
        @endauth

        <div class="card sans mt-2">
            <h3 class="text-gold" style="margin-bottom:0.75rem;font-size:1rem">Stok Tersedia</h3>
            <div class="table-wrap">
                <table class="stock-table">
                    <thead><tr><th>Ukuran</th><th>Warna</th><th>Stok</th></tr></thead>
                    <tbody>
                        @foreach($product->variants as $v)
                            <tr class="{{ $v->stock < 1 ? 'out-of-stock' : '' }}">
                                <td>{{ $v->size }}</td>
                                <td>{{ $v->color }}</td>
                                <td>{{ $v->stock > 0 ? $v->stock . ' pcs' : 'Habis' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if($related->isNotEmpty())
    <h2 class="text-gold mt-2 section-title">Produk Terkait</h2>
    <div class="product-grid">
        @foreach($related as $p)
            <a href="{{ route('products.show', $p) }}" class="product-card">
                <img src="{{ $p->image_url }}" alt="{{ $p->name }}">
                <div class="product-card-body">
                    <h3>{{ $p->name }}</h3>
                    <div class="product-price sans">{{ $p->formattedPrice() }}</div>
                    <div class="product-meta sans">Stok {{ $p->totalStock() }}</div>
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection

@push('scripts')
<script>
const variants = @json($product->variants);
let selectedSize = null;
let selectedColor = null;

function findVariant(size, color) {
    return variants.find(v => v.size === size && v.color === color);
}

function updateSelection() {
    const info = document.getElementById('variant-stock-info');
    const qty = document.getElementById('quantity');
    const variantInput = document.getElementById('variant-id');
    const btn = document.getElementById('add-cart-btn');

    if (!selectedSize || !selectedColor) {
        info.textContent = 'Pilih ukuran dan warna';
        qty.disabled = true;
        btn.disabled = true;
        variantInput.value = '';
        return;
    }

    const variant = findVariant(selectedSize, selectedColor);
    if (!variant || variant.stock < 1) {
        info.textContent = 'Stok habis untuk kombinasi ini';
        info.classList.add('text-danger');
        qty.disabled = true;
        btn.disabled = true;
        variantInput.value = '';
        return;
    }

    info.classList.remove('text-danger');
    info.textContent = 'Stok tersedia: ' + variant.stock + ' pcs';
    variantInput.value = variant.id;
    qty.max = variant.stock;
    qty.value = Math.min(parseInt(qty.value) || 1, variant.stock);
    qty.disabled = false;
    btn.disabled = false;
}

document.querySelectorAll('#size-options .option-pill').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#size-options .option-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedSize = btn.dataset.size;
        updateSelection();
    });
});

document.querySelectorAll('#color-options .option-pill').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#color-options .option-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedColor = btn.dataset.color;
        updateSelection();
    });
});
</script>
@endpush
