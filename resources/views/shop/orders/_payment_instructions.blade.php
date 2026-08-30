@if($order->status === 'pending')
    <div class="alert alert-success sans mt-2">
        <strong>Instruksi Pembayaran</strong>
        <p class="mt-1">Transfer <strong class="text-gold">{{ $order->formattedTotal() }}</strong> ke:</p>
        <p><strong>{{ $order->paymentMethodLabel() }}</strong> — {{ $order->paymentAccount() }}</p>
        @if($order->paymentProofUrl())
            <p class="mt-1">Bukti transfer Anda sudah dilampirkan dan sedang diverifikasi oleh admin.</p>
        @endif
    </div>
@endif