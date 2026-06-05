@extends('layouts.admin')

@section('title', 'Edit Produk')

@section('content')
<div class="admin-header"><h1>Edit Produk</h1></div>
<div class="card" style="max-width:700px">
    @include('admin.products._form', ['action' => route('admin.products.update', $product), 'product' => $product, 'method' => 'PUT'])
</div>
@endsection
