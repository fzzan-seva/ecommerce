<div class="contact-info sans">
    <div class="contact-grid">
        <div class="contact-item">
            <strong class="text-gold">Lokasi</strong>
            <p>{{ config('fqueensha.location') }}</p>
        </div>
        <div class="contact-item">
            <strong class="text-gold">WhatsApp</strong>
            <p><a href="https://wa.me/{{ config('fqueensha.whatsapp_link') }}" target="_blank">{{ config('fqueensha.whatsapp') }}</a></p>
        </div>
        <div class="contact-item">
            <strong class="text-gold">Instagram</strong>
            <p><a href="{{ config('fqueensha.instagram_url') }}" target="_blank">@{{ config('fqueensha.instagram') }}</a></p>
        </div>
    </div>
</div>
