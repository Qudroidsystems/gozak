<?php
namespace App\Http\Controllers;

use App\Models\Brand;
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
     * Get a list of products with optional filters and limit.
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

        try {
            // Handle limit parameter
            if ($request->has('limit') && $request->limit != -1) {
                $limit = min(max((int) $request->limit, 1), 100); // Ensure limit is between 1 and 100
                $products = $query->take($limit)->get();
                $formattedProducts = $products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'title' => $product->title ?? '',
                        'sku' => $product->sku ?? '',
                        'stock' => $product->stock ?? 0,
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
                    ];
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

            // Apply pagination if no limit is specified
            $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
            $products = $query->paginate($perPage);

            $formattedProducts = collect($products->items())->map(function ($product) {
                return [
                    'id' => $product->id,
                    'title' => $product->title ?? '',
                    'sku' => $product->sku ?? '',
                    'stock' => $product->stock ?? 0,
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
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $formattedProducts,
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'total' => $products->total(),
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
     * Get a single product by ID.
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

            $formattedProduct = [
                'id' => $product->id,
                'title' => $product->title ?? '',
                'sku' => $product->sku ?? '',
                'stock' => $product->stock ?? 0,
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
            ];

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
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku',
            'stock' => 'required|integer|min:0',
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
            $product = Product::create($request->only([
                'title', 'sku', 'stock', 'price', 'sale_price', 'thumbnail', 'description',
                'product_type', 'sold_quantity', 'is_featured', 'category_id', 'brand_id'
            ]));

            if ($request->has('images')) {
                foreach ($request->images as $imagePath) {
                    $cleanPath = preg_replace('/^storage\//', '', $imagePath);
                    $product->images()->create(['image_path' => $cleanPath]);
                }
            }

            if ($request->has('product_attributes')) {
                foreach ($request->product_attributes as $attr) {
                    $product->attributes()->create([
                        'name' => $attr['name'],
                        'values' => $attr['values'],
                    ]);
                }
            }

            if ($request->has('product_variations')) {
                foreach ($request->product_variations as $var) {
                    $cleanImagePath = isset($var['image']) ? preg_replace('/^storage\//', '', $var['image']) : null;
                    $product->variations()->create([
                        'sku' => $var['sku'],
                        'price' => $var['price'],
                        'sale_price' => $var['sale_price'] ?? null,
                        'stock' => $var['stock'],
                        'attributes' => $var['attributes'] ?? [],
                        'image' => $cleanImagePath,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $product->load(['category:id,name', 'brand:id,name,logo', 'attributes', 'variations', 'images']),
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
                'stock' => 'integer|min:0',
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

            $product->update($request->only([
                'title', 'sku', 'stock', 'price', 'sale_price', 'thumbnail', 'description',
                'product_type', 'sold_quantity', 'is_featured', 'category_id', 'brand_id'
            ]));

            return response()->json([
                'success' => true,
                'data' => $product->load(['category:id,name', 'brand:id,name,logo', 'attributes', 'variations', 'images']),
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
                'stock' => 'required|integer|min:0',
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

            $product->update($request->only([
                'title', 'sku', 'stock', 'price', 'sale_price', 'thumbnail', 'description',
                'product_type', 'sold_quantity', 'is_featured', 'category_id', 'brand_id'
            ]));

            if ($request->has('images')) {
                $product->images()->delete();
                foreach ($request->images as $imagePath) {
                    $cleanPath = preg_replace('/^storage\//', '', $imagePath);
                    $product->images()->create(['image_path' => $cleanPath]);
                }
            }

            if ($request->has('product_attributes')) {
                $product->attributes()->delete();
                foreach ($request->product_attributes as $attr) {
                    $product->attributes()->create([
                        'name' => $attr['name'],
                        'values' => $attr['values'],
                    ]);
                }
            }

            if ($request->has('product_variations')) {
                $product->variations()->delete();
                foreach ($request->product_variations as $var) {
                    $cleanImagePath = isset($var['image']) ? preg_replace('/^storage\//', '', $var['image']) : null;
                    $product->variations()->create([
                        'sku' => $var['sku'],
                        'price' => $var['price'],
                        'sale_price' => $var['sale_price'] ?? null,
                        'stock' => $var['stock'],
                        'attributes' => $var['attributes'] ?? [],
                        'image' => $cleanImagePath,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $product->load(['category:id,name', 'brand:id,name,logo', 'attributes', 'variations', 'images']),
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

    /**
     * Create a product-category relationship.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    // public function storeProductCategory(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'product_id' => 'required|exists:products,id',
    //         'category_id' => 'required|exists:categories,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation failed',
    //             'errors' => $validator->errors(),
    //         ], 422);
    //     }

    //     try {
    //         $productCategory = ProductCategory::create([
    //             'product_id' => $request->product_id,
    //             'category_id' => $request->category_id,
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'data' => [
    //                 'product_id' => $productCategory->product_id,
    //                 'category_id' => $productCategory->category_id,
    //             ],
    //             'message' => 'Product-category relationship created successfully',
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to create product-category relationship: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }
}