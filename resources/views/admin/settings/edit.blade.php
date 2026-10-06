@extends('layouts.admin')

@section('title', 'Pengaturan Toko')

@section('content')
<div class="admin-header"><h1>Pengaturan Toko</h1></div>

<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="sans" style="max-width:820px">
    @csrf @method('PUT')

    <div class="card">
        <h3>Identitas Toko</h3>

        <div class="form-group">
            <label>Nama Toko</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $settings->name()) }}" required maxlength="100">
            @error('name')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label>Tagline</label>
            <input type="text" name="tagline" class="form-control" value="{{ old('tagline', $settings->tagline()) }}" maxlength="150">
        </div>

        <div class="form-group">
            <label>Deskripsi Toko</label>
            <textarea name="description" class="form-control" rows="2" maxlength="500">{{ old('description', $settings->description()) }}</textarea>
        </div>

        <div class="form-group">
            <label>Logo</label>
            <input type="file" name="logo" class="form-control" accept="image/jpeg,image/png,image/webp">
            @error('logo')<span class="text-danger">{{ $message }}</span>@enderror
            @if($settings->logo())
                <div class="mt-1">
                    <img src="{{ asset('storage/' . $settings->logo()) }}" alt="Logo" style="max-width:160px;border-radius:4px">
                    <label style="display:block;margin-top:0.5rem">
                        <input type="checkbox" name="remove_logo" value="1"> Hapus logo
                    </label>
                </div>
            @endif
        </div>

        <div class="form-group">
            <label>Telepon</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $settings->phone() ?? '') }}" maxlength="30">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $settings->email() ?? '') }}" maxlength="100">
            @error('email')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label>Alamat Toko</label>
            <input type="text" name="address" class="form-control" value="{{ old('address', $settings->address() ?? '') }}" maxlength="255">
        </div>
    </div>

    <div class="card">
        <h3>Mata Uang &amp; Pengiriman</h3>

        <div class="form-group">
            <label>Kode Mata Uang</label>
            <input type="text" name="currency" class="form-control" value="{{ old('currency', $settings->currency()) }}" required maxlength="10" placeholder="IDR">
            @error('currency')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label>Simbol Mata Uang</label>
            <input type="text" name="currency_symbol" class="form-control" value="{{ old('currency_symbol', $settings->currencySymbol()) }}" required maxlength="6" placeholder="Rp">
            @error('currency_symbol')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label>Desimal Harga</label>
            <input type="number" name="currency_decimals" class="form-control" value="{{ old('currency_decimals', $settings->currencyDecimals()) }}" required min="0" max="4">
            <p class="text-muted" style="font-size:0.8rem">0 untuk tanpa desimal (misal Rp 150.000), 2 untuk dua desimal (misal 150,000.00).</p>
            @error('currency_decimals')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label>Ongkos Kirim (flat)</label>
            <input type="number" name="shipping_cost" class="form-control" value="{{ old('shipping_cost', $settings->shippingCost()) }}" required min="0" step="1">
            <p class="text-muted" style="font-size:0.8rem">Dipakai untuk ringkasan keranjang, checkout, dan nilai yang disimpan pada setiap pesanan.</p>
            @error('shipping_cost')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>

    <div class="card">
        <h3>Sosial Media</h3>

        <div class="form-group">
            <label>WhatsApp (format internasional, contoh 6281234567890)</label>
            <input type="text" name="whatsapp" class="form-control" value="{{ old('whatsapp', $settings->whatsapp() ?? '') }}" maxlength="30">
        </div>

        <div class="form-group">
            <label>Instagram (username atau URL)</label>
            <input type="text" name="instagram" class="form-control" value="{{ old('instagram', $settings->instagram() ?? '') }}" maxlength="100">
        </div>

        <div class="form-group">
            <label>Facebook (username atau URL)</label>
            <input type="text" name="facebook" class="form-control" value="{{ old('facebook', $settings->facebook() ?? '') }}" maxlength="100">
        </div>

        <p class="text-muted" style="font-size:0.8rem">Kosongkan untuk menyembunyikan bagian tersebut dari halaman publik.</p>
    </div>

    <div class="card">
        <h3>Metode Pembayaran</h3>
        <p class="text-muted" style="font-size:0.85rem">
            Checkout manual: pelanggan memilih metode ini lalu mengunggah foto bukti transfer.
            Gunakan nomor rekening contoh saat pengembangan, ganti dengan rekening toko sebelum go-live.
        </p>

        @error('payment_methods')<span class="text-danger">{{ $message }}</span>@enderror

        <div id="payment-method-rows">
            @php($rows = old('payment_methods') ?? collect($methods)->map(fn ($method, $key) => $method + ['key' => $key])->values()->all())
            @foreach($rows as $i => $row)
                <div class="variant-row" style="flex-wrap:wrap;gap:0.5rem;margin-bottom:0.75rem;padding:0.75rem;border:1px solid var(--border);border-radius:6px">
                    <input type="hidden" name="payment_methods[{{ $i }}][key]" value="{{ $row['key'] ?? '' }}">
                    <input type="text" class="form-control" style="flex:1 1 130px" placeholder="Kode (bank_transfer)" value="{{ $row['key'] ?? '' }}" data-key-field>
                    <input type="text" name="payment_methods[{{ $i }}][label]" class="form-control" style="flex:1 1 150px" placeholder="Nama metode" value="{{ $row['label'] ?? '' }}" required>
                    <input type="text" name="payment_methods[{{ $i }}][type]" class="form-control" style="flex:0 1 110px" placeholder="Tipe" value="{{ $row['type'] ?? 'bank' }}">
                    <input type="text" name="payment_methods[{{ $i }}][account]" class="form-control" style="flex:1 1 170px" placeholder="Nomor rekening" value="{{ $row['account'] ?? '' }}" required>
                    <input type="text" name="payment_methods[{{ $i }}][account_name]" class="form-control" style="flex:1 1 170px" placeholder="Atas nama" value="{{ $row['account_name'] ?? '' }}">
                    <label style="display:flex;align-items:center;gap:0.35rem;white-space:nowrap">
                        <input type="checkbox" name="payment_methods[{{ $i }}][enabled]" value="1" {{ ($row['enabled'] ?? true) ? 'checked' : '' }}> Aktif
                    </label>
                    <button type="button" class="btn btn-danger btn-sm payment-method-remove" onclick="removePaymentMethodRow(this)">×</button>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-outline btn-sm mt-1" onclick="addPaymentMethodRow()">+ Tambah Metode</button>
    </div>

    <button type="submit" class="btn btn-gold mt-2">Simpan Pengaturan</button>
</form>
@endsection

@push('scripts')
<script>
let paymentMethodIndex = document.querySelectorAll('#payment-method-rows .variant-row').length;
function addPaymentMethodRow() {
    const i = paymentMethodIndex++;
    const row = document.createElement('div');
    row.className = 'variant-row';
    row.style.cssText = 'flex-wrap:wrap;gap:0.5rem;margin-bottom:0.75rem;padding:0.75rem;border:1px solid var(--border);border-radius:6px';
    row.innerHTML = `
        <input type="hidden" name="payment_methods[${i}][key]" value="">
        <input type="text" class="form-control" style="flex:1 1 130px" placeholder="Kode (bank_transfer)" value="" data-key-field>
        <input type="text" name="payment_methods[${i}][label]" class="form-control" style="flex:1 1 150px" placeholder="Nama metode" required>
        <input type="text" name="payment_methods[${i}][type]" class="form-control" style="flex:0 1 110px" placeholder="Tipe" value="bank">
        <input type="text" name="payment_methods[${i}][account]" class="form-control" style="flex:1 1 170px" placeholder="Nomor rekening" required>
        <input type="text" name="payment_methods[${i}][account_name]" class="form-control" style="flex:1 1 170px" placeholder="Atas nama">
        <label style="display:flex;align-items:center;gap:0.35rem;white-space:nowrap">
            <input type="checkbox" name="payment_methods[${i}][enabled]" value="1" checked> Aktif
        </label>
        <button type="button" class="btn btn-danger btn-sm payment-method-remove" onclick="removePaymentMethodRow(this)">×</button>
    `;
    document.getElementById('payment-method-rows').appendChild(row);
}
function removePaymentMethodRow(btn) {
    const rows = document.querySelectorAll('#payment-method-rows .variant-row');
    if (rows.length > 1) btn.closest('.variant-row').remove();
}
// Keep the hidden key field in sync with the visible key input.
document.addEventListener('input', function (e) {
    if (e.target.matches('[data-key-field]')) {
        e.target.previousElementSibling.value = e.target.value.trim().toLowerCase().replace(/[^a-z0-9_]+/g, '_');
    }
});
</script>
@endpush
