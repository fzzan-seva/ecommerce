@extends('layouts.app')

@section('title', $order->order_number)

@section('content')
<h1 class="text-gold" style="padding:1.5rem 0">Pesanan {{ $order->order_number }}</h1>
<div class="card sans">
    <p><span class="status-badge status-{{ $order->status }}">{{ $order->statusLabel() }}</span></p>
    <p class="mt-1"><strong>Penerima:</strong> {{ $order->recipient_name }} ({{ $order->phone }})</p>
    <p><strong>Alamat:</strong> {{ $order->shipping_address }}</p>
    <p><strong>Pembayaran:</strong> {{ $order->paymentMethodLabel() }} — {{ $order->paymentAccount() }}</p>
    @include('shop.orders._payment_instructions')
    @if($order->paymentProofUrl())
        <p class="mt-1"><strong>Bukti Transfer:</strong></p>
        <a href="{{ $order->paymentProofUrl() }}" target="_blank">
            <img src="{{ $order->paymentProofUrl() }}" alt="Bukti transfer" style="max-width:260px;border-radius:6px;border:1px solid var(--border);">
        </a>
    @endif
    @if($order->notes)<p><strong>Catatan:</strong> {{ $order->notes }}</p>@endif

    <h3 class="text-gold mt-2">Item</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Produk</th><th>Varian</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr></thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->variantLabel() }}</td>
                        <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="text-gold mt-2" style="font-size:1.25rem">Total: {{ $order->formattedTotal() }}</p>
    <a href="{{ route('orders.index') }}" class="btn btn-outline btn-sm mt-2">Kembali</a>
</div>
@endsection
