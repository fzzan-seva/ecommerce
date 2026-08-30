@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
<h1 class="page-title text-gold">Checkout</h1>

@if($addresses->isEmpty())
    <div class="alert alert-error sans">Anda belum punya alamat. <a href="{{ route('addresses.create') }}">Tambah alamat</a> terlebih dahulu.</div>
@else
    <form method="POST" action="{{ route('checkout.store') }}" enctype="multipart/form-data" class="sans checkout-layout">
        @csrf
        <div class="card">
            <h3>Alamat Pengiriman</h3>
            @foreach($addresses as $addr)
                <label class="address-option">
                    <input type="radio" name="address_id" value="{{ $addr->id }}" {{ $addr->is_default ? 'checked' : '' }} required>
                    <span>
                        <strong>{{ $addr->label }}</strong> — {{ $addr->recipient_name }} ({{ $addr->phone }})<br>
                        <span class="text-muted">{{ $addr->fullAddress() }}</span>
                    </span>
                </label>
            @endforeach
            <a href="{{ route('addresses.create') }}" class="btn btn-outline btn-sm">+ Alamat Baru</a>

            <h3 class="mt-2">Metode Pembayaran</h3>
            <p class="text-muted mb-2" style="font-size:0.9rem">Hanya transfer — pilih salah satu:</p>
            @include('shop.checkout._payment_methods')

            <div class="form-group mt-2">
                <label for="payment_proof">Foto Bukti Transfer <span class="text-danger">*</span></label>
                <input type="file" name="payment_proof" id="payment_proof" class="form-control" accept="image/jpeg,image/png,image/jpg" required>
                @error('payment_proof')<span class="text-danger">{{ $message }}</span>@enderror
                <p class="text-muted" style="font-size:0.8rem;margin-top:0.5rem">Wajib melampirkan foto bukti transfer. Tanpa bukti, pesanan tidak akan diproses.</p>
            </div>

            <div class="form-group mt-2">
                <label>Catatan (opsional)</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>

            <div class="alert alert-warning mt-2">
                <strong>Catatan Penting:</strong> Jika bukti transfer yang dilampirkan tidak sesuai (jumlah, rekening tujuan, atau atas nama yang salah), maka uang akan dianggap hangus dan barang <strong>tidak akan dikirimkan</strong>.
            </div>
        </div>

        <div class="cart-summary">
            <h3 class="text-gold mb-2">Pesanan</h3>
            @foreach($items as $item)
                <div class="row">
                    <span>{{ $item->product->name }} ({{ $item->variant->label() }}) x{{ $item->quantity }}</span>
                    <span>Rp {{ number_format($item->subtotal(), 0, ',', '.') }}</span>
                </div>
            @endforeach
            <div class="row"><span>Subtotal</span><span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span></div>
            <div class="row"><span>Ongkir</span><span>Rp {{ number_format($shipping, 0, ',', '.') }}</span></div>
            <div class="row total"><span>Total</span><span>Rp {{ number_format($subtotal + $shipping, 0, ',', '.') }}</span></div>
            <button type="submit" class="btn btn-gold btn-block mt-2">Buat Pesanan</button>
        </div>
    </form>
@endif
@endsection
