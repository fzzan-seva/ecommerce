<form method="POST" action="{{ $action }}">
    @csrf
    @if(isset($method)) @method($method) @endif
    <div class="form-group">
        <label>Label</label>
        <input type="text" name="label" class="form-control" value="{{ old('label', $address->label ?? 'Rumah') }}" required>
    </div>
    <div class="form-group">
        <label>Nama Penerima</label>
        <input type="text" name="recipient_name" class="form-control" value="{{ old('recipient_name', $address->recipient_name ?? auth()->user()->name) }}" required>
    </div>
    <div class="form-group">
        <label>Telepon</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $address->phone ?? auth()->user()->phone) }}" required>
    </div>
    <div class="form-group">
        <label>Alamat Lengkap</label>
        <textarea name="address_line" class="form-control" required>{{ old('address_line', $address->address_line ?? '') }}</textarea>
    </div>
    <div class="form-group">
        <label>Kota</label>
        <input type="text" name="city" class="form-control" value="{{ old('city', $address->city ?? '') }}" required>
    </div>
    <div class="form-group">
        <label>Provinsi</label>
        <input type="text" name="province" class="form-control" value="{{ old('province', $address->province ?? '') }}" required>
    </div>
    <div class="form-group">
        <label>Kode Pos</label>
        <input type="text" name="postal_code" class="form-control" value="{{ old('postal_code', $address->postal_code ?? '') }}" required>
    </div>
    <div class="form-check">
        <input type="checkbox" name="is_default" value="1" id="is_default" {{ old('is_default', $address->is_default ?? false) ? 'checked' : '' }}>
        <label for="is_default">Jadikan alamat utama</label>
    </div>
    <button type="submit" class="btn btn-gold mt-2">Simpan</button>
    <a href="{{ route('addresses.index') }}" class="btn btn-outline mt-2">Batal</a>
</form>
