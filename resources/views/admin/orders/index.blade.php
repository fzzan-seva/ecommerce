@extends('layouts.admin')

@section('title', 'Pesanan')

@section('content')
<div class="admin-header"><h1>Semua Pesanan</h1></div>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>No. Order</th><th>Pelanggan</th><th>Total</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead>
            <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->user->name }}</td>
                        <td>{{ $order->formattedTotal() }}</td>
                        <td><span class="status-badge status-{{ $order->status }}">{{ $order->statusLabel() }}</span></td>
                        <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline btn-sm">Detail</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="pagination mt-2">{{ $orders->links() }}</div>
</div>
@endsection
