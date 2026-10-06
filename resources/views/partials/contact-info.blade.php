@php($hasContact = shop()->address() || shop()->whatsapp() || shop()->instagram() || shop()->facebook())
@if($hasContact)
<div class="contact-info sans">
    <div class="contact-grid">
        @if(shop()->address())
            <div class="contact-item">
                <strong class="text-gold">Alamat</strong>
                <p>{{ shop()->address() }}</p>
            </div>
        @endif
        @if(shop()->phone())
            <div class="contact-item">
                <strong class="text-gold">Telepon</strong>
                <p><a href="tel:{{ preg_replace('/\s+/', '', shop()->phone()) }}">{{ shop()->phone() }}</a></p>
            </div>
        @endif
        @if(shop()->whatsapp())
            <div class="contact-item">
                <strong class="text-gold">WhatsApp</strong>
                <p><a href="{{ shop()->whatsappUrl() }}" target="_blank" rel="noopener">{{ shop()->whatsapp() }}</a></p>
            </div>
        @endif
        @if(shop()->instagram())
            <div class="contact-item">
                <strong class="text-gold">Instagram</strong>
                <p><a href="{{ shop()->instagramUrl() }}" target="_blank" rel="noopener">{{ shop()->instagram() }}</a></p>
            </div>
        @endif
        @if(shop()->facebook())
            <div class="contact-item">
                <strong class="text-gold">Facebook</strong>
                <p><a href="{{ shop()->facebookUrl() }}" target="_blank" rel="noopener">{{ shop()->facebook() }}</a></p>
            </div>
        @endif
    </div>
</div>
@endif
