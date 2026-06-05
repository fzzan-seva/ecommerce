<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — FQueensha</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/fqueensha.css') }}">
</head>
<body>
    <div class="admin-layout sans">
        <button type="button" class="admin-menu-toggle" id="admin-menu-toggle" aria-label="Menu Admin">
            <span></span><span></span><span></span>
        </button>
        <div class="admin-sidebar-overlay" id="admin-overlay"></div>
        <aside class="admin-sidebar" id="admin-sidebar">
            <a href="{{ route('admin.dashboard') }}" class="logo">F<span>Queensha</span></a>
            <nav class="admin-nav">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('admin.products.index') }}" class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}">Produk</a>
                <a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">Pesanan</a>
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Pengguna</a>
                <a href="{{ route('home') }}">Lihat Toko</a>
            </nav>
            <form action="{{ route('logout') }}" method="POST" class="mt-2">
                @csrf
                <button type="submit" class="btn btn-outline btn-sm btn-block">Logout</button>
            </form>
        </aside>
        <main class="admin-main">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error">
                    <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
    <script>
        const sidebar = document.getElementById('admin-sidebar');
        const overlay = document.getElementById('admin-overlay');
        const toggle = document.getElementById('admin-menu-toggle');
        function closeAdminMenu() {
            sidebar?.classList.remove('open');
            overlay?.classList.remove('open');
            toggle?.classList.remove('active');
        }
        toggle?.addEventListener('click', () => {
            sidebar?.classList.toggle('open');
            overlay?.classList.toggle('open');
            toggle?.classList.toggle('active');
        });
        overlay?.addEventListener('click', closeAdminMenu);
    </script>
</body>
</html>
