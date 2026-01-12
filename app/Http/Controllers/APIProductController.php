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

        return max(0, (int) $totalStock);
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

        return $attributes->map(function ($attr) {
            // Format values properly
            $values = $this->formatAttributeValues($attr->values);

            return [
                'id' => (int) ($attr->id ?? 0),
                'name' => $attr->name ?? '',
                'values' => $values,
            ];
        })->toArray();
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
                    return array_map('strval', $decoded);
                }
            } catch (\Exception $e) {
                // If not JSON, try comma-separated
                $exploded = array_map('trim', explode(',', $values));
                return array_filter($exploded);
            }
        } elseif (is_array($values)) {
            return array_map('strval', $values);
        }

        return [];
    }

    /**
     * Extract attributes from variations when product_attributes table is empty
     */
    private function extractAttributesFromVariations($variations)
    {
        if (!$variations || $variations->isEmpty()) {
            return [];
        }

        $attributes = [];

        foreach ($variations as $variation) {
            $varAttributes = $variation->attributes;

            if (is_string($varAttributes) && $varAttributes !== '') {
                try {
                    $varAttributes = json_decode($varAttributes, true);
                } catch (\Exception $e) {
                    continue;
                }
            }

            if (is_array($varAttributes) && !empty($varAttributes)) {
                foreach ($varAttributes as $key => $value) {
                    if (!isset($attributes[$key])) {
                        $attributes[$key] = [];
                    }

                    if (!in_array($value, $attributes[$key])) {
                        $attributes[$key][] = (string) $value;
                    }
                }
            }
        }

        // Convert to the format Flutter expects
        $formattedAttributes = [];
        foreach ($attributes as $name => $values) {
            $formattedAttributes[] = [
                'id' => 0, // No ID since extracted from variations
                'name' => (string) $name,
                'values' => $values,
            ];
        }

        return $formattedAttributes;
    }

    /**
     * Format product variations properly for Flutter with real-time stock calculation
     */
    private function formatProductVariations($variations, $productId)
    {
        if (!$variations || $variations->isEmpty()) {
            return [];
        }

        return $variations->map(function ($var) use ($productId) {
            $cleanImagePath = $var->image ? preg_replace('/^storage\//', '', $var->image) : null;
            $imageUrl = $cleanImagePath ? Storage::url($cleanImagePath) : null;

            // Calculate real-time stock for this variation
            $realStock = $this->calculateProductStock($productId, $var->id);

            return [
                'id' => (int) ($var->id ?? 0),
                'sku' => (string) ($var->sku ?? ''),
                'barcode' => (string) ($var->barcode ?? ''),
                'price' => (float) ($var->price ?? 0.0),
                'sale_price' => $var->sale_price ? (float) $var->sale_price : null,
                'stock' => (int) $realStock,
                'real_time_stock' => (int) $realStock,
                'stock_status' => $this->getStockStatus($realStock),
                'attributes' => $this->parseVariationAttributes($var->attributes),
                'image' => $imageUrl,
                'is_in_stock' => $realStock > 0,
                'is_on_sale' => !is_null($var->sale_price) && $var->sale_price < $var->price,
                'effective_price' => $var->sale_price ?? $var->price,
            ];
        })->toArray();
    }

    /**
     * Parse variation attributes
     */
    private function parseVariationAttributes($attributes)
    {
        if (is_string($attributes) && $attributes !== '') {
            try {
                return json_decode($attributes, true);
            } catch (\Exception $e) {
                return [];
            }
        } elseif (is_array($attributes)) {
            return $attributes;
        }

        return [];
    }

    /**
     * Format product data with real-time stock calculation
     */
    private function formatProductData($product)
    {
        // Calculate real-time stock
        $realStock = $this->calculateProductStock($product->id);

        // Get attributes from both sources
        $formattedAttributes = [];

        // 1. First try to get from product_attributes table
        if ($product->attributes && $product->attributes->isNotEmpty()) {
            $formattedAttributes = $this->formatProductAttributes($product->attributes);
        }
        // 2. If no attributes in product_attributes table, extract from variations
        elseif ($product->variations && $product->variations->isNotEmpty()) {
            $formattedAttributes = $this->extractAttributesFromVariations($product->variations);
        }

        // Get product images with proper URLs
        $images = [];
        if ($product->images) {
            foreach ($product->images as $image) {
                $cleanPath = preg_replace('/^storage\//', '', $image->image_path);
                if ($cleanPath) {
                    $images[] = Storage::url($cleanPath);
                }
            }
        }

        // Get thumbnail URL
        $thumbnail = null;
        if ($product->thumbnail) {
            $cleanThumbnail = preg_replace('/^storage\//', '', $product->thumbnail);
            $thumbnail = Storage::url($cleanThumbnail);
        }

        return [
            'id' => (int) $product->id,
            'title' => (string) ($product->title ?? ''),
            'sku' => (string) ($product->sku ?? ''),
            'stock' => (int) $realStock,
            'price' => (float) ($product->price ?? 0.0),
            'sale_price' => $product->sale_price ? (float) $product->sale_price : null,
            'thumbnail' => $thumbnail,
            'description' => (string) ($product->description ?? ''),
            'product_type' => (string) ($product->product_type ?? ''),
            'sold_quantity' => (int) ($product->sold_quantity ?? 0),
            'is_featured' => (bool) ($product->is_featured ?? false),
            'category_id' => $product->category_id ? (int) $product->category_id : null,
            'brand_id' => $product->brand_id ? (int) $product->brand_id : null,
            'real_time_stock' => (int) $realStock,
            'stock_status' => $this->getStockStatus($realStock),
            'brand' => $product->brand ? [
                'id' => (int) $product->brand->id,
                'name' => (string) ($product->brand->name ?? ''),
                'logo' => $product->brand->logo ? Storage::url(preg_replace('/^storage\//', '', $product->brand->logo)) : null,
            ] : null,
            'category' => $product->category ? [
                'id' => (int) $product->category->id,
                'name' => (string) ($product->category->name ?? ''),
            ] : null,
            'images' => $images,
            'product_attributes' => $formattedAttributes,
            'product_variations' => $this->formatProductVariations($product->variations, $product->id),
        ];
    }

    /**
     * Get a list of products with optional filters and limit.
     */
    public function index(Request $request)
    {
        try {
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

            if ($request->has('category_id') && $request->category_id) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->has('brand_id') && $request->brand_id) {
                $query->where('brand_id', $request->brand_id);
            }

            if ($request->has('ids')) {
                $ids = explode(',', $request->ids);
                $query->whereIn('id', array_filter($ids));
            }

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($request->has('min_price')) {
                $query->where('price', '>=', (float) $request->min_price);
            }

            if ($request->has('max_price')) {
                $query->where('price', '<=', (float) $request->max_price);
            }

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
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single product by ID with real-time stock calculation
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

            $formattedProduct = $this->formatProductData($product);

            return response()->json([
                'success' => true,
                'data' => $formattedProduct,
            ]);
        } catch (\Exception $e) {
            Log::error('Product not found: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Create a new product.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'thumbnail' => 'nullable|string',
            'description' => 'nullable|string',
            'product_type' => 'required|string|in:single,variable',
            'sold_quantity' => 'nullable|integer|min:0',
            'is_featured' => 'nullable|boolean',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'product_attributes' => 'nullable|array',
            'product_attributes.*.name' => 'required_with:product_attributes|string',
            'product_attributes.*.values' => 'required_with:product_attributes|array',
            'product_variations' => 'nullable|array',
            'product_variations.*.sku' => 'required_with:product_variations|string',
            'product_variations.*.barcode' => 'nullable|string',
            'product_variations.*.price' => 'required_with:product_variations|numeric|min:0',
            'product_variations.*.sale_price' => 'nullable|numeric|min:0',
            'product_variations.*.attributes' => 'nullable|array',
            'product_variations.*.image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Create product with initial stock
            $productData = $request->only([
                'title', 'sku', 'price', 'sale_price', 'thumbnail', 'description',
                'product_type', 'sold_quantity', 'is_featured', 'category_id', 'brand_id'
            ]);

            $product = Product::create($productData);

            // Handle images
            if ($request->has('images')) {
                foreach ($request->images as $imagePath) {
                    $cleanPath = preg_replace('/^storage\//', '', $imagePath);
                    $product->images()->create(['image_path' => $cleanPath]);
                }
            }

            // Handle attributes
            if ($request->has('product_attributes')) {
                foreach ($request->product_attributes as $attr) {
                    $product->attributes()->create([
                        'name' => $attr['name'],
                        'values' => json_encode($attr['values']),
                    ]);
                }
            }

            // Handle variations
            if ($request->has('product_variations')) {
                foreach ($request->product_variations as $var) {
                    $cleanImagePath = isset($var['image']) ? preg_replace('/^storage\//', '', $var['image']) : null;
                    $product->variations()->create([
                        'sku' => $var['sku'],
                        'barcode' => $var['barcode'] ?? null,
                        'price' => $var['price'],
                        'sale_price' => $var['sale_price'] ?? null,
                        'attributes' => json_encode($var['attributes'] ?? []),
                        'image' => $cleanImagePath,
                    ]);
                }
            }

            // Format response with real-time stock calculation
            $formattedProduct = $this->formatProductData($product->fresh());

            return response()->json([
                'success' => true,
                'data' => $formattedProduct,
                'message' => 'Product created successfully',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create product: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a product.
     */
    public function update(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'sku' => 'required|string|unique:products,sku,' . $id,
                'price' => 'required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'thumbnail' => 'nullable|string',
                'description' => 'nullable|string',
                'product_type' => 'required|string|in:single,variable',
                'sold_quantity' => 'nullable|integer|min:0',
                'is_featured' => 'nullable|boolean',
                'category_id' => 'nullable|exists:categories,id',
                'brand_id' => 'nullable|exists:brands,id',
                'images' => 'nullable|array',
                'images.*' => 'string',
                'product_attributes' => 'nullable|array',
                'product_attributes.*.name' => 'required_with:product_attributes|string',
                'product_attributes.*.values' => 'required_with:product_attributes|array',
                'product_variations' => 'nullable|array',
                'product_variations.*.sku' => 'required_with:product_variations|string',
                'product_variations.*.barcode' => 'nullable|string',
                'product_variations.*.price' => 'required_with:product_variations|numeric|min:0',
                'product_variations.*.sale_price' => 'nullable|numeric|min:0',
                'product_variations.*.attributes' => 'nullable|array',
                'product_variations.*.image' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Update product
            $product->update($request->only([
                'title', 'sku', 'price', 'sale_price', 'thumbnail', 'description',
                'product_type', 'sold_quantity', 'is_featured', 'category_id', 'brand_id'
            ]));

            // Handle images
            if ($request->has('images')) {
                $product->images()->delete();
                foreach ($request->images as $imagePath) {
                    $cleanPath = preg_replace('/^storage\//', '', $imagePath);
                    $product->images()->create(['image_path' => $cleanPath]);
                }
            }

            // Handle attributes
            if ($request->has('product_attributes')) {
                $product->attributes()->delete();
                foreach ($request->product_attributes as $attr) {
                    $product->attributes()->create([
                        'name' => $attr['name'],
                        'values' => json_encode($attr['values']),
                    ]);
                }
            }

            // Handle variations
            if ($request->has('product_variations')) {
                $product->variations()->delete();
                foreach ($request->product_variations as $var) {
                    $cleanImagePath = isset($var['image']) ? preg_replace('/^storage\//', '', $var['image']) : null;
                    $product->variations()->create([
                        'sku' => $var['sku'],
                        'barcode' => $var['barcode'] ?? null,
                        'price' => $var['price'],
                        'sale_price' => $var['sale_price'] ?? null,
                        'attributes' => json_encode($var['attributes'] ?? []),
                        'image' => $cleanImagePath,
                    ]);
                }
            }

            // Format response with real-time stock
            $formattedProduct = $this->formatProductData($product->fresh());

            return response()->json([
                'success' => true,
                'data' => $formattedProduct,
                'message' => 'Product updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update product: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload an image file for products.
     */
    public function uploadFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $path = $file->store('products', 'public');
                $url = Storage::url($path);

                return response()->json([
                    'success' => true,
                    'url' => $url,
                    'path' => $path,
                    'message' => 'Image uploaded successfully',
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'No image file provided',
            ], 400);
        } catch (\Exception $e) {
            Log::error('Failed to upload image: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
