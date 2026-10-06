@php($methods = shop()->enabledPaymentMethods())
@php($defaultMethod = shop()->defaultPaymentMethod())
<div class="payment-methods">
    @forelse($methods as $key => $method)
        <label class="payment-option">
            <input type="radio" name="payment_method" value="{{ $key }}" {{ old('payment_method', $defaultMethod) === $key ? 'checked' : '' }} required>
            <span class="payment-option-body">
                <strong>{{ $method['label'] }}</strong>
                <span class="text-muted">{{ $method['account'] }}{{ $method['account_name'] !== '' ? ' — a.n. ' . $method['account_name'] : '' }}</span>
            </span>
        </label>
    @empty
        <p class="alert alert-error">Belum ada metode pembayaran yang aktif. Hubungi admin toko.</p>
    @endforelse
</div>
<p class="text-muted" style="font-size:0.85rem;margin-top:0.75rem">
    Unggah foto bukti transfer pada form di bawah — pesanan diproses setelah bukti diverifikasi oleh admin.
</p>
