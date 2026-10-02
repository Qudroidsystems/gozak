<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\StoreSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Works out what an order really costs, from the DATABASE — never from what
 * the app sends. The app's prices are only used to tell the customer when
 * something changed since they loaded the product.
 *
 *   unit price = sale_price when > 0, else price (variation first, then product)
 *   shipping   = shipping_fee, or 0 when subtotal >= free_shipping_threshold (> 0)
 *   tax        = subtotal × tax_rate %
 *   total      = subtotal + shipping + tax
 *
 * The rates live on store_settings (Settings › Payment Gateways › Checkout).
 */
class OrderPricingService
{
    public const DEFAULTS = [
        'tax_rate'                => 5.0,     // %
        'shipping_fee'            => 500.0,   // ₦
        'free_shipping_threshold' => 10000.0, // ₦ (0 = never free)
    ];

    /** Checkout rates (also served to the app by GET /api/checkout/settings). */
    public function settings(): array
    {
        $s   = null;
        $out = self::DEFAULTS;
        try {
            if (Schema::hasColumn('store_settings', 'tax_rate')) {
                $s = StoreSetting::getSettings();
            }
        } catch (\Throwable $e) {
            $s = null;
        }
        if ($s) {
            foreach (array_keys(self::DEFAULTS) as $k) {
                if ($s->{$k} !== null) {
                    $out[$k] = (float) $s->{$k};
                }
            }
        }
        $out['currency'] = 'NGN';
        $out['currency_symbol'] = '₦';
        return $out;
    }

    /**
     * @param  array $items  [['product_id', 'variation_id'?, 'quantity', 'price'?], ...]
     * @return array ['items' => [...priced items], 'subtotal', 'shipping', 'tax', 'total', 'changed' => bool]
     * @throws ValidationException when a product/variation doesn't exist or doesn't match
     */
    public function price(array $items): array
    {
        $productIds   = collect($items)->pluck('product_id')->filter()->unique()->values();
        $variationIds = collect($items)->pluck('variation_id')->filter()->unique()->values();

        $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $variations = $variationIds->isNotEmpty()
            ? ProductVariation::whereIn('id', $variationIds)->get()->keyBy('id')
            : collect();

        $priced   = [];
        $subtotal = 0.0;
        $changed  = false;

        foreach ($items as $i => $item) {
            $product = $products->get($item['product_id']);
            if (!$product) {
                throw ValidationException::withMessages(["items.$i.product_id" => 'This product is no longer available.']);
            }

            $variation = null;
            if (!empty($item['variation_id'])) {
                $variation = $variations->get($item['variation_id']);
                if (!$variation || (int) $variation->product_id !== (int) $product->id) {
                    throw ValidationException::withMessages(["items.$i.variation_id" => 'The selected option is no longer available for ' . $product->title . '.']);
                }
            }

            $unit = $this->unitPrice($product, $variation);
            $qty  = max(1, (int) $item['quantity']);

            if (isset($item['price']) && abs((float) $item['price'] - $unit) >= 0.01) {
                $changed = true;
            }

            $priced[] = array_merge($item, [
                'price'    => $unit,
                'quantity' => $qty,
                'title'    => $product->title ?: ($item['title'] ?? 'Product'),
            ]);
            $subtotal += $unit * $qty;
        }

        $subtotal = round($subtotal, 2);
        $rates    = $this->settings();
        $shipping = ($rates['free_shipping_threshold'] > 0 && $subtotal >= $rates['free_shipping_threshold'])
            ? 0.0
            : round($rates['shipping_fee'], 2);
        $tax   = round($subtotal * $rates['tax_rate'] / 100, 2);
        $total = round($subtotal + $shipping + $tax, 2);

        return compact('subtotal', 'shipping', 'tax', 'total', 'changed') + ['items' => $priced];
    }

    protected function unitPrice(Product $product, ?ProductVariation $variation): float
    {
        foreach ([$variation, $product] as $m) {
            if (!$m) continue;
            $sale  = (float) ($m->sale_price ?? 0);
            $price = (float) ($m->price ?? 0);
            if ($sale > 0) return round($sale, 2);
            if ($price > 0) return round($price, 2);
        }
        return 0.0;
    }
}
