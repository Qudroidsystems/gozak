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
     *
     * @param int $productId
     * @param int|null $variationId
     * @return int
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
     * Get human-readable stock status
     *
     * @param int $stock
     * @return string
     */
    private function getStockStatus($stock)
    {
        if ($stock > 10) return 'in_stock';
        if ($stock > 0)  return 'low_stock';
        return 'out_of_stock';
    }

    /**
     * Format product attributes for frontend
     *
     * @param \Illuminate\Database\Eloquent\Collection $attributes
     * @return array
     */
    private function formatProductAttributes($attributes)
    {
        if (!$attributes || $attributes->isEmpty()) {
            return [];
        }

        return $attributes->map(function ($attr) {
            return [
                'id' => $attr->id ?? null,
                'name' => $attr->name ?? '',
                'values' => $this->formatAttributeValues($attr->values),
            ];
        })->toArray();
    }

    /**
     * Format attribute values (JSON or comma-separated)
     *
     * @param mixed $values
     * @return array
     */
    private function formatAttributeValues($values)
    {
        if (is_string($values)) {
            try {
                $decoded = json_decode($values, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            } catch (\Exception $e) {
                // fallback to comma-separated
            }
            return array_map('trim', explode(',', $values));
        }
        if (is_array($values)) {
            return $values;
        }
        return [];
    }

    /**
     * Extract attributes from variations when no product_attributes exist
     *
     * @param \Illuminate\Database\Eloquent\Collection $variations
     * @return array
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
                'id' => null,
                'name' => $name,
                'values' => $values,
            ];
        })->values()->toArray();
    }

    /**
     * Format variations with real-time stock
     *
     * @param \Illuminate\Database\Eloquent\Collection $variations
     * @param int $productId
     * @return array
     */
    private function formatProductVariations($variations, $productId)
    {
        if (!$variations || $variations->isEmpty()) {
            return [];
        }

        return $variations->map(function ($var) use ($productId) {
            $cleanImagePath = $var->image ? preg_replace('/^storage\//', '', $var->image) : null;

            $realStock = $this->calculateProductStock($productId, $var->id);

            $attributes = is_string($var->attributes)
                ? json_decode($var->attributes, true) ?? []
                : ($var->attributes ?? []);

            $salePrice = $var->sale_price > 0 ? (float) $var->sale_price : null;

            return [
                'id' => (int) $var->id,
                'sku' => $var->sku ?? '',
                'barcode' => $var->barcode ?? '',
                'price' => (float) $var->price,
                'sale_price' => $salePrice,
                'stock' => (int) $realStock,
                'real_time_stock' => (int) $realStock,
                'stock_status' => $this->getStockStatus($realStock),
                'attributes' => $attributes,
                'image' => $cleanImagePath ? url(Storage::url($cleanImagePath)) : null,
                'is_in_stock' => $realStock > 0,
                'is_on_sale' => $salePrice !== null && $salePrice < $var->price,
                'effective_price' => $salePrice ?? (float) $var->price,
            ];
        })->toArray();
    }

    /**
     * Format complete product data with real-time calculations
     *
     * @param Product $product
     * @return array
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
            ? $product->images->map(fn($img) => url(Storage::url(preg_replace('/^storage\//', '', $img->image_path))))->filter()->values()->toArray()
            : [];

        $variations = $this->formatProductVariations($product->variations, $product->id);

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
            'category_id'         => $product->category_id ? (int) $product->category_id : null,
            'brand_id'            => $product->brand_id ? (int) $product->brand_id : null,
            'real_time_stock'     => (int) $realStock,
            'stock_status'        => $this->getStockStatus($realStock),
            'brand'               => $brand,
            'category'            => $category,
            'images'              => $images,
            'product_attributes'  => $attributes,
            'product_variations'  => $variations,
        ];
    }

    /**
     * Get paginated or limited list of products
     *
     * @queryParam featured boolean Only featured products. Example: true
     * @queryParam category_id integer Filter by category. Example: 7
     * @queryParam brand_id integer Filter by brand. Example: 3
     * @queryParam ids string Comma-separated IDs. Example: 1,5,12
     * @queryParam search string Search in title/description
     * @queryParam min_price number Minimum price
     * @queryParam max_price number Maximum price
     * @queryParam limit integer Number of items (overrides pagination). Max 100
     * @queryParam per_page integer Items per page when paginating. Default 20
     *
     * @response 200 paginated or limited products with formatted data
     * @response 500 fetch error
     */
    public function index(Request $request)
    {
        $query = Product::query()
            ->with([
                'category:id,name',
                'brand:id,name,logo',
                'attributes:id,product_id,name,values',
                'variations:id,product_id,sku,barcode,price,sale_price,attributes,image',
                'images:id,product_id,image_path'
            ]);

        if ($request->has('featured') && $request->featured === 'true') {
            $query->where('is_featured', true);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->has('ids')) {
            $ids = explode(',', $request->ids);
            $query->whereIn('id', $ids);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        try {
            if ($request->has('limit') && $request->limit != -1) {
                $limit = min(max((int) $request->limit, 1), 100);
                $products = $query->take($limit)->get();

                $formatted = $products->map(fn($p) => $this->formatProductData($p))->values();

                return response()->json([
                    'success' => true,
                    'data' => $formatted,
                    'pagination' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'total' => $formatted->count(),
                    ],
                ]);
            }

            $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
            $products = $query->paginate($perPage);

            $formatted = collect($products->items())->map(fn($p) => $this->formatProductData($p))->values();

            return response()->json([
                'success' => true,
                'data' => $formatted,
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single product with real-time stock & full relations
     *
     * @urlParam id integer required Product ID
     *
     * @response 200 detailed product object
     * @response 404 product not found
     */
    public function show($id)
    {
        try {
            $product = Product::query()
                ->with([
                    'category:id,name',
                    'brand:id,name,logo',
                    'attributes:id,product_id,name,values',
                    'variations:id,product_id,sku,barcode,price,sale_price,attributes,image',
                    'images:id,product_id,image_path'
                ])
                ->findOrFail($id);

            $formatted = $this->formatProductData($product);

            return response()->json([
                'success' => true,
                'data' => $formatted,
            ]);
        } catch (\Exception $e) {
            Log::error('Product not found: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Product not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get related / similar products
     *
     * @urlParam id integer required Base product ID
     *
     * @queryParam limit integer Number of related items (default 6, max 12). Example: 8
     *
     * @response 200 array of related products
     * @response 404 base product not found
     * @response 500 error
     */
    public function related(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);

            $limit = min(max((int) $request->input('limit', 6), 1), 12);

            $query = Product::query()
                ->where('id', '!=', $id)
                ->where('product_type', '!=', 'variation')
                ->with([
                    'category:id,name',
                    'brand:id,name,logo',
                    'images:id,product_id,image_path'
                ])
                ->select('products.*');

            $related = (clone $query)
                ->where('category_id', $product->category_id)
                ->where('brand_id', $product->brand_id)
                ->whereNotNull('brand_id')
                ->orderByDesc('sold_quantity')
                ->take($limit)
                ->get();

            if ($related->count() < $limit / 2) {
                $more = (clone $query)
                    ->where('category_id', $product->category_id)
                    ->where('id', '!=', $id)
                    ->orderByDesc('sold_quantity')
                    ->take($limit - $related->count())
                    ->get();

                $related = $related->merge($more);
            }

            if ($related->count() < 3) {
                $fallback = (clone $query)
                    ->orderByDesc('sold_quantity')
                    ->take($limit - $related->count())
                    ->get();

                $related = $related->merge($fallback);
            }

            $formatted = $related->map(fn($p) => $this->formatProductData($p))->values();

            return response()->json([
                'success' => true,
                'data'    => $formatted,
                'count'   => $formatted->count(),
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

    /**
     * Get active lightning deals with product data for Flutter app.
     *
     * @queryParam limit integer Max deals to return (default 6, max 20). Example: 4
     *
     * @response 200 array of active deals with embedded product + deal metadata
     *
     * Route: GET /api/products/lightning-deals
     */
    public function lightningDeals(Request $request)
    {
        try {
            $limit = min(max((int) $request->input('limit', 6), 1), 20);

            $deals = LightningDeal::active()
                ->with([
                    'product' => function ($q) {
                        $q->with([
                            'brand:id,name,logo',
                            'category:id,name',
                        ]);
                    },
                ])
                ->orderBy('sort_order')
                ->orderByDesc('created_at')
                ->take($limit)
                ->get();

            $formatted = $deals->map(function (LightningDeal $deal) {
                $product = $deal->product;

                if (!$product) return null;

                // Discounted price = original price minus the deal percentage
                $originalPrice   = (float) $product->price;
                $discountedPrice = round($originalPrice * (1 - $deal->discount_percentage / 100), 2);

                $thumbnail = $product->thumbnail
                    ? url(Storage::url(preg_replace('/^storage\//', '', $product->thumbnail)))
                    : null;

                return [
                    'deal_id'             => (int) $deal->id,
                    'product_id'          => (int) $product->id,
                    'title'               => $product->title,
                    'thumbnail'           => $thumbnail,
                    'original_price'      => $originalPrice,
                    'discounted_price'    => $discountedPrice,
                    'discount_percentage' => (int) $deal->discount_percentage,
                    'stock_left'          => $deal->stock_left,
                    'ends_at'             => $deal->ends_at?->toIso8601String(),
                    'is_active'           => $deal->is_active,
                    'brand'               => $product->brand ? [
                        'id'   => $product->brand->id,
                        'name' => $product->brand->name,
                    ] : null,
                ];
            })->filter()->values();

            return response()->json([
                'success' => true,
                'data'    => $formatted,
                'count'   => $formatted->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch lightning deals: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch lightning deals',
            ], 500);
        }
    }
}
