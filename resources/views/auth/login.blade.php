@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="auth-card">
    <h2>Masuk ke FQueensha</h2>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="form-group sans">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
        </div>
        <div class="form-group sans">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-group form-check sans">
            <input type="checkbox" name="remember" id="remember">
            <label for="remember">Ingat saya</label>
        </div>
        <button type="submit" class="btn btn-gold btn-block">Masuk</button>
    </form>
    <p class="text-muted sans mt-2" style="text-align:center">Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a></p>
</div>
@endsection
