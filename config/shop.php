<?php

return [
    /*
    | Block orders for more units than are in stock (and require a size/colour
    | to be chosen on variable products). Turn off with SHOP_ENFORCE_STOCK=false
    | in .env if your stock counts are not kept up to date yet.
    */
    'enforce_stock_on_order' => env('SHOP_ENFORCE_STOCK', true),

    /*
    | Customer-facing website(s) built on this API (the Nuxt storefront).
    | After paying, Paystack/OPay send the shopper back here only when the
    | return address starts with one of these. Comma-separated in .env.
    */
    'storefront_urls' => array_values(array_filter(array_map(
        fn ($u) => rtrim(trim($u), '/'),
        explode(',', env('STOREFRONT_URLS', 'https://gozakmart.ng,https://www.gozakmart.ng,http://localhost:3000'))
    ))),
];
