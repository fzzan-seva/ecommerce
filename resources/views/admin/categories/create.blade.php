@extends('layouts.admin')

@section('title', 'Tambah Kategori')

@section('content')
<div class="admin-header"><h1>Tambah Kategori</h1></div>
<div class="card" style="max-width:600px">
    @include('admin.categories._form', ['action' => route('admin.categories.store')])
</div>
@endsection
