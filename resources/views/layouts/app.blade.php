<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FQueensha') — Gamis Elegan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/fqueensha.css') }}">
    @stack('styles')
</head>
<body>
    <header class="site-header">
        <div class="container header-inner sans">
            <a href="{{ route('home') }}" class="logo">F<span>Queensha</span></a>

            <button type="button" class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>

            <form action="{{ route('home') }}" method="GET" class="search-form">
                <input type="text" name="q" placeholder="Cari gamis..." value="{{ request('q') }}">
                <button type="submit">Cari</button>
            </form>

            <nav class="nav-links" id="main-nav">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Beranda</a>
                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}">Admin</a>
                    @else
                        <a href="{{ route('cart.index') }}">Keranjang
                            @php $cartCount = auth()->user()->cartItems()->sum('quantity'); @endphp
                            @if($cartCount > 0)<span class="badge">{{ $cartCount }}</span>@endif
                        </a>
                        <a href="{{ route('orders.index') }}">Pesanan</a>
                        <a href="{{ route('addresses.index') }}">Alamat</a>
                    @endif
                    <form action="{{ route('logout') }}" method="POST" class="nav-logout-form">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">Login</a>
                    <a href="{{ route('register') }}" class="btn btn-gold btn-sm">Daftar</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="container">
        @if(session('success'))
            <div class="alert alert-success sans">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error sans">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error sans">
                <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="footer sans">
        @include('partials.contact-info')
        <p class="mt-2">&copy; {{ date('Y') }} <strong class="text-gold">FQueensha</strong> — Koleksi Gamis Perempuan Elegan</p>
    </footer>

    <script>
        document.getElementById('mobile-menu-toggle')?.addEventListener('click', function () {
            document.getElementById('main-nav')?.classList.toggle('open');
            this.classList.toggle('active');
        });
    </script>
    @stack('scripts')
</body>
</html>
