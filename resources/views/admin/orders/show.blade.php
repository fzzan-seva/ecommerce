@extends('layouts.admin')

@section('title', $order->order_number)

@section('content')
<div class="admin-header"><h1>Pesanan {{ $order->order_number }}</h1></div>

<div class="card">
    <p><strong>Pelanggan:</strong> {{ $order->user->name }} ({{ $order->user->email }})</p>
    <p><strong>Penerima:</strong> {{ $order->recipient_name }} — {{ $order->phone }}</p>
    <p><strong>Alamat:</strong> {{ $order->shipping_address }}</p>
    <p><strong>Pembayaran:</strong> {{ $order->paymentMethodLabel() }} — {{ $order->paymentAccount() }}</p>

    <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="flex gap-1 items-center mt-2">
        @csrf @method('PATCH')
        <label>Update Status:</label>
        <select name="status" class="form-control" style="width:auto">
            @foreach(['pending','paid','processing','shipped','completed','cancelled'] as $s)
                <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-gold btn-sm">Simpan</button>
    </form>
</div>

<div class="card">
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
</div>
<a href="{{ route('admin.orders.index') }}" class="btn btn-outline btn-sm">Kembali</a>
@endsection
