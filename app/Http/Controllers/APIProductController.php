<?php
namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class APIProductController extends Controller
{
    /**
     * Calculate real-time stock from inventory for a product or variation
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
     * Get stock status label
     */
    private function getStockStatus($stock)
    {
        if ($stock > 10) {
            return 'in_stock';
        } elseif ($stock > 0) {
            return 'low_stock';
        } else {
            return 'out_of_stock';
        }
    }

    /**
     * Format product attributes properly for Flutter
     */
    private function formatProductAttributes($attributes)
    {
        if (!$attributes || $attributes->isEmpty()) {
            return [];
        }

        $formatted = $attributes->map(function ($attr) {
            return [
                'id' => $attr->id ?? null,
                'name' => $attr->name ?? '',
                'values' => $this->formatAttributeValues($attr->values),
            ];
        })->toArray();

        Log::info('Formatted attributes: ' . json_encode($formatted));
        return $formatted;
    }

    /**
     * Format attribute values properly
     */
    private function formatAttributeValues($values)
    {
        if (is_string($values)) {
            try {
                $decoded = json_decode($values, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    Log::info('Decoded JSON values: ' . json_encode($decoded));
                    return $decoded;
                }
            } catch (\Exception $e) {
                // If not JSON, try comma-separated
                $valuesArray = array_map('trim', explode(',', $values));
                Log::info('Comma-separated values: ' . json_encode($valuesArray));
                return $valuesArray;
            }
        } elseif (is_array($values)) {
            Log::info('Array values: ' . json_encode($values));
            return $values;
        } elseif (is_null($values)) {
            Log::info('Null values, returning empty array');
            return [];
        }

        Log::info('Unknown values type: ' . gettype($values));
        return [];
    }

    /**
     * Extract attributes from variations when product_attributes table is empty
     */
    private function extractAttributesFromVariations($variations)
    {
        if (!$variations || $variations->isEmpty()) {
            Log::info('No variations to extract attributes from');
            return [];
        }

        Log::info('Extracting attributes from ' . $variations->count() . ' variations');

        $attributes = [];

        foreach ($variations as $variation) {
            $varAttributes = $variation->attributes;

            Log::info('Variation ID: ' . $variation->id . ', Raw attributes: ' . json_encode($varAttributes));

            if (is_string($varAttributes) && $varAttributes !== '') {
                try {
                    $varAttributes = json_decode($varAttributes, true);
                    Log::info('Decoded variation attributes: ' . json_encode($varAttributes));
                } catch (\Exception $e) {
                    Log::error('Error decoding variation attributes: ' . $e->getMessage());
                    continue;
                }
            }

            if (is_array($varAttributes) && !empty($varAttributes)) {
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

        // Convert to the format Flutter expects
        $formattedAttributes = [];
        foreach ($attributes as $name => $values) {
            $formattedAttributes[] = [
                'id' => null, // No ID since extracted from variations
                'name' => $name,
                'values' => $values,
            ];
        }

        Log::info('Extracted ' . count($formattedAttributes) . ' attributes from variations');

        return $formattedAttributes;
    }

    /**
     * Format product variations properly for Flutter with real-time stock calculation
     */
    private function formatProductVariations($variations, $productId)
    {
        if (!$variations || $variations->isEmpty()) {
            Log::info('No variations to format for product ' . $productId);
            return [];
        }

        Log::info('Formatting ' . $variations->count() . ' variations for product ' . $productId);

        $formattedVariations = $variations->map(function ($var) use ($productId) {
            $cleanImagePath = $var->image ? preg_replace('/^storage\//', '', $var->image) : null;

            // Calculate real-time stock for this variation
            $realStock = $this->calculateProductStock($productId, $var->id);

            // Parse attributes
            $attributes = $this->parseVariationAttributes($var->attributes);

            // Format sale price as number
            $salePrice = null;
            if ($var->sale_price !== null && $var->sale_price > 0) {
                $salePrice = (float) $var->sale_price;
            }

            $variationData = [
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
                'is_on_sale' => !is_null($salePrice) && $salePrice < $var->price,
                'effective_price' => $salePrice ?? (float) $var->price,
            ];

            Log::info('Formatted variation ' . $var->id . ': ' . json_encode($variationData));
            return $variationData;
        })->toArray();

        return $formattedVariations;
    }

    /**
     * Parse variation attributes
     */
    private function parseVariationAttributes($attributes)
    {
        if (is_string($attributes) && $attributes !== '') {
            try {
                $decoded = json_decode($attributes, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            } catch (\Exception $e) {
                Log::error('Error parsing variation attributes: ' . $e->getMessage());
                return [];
            }
        } elseif (is_array($attributes)) {
            return $attributes;
        } elseif ($attributes instanceof \stdClass) {
            return (array) $attributes;
        }

        return [];
    }

    /**
     * Format product data with real-time stock calculation
     */
    private function formatProductData($product)
    {
        Log::info('=== FORMATTING PRODUCT DATA ===');
        Log::info('Product ID: ' . $product->id);
        Log::info('Product Title: ' . $product->title);
        Log::info('Product Type: ' . $product->product_type);

        // Calculate real-time stock
        $realStock = $this->calculateProductStock($product->id);
        Log::info('Real stock for product: ' . $realStock);

        // Get attributes
        $formattedAttributes = [];

        // 1. First try to get from product_attributes table
        if ($product->attributes && $product->attributes->isNotEmpty()) {
            Log::info('Using ' . $product->attributes->count() . ' attributes from product_attributes table');
            $formattedAttributes = $this->formatProductAttributes($product->attributes);
        }
        // 2. If no attributes in product_attributes table, extract from variations
        else if ($product->variations && $product->variations->isNotEmpty()) {
            Log::info('Extracting attributes from ' . $product->variations->count() . ' variations');
            $formattedAttributes = $this->extractAttributesFromVariations($product->variations);
        } else {
            Log::info('No attributes found in database');
        }

        Log::info('Final attributes count: ' . count($formattedAttributes));

        // Format sale price
        $salePrice = null;
        if ($product->sale_price !== null && $product->sale_price > 0) {
            $salePrice = (float) $product->sale_price;
        }

        // Format brand
        $brand = null;
        if ($product->brand) {
            $brand = [
                'id' => (int) $product->brand->id,
                'name' => $product->brand->name ?? '',
                'logo' => $product->brand->logo ? url(Storage::url(preg_replace('/^storage\//', '', $product->brand->logo))) : null,
            ];
        }

        // Format category
        $category = null;
        if ($product->category) {
            $category = [
                'id' => (int) $product->category->id,
                'name' => $product->category->name ?? '',
            ];
        }

        // Format images
        $images = [];
        if ($product->images && $product->images->isNotEmpty()) {
            $images = $product->images->map(function ($image) {
                $cleanPath = preg_replace('/^storage\//', '', $image->image_path);
                return $cleanPath ? url(Storage::url($cleanPath)) : null;
            })->filter()->values()->toArray();
        }

        // Format variations
        $formattedVariations = $this->formatProductVariations($product->variations, $product->id);

        // Build final product data
        $productData = [
            'id' => (int) $product->id,
            'title' => $product->title ?? '',
            'sku' => $product->sku ?? '',
            'stock' => (int) $realStock,
            'price' => (float) $product->price,
            'sale_price' => $salePrice,
            'thumbnail' => $product->thumbnail ? url(Storage::url(preg_replace('/^storage\//', '', $product->thumbnail))) : null,
            'description' => $product->description ?? '',
            'product_type' => $product->product_type ?? '',
            'sold_quantity' => (int) ($product->sold_quantity ?? 0),
            'is_featured' => (bool) ($product->is_featured ?? false),
            'category_id' => $product->category_id ? (int) $product->category_id : null,
            'brand_id' => $product->brand_id ? (int) $product->brand_id : null,
            'real_time_stock' => (int) $realStock,
            'stock_status' => $this->getStockStatus($realStock),
            'brand' => $brand,
            'category' => $category,
            'images' => $images,
            'product_attributes' => $formattedAttributes,
            'product_variations' => $formattedVariations,
        ];

        Log::info('=== FINAL PRODUCT DATA ===');
        Log::info('Product ID: ' . $productData['id']);
        Log::info('Attributes count: ' . count($productData['product_attributes']));
        Log::info('Variations count: ' . count($productData['product_variations']));
        Log::info('===========================');

        return $productData;
    }

    /**
     * Get a list of products with optional filters and limit.
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

        // Apply filters
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
            // Handle limit parameter
            if ($request->has('limit') && $request->limit != -1) {
                $limit = min(max((int) $request->limit, 1), 100);
                $products = $query->take($limit)->get();

                $formattedProducts = $products->map(function ($product) {
                    return $this->formatProductData($product);
                })->values();

                return response()->json([
                    'success' => true,
                    'data' => $formattedProducts,
                    'pagination' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'total' => $products->count(),
                    ],
                ]);
            }

            // Apply regular pagination
            $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
            $products = $query->paginate($perPage);

            $formattedProducts = collect($products->items())->map(function ($product) {
                return $this->formatProductData($product);
            })->values();

            return response()->json([
                'success' => true,
                'data' => $formattedProducts,
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
     * Get a single product by ID with real-time stock calculation
     */
    public function show($id)
    {
        try {
            Log::info('=== FETCHING SINGLE PRODUCT ===');
            Log::info('Product ID: ' . $id);

            $product = Product::query()
                ->with([
                    'category:id,name',
                    'brand:id,name,logo',
                    'attributes:id,product_id,name,values',
                    'variations:id,product_id,sku,barcode,price,sale_price,attributes,image',
                    'images:id,product_id,image_path'
                ])
                ->findOrFail($id);

            Log::info('Found product: ' . $product->title);
            Log::info('Product type: ' . $product->product_type);
            Log::info('Attributes count: ' . ($product->attributes ? $product->attributes->count() : 0));
            Log::info('Variations count: ' . ($product->variations ? $product->variations->count() : 0));

            $formattedProduct = $this->formatProductData($product);

            return response()->json([
                'success' => true,
                'data' => $formattedProduct,
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
     * Get related products for a specific product
     */
    public function related(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);

            $limit = $request->input('limit', 6);
            $limit = min(max((int)$limit, 1), 12);

            $query = Product::query()
                ->where('id', '!=', $id)           // exclude itself
                ->where('product_type', '!=', 'variation') // optional
                ->with([
                    'category:id,name',
                    'brand:id,name,logo',
                    'images:id,product_id,image_path'
                ])
                ->select('products.*');

            // Strategy 1: Same category + same brand (strongest signal)
            $related = (clone $query)
                ->where('category_id', $product->category_id)
                ->where('brand_id', $product->brand_id)
                ->whereNotNull('brand_id')
                ->orderByDesc('sold_quantity')
                ->take($limit)
                ->get();

            // Strategy 2: If not enough → same category only
            if ($related->count() < $limit / 2) {
                $more = (clone $query)
                    ->where('category_id', $product->category_id)
                    ->where('id', '!=', $id)
                    ->orderByDesc('sold_quantity')
                    ->take($limit - $related->count())
                    ->get();

                $related = $related->merge($more);
            }

            // Strategy 3: Fallback - most sold overall (last resort)
            if ($related->count() < 3) {
                $fallback = (clone $query)
                    ->orderByDesc('sold_quantity')
                    ->take($limit - $related->count())
                    ->get();

                $related = $related->merge($fallback);
            }

            $formatted = $related->map(function ($p) {
                return $this->formatProductData($p);  // reuse your existing formatter
            })->values();

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

    // ... rest of the controller methods remain the same
}
