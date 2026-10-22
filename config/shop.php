<?php

return [
    /*
    | Block orders for more units than are in stock (and require a size/colour
    | to be chosen on variable products). Turn off with SHOP_ENFORCE_STOCK=false
    | in .env if your stock counts are not kept up to date yet.
    */
    'enforce_stock_on_order' => env('SHOP_ENFORCE_STOCK', true),
];
