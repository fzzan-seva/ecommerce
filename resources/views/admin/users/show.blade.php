@extends('layouts.admin')

@section('title', $user->name)

@section('content')
<div class="admin-header"><h1>{{ $user->name }}</h1></div>

<div class="card">
    <h3>Informasi Akun</h3>
    <p><strong>Email:</strong> {{ $user->email }}</p>
    <p><strong>Telepon:</strong> {{ $user->phone ?? '-' }}</p>
    <p><strong>Bergabung:</strong> {{ $user->created_at->format('d F Y') }}</p>
</div>

<div class="card">
    <h3>Alamat Pengguna ({{ $user->addresses->count() }})</h3>
    @if($user->addresses->isEmpty())
        <p class="text-muted">Belum ada alamat tersimpan.</p>
    @else
        @foreach($user->addresses as $addr)
            <div style="padding:1rem;border-bottom:1px solid var(--border)">
                <strong class="text-gold">{{ $addr->label }}</strong>
                @if($addr->is_default) <span class="status-badge status-completed">Utama</span> @endif
                <p>{{ $addr->recipient_name }} · {{ $addr->phone }}</p>
                <p class="text-muted">{{ $addr->fullAddress() }}</p>
            </div>
        @endforeach
    @endif
</div>

<div class="card">
    <h3>Riwayat Pesanan</h3>
    @if($user->orders->isEmpty())
        <p class="text-muted">Belum ada pesanan.</p>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th>No. Order</th><th>Total</th><th>Status</th><th>Tanggal</th></tr></thead>
                <tbody>
                    @foreach($user->orders as $order)
                        <tr>
                            <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td>
                            <td>{{ $order->formattedTotal() }}</td>
                            <td><span class="status-badge status-{{ $order->status }}">{{ $order->statusLabel() }}</span></td>
                            <td>{{ $order->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
<a href="{{ route('admin.users.index') }}" class="btn btn-outline btn-sm">Kembali</a>
@endsection
