<?php

namespace App\Services;

use App\Models\LightningDeal;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Stock;
use App\Models\StoreSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Works out what an order really costs, from the DATABASE — never from what
 * the app sends. The app's prices are only used to tell the customer when
 * something changed since they loaded the product.
 *
 *   unit price = sale_price when > 0, else price (variation first, then product);
 *                a live lightning deal with units left gives price × (1 − deal %)
 *                when that is lower (same as the app's deal price)
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

        // Tax on/off (Settings › Payment Gateways › Checkout). When off, the
        // rate sent to the app is 0 so every app version shows no tax, and the
        // saved rate is kept for when it's switched back on.
        $enabled = true;
        try {
            if ($s && Schema::hasColumn('store_settings', 'tax_enabled') && $s->tax_enabled !== null) {
                $enabled = (bool) $s->tax_enabled;
            }
        } catch (\Throwable $e) {
            $enabled = true;
        }
        $out['tax_enabled']         = $enabled;
        $out['tax_rate_configured'] = $out['tax_rate'];
        if (!$enabled) {
            $out['tax_rate'] = 0.0;
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
    public function price(array $items, array $options = []): array
    {
        $productIds   = collect($items)->pluck('product_id')->filter()->unique()->values();
        $variationIds = collect($items)->pluck('variation_id')->filter()->unique()->values();

        $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $variations = $variationIds->isNotEmpty()
            ? ProductVariation::whereIn('id', $variationIds)->get()->keyBy('id')
            : collect();

        $priced    = [];
        $subtotal  = 0.0;
        $dealCache = [];
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

            // Lightning deal: price × (1 − %) on the regular price, if cheaper.
            if (!array_key_exists($product->id, $dealCache)) {
                $dealCache[$product->id] = LightningDeal::liveDealFor($product->id);
            }
            if ($deal = $dealCache[$product->id]) {
                $base = (float) (($variation && (float) $variation->price > 0) ? $variation->price : $product->price);
                $dealUnit = round($base * (1 - ((int) $deal->discount_percentage) / 100), 2);
                if ($dealUnit > 0 && $dealUnit < $unit) {
                    $unit = $dealUnit;
                }
            }

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

        // Scan & Shop: the customer is holding the item in the store, so the
        // shelf is the truth, not the stock count.
        if (config('shop.enforce_stock_on_order', true) && empty($options['skip_stock'])) {
            $this->assertInStock($items, $products, $variations);
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

    /**
     * Stops orders for options that don't exist or more units than are in
     * stock. Uses the same stock ledger as the product API (one grouped query).
     * Quantities of the same item on several cart lines are added together.
     */
    protected function assertInStock(array $items, $products, $variations): void
    {
        $productIds = $products->keys();
        $withOptions = ProductVariation::whereIn('product_id', $productIds)
            ->select('product_id')->distinct()->pluck('product_id')->map(fn ($id) => (int) $id)->flip();

        $rows = Stock::whereIn('product_id', $productIds)
            ->selectRaw('product_id, product_variant_id, SUM(CASE
                WHEN type IN ("in", "adjustment", "transfer_in", "return") THEN quantity
                WHEN type IN ("out", "damage", "transfer") THEN -quantity
                ELSE 0 END) as qty')
            ->groupBy('product_id', 'product_variant_id')
            ->get();

        $productStock = [];
        $variantStock = [];
        foreach ($rows as $r) {
            $productStock[(int) $r->product_id] = ($productStock[(int) $r->product_id] ?? 0) + (int) $r->qty;
            if ($r->product_variant_id) {
                $variantStock[(int) $r->product_variant_id] = ($variantStock[(int) $r->product_variant_id] ?? 0) + (int) $r->qty;
            }
        }

        $wanted = [];   // "p:v" => [qty, index, product, variation]
        foreach ($items as $i => $item) {
            $product = $products->get($item['product_id']);
            $vid     = !empty($item['variation_id']) ? (int) $item['variation_id'] : null;

            if (!$vid && isset($withOptions[(int) $product->id])) {
                throw ValidationException::withMessages([
                    "items.$i.variation_id" => 'Please choose an option (size, colour …) for ' . $product->title . '.',
                ]);
            }

            $key = $product->id . ':' . ($vid ?? 0);
            $wanted[$key] ??= [0, $i, $product, $vid ? $variations->get($vid) : null];
            $wanted[$key][0] += max(1, (int) $item['quantity']);
        }

        foreach ($wanted as [$qty, $i, $product, $variation]) {
            $available = max(0, $variation
                ? ($variantStock[(int) $variation->id] ?? 0)
                : ($productStock[(int) $product->id] ?? 0));

            if ($qty > $available) {
                $label = $product->title;
                if ($variation && is_array($variation->attributes) && $variation->attributes) {
                    $label .= ' (' . implode(', ', array_map('strval', $variation->attributes)) . ')';
                }
                throw ValidationException::withMessages([
                    "items.$i.quantity" => $available > 0
                        ? "Only {$available} left of {$label}. Please reduce the quantity."
                        : "{$label} is sold out.",
                ]);
            }
        }
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
