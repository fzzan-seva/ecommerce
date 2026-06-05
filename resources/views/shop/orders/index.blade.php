@extends('layouts.app')

@section('title', 'Pesanan Saya')

@section('content')
<h1 class="text-gold" style="padding:1.5rem 0 1rem">Pesanan Saya</h1>

@if($orders->isEmpty())
    <div class="empty-state sans"><h3>Belum ada pesanan</h3><a href="{{ route('home') }}" class="btn btn-gold mt-2">Belanja</a></div>
@else
    @foreach($orders as $order)
        <div class="card sans mb-2">
            <div class="flex justify-between items-center">
                <div>
                    <strong>{{ $order->order_number }}</strong>
                    <span class="status-badge status-{{ $order->status }}">{{ $order->statusLabel() }}</span>
                    <p class="text-muted">{{ $order->created_at->format('d M Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-gold">{{ $order->formattedTotal() }}</p>
                    <a href="{{ route('orders.show', $order) }}" class="btn btn-outline btn-sm">Detail</a>
                </div>
            </div>
        </div>
    @endforeach
    <div class="pagination sans">{{ $orders->links() }}</div>
@endif
@endsection
