@extends('layouts.app')

@section('title', 'Keranjang')

@section('content')
<h1 class="page-title text-gold">Keranjang Belanja</h1>

@if($items->isEmpty())
    <div class="empty-state sans">
        <h3>Keranjang kosong</h3>
        <a href="{{ route('home') }}" class="btn btn-gold mt-2">Belanja Sekarang</a>
    </div>
@else
    <div class="cart-layout">
        <div>
            @foreach($items as $item)
                <div class="cart-item sans">
                    <img src="{{ $item->product->image_url }}" alt="">
                    <div class="cart-item-info">
                        <h3><a href="{{ route('products.show', $item->product) }}">{{ $item->product->name }}</a></h3>
                        <p class="text-muted">{{ $item->variant->label() }}</p>
                        <p class="text-gold">{{ $item->product->formattedPrice() }}</p>
                        <form action="{{ route('cart.update', $item) }}" method="POST" class="flex gap-1 items-center mt-1 cart-qty-form">
                            @csrf @method('PATCH')
                            <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ $item->variant->stock }}" class="form-control qty-input">
                            <button type="submit" class="btn btn-outline btn-sm">Update</button>
                        </form>
                    </div>
                    <div class="cart-item-actions">
                        <p class="text-gold sans">Rp {{ number_format($item->subtotal(), 0, ',', '.') }}</p>
                        <form action="{{ route('cart.destroy', $item) }}" method="POST" class="mt-1">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="cart-summary sans">
            <h3 class="text-gold mb-2">Ringkasan</h3>
            <div class="row"><span>Subtotal</span><span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span></div>
            <div class="row"><span>Ongkir</span><span>Rp 15.000</span></div>
            <div class="row total flex justify-between"><span>Total</span><span>Rp {{ number_format($subtotal + 15000, 0, ',', '.') }}</span></div>
            <a href="{{ route('checkout.index') }}" class="btn btn-gold btn-block mt-2">Checkout</a>
        </div>
    </div>
@endif
@endsection
