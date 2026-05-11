<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\LightningDeal;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class APIProductController extends Controller
{
    /**
     * Calculate real-time stock from inventory movements
     */
    private function calculateProductStock($productId, $variationId = null)
    {
        $query = Stock::where('product_id', $productId);

        if ($variationId) {
            $query->where('product_variant_id', $variationId);
        }

        $totalStock = $query->selectRaw('
            SUM(CASE
                WHEN type IN ("in", "adjustment", "transfer_in", "return") THEN quantity
                WHEN type IN ("out", "damage", "transfer") THEN -quantity
                ELSE 0
            END) as total
        ')->value('total') ?? 0;

        return max(0, $totalStock);
    }

    /**
     * Get stock status
     */
    private function getStockStatus($stock)
    {
        if ($stock > 10) return 'in_stock';
        if ($stock > 0)  return 'low_stock';

        return 'out_of_stock';
    }

    /**
     * Format product attributes
     */
    private function formatProductAttributes($attributes)
    {
        if (!$attributes || $attributes->isEmpty()) {
            return [];
        }

        return $attributes->map(function ($attr) {
            return [
                'id'     => $attr->id ?? null,
                'name'   => $attr->name ?? '',
                'values' => $this->formatAttributeValues($attr->values),
            ];
        })->toArray();
    }

    /**
     * Format attribute values
     */
    private function formatAttributeValues($values)
    {
        if (is_string($values)) {

            try {
                $decoded = json_decode($values, true);

                if (
                    json_last_error() === JSON_ERROR_NONE &&
                    is_array($decoded)
                ) {
                    return $decoded;
                }
            } catch (\Exception $e) {
            }

            return array_map('trim', explode(',', $values));
        }

        if (is_array($values)) {
            return $values;
        }

        return [];
    }

    /**
     * Extract attributes from variations
     */
    private function extractAttributesFromVariations($variations)
    {
        if (!$variations || $variations->isEmpty()) {
            return [];
        }

        $attributes = [];

        foreach ($variations as $variation) {

            $varAttributes = $variation->attributes;

            if (is_string($varAttributes)) {
                try {
                    $varAttributes = json_decode($varAttributes, true);
                } catch (\Exception $e) {
                    continue;
                }
            }

            if (is_array($varAttributes)) {

                foreach ($varAttributes as $key => $value) {

                    if (!isset($attributes[$key])) {
                        $attributes[$key] = [];
                    }

                    if (!in_array($value, $attributes[$key])) {
                        $attributes[$key][] = $value;
                    }
                }
            }
        }

        return collect($attributes)->map(function ($values, $name) {

            return [
                'id'     => null,
                'name'   => $name,
                'values' => $values,
            ];

        })->values()->toArray();
    }

    /**
     * Format variations
     */
    private function formatProductVariations($variations, $productId)
    {
        if (!$variations || $variations->isEmpty()) {
            return [];
        }

        return $variations->map(function ($var) use ($productId) {

            $cleanImagePath = $var->image
                ? preg_replace('/^storage\//', '', $var->image)
                : null;

            $realStock = $this->calculateProductStock(
                $productId,
                $var->id
            );

            $attributes = is_string($var->attributes)
                ? json_decode($var->attributes, true) ?? []
                : ($var->attributes ?? []);

            $salePrice = $var->sale_price > 0
                ? (float) $var->sale_price
                : null;

            return [
                'id'               => (int) $var->id,
                'sku'              => $var->sku ?? '',
                'barcode'          => $var->barcode ?? '',
                'price'            => (float) $var->price,
                'sale_price'       => $salePrice,
                'stock'            => (int) $realStock,
                'real_time_stock'  => (int) $realStock,
                'stock_status'     => $this->getStockStatus($realStock),
                'attributes'       => $attributes,
                'image'            => $cleanImagePath
                    ? url(Storage::url($cleanImagePath))
                    : null,
                'is_in_stock'      => $realStock > 0,
                'is_on_sale'       => $salePrice !== null &&
                    $salePrice < $var->price,
                'effective_price'  => $salePrice ??
                    (float) $var->price,
            ];

        })->toArray();
    }

    /**
     * Format product data
     */
    private function formatProductData($product)
    {
        $realStock = $this->calculateProductStock($product->id);

        $attributes = $product->attributes &&
            $product->attributes->isNotEmpty()
            ? $this->formatProductAttributes($product->attributes)
            : $this->extractAttributesFromVariations($product->variations);

        $salePrice = $product->sale_price > 0
            ? (float) $product->sale_price
            : null;

        $brand = $product->brand ? [
            'id'   => (int) $product->brand->id,
            'name' => $product->brand->name ?? '',
            'logo' => $product->brand->logo
                ? url(Storage::url(
                    preg_replace(
                        '/^storage\//',
                        '',
                        $product->brand->logo
                    )
                ))
                : null,
        ] : null;

        $category = $product->category ? [
            'id'   => (int) $product->category->id,
            'name' => $product->category->name ?? '',
        ] : null;

        $images = $product->images &&
            $product->images->isNotEmpty()
            ? $product->images->map(fn($img) =>
                url(Storage::url(
                    preg_replace(
                        '/^storage\//',
                        '',
                        $img->image_path
                    )
                ))
            )->filter()->values()->toArray()
            : [];

        $variations = $this->formatProductVariations(
            $product->variations,
            $product->id
        );

        return [
            'id'                 => (int) $product->id,
            'title'              => $product->title ?? '',
            'sku'                => $product->sku ?? '',
            'stock'              => (int) $realStock,
            'price'              => (float) $product->price,
            'sale_price'         => $salePrice,

            'thumbnail'          => $product->thumbnail
                ? url(Storage::url(
                    preg_replace(
                        '/^storage\//',
                        '',
                        $product->thumbnail
                    )
                ))
                : null,

            'description'        => $product->description ?? '',
            'product_type'       => $product->product_type ?? '',
            'sold_quantity'      => (int) ($product->sold_quantity ?? 0),

            'is_featured'        => (bool) ($product->is_featured ?? false),

            // FILTER FLAGS
            'is_new'             => (bool) ($product->is_new ?? false),
            'is_trending'        => (bool) ($product->is_trending ?? false),
            'is_top_rated'       => (bool) ($product->is_top_rated ?? false),

            'category_id'        => $product->category_id
                ? (int) $product->category_id
                : null,

            'brand_id'           => $product->brand_id
                ? (int) $product->brand_id
                : null,

            'real_time_stock'    => (int) $realStock,
            'stock_status'       => $this->getStockStatus($realStock),

            'brand'              => $brand,
            'category'           => $category,
            'images'             => $images,

            'product_attributes' => $attributes,
            'product_variations' => $variations,
        ];
    }

    /**
     * PRODUCTS INDEX
     */
    public function index(Request $request)
    {
        $query = Product::query()
            ->with([
                'category:id,name',
                'brand:id,name,logo',
                'attributes:id,product_id,name,values',
                'variations:id,product_id,sku,barcode,price,sale_price,attributes,image',
                'images:id,product_id,image_path',
            ]);

        // FEATURED
        if (
            $request->has('featured') &&
            $request->featured === 'true'
        ) {
            $query->where('is_featured', true);
        }

        // CATEGORY
        if ($request->has('category_id')) {
            $query->where(
                'category_id',
                $request->category_id
            );
        }

        // BRAND
        if ($request->has('brand_id')) {
            $query->where(
                'brand_id',
                $request->brand_id
            );
        }

        // IDS
        if ($request->has('ids')) {

            $ids = explode(',', $request->ids);

            $query->whereIn('id', $ids);
        }

        // SEARCH
        if ($request->has('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where('title', 'like', "%{$search}%")
                    ->orWhere(
                        'description',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        // MIN PRICE
        if ($request->has('min_price')) {
            $query->where(
                'price',
                '>=',
                $request->min_price
            );
        }

        // MAX PRICE
        if ($request->has('max_price')) {
            $query->where(
                'price',
                '<=',
                $request->max_price
            );
        }

        // FILTER PRODUCTS
        if ($request->has('filter')) {

            switch ($request->filter) {

                case 'new':
                    $query->new();
                    break;

                case 'trending':
                    $query->trending();
                    break;

                case 'top_rated':
                    $query->topRated();
                    break;

                case 'on_sale':
                    $query->onSale();
                    break;

                case 'all':
                default:
                    break;
            }
        }

        try {

            // LIMITED RESPONSE
            if (
                $request->has('limit') &&
                $request->limit != -1
            ) {

                $limit = min(
                    max((int) $request->limit, 1),
                    100
                );

                $products = $query
                    ->latest()
                    ->take($limit)
                    ->get();

                $formatted = $products
                    ->map(fn($p) => $this->formatProductData($p))
                    ->values();

                return response()->json([
                    'success' => true,
                    'data'    => $formatted,

                    'pagination' => [
                        'current_page' => 1,
                        'last_page'    => 1,
                        'total'        => $formatted->count(),
                    ],
                ]);
            }

            // PAGINATION
            $perPage = min(
                max((int) $request->input('per_page', 20), 1),
                100
            );

            $products = $query
                ->latest()
                ->paginate($perPage);

            $formatted = collect($products->items())
                ->map(fn($p) => $this->formatProductData($p))
                ->values();

            return response()->json([
                'success' => true,
                'data'    => $formatted,

                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page'    => $products->lastPage(),
                    'total'        => $products->total(),
                    'per_page'     => $products->perPage(),
                ],
            ]);

        } catch (\Exception $e) {

            Log::error(
                'Failed to fetch products: ' .
                $e->getMessage()
            );

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
            ], 500);
        }
    }

    public function lightningDeals()
{
    try {

        $now = now();

        \Log::info('🔥 Lightning Deals Request Started', [
            'time_now' => $now,
        ]);

        // STEP 1: base query count (NO FILTERS)
        $baseQuery = LightningDeal::query();

        \Log::info('📊 Total deals in DB', [
            'total_deals' => $baseQuery->count(),
        ]);

        // STEP 2: check active only
        $activeQuery = LightningDeal::where('is_active', 1);

        \Log::info('🟢 Active deals count', [
            'active_deals' => $activeQuery->count(),
        ]);

        // STEP 3: check time window
        $timeQuery = LightningDeal::where('is_active', 1)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now);

        \Log::info('⏰ Time-valid deals count', [
            'time_valid_deals' => $timeQuery->count(),
        ]);

        // STEP 4: fetch final deals with product
        $deals = $timeQuery
            ->with('product')
            ->orderBy('sort_order', 'asc')
            ->get();

        \Log::info('📦 Final fetched deals BEFORE mapping', [
            'count' => $deals->count(),
        ]);

        // STEP 5: map response
        $mapped = $deals->map(function ($deal) {

            if (!$deal->product) {
                \Log::warning('⚠️ Missing product for deal', [
                    'deal_id' => $deal->id,
                    'product_id' => $deal->product_id,
                ]);
                return null;
            }

            $originalPrice = (float) $deal->product->price;

            $discountedPrice =
                $originalPrice -
                (($deal->discount_percentage / 100) * $originalPrice);

            return [
                'id' => $deal->id,
                'product_id' => $deal->product_id,
                'title' => $deal->product->title ?? '',
                'thumbnail' => $deal->product->thumbnail ?? null,
                'original_price' => $originalPrice,
                'discounted_price' => round($discountedPrice, 2),
                'discount_percentage' => $deal->discount_percentage,
                'stock_left' => $deal->stock_limit,
            ];
        })
        ->filter()
        ->values();

        \Log::info('✅ Final mapped deals', [
            'count' => $mapped->count(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $mapped,
            'count' => $mapped->count(),
        ]);

    } catch (\Exception $e) {

        \Log::error('❌ Lightning Deals Error', [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch lightning deals',
            'data' => [],
            'count' => 0,
        ], 500);
    }
}
}
