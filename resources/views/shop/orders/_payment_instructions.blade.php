@if($order->status === 'pending')
    <div class="alert alert-success sans mt-2">
        <strong>Instruksi Pembayaran</strong>
        <p class="mt-1">Transfer <strong class="text-gold">{{ $order->formattedTotal() }}</strong> ke:</p>
        <p><strong>{{ $order->paymentMethodLabel() }}</strong> — {{ $order->paymentAccount() }}</p>
        <p class="mt-1">Setelah transfer, kirim bukti pembayaran ke WhatsApp
            <a href="https://wa.me/{{ config('fqueensha.whatsapp_link') }}?text=Konfirmasi%20pembayaran%20{{ urlencode($order->order_number) }}" target="_blank">
                {{ config('fqueensha.whatsapp') }}
            </a>
        </p>
    </div>
@endif
