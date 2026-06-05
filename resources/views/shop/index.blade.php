@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
<section class="hero">
    <h1>FQueensha</h1>
    <p>Koleksi gamis perempuan elegan — nuansa hitam & emas untuk penampilan anggun</p>
    <p class="sans text-muted mt-1" style="font-size:0.9rem">{{ config('fqueensha.location') }}</p>
</section>

<div class="category-pills sans">
    <a href="{{ route('home') }}" class="category-pill {{ !request('category') ? 'active' : '' }}">Semua</a>
    @foreach($categories as $cat)
        <a href="{{ route('home', ['category' => $cat->slug]) }}"
           class="category-pill {{ request('category') === $cat->slug ? 'active' : '' }}">
            {{ $cat->name }} ({{ $cat->products_count }})
        </a>
    @endforeach
</div>

@if($products->isEmpty())
    <div class="empty-state">
        <h3>Produk tidak ditemukan</h3>
        <p>Coba kata kunci atau kategori lain.</p>
    </div>
@else
    <div class="product-grid">
        @foreach($products as $product)
            <a href="{{ route('products.show', $product) }}" class="product-card">
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                <div class="product-card-body">
                    <h3>{{ $product->name }}</h3>
                    <div class="product-price sans">{{ $product->formattedPrice() }}</div>
                    <div class="product-meta sans">Stok {{ $product->totalStock() }} · {{ $product->variants->pluck('size')->unique()->implode(', ') }}</div>
                </div>
            </a>
        @endforeach
    </div>
    <div class="pagination sans">{{ $products->withQueryString()->links() }}</div>
@endif
@endsection
