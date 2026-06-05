@extends('layouts.admin')

@section('title', 'Kelola Produk')

@section('content')
<div class="admin-header flex justify-between items-center">
    <h1>Kelola Produk</h1>
    <a href="{{ route('admin.products.create') }}" class="btn btn-gold btn-sm">+ Tambah Produk</a>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Gambar</th><th>Nama</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                    <tr>
                        <td><img src="{{ $product->image_url }}" alt="" style="width:50px;height:60px;object-fit:cover;border-radius:4px"></td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->category?->name ?? '-' }}</td>
                        <td>{{ $product->formattedPrice() }}</td>
                        <td>{{ $product->totalStock() }}</td>
                        <td>{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td class="flex gap-1">
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form action="{{ route('admin.products.destroy', $product) }}" method="POST" onsubmit="return confirm('Hapus produk ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="pagination mt-2">{{ $products->links() }}</div>
</div>
@endsection
