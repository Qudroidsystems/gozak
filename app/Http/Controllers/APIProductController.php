<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class APIProductController extends Controller
{
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

        if ($request->has('limit')) {
            $query->limit($request->limit);
        }

        $products = $query->get()->filter()->map(function ($product) {
            return [
                'id' => $product->id,
                'title' => $product->title ?? '',
                'sku' => $product->sku ?? '',
                'stock' => $product->stock ?? 0,
                'price' => $product->price ?? 0.0,
                'sale_price' => $product->sale_price ?? 0.0,
                'thumbnail' => $product->thumbnail ? url(Storage::url($product->thumbnail)) : '',
                'description' => $product->description ?? '',
                'product_type' => $product->product_type ?? '',
                'sold_quantity' => $product->sold_quantity ?? 0,
                'is_featured' => $product->is_featured ?? false,
                'category_id' => $product->category_id,
                'brand_id' => $product->brand_id,
                'brand' => $product->brand ? [
                    'id' => $product->brand->id,
                    'name' => $product->brand->name ?? '',
                    'logo' => $product->brand->logo ? url(Storage::url($product->brand->logo)) : '',
                ] : null,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name ?? '',
                ] : null,
                'images' => $product->images ? $product->images->pluck('image_path')->map(function ($path) {
                    // Remove 'storage/' prefix if present to avoid double 'storage/storage/'
                    $cleanPath = preg_replace('/^storage\//', '', $path);
                    return $cleanPath ? url(Storage::url($cleanPath)) : '';
                })->toArray() : [],
                'product_attributes' => $product->attributes ? $product->attributes->map(function ($attr) {
                    return [
                        'id' => $attr->id,
                        'name' => $attr->name ?? '',
                        'values' => $attr->values ?? [],
                    ];
                })->toArray() : [],
                'product_variations' => $product->variations ? $product->variations->map(function ($var) {
                    // Remove 'storage/' prefix if present
                    $cleanImagePath = $var->image ? preg_replace('/^storage\//', '', $var->image) : '';
                    return [
                        'id' => $var->id,
                        'sku' => $var->sku ?? '',
                        'price' => $var->price ?? 0.0,
                        'sale_price' => $var->sale_price ?? 0.0,
                        'stock' => $var->stock ?? 0,
                        'attributes' => $var->attributes ?? [],
                        'image' => $cleanImagePath ? url(Storage::url($cleanImagePath)) : '',
                    ];
                })->toArray() : [],
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function show($id)
    {
        $product = Product::query()
            ->select('id', 'title', 'sku', 'stock', 'price', 'sale_price', 'thumbnail', 'description', 'product_type', 'sold_quantity', 'is_featured', 'category_id', 'brand_id')
            ->with([
                'category:id,name',
                'brand:id,name,logo',
                'attributes:id,product_id,name,values',
                'variations:id,product_id,sku,price,sale_price,stock,attributes,image',
                'images:id,product_id,image_path'
            ])
            ->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $formattedProduct = [
            'id' => $product->id,
            'title' => $product->title ?? '',
            'sku' => $product->sku ?? '',
            'stock' => $product->stock ?? 0,
            'price' => $product->price ?? 0.0,
            'sale_price' => $product->sale_price ?? 0.0,
            'thumbnail' => $product->thumbnail ? url(Storage::url($product->thumbnail)) : '',
            'description' => $product->description ?? '',
            'product_type' => $product->product_type ?? '',
            'sold_quantity' => $product->sold_quantity ?? 0,
            'is_featured' => $product->is_featured ?? false,
            'category_id' => $product->category_id,
            'brand_id' => $product->brand_id,
            'brand' => $product->brand ? [
                'id' => $product->brand->id,
                'name' => $product->brand->name ?? '',
                'logo' => $product->brand->logo ? url(Storage::url($product->brand->logo)) : '',
            ] : null,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name ?? '',
            ] : null,
            'images' => $product->images ? $product->images->pluck('image_path')->map(function ($path) {
                // Remove 'storage/' prefix if present
                $cleanPath = preg_replace('/^storage\//', '', $path);
                return $cleanPath ? url(Storage::url($cleanPath)) : '';
            })->toArray() : [],
            'product_attributes' => $product->attributes ? $product->attributes->map(function ($attr) {
                return [
                    'id' => $attr->id,
                    'name' => $attr->name ?? '',
                    'values' => $attr->values ?? [],
                ];
            })->toArray() : [],
            'product_variations' => $product->variations ? $product->variations->map(function ($var) {
                // Remove 'storage/' prefix if present
                $cleanImagePath = $var->image ? preg_replace('/^storage\//', '', $var->image) : '';
                return [
                    'id' => $var->id,
                    'sku' => $var->sku ?? '',
                    'price' => $var->price ?? 0.0,
                    'sale_price' => $var->sale_price ?? 0.0,
                    'stock' => $var->stock ?? 0,
                    'attributes' => $var->attributes ?? [],
                    'image' => $cleanImagePath ? url(Storage::url($cleanImagePath)) : '',
                ];
            })->toArray() : [],
        ];

        return response()->json([
            'success' => true,
            'data' => $formattedProduct,
        ]);
    }

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
            'is_featured' => 'boolean',
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
        ], 201);
    }

    public function updateSingleField(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

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
            'data' => $product,
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

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
            'is_featured' => 'boolean',
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
        ]);
    }

    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:jpeg,png,jpg|max:2048',
            'type' => 'required|in:image',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->type === 'image') {
            $path = $request->file('file')->store('public');
            return response()->json([
                'success' => true,
                'url' => url(Storage::url($path)),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid file type',
        ], 400);
    }
}