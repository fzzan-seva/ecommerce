@extends('layouts.app')

@section('title', 'Edit Alamat')

@section('content')
<h1 class="text-gold" style="padding:1.5rem 0">Edit Alamat</h1>
<div class="card sans" style="max-width:600px">
    @include('shop.addresses._form', ['action' => route('addresses.update', $address), 'address' => $address, 'method' => 'PUT'])
</div>
@endsection
