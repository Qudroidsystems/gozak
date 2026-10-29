<?php

namespace App\Services\Storefront;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Models\Stock;
use App\Services\OrderPricingService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The website cart lives in the session (guests can shop before signing in).
 * Lines are keyed "productId:variationId". Prices are always worked out by
 * OrderPricingService, the same code that prices every order, so the cart,
 * checkout and the final order can never disagree.
 */
class CartService
{
    private const KEY = 'store_cart_v1';
    public const MAX_LINES = 100;
    public const MAX_QTY = 1000;

    public function __construct(private OrderPricingService $pricing)
    {
    }

    /** ["pid:vid" => qty] */
    public function raw(): array
    {
        $cart = session(self::KEY, []);
        return is_array($cart) ? $cart : [];
    }

    public function count(): int
    {
        return (int) array_sum($this->raw());
    }

    public function isEmpty(): bool
    {
        return $this->raw() === [];
    }

    /**
     * @throws ValidationException when the product/option can't be bought
     */
    public function add(int $productId, ?int $variationId, int $qty): void
    {
        $product = Product::where(fn ($q) => $q->where('is_active', true)->orWhereNull('is_active'))->find($productId);
        if (!$product) {
            throw ValidationException::withMessages(['product_id' => 'This product is no longer available.']);
        }

        $hasOptions = ProductVariation::where('product_id', $productId)->exists();
        if ($hasOptions && !$variationId) {
            throw ValidationException::withMessages(['variation_id' => 'Please choose an option (size, colour …) first.']);
        }
        if ($variationId && !ProductVariation::where('product_id', $productId)->whereKey($variationId)->exists()) {
            throw ValidationException::withMessages(['variation_id' => 'That option is no longer available.']);
        }

        $cart = $this->raw();
        $key  = $productId . ':' . ($variationId ?: 0);
        if (!isset($cart[$key]) && count($cart) >= self::MAX_LINES) {
            throw ValidationException::withMessages(['product_id' => 'Your cart is full.']);
        }
        $cart[$key] = min(self::MAX_QTY, ($cart[$key] ?? 0) + max(1, $qty));
        session([self::KEY => $cart]);
    }

    public function setQuantity(string $key, int $qty): void
    {
        $cart = $this->raw();
        if (!isset($cart[$key])) {
            return;
        }
        if ($qty <= 0) {
            unset($cart[$key]);
        } else {
            $cart[$key] = min(self::MAX_QTY, $qty);
        }
        session([self::KEY => $cart]);
    }

    public function remove(string $key): void
    {
        $cart = $this->raw();
        unset($cart[$key]);
        session([self::KEY => $cart]);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    /** Items in the shape POST /api/orders and OrderPricingService expect. */
    public function orderItems(): array
    {
        $items = [];
        foreach ($this->raw() as $key => $qty) {
            [$pid, $vid] = array_map('intval', explode(':', $key) + [1 => 0]);
            $items[] = array_filter([
                'product_id'   => $pid,
                'variation_id' => $vid ?: null,
                'quantity'     => (int) $qty,
            ], fn ($v) => $v !== null);
        }
        return $items;
    }

    /**
     * Everything the cart / checkout pages show. Lines for products that were
     * removed are dropped. `problems` lists stock issues the customer must fix
     * before checkout (the server would refuse the order otherwise).
     */
    public function summary(): array
    {
        $raw = $this->raw();
        if (!$raw) {
            return ['lines' => [], 'subtotal' => 0.0, 'shipping' => 0.0, 'tax' => 0.0, 'total' => 0.0, 'count' => 0, 'problems' => [], 'tax_enabled' => true, 'free_shipping_threshold' => 0];
        }

        $items = $this->orderItems();
        $productIds   = collect($items)->pluck('product_id')->unique();
        $variationIds = collect($items)->pluck('variation_id')->filter()->unique();
        $products     = Product::whereIn('id', $productIds)->get(['id', 'title', 'thumbnail', 'price', 'sale_price', 'product_type'])->keyBy('id');
        $variations   = $variationIds->isNotEmpty()
            ? ProductVariation::whereIn('id', $variationIds)->get(['id', 'product_id', 'price', 'sale_price', 'attributes', 'image'])->keyBy('id')
            : collect();

        // Drop lines whose product/option disappeared.
        $clean = [];
        foreach ($items as $it) {
            $p = $products->get($it['product_id']);
            $v = isset($it['variation_id']) ? $variations->get($it['variation_id']) : null;
            if ($p && (!isset($it['variation_id']) || ($v && (int) $v->product_id === (int) $p->id))) {
                $clean[] = $it;
            }
        }
        if (count($clean) !== count($items)) {
            session([self::KEY => collect($clean)->mapWithKeys(fn ($i) => [$i['product_id'] . ':' . ($i['variation_id'] ?? 0) => $i['quantity']])->all()]);
        }
        if (!$clean) {
            return $this->summary();
        }

        $quote = $this->pricing->price($clean, ['skip_stock' => true]);
        $stock = $this->stockFor($clean);

        $lines = [];
        $problems = [];
        foreach ($quote['items'] as $it) {
            $p = $products->get($it['product_id']);
            $v = isset($it['variation_id']) ? $variations->get($it['variation_id']) : null;
            $attrs = $v ? (is_array($v->attributes) ? $v->attributes : (json_decode((string) $v->attributes, true) ?: [])) : [];
            $key = $p->id . ':' . ($v->id ?? 0);
            $available = $v ? ($stock['variants'][$v->id] ?? 0) : ($stock['products'][$p->id] ?? 0);
            $regular = (float) (($v && (float) $v->price > 0) ? $v->price : $p->price);

            $line = [
                'key'          => $key,
                'product_id'   => $p->id,
                'variation_id' => $v->id ?? null,
                'title'        => $p->title,
                'options'      => $attrs,
                'image'        => ProductImage::publicUrl(($v && $v->image) ? $v->image : $p->thumbnail),
                'url'          => route('store.product', ['id' => $p->id, 'slug' => Str::slug($p->title) ?: 'item']),
                'price'        => (float) $it['price'],
                'old_price'    => $regular > (float) $it['price'] ? $regular : null,
                'quantity'     => (int) $it['quantity'],
                'line_total'   => round((float) $it['price'] * (int) $it['quantity'], 2),
                'available'    => max(0, (int) $available),
            ];
            if (config('shop.enforce_stock_on_order', true) && $line['quantity'] > $line['available']) {
                $problems[$key] = $line['available'] > 0
                    ? "Only {$line['available']} left. Please reduce the quantity."
                    : 'Sold out. Please remove it from your cart.';
            }
            $lines[] = $line;
        }

        $settings = $this->pricing->settings();

        return [
            'lines'    => $lines,
            'subtotal' => $quote['subtotal'],
            'shipping' => $quote['shipping'],
            'tax'      => $quote['tax'],
            'total'    => $quote['total'],
            'count'    => array_sum(array_column($lines, 'quantity')),
            'problems' => $problems,
            'tax_enabled' => (bool) ($settings['tax_enabled'] ?? true) && (float) $settings['tax_rate'] > 0,
            'tax_rate'    => (float) $settings['tax_rate'],
            'free_shipping_threshold' => (float) $settings['free_shipping_threshold'],
        ];
    }

    /** Ledger stock for the cart's products and options (one query). */
    private function stockFor(array $items): array
    {
        $rows = Stock::whereIn('product_id', collect($items)->pluck('product_id')->unique())
            ->selectRaw('product_id, product_variant_id, SUM(CASE
                WHEN type IN ("in", "adjustment", "transfer_in", "return") THEN quantity
                WHEN type IN ("out", "damage", "transfer") THEN -quantity
                ELSE 0 END) as qty')
            ->groupBy('product_id', 'product_variant_id')
            ->get();
        $out = ['products' => [], 'variants' => []];
        foreach ($rows as $r) {
            $out['products'][$r->product_id] = ($out['products'][$r->product_id] ?? 0) + (int) $r->qty;
            if ($r->product_variant_id) {
                $out['variants'][$r->product_variant_id] = ($out['variants'][$r->product_variant_id] ?? 0) + (int) $r->qty;
            }
        }
        return $out;
    }
}
