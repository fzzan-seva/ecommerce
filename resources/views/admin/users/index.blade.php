@extends('layouts.admin')

@section('title', 'Pengguna')

@section('content')
<div class="admin-header"><h1>Daftar Pengguna</h1></div>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nama</th><th>Email</th><th>Telepon</th><th>Pesanan</th><th>Terdaftar</th><th>Aksi</th></tr></thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->phone ?? '-' }}</td>
                        <td>{{ $user->orders_count }}</td>
                        <td>{{ $user->created_at->format('d/m/Y') }}</td>
                        <td><a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline btn-sm">Detail & Alamat</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="pagination mt-2">{{ $users->links() }}</div>
</div>
@endsection
