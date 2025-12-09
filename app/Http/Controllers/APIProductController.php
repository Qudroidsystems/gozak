<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class APIProductController extends Controller
{
    /**
     * Display a listing of products
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

        // Filters
        if ($request->filled('featured')) {
            $query->where('is_featured', filter_var($request->featured, FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->filled('category_id')) $query->where('category_id', $request->category_id);
        if ($request->filled('brand_id')) $query->where('brand_id', $request->brand_id);
        if ($request->filled('ids')) {
            $ids = array_filter(explode(',', $request->ids));
            $query->whereIn('id', $ids);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }
        if ($request->filled('min_price')) $query->where('price', '>=', $request->min_price);
        if ($request->filled('max_price')) $query->where('price', '<=', $request->max_price);

        try {
            if ($request->has('limit') && $request->limit != -1) {
                $limit = min(max((int)$request->limit, 1), 100);
                $products = $query->take($limit)->get();
                $total = $products->count();
            } else {
                $perPage = min(max((int)$request->input('per_page', 20), 1), 100);
                $products = $query->paginate($perPage);
                $total = $products->total();
            }

            $collection = $products instanceof \Illuminate\Pagination\LengthAwarePaginator
                ? $products->getCollection()
                : $products;

            $data = $collection->map(fn($p) => $this->formatProduct($p))->values();

            return response()->json([
                'success' => true,
                'message' => 'Products retrieved successfully',
                'data' => $data,
                'pagination' => [
                    'current_page' => $products->currentPage ?? 1,
                    'last_page'    => $products->lastPage ?? 1,
                    'per_page'     => $products->perPage ?? $total,
                    'total'        => $total,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified product
     */
    public function show($id)
    {
        try {
            $product = Product::with([
                'category:id,name',
                'brand:id,name,logo',
                'attributes',
                'variations',
                'images'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Product retrieved successfully',
                'data' => $this->formatProduct($product)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'        => 'required|string|max:255',
            'sku'          => 'required|string|unique:products,sku',
            'price'        => 'required|numeric|min:0',
            'sale_price'   => 'nullable|numeric|min:0|lt:price',
            'stock'        => 'required|integer|min:0',
            'thumbnail'    => 'nullable|string',
            'description'  => 'nullable|string',
            'product_type' => 'required|in:simple,variable',
            'is_featured'  => 'nullable|boolean',
            'brand_id'     => 'nullable|exists:brands,id',
            'category_id'  => 'nullable|exists:categories,id',
            'images'       => 'nullable|array',
            'images.*'     => 'string',
            'product_attributes' => 'nullable|array',
            'product_attributes.*.name'   => 'required_with:product_attributes|string',
            'product_attributes.*.values' => 'required_with:product_attributes|array',
            'product_variations' => 'nullable|array',
            'product_variations.*.sku'        => 'required_with:product_variations|string',
            'product_variations.*.price'      => 'required_with:product_variations|numeric|min:0',
            'product_variations.*.sale_price' => 'nullable|numeric|min:0',
            'product_variations.*.stock'      => 'required_with:product_variations|integer|min:0',
            'product_variations.*.attributes' => 'nullable|array',
            'product_variations.*.image'      => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $product = Product::create([
                'title'        => $request->title,
                'sku'          => $request->sku,
                'price'        => $request->price,
                'sale_price'   => $request->sale_price,
                'stock'        => $request->stock,
                'thumbnail'    => $request->thumbnail,
                'description'  => $request->description,
                'product_type' => $request->product_type,
                'is_featured'  => $request->boolean('is_featured', false),
                'brand_id'     => $request->brand_id,
                'category_id'  => $request->category_id,
            ]);

            $this->syncRelations($product, $request);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $this->formatProduct($product->fresh(['category', 'brand', 'attributes', 'variations', 'images']))
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title'        => 'required|string|max:255',
            'sku'          => 'required|string|unique:products,sku,' . $id,
            'price'        => 'required|numeric|min:0',
            'sale_price'   => 'nullable|numeric|min:0|lt:price',
            'stock'        => 'required|integer|min:0',
            'thumbnail'    => 'nullable|string',
            'description'  => 'nullable|string',
            'product_type' => 'required|in:simple,variable',
            'is_featured'  => 'nullable|boolean',
            'brand_id'     => 'nullable|exists:brands,id',
            'category_id'  => 'nullable|exists:categories,id',
            'images'       => 'nullable|array',
            'images.*'     => 'string',
            'product_attributes' => 'nullable|array',
            'product_variations' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $product->update([
                'title'        => $request->title,
                'sku'          => $request->sku,
                'price'        => $request->price,
                'sale_price'   => $request->sale_price,
                'stock'        => $request->stock,
                'thumbnail'    => $request->thumbnail ?? $product->thumbnail,
                'description'  => $request->description,
                'product_type' => $request->product_type,
                'is_featured'  => $request->boolean('is_featured', $product->is_featured),
                'brand_id'     => $request->brand_id,
                'category_id'  => $request->category_id,
            ]);

            $this->syncRelations($product, $request);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $this->formatProduct($product->fresh(['category', 'brand', 'attributes', 'variations', 'images']))
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);

            // Delete physical files
            if ($product->thumbnail) Storage::disk('public')->delete($product->thumbnail);
            foreach ($product->images as $img) Storage::disk('public')->delete($img->image_path);
            foreach ($product->variations as $var) if ($var->image) Storage::disk('public')->delete($var->image);

            $product->images()->delete();
            $product->attributes()->delete();
            $product->variations()->delete();
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product'
            ], 500);
        }
    }

    /**
     * Upload image (used by Flutter)
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
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $path = $request->file('image')->store('products', 'public');
            $url = asset('storage/' . $path);

            return response()->json([
                'success' => true,
                'url' => $url,
                'path' => $path,
                'message' => 'Image uploaded successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format product for API response
     */
    private function formatProduct(Product $product)
    {
        return [
            'id'            => $product->id,
            'title'         => $product->title ?? '',
            'sku'           => $product->sku ?? '',
            'stock'         => $product->stock ?? 0,
            'price'         => (float) $product->price,
            'sale_price'    => $product->sale_price ? (float) $product->sale_price : null,
            'thumbnail'     => $product->thumbnail ? asset('storage/' . $product->thumbnail) : null,
            'description'   => $product->description ?? '',
            'product_type'  => $product->product_type ?? 'simple',
            'sold_quantity' => $product->sold_quantity ?? 0,
            'is_featured'   => (bool) $product->is_featured,
            'category_id'   => $product->category_id,
            'brand_id'      => $product->brand_id,

            'brand' => $product->brand ? [
                'id'   => $product->brand->id,
                'name' => $product->brand->name ?? '',
                'logo' => $product->brand->logo ? asset('storage/' . $product->brand->logo) : null,
            ] : null,

            'category' => $product->category ? [
                'id'   => $product->category->id,
                'name' => $product->category->name ?? '',
            ] : null,

            'images' => $product->images->pluck('image_path')->map(fn($path) => asset('storage/' . $path))->toArray(),

            'product_attributes' => $product->attributes->map(fn($attr) => [
                'id'     => $attr->id,
                'name'   => $attr->name ?? '',
                'values' => is_array($attr->values) ? $attr->values : json_decode($attr->values, true) ?? [],
            ])->toArray(),

            'product_variations' => $product->variations->map(fn($var) => [
                'id'         => $var->id,
                'sku'        => $var->sku ?? '',
                'price'      => (float) $var->price,
                'sale_price' => $var->sale_price ? (float) $var->sale_price : null,
                'stock'      => $var->stock ?? 0,
                'attributes' => is_array($var->attributes) ? $var->attributes : json_decode($var->attributes, true) ?? [],
                'image'      => $var->image ? asset('storage/' . $var->image) : null,
            ])->toArray(),
        ];
    }

    /**
     * Sync images, attributes, variations
     */
    private function syncRelations(Product $product, Request $request)
    {
        if ($request->has('images')) {
            $product->images()->delete();
            foreach ($request->images as $path) {
                $product->images()->create(['image_path' => $path]);
            }
        }

        if ($request->has('product_attributes')) {
            $product->attributes()->delete();
            foreach ($request->product_attributes as $attr) {
                $product->attributes()->create([
                    'name'   => $attr['name'],
                    'values' => $attr['values']
                ]);
            }
        }

        if ($request->has('product_variations')) {
            $product->variations()->delete();
            foreach ($request->product_variations as $var) {
                $product->variations()->create([
                    'sku'        => $var['sku'],
                    'price'      => $var['price'],
                    'sale_price' => $var['sale_price'] ?? null,
                    'stock'      => $var['stock'],
                    'attributes' => $var['attributes'] ?? [],
                    'image'      => $var['image'] ?? null,
                ]);
            }
        }
    }
}