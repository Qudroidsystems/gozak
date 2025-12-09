<?php
namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class APIProductController extends Controller
{
    /**
     * Calculate real-time stock from inventory for a product
     */
    private function calculateProductStock($productId)
    {
        $totalStock = Stock::where('product_id', $productId)
            ->selectRaw('
                SUM(CASE 
                    WHEN type IN ("in", "adjustment", "transfer_in", "return") THEN quantity
                    WHEN type IN ("out", "damage", "transfer") THEN -quantity
                    ELSE 0
                END) as total
            ')
            ->value('total') ?? 0;
        
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
     * Format product data with real-time stock calculation
     */
    private function formatProductData($product)
    {
        // Calculate real-time stock
        $realStock = $this->calculateProductStock($product->id);
        
        return [
            'id' => $product->id,
            'title' => $product->title ?? '',
            'sku' => $product->sku ?? '',
            'stock' => $realStock, // Always use calculated stock
            'price' => $product->price ?? 0.0,
            'sale_price' => $product->sale_price ?? null,
            'thumbnail' => $product->thumbnail ? url(Storage::url($product->thumbnail)) : null,
            'description' => $product->description ?? '',
            'product_type' => $product->product_type ?? '',
            'sold_quantity' => $product->sold_quantity ?? 0,
            'is_featured' => $product->is_featured ?? false,
            'category_id' => $product->category_id,
            'brand_id' => $product->brand_id,
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
            'product_attributes' => $product->attributes ? $product->attributes->map(function ($attr) {
                return [
                    'id' => $attr->id,
                    'name' => $attr->name ?? '',
                    'values' => $attr->values ?? [],
                ];
            })->toArray() : [],
            'product_variations' => $product->variations ? $product->variations->map(function ($var) {
                $cleanImagePath = $var->image ? preg_replace('/^storage\//', '', $var->image) : null;
                return [
                    'id' => $var->id,
                    'sku' => $var->sku ?? '',
                    'price' => $var->price ?? 0.0,
                    'sale_price' => $var->sale_price ?? null,
                    'stock' => $var->stock ?? 0,
                    'attributes' => $var->attributes ?? [],
                    'image' => $cleanImagePath ? url(Storage::url($cleanImagePath)) : null,
                ];
            })->toArray() : [],
            'stock_status' => $this->getStockStatus($realStock), // Add stock status
        ];
    }

    /**
     * Get a list of products with optional filters and limit.
     * Stock is always calculated from inventory in real-time
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Product::query()
            ->select('id', 'title', 'sku', 'stock', 'price', 'sale_price', 'thumbnail', 'description', 'product_type', 'sold_quantity', 'is_featured', 'category_id', 'brand_id')
            ->with([
                'category:id,name',
                'brand:id,name,logo',
                'attributes:id,product_id,name,values',
                'variations:id,product_id,sku,price,sale_price,stock,attributes,image',
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

        // Optional stock status filter
        if ($request->has('stock_status')) {
            $products = $query->get();
            $filteredProducts = $products->filter(function ($product) use ($request) {
                $realStock = $this->calculateProductStock($product->id);
                $status = $this->getStockStatus($realStock);
                return $status === $request->stock_status;
            });
            
            // Apply pagination manually
            $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
            $page = $request->input('page', 1);
            $offset = ($page - 1) * $perPage;
            $paginatedProducts = $filteredProducts->slice($offset, $perPage)->values();
            
            $formattedProducts = $paginatedProducts->map(function ($product) {
                return $this->formatProductData($product);
            });

            return response()->json([
                'success' => true,
                'data' => $formattedProducts,
                'pagination' => [
                    'current_page' => (int) $page,
                    'last_page' => ceil($filteredProducts->count() / $perPage),
                    'total' => $filteredProducts->count(),
                    'per_page' => $perPage,
                ],
            ]);
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single product by ID with real-time stock calculation
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $product = Product::query()
                ->select('id', 'title', 'sku', 'stock', 'price', 'sale_price', 'thumbnail', 'description', 'product_type', 'sold_quantity', 'is_featured', 'category_id', 'brand_id')
                ->with([
                    'category:id,name',
                    'brand:id,name,logo',
                    'attributes:id,product_id,name,values',
                    'variations:id,product_id,sku,price,sale_price,stock,attributes,image',
                    'images:id,product_id,image_path'
                ])
                ->findOrFail($id);

            $formattedProduct = $this->formatProductData($product);

            return response()->json([
                'success' => true,
                'data' => $formattedProduct,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Create a new product.
     * IMPORTANT: Stock should be managed through inventory system
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku',
            'stock' => 'integer|min:0', // Stock is optional - managed by inventory
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
            'product_variations.*.price' => 'required_with:product_variations|numeric|min:0',
            'product_variations.*.sale_price' => 'nullable|numeric|min:0',
            'product_variations.*.stock' => 'required_with:product_variations|integer|min:0',
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
            // Create product with initial stock (0 by default)
            $productData = $request->only([
                'title', 'sku', 'price', 'sale_price', 'thumbnail', 'description',
                'product_type', 'sold_quantity', 'is_featured', 'category_id', 'brand_id'
            ]);
            
            // Set initial stock from request or default to 0
            $productData['stock'] = $request->stock ?? 0;
            
            $product = Product::create($productData);

            // If initial stock is provided, create an inventory transaction
            if ($request->has('stock') && $request->stock > 0) {
                $defaultLocation = \App\Models\StockLocation::where('is_default', true)->first();
                
                if ($defaultLocation) {
                    Stock::create([
                        'product_id' => $product->id,
                        'stock_location_id' => $defaultLocation->id,
                        'user_id' => auth()->id() ?? 1,
                        'type' => 'in',
                        'quantity' => $request->stock,
                        'previous_quantity' => 0,
                        'new_quantity' => $request->stock,
                        'unit_cost' => $product->price,
                        'total_cost' => $product->price * $request->stock,
                        'reference_number' => 'API-INIT-' . date('YmdHis'),
                        'reference_type' => 'initial',
                        'notes' => 'Initial stock from API creation',
                        'transaction_date' => now(),
                    ]);
                }
            }

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
                        'price' => $var['price'],
                        'sale_price' => $var['sale_price'] ?? null,
                        'stock' => $var['stock'] ?? 0,
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update specific fields of a product.
     * Stock updates are handled through inventory system
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSingleField(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title' => 'string|max:255',
                'sku' => 'string|unique:products,sku,' . $id,
                'stock' => 'integer|min:0', // Note: Not recommended to update directly
                'price' => 'numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'thumbnail' => 'nullable|string',
                'description' => 'nullable|string',
                'product_type' => 'string',
                'sold_quantity' => 'integer|min:0',
                'is_featured' => 'boolean',
                'category_id' => 'nullable|exists:categories,id',
                'brand_id' => 'nullable|exists:brands,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // If updating stock, create inventory transaction
            if ($request->has('stock')) {
                $currentStock = $this->calculateProductStock($product->id);
                $newStock = $request->stock;
                
                if ($newStock != $currentStock) {
                    $adjustment = $newStock - $currentStock;
                    
                    if ($adjustment != 0) {
                        $defaultLocation = \App\Models\StockLocation::where('is_default', true)->first();
                        
                        if ($defaultLocation) {
                            $type = $adjustment > 0 ? 'in' : 'out';
                            $quantity = abs($adjustment);
                            
                            Stock::create([
                                'product_id' => $product->id,
                                'stock_location_id' => $defaultLocation->id,
                                'user_id' => auth()->id() ?? 1,
                                'type' => $type,
                                'quantity' => $quantity,
                                'previous_quantity' => $currentStock,
                                'new_quantity' => $newStock,
                                'unit_cost' => $product->price,
                                'total_cost' => $product->price * $quantity,
                                'reference_number' => 'API-ADJ-' . date('YmdHis'),
                                'reference_type' => 'adjustment',
                                'adjustment_reason' => 'Stock update via API',
                                'notes' => 'Stock updated via API single field update',
                                'transaction_date' => now(),
                            ]);
                        }
                    }
                    
                    // Remove stock from request since it's handled
                    $request->request->remove('stock');
                }
            }

            // Update other fields
            $product->update($request->only([
                'title', 'sku', 'price', 'sale_price', 'thumbnail', 'description',
                'product_type', 'sold_quantity', 'is_featured', 'category_id', 'brand_id'
            ]));

            // Format response with real-time stock
            $formattedProduct = $this->formatProductData($product->fresh());

            return response()->json([
                'success' => true,
                'data' => $formattedProduct,
                'message' => 'Product updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Fully update a product.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'sku' => 'required|string|unique:products,sku,' . $id,
                'stock' => 'integer|min:0', // Optional - managed by inventory
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
                'product_variations.*.price' => 'required_with:product_variations|numeric|min:0',
                'product_variations.*.sale_price' => 'nullable|numeric|min:0',
                'product_variations.*.stock' => 'required_with:product_variations|integer|min:0',
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

            // Handle stock update through inventory system
            if ($request->has('stock')) {
                $currentStock = $this->calculateProductStock($product->id);
                $newStock = $request->stock;
                
                if ($newStock != $currentStock) {
                    $adjustment = $newStock - $currentStock;
                    
                    if ($adjustment != 0) {
                        $defaultLocation = \App\Models\StockLocation::where('is_default', true)->first();
                        
                        if ($defaultLocation) {
                            $type = $adjustment > 0 ? 'in' : 'out';
                            $quantity = abs($adjustment);
                            
                            Stock::create([
                                'product_id' => $product->id,
                                'stock_location_id' => $defaultLocation->id,
                                'user_id' => auth()->id() ?? 1,
                                'type' => $type,
                                'quantity' => $quantity,
                                'previous_quantity' => $currentStock,
                                'new_quantity' => $newStock,
                                'unit_cost' => $product->price,
                                'total_cost' => $product->price * $quantity,
                                'reference_number' => 'API-FULL-' . date('YmdHis'),
                                'reference_type' => 'adjustment',
                                'adjustment_reason' => 'Stock update via API',
                                'notes' => 'Stock updated via API full update',
                                'transaction_date' => now(),
                            ]);
                        }
                    }
                    
                    // Remove stock from request
                    $requestData = $request->all();
                    unset($requestData['stock']);
                    $request->replace($requestData);
                }
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
                        'price' => $var['price'],
                        'sale_price' => $var['sale_price'] ?? null,
                        'stock' => $var['stock'] ?? 0,
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
            return response()->json([
                'success' => false,
                'message' => 'Product not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Upload an image file for products.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image: ' . $e->getMessage(),
            ], 500);
        }
    }
}