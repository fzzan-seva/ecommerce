@extends('layouts.admin')

@section('title', 'Edit Kategori')

@section('content')
<div class="admin-header"><h1>Edit Kategori</h1></div>
<div class="card" style="max-width:600px">
    @include('admin.categories._form', ['action' => route('admin.categories.update', $category), 'category' => $category, 'method' => 'PUT'])
</div>
@endsection
