<?php

use App\Support\Shop;

if (! function_exists('shop')) {
    /**
     * The store's runtime configuration (Admin > Store Settings over config/shop.php).
     */
    function shop(): Shop
    {
        return app(Shop::class);
    }
}

if (! function_exists('price')) {
    /**
     * Format an amount with the configured currency, e.g. "Rp 150,000".
     *
     * Every price in the application goes through this helper.
     */
    function price(float|int|string $amount): string
    {
        return shop()->formatPrice($amount);
    }
}
