@extends('layouts.app')

@section('title', 'Alamat Saya')

@section('content')
<div class="flex justify-between items-center" style="padding:1.5rem 0 1rem">
    <h1 class="text-gold">Alamat Saya</h1>
    <a href="{{ route('addresses.create') }}" class="btn btn-gold btn-sm sans">+ Tambah Alamat</a>
</div>

@if($addresses->isEmpty())
    <div class="empty-state sans"><h3>Belum ada alamat</h3></div>
@else
    @foreach($addresses as $address)
        <div class="card sans mb-2">
            <div class="flex justify-between">
                <div>
                    <strong class="text-gold">{{ $address->label }}</strong>
                    @if($address->is_default)<span class="status-badge status-completed">Utama</span>@endif
                    <p>{{ $address->recipient_name }} · {{ $address->phone }}</p>
                    <p class="text-muted">{{ $address->fullAddress() }}</p>
                </div>
                <div class="flex gap-1">
                    <a href="{{ route('addresses.edit', $address) }}" class="btn btn-outline btn-sm">Edit</a>
                    <form action="{{ route('addresses.destroy', $address) }}" method="POST" onsubmit="return confirm('Hapus alamat?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endif
@endsection
