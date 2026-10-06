<form method="POST" action="{{ $action }}">
    @csrf
    @if(isset($method)) @method($method) @endif

    <div class="form-group">
        <label>Nama Kategori</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $category->name ?? '') }}" required maxlength="100">
        <p class="text-muted" style="font-size:0.8rem;margin-top:0.35rem">Slug dibuat otomatis dari nama, misalnya "Dress Premium" menjadi dress-premium.</p>
        @error('name')<span class="text-danger">{{ $message }}</span>@enderror
    </div>

    <button type="submit" class="btn btn-gold mt-2">Simpan</button>
    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline mt-2">Batal</a>
</form>
