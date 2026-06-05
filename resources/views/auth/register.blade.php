@extends('layouts.app')

@section('title', 'Daftar')

@section('content')
<div class="auth-card">
    <h2>Daftar FQueensha</h2>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="form-group sans">
            <label>Nama Lengkap</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        </div>
        <div class="form-group sans">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
        </div>
        <div class="form-group sans">
            <label>No. Telepon</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
        </div>
        <div class="form-group sans">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-group sans">
            <label>Konfirmasi Password</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-gold btn-block">Daftar</button>
    </form>
    <p class="text-muted sans mt-2" style="text-align:center">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
</div>
@endsection
