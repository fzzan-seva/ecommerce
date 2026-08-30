<div class="payment-methods">
    @foreach(config('fqueensha.payment_methods') as $key => $method)
        <label class="payment-option">
            <input type="radio" name="payment_method" value="{{ $key }}" {{ old('payment_method', 'gopay') === $key ? 'checked' : '' }} required>
            <span class="payment-option-body">
                <strong>{{ $method['label'] }}</strong>
                <span class="text-muted">{{ $method['account'] }}</span>
            </span>
        </label>
    @endforeach
</div>
<p class="text-muted" style="font-size:0.85rem;margin-top:0.75rem">
    Pembayaran hanya via transfer. Lampirkan foto bukti transfer pada form di bawah — pesanan baru diproses setelah bukti terverifikasi oleh admin.
</p>
