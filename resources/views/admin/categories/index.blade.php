@extends('layouts.admin')

@section('title', 'Kategori')

@section('content')
<div class="admin-header flex justify-between items-center">
    <h1>Kategori</h1>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-gold btn-sm">+ Tambah Kategori</a>
</div>

<div class="card">
    @if($categories->isEmpty())
        <p class="text-muted">Belum ada kategori. Buat kategori pertama untuk mengelompokkan produk.</p>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Nama</th><th>Slug</th><th>Jumlah Produk</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td class="text-muted">{{ $category->slug }}</td>
                            <td>{{ $category->products_count }}</td>
                            <td class="flex gap-1">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-outline btn-sm">Edit</a>
                                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Hapus kategori ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination mt-2">{{ $categories->links() }}</div>
    @endif
</div>
@endsection
