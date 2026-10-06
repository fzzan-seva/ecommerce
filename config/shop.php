<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Store Identity
    |--------------------------------------------------------------------------
    |
    | These values are used for the public storefront, page titles, the admin
    | panel header, the footer and e-mails. Every value can be overridden at
    | runtime from Admin > Store Settings, which wins over the values below.
    |
    */

    'name' => env('SHOP_NAME', env('APP_NAME', 'Laravel E-Commerce')),

    'tagline' => env('SHOP_TAGLINE', 'Your online store'),

    'description' => env('SHOP_DESCRIPTION', 'A modern online store powered by Laravel'),

    'logo' => env('SHOP_LOGO', ''),

    'phone' => env('SHOP_PHONE', ''),

    'email' => env('SHOP_EMAIL', ''),

    'address' => env('SHOP_ADDRESS', ''),

    /*
    |--------------------------------------------------------------------------
    | Social Links
    |--------------------------------------------------------------------------
    |
    | Leave a value empty to hide it from the footer. WhatsApp is stored as
    | digits only in international format, e.g. 6281234567890, because it is
    | used to build the wa.me link.
    |
    */

    'whatsapp' => env('SHOP_WHATSAPP', ''),

    'instagram' => env('SHOP_INSTAGRAM', ''),

    'facebook' => env('SHOP_FACEBOOK', ''),

    /*
    |--------------------------------------------------------------------------
    | Commerce
    |--------------------------------------------------------------------------
    |
    | shipping_cost is used for the cart summary, the checkout calculation and
    | the value stored on every order, so the three always agree. It is a flat
    | rate; there is no shipping provider integration on purpose.
    |
    */

    'currency' => env('SHOP_CURRENCY', 'IDR'),

    'currency_symbol' => env('SHOP_CURRENCY_SYMBOL', 'Rp'),

    'currency_decimals' => (int) env('SHOP_CURRENCY_DECIMALS', 0),

    'shipping_cost' => (float) env('SHOP_SHIPPING_COST', 15000),

    /*
    |--------------------------------------------------------------------------
    | Orders
    |--------------------------------------------------------------------------
    |
    | order_prefix is prepended to the random part of every order number,
    | e.g. ORD-651F2A9C0B3D4.
    |
    */

    'order_prefix' => env('SHOP_ORDER_PREFIX', 'ORD'),

    /*
    |--------------------------------------------------------------------------
    | Payment Methods (manual bank / e-wallet transfer)
    |--------------------------------------------------------------------------
    |
    | The checkout asks the customer to upload a photo of the transfer
    | receipt, so only "pay first, then upload proof" methods fit this flow.
    | The accounts below are placeholders — replace them from Admin > Store
    | Settings before going live. Never commit real account numbers.
    |
    | Every method supports: label, type, account, account_name, enabled.
    | Disabled methods are hidden from checkout and rejected by validation.
    |
    */

    'payment_methods' => [
        'bank_transfer' => [
            'label' => 'Bank Transfer',
            'type' => 'bank',
            'account' => '0000000000',
            'account_name' => 'Store Owner',
            'enabled' => true,
        ],
        'e_wallet' => [
            'label' => 'E-Wallet',
            'type' => 'e-wallet',
            'account' => '000000000000',
            'account_name' => 'Store Owner',
            'enabled' => true,
        ],
    ],
];
