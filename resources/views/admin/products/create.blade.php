@extends('layouts.admin')

@section('title', 'Tambah Produk')

@section('content')
<div class="admin-header"><h1>Tambah Produk</h1></div>
<div class="card" style="max-width:700px">
    @include('admin.products._form', ['action' => route('admin.products.store')])
</div>
@endsection
