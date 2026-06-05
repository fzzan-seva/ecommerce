@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="admin-header">
    <h1>Dashboard Admin</h1>
    <p class="text-muted">Selamat datang, {{ auth()->user()->name }}</p>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="value">{{ $stats['products'] }}</div><div class="label">Produk</div></div>
    <div class="stat-card"><div class="value">{{ $stats['users'] }}</div><div class="label">Pengguna</div></div>
    <div class="stat-card"><div class="value">{{ $stats['orders'] }}</div><div class="label">Total Pesanan</div></div>
    <div class="stat-card"><div class="value">{{ $stats['pending_orders'] }}</div><div class="label">Menunggu</div></div>
    <div class="stat-card"><div class="value" style="font-size:1.1rem">Rp {{ number_format($stats['revenue'], 0, ',', '.') }}</div><div class="label">Pendapatan</div></div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <h3>Pesanan Terbaru</h3>
        <div class="table-wrap">
            <table>
                <thead><tr><th>No. Order</th><th>User</th><th>Total</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach($recentOrders as $order)
                        <tr>
                            <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td>
                            <td>{{ $order->user->name }}</td>
                            <td>{{ $order->formattedTotal() }}</td>
                            <td><span class="status-badge status-{{ $order->status }}">{{ $order->statusLabel() }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <h3>Stok Menipis</h3>
        @if($lowStock->isEmpty())
            <p class="text-muted">Semua stok aman.</p>
        @else
            <ul class="low-stock-list">
                @foreach($lowStock as $variant)
                    <li>{{ $variant->product->name }} ({{ $variant->label() }}) — <strong class="text-gold">{{ $variant->stock }} pcs</strong></li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
