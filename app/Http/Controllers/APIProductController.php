<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Models\LightningDeal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @group Products
 *
 * Product catalog endpoints with real-time stock calculation,
 * attribute/variation formatting, and related products logic.
 */
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

    private function getStockStatus($stock)
    {
        if ($stock > 10) return 'in_stock';
        if ($stock > 0)  return 'low_stock';
        return 'out_of_stock';
    }

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

    private function formatAttributeValues($values)
    {
        if (is_string($values)) {
            try {
                $decoded = json_decode($values, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            } catch (\Exception $e) {
                // fallback
            }
            return array_map('trim', explode(',', $values));
        }
        if (is_array($values)) {
            return $values;
        }
        return [];
    }

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

    private function formatProductVariations($variations, $productId)
    {
        if (!$variations || $variations->isEmpty()) {
            return [];
        }

        return $variations->map(function ($var) use ($productId) {
            $cleanImagePath = $var->image
                ? preg_replace('/^storage\//', '', $var->image)
                : null;

            $realStock = $this->calculateProductStock($productId, $var->id);

            $attributes = is_string($var->attributes)
                ? json_decode($var->attributes, true) ?? []
                : ($var->attributes ?? []);

            $salePrice = $var->sale_price > 0 ? (float) $var->sale_price : null;

            return [
                'id'              => (int) $var->id,
                'sku'             => $var->sku ?? '',
                'barcode'         => $var->barcode ?? '',
                'price'           => (float) $var->price,
                'sale_price'      => $salePrice,
                'stock'           => (int) $realStock,
                'real_time_stock' => (int) $realStock,
                'stock_status'    => $this->getStockStatus($realStock),
                'attributes'      => $attributes,
                'image'           => $cleanImagePath
                    ? url(Storage::url($cleanImagePath))
                    : null,
                'is_in_stock'     => $realStock > 0,
                'is_on_sale'      => $salePrice !== null && $salePrice < $var->price,
                'effective_price' => $salePrice ?? (float) $var->price,
            ];
        })->toArray();
    }

    /**
     * Format complete product data with real-time calculations.
     *
     * Expects the product to have been loaded with:
     *   ->withAvg('reviews', 'rating')
     *   ->withCount('reviews')
     *
     * so that $product->reviews_avg_rating and $product->reviews_count
     * are available without extra queries per product.
     */
    private function formatProductData($product)
    {
        $realStock = $this->calculateProductStock($product->id);

        $attributes = $product->attributes && $product->attributes->isNotEmpty()
            ? $this->formatProductAttributes($product->attributes)
            : $this->extractAttributesFromVariations($product->variations);

        $salePrice = $product->sale_price > 0 ? (float) $product->sale_price : null;

        $brand = $product->brand ? [
            'id'   => (int) $product->brand->id,
            'name' => $product->brand->name ?? '',
            'logo' => $product->brand->logo
                ? url(Storage::url(preg_replace('/^storage\//', '', $product->brand->logo)))
                : null,
        ] : null;

        $category = $product->category ? [
            'id'   => (int) $product->category->id,
            'name' => $product->category->name ?? '',
        ] : null;

        $images = $product->images && $product->images->isNotEmpty()
            ? $product->images
                ->map(fn($img) => url(Storage::url(preg_replace('/^storage\//', '', $img->image_path))))
                ->filter()
                ->values()
                ->toArray()
            : [];

        $variations = $this->formatProductVariations($product->variations, $product->id);

        // ── Rating aggregates ─────────────────────────────────────────────
        // withAvg / withCount append virtual attributes to the model.
        // We round avg to 1 decimal (e.g. 4.3) and cast count to int.
        $avgRating   = round((float) ($product->reviews_avg_rating ?? 0), 1);
        $reviewCount = (int) ($product->reviews_count ?? 0);

        return [
            'id'                  => (int) $product->id,
            'title'               => $product->title ?? '',
            'sku'                 => $product->sku ?? '',
            'stock'               => (int) $realStock,
            'price'               => (float) $product->price,
            'sale_price'          => $salePrice,
            'thumbnail'           => $product->thumbnail
                ? url(Storage::url(preg_replace('/^storage\//', '', $product->thumbnail)))
                : null,
            'description'         => $product->description ?? '',
            'product_type'        => $product->product_type ?? '',
            'sold_quantity'       => (int) ($product->sold_quantity ?? 0),
            'is_featured'         => (bool) ($product->is_featured ?? false),
            'is_new'              => (bool) ($product->is_new ?? false),
            'is_trending'         => (bool) ($product->is_trending ?? false),
            'is_top_rated'        => (bool) ($product->is_top_rated ?? false),
            'category_id'         => $product->category_id ? (int) $product->category_id : null,
            'brand_id'            => $product->brand_id ? (int) $product->brand_id : null,
            'real_time_stock'     => (int) $realStock,
            'stock_status'        => $this->getStockStatus($realStock),
            // ── Rating ───────────────────────────────────────────────────
            'average_rating'      => $avgRating,
            'review_count'        => $reviewCount,
            // ─────────────────────────────────────────────────────────────
            'brand'               => $brand,
            'category'            => $category,
            'images'              => $images,
            'product_attributes'  => $attributes,
            'product_variations'  => $variations,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHARED QUERY BUILDER
    // Centralises the with() + withAvg() + withCount() so every action
    // (index, show, related) automatically includes rating data.
    // ─────────────────────────────────────────────────────────────────────────
    private function baseQuery()
    {
        return Product::query()
            ->with([
                'category:id,name',
                'brand:id,name,logo',
                'attributes:id,product_id,name,values',
                'variations:id,product_id,sku,barcode,price,sale_price,attributes,image',
                'images:id,product_id,image_path',
            ])
            ->withAvg('reviews', 'rating')   // → reviews_avg_rating
            ->withCount('reviews');           // → reviews_count
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Apply a category filter that matches EITHER:
    //   - the product's direct `category_id` column, OR
    //   - a row in the `product_category` pivot table (Product::categories())
    //
    // Products can be linked to a category via either mechanism depending on
    // how they were created/assigned in the admin panel, so both must be
    // checked or products attached only via the pivot table (e.g. many
    // subcategories) will silently return zero results.
    // ─────────────────────────────────────────────────────────────────────────
    private function applyCategoryFilter($query, $categoryId)
    {
        return $query->where(function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId)
              ->orWhereHas('categories', function ($q2) use ($categoryId) {
                  $q2->where('categories.id', $categoryId);
              });
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = $this->baseQuery();

        // FEATURED
        if ($request->has('featured') && $request->featured === 'true') {
            $query->where('is_featured', true);
        }

        // CATEGORY (direct column OR pivot table — see applyCategoryFilter)
        if ($request->has('category_id')) {
            $query = $this->applyCategoryFilter($query, $request->category_id);
        }

        // BRAND
        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // IDS
        if ($request->has('ids')) {
            $query->whereIn('id', explode(',', $request->ids));
        }

        // SEARCH
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // PRICE RANGE
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // FILTER FLAG
        if ($request->has('filter')) {
            switch ($request->filter) {
                case 'new':      $query->new();      break;
                case 'trending': $query->trending(); break;
                case 'top_rated':$query->topRated(); break;
                case 'on_sale':  $query->onSale();   break;
                default: break;
            }
        }

        try {
            // LIMITED RESPONSE
            if ($request->has('limit') && $request->limit != -1) {
                $limit    = min(max((int) $request->limit, 1), 100);
                $products = $query->latest()->take($limit)->get();
                $formatted = $products->map(fn($p) => $this->formatProductData($p))->values();

                return response()->json([
                    'success'    => true,
                    'data'       => $formatted,
                    'pagination' => [
                        'current_page' => 1,
                        'last_page'    => 1,
                        'total'        => $formatted->count(),
                    ],
                ]);
            }

            // PAGINATED RESPONSE
            $perPage  = min(max((int) $request->input('per_page', 20), 1), 100);
            $products = $query->latest()->paginate($perPage);
            $formatted = collect($products->items())
                ->map(fn($p) => $this->formatProductData($p))
                ->values();

            return response()->json([
                'success'    => true,
                'data'       => $formatted,
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page'    => $products->lastPage(),
                    'total'        => $products->total(),
                    'per_page'     => $products->perPage(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHOW
    // ─────────────────────────────────────────────────────────────────────────
    public function show($id)
    {
        try {
            $product   = $this->baseQuery()->findOrFail($id);
            $formatted = $this->formatProductData($product);

            return response()->json([
                'success' => true,
                'data'    => $formatted,
            ]);
        } catch (\Exception $e) {
            Log::error('Product not found: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Product not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RELATED
    // ─────────────────────────────────────────────────────────────────────────
    public function related(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            $limit   = min(max((int) $request->input('limit', 6), 1), 12);

            // Base query for related — reuse baseQuery() for consistent rating data
            $base = $this->baseQuery()
                ->where('id', '!=', $id)
                ->where('product_type', '!=', 'variation')
                ->select('products.*');

            // Priority 1: same category + same brand
            // (category match checks direct column OR pivot table)
            $related = $this->applyCategoryFilter(clone $base, $product->category_id)
                ->where('brand_id', $product->brand_id)
                ->whereNotNull('brand_id')
                ->orderByDesc('sold_quantity')
                ->take($limit)
                ->get();

            // Priority 2: same category
            if ($related->count() < $limit / 2) {
                $more = $this->applyCategoryFilter(clone $base, $product->category_id)
                    ->orderByDesc('sold_quantity')
                    ->take($limit - $related->count())
                    ->get();

                $related = $related->merge($more);
            }

            // Priority 3: global fallback
            if ($related->count() < 3) {
                $fallback = (clone $base)
                    ->orderByDesc('sold_quantity')
                    ->take($limit - $related->count())
                    ->get();

                $related = $related->merge($fallback);
            }

            $formatted = $related
                ->map(fn($p) => $this->formatProductData($p))
                ->values();

            return response()->json([
                'success'    => true,
                'data'       => $formatted,
                'count'      => $formatted->count(),
                'product_id' => (int) $id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get related products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Could not load related products',
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LIGHTNING DEALS
    // ─────────────────────────────────────────────────────────────────────────
    public function lightningDeals()
    {
        try {
            $now = now()->timezone('Africa/Lagos');

            Log::info('🔥 Lightning Deals Request Started', [
                'now' => $now->toDateTimeString(),
            ]);

            $total  = LightningDeal::count();
            $active = LightningDeal::where('is_active', 1)->count();

            Log::info('📊 Deals in DB', ['total' => $total, 'active' => $active]);

            // Debug individual deal time windows
            LightningDeal::where('is_active', 1)->get()->each(function ($deal) use ($now) {
                Log::info('⏰ Deal Time Check', [
                    'deal_id'  => $deal->id,
                    'starts_at'=> $deal->starts_at,
                    'ends_at'  => $deal->ends_at,
                    'now'      => $now,
                    'is_valid' => ($deal->starts_at <= $now && $deal->ends_at >= $now) ? 'YES' : 'NO',
                ]);
            });

            $deals = LightningDeal::with(['product' => function ($q) {
                    $q->select('id', 'title', 'price', 'thumbnail');
                }])
                ->where('is_active', 1)
                ->where('starts_at', '<=', $now)
                ->where('ends_at', '>=', $now)
                ->orderBy('sort_order', 'asc')
                ->get();

            Log::info('📦 Filtered deals count', ['count' => $deals->count()]);

            $mapped = $deals->map(function ($deal) {
                if (!$deal->product) {
                    Log::warning('⚠️ Missing product', [
                        'deal_id'    => $deal->id,
                        'product_id' => $deal->product_id,
                    ]);
                    return null;
                }

                $price      = (float) $deal->product->price;
                $discounted = $deal->discount_percentage > 0
                    ? $price - (($deal->discount_percentage / 100) * $price)
                    : $price;

                return [
                    'id'                  => $deal->id,
                    'product_id'          => $deal->product_id,
                    'title'               => $deal->product->title ?? '',
                    'thumbnail'           => $deal->product->thumbnail
                        ? url(Storage::url(preg_replace('/^storage\//', '', $deal->product->thumbnail)))
                        : null,
                    'original_price'      => round($price, 2),
                    'discounted_price'    => round($discounted, 2),
                    'discount_percentage' => (int) $deal->discount_percentage,
                    'stock_left'          => (int) $deal->stock_limit,
                    'starts_at'           => $deal->starts_at,
                    'ends_at'             => $deal->ends_at,
                ];
            })->filter()->values();

            Log::info('✅ Final response count', ['count' => $mapped->count()]);

            return response()->json([
                'success' => true,
                'data'    => $mapped,
                'count'   => $mapped->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Lightning Deals Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'data'    => [],
                'count'   => 0,
                'message' => 'Error fetching lightning deals',
            ], 500);
        }
    }
}
