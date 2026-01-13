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

        return $attributes->map(function ($attr) {
            return [
                'id' => $attr->id ?? null,
                'name' => $attr->name ?? '',
                'values' => $this->formatAttributeValues($attr->values),
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
                    return $decoded;
                }
            } catch (\Exception $e) {
                // If not JSON, try comma-separated
                return array_map('trim', explode(',', $values));
            }
        } elseif (is_array($values)) {
            return $values;
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

        Log::info('Extracting attributes from variations');

        $attributes = [];

        foreach ($variations as $variation) {
            $varAttributes = $variation->attributes;

            Log::info('Variation ID: ' . $variation->id . ', Raw attributes: ' . json_encode($varAttributes));

            if (is_string($varAttributes) && $varAttributes !== '') {
                try {
                    $varAttributes = json_decode($varAttributes, true);
                    Log::info('Decoded attributes: ' . json_encode($varAttributes));
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

        Log::info('Extracted attributes from variations: ' . json_encode($formattedAttributes));

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

            // Calculate real-time stock for this variation
            $realStock = $this->calculateProductStock($productId, $var->id);

            return [
                'id' => $var->id ?? null,
                'sku' => $var->sku ?? '',
                'barcode' => $var->barcode ?? '',
                'price' => floatval($var->price ?? 0.0),
                'sale_price' => $var->sale_price ? floatval($var->sale_price) : null,
                'stock' => $realStock,
                'real_time_stock' => $realStock,
                'stock_status' => $this->getStockStatus($realStock),
                'attributes' => $this->parseVariationAttributes($var->attributes),
                'image' => $cleanImagePath ? url(Storage::url($cleanImagePath)) : null,
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
                Log::error('Error parsing variation attributes: ' . $e->getMessage());
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

        Log::info('Formatting product ID: ' . $product->id);
        Log::info('Product type: ' . $product->product_type);
        Log::info('Attributes from DB: ' . ($product->attributes ? $product->attributes->count() : 0));
        Log::info('Variations from DB: ' . ($product->variations ? $product->variations->count() : 0));

        // Get attributes from both sources
        $formattedAttributes = [];

        // 1. First try to get from product_attributes table
        if ($product->attributes && $product->attributes->isNotEmpty()) {
            Log::info('Using attributes from product_attributes table');
            $formattedAttributes = $this->formatProductAttributes($product->attributes);
        }
        // 2. If no attributes in product_attributes table, extract from variations
        else if ($product->variations && $product->variations->isNotEmpty()) {
            Log::info('Extracting attributes from variations');
            $formattedAttributes = $this->extractAttributesFromVariations($product->variations);
        } else {
            Log::info('No attributes found anywhere');
        }

        Log::info('Final attributes count: ' . count($formattedAttributes));
        Log::info('Final attributes: ' . json_encode($formattedAttributes));

        return [
            'id' => $product->id,
            'title' => $product->title ?? '',
            'sku' => $product->sku ?? '',
            'stock' => $realStock,
            'price' => floatval($product->price ?? 0.0),
            'sale_price' => $product->sale_price ? floatval($product->sale_price) : null,
            'thumbnail' => $product->thumbnail ? url(Storage::url($product->thumbnail)) : null,
            'description' => $product->description ?? '',
            'product_type' => $product->product_type ?? '',
            'sold_quantity' => intval($product->sold_quantity ?? 0),
            'is_featured' => boolval($product->is_featured ?? false),
            'category_id' => $product->category_id,
            'brand_id' => $product->brand_id,
            'real_time_stock' => $realStock,
            'stock_status' => $this->getStockStatus($realStock),
            'brand' => $product->brand ? [
                'id' => $product->brand->id,
                'name' => $product->brand->name ?? '',
                'logo' => $product->brand->logo ? url(Storage::url($product->brand->logo)) : null,
            ] : null,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name ?? '',
            ] : null,
            'images' => $product->images ? $product->images->pluck('image_path')->map(function ($path) {
                $cleanPath = preg_replace('/^storage\//', '', $path);
                return $cleanPath ? url(Storage::url($cleanPath)) : null;
            })->filter()->toArray() : [],
            'product_attributes' => $formattedAttributes,
            'product_variations' => $this->formatProductVariations($product->variations, $product->id),
        ];
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
            Log::info('Fetching product ID: ' . $id);

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
                'message' => 'Product not found: ' . $e->getMessage(),
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
            'product_type' => 'required|string',
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
                        'values' => $attr['values'],
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
                        'attributes' => $var['attributes'] ?? [],
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
                'message' => 'Failed to create product: ' . $e->getMessage(),
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
                'product_type' => 'required|string',
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
                        'values' => $attr['values'],
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
                        'attributes' => $var['attributes'] ?? [],
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
            Log::error('Product not found: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Product not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Diagnostic endpoint to check product data structure
     */
    public function diagnose($id)
    {
        try {
            $product = Product::with(['attributes', 'variations'])->findOrFail($id);

            // Check database directly
            $attributesFromDB = \DB::table('product_attributes')
                ->where('product_id', $id)
                ->get();

            $variationsFromDB = \DB::table('product_variations')
                ->where('product_id', $id)
                ->get();

            // Extract attributes from variations
            $extractedAttributes = $this->extractAttributesFromVariations($product->variations);

            return response()->json([
                'success' => true,
                'data' => [
                    'product_id' => $id,
                    'title' => $product->title,
                    'product_type' => $product->product_type,
                    'database_check' => [
                        'attributes_table_count' => $attributesFromDB->count(),
                        'attributes_table_data' => $attributesFromDB,
                        'variations_table_count' => $variationsFromDB->count(),
                        'variations_table_data' => $variationsFromDB,
                    ],
                    'eloquent_check' => [
                        'attributes_count' => $product->attributes ? $product->attributes->count() : 0,
                        'attributes_data' => $product->attributes,
                        'variations_count' => $product->variations ? $product->variations->count() : 0,
                        'variations_data' => $product->variations,
                    ],
                    'extracted_attributes' => $extractedAttributes,
                    'api_response_sample' => [
                        'product_attributes' => $this->formatProductAttributes($product->attributes),
                        'extracted_product_attributes' => $extractedAttributes,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload an image file for products.
     */
    public function uploadFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
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
                $url = url(Storage::url($path));

                return response()->json([
                    'success' => true,
                    'url' => $url,
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
                'message' => 'Failed to upload image: ' . $e->getMessage(),
            ], 500);
        }
    }
}
