<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View product|Create product|Update product|Delete product', ['only' => ['index']]);
        $this->middleware('permission:Create product', ['only' => ['store']]);
        $this->middleware('permission:Update product', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete product', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $pagetitle = "Product Management";
        
        // Start query
        $query = Product::with(['brand', 'category', 'images'])
            ->withCount('variations')
            ->latest();
        
        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('brand', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('category', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Apply brand filter (multiple)
        if ($request->has('brands') && !empty($request->brands)) {
            $brandIds = is_array($request->brands) ? $request->brands : explode(',', $request->brands);
            $query->whereIn('brand_id', $brandIds);
        }
        
        // Apply category filter
        if ($request->has('category') && !empty($request->category)) {
            $query->where('category_id', $request->category);
        }
        
        // Apply stock filter
        if ($request->has('stock') && !empty($request->stock)) {
            switch ($request->stock) {
                case 'in_stock':
                    $query->where('stock', '>', 10);
                    break;
                case 'low_stock':
                    $query->whereBetween('stock', [1, 10]);
                    break;
                case 'out_of_stock':
                    $query->where('stock', '=', 0);
                    break;
            }
        }
        
        // Apply featured filter
        if ($request->has('featured') && !empty($request->featured)) {
            $query->where('is_featured', $request->featured === 'yes' ? 1 : 0);
        }
        
        $products = $query->paginate(12)->appends($request->all());

        $brands = Brand::orderBy('name')->get();
        $categories = Category::whereNull('parent_id')->with('children')->orderBy('name')->get();

        return view('products.index', compact('products', 'brands', 'categories', 'pagetitle'));
    }

    public function create()
    {
        $brands = Brand::orderBy('name')->get();
        $categories = Category::whereNull('parent_id')->with('children')->orderBy('name')->get();
        
        return response()->json([
            'brands' => $brands,
            'categories' => $categories
        ]);
    }

    public function edit($id)
    {
        $product = Product::with(['brand', 'category', 'images'])->findOrFail($id);

        return response()->json([
            'id'            => $product->id,
            'title'         => $product->title,
            'sku'           => $product->sku,
            'price'         => $product->price,
            'sale_price'    => $product->sale_price,
            'stock'         => $product->stock,
            'description'   => $product->description,
            'product_type'  => $product->product_type ?? 'simple',
            'is_featured'   => (bool) $product->is_featured,
            'brand_id'      => $product->brand_id,
            'category_id'   => $product->category_id,
            'thumbnail'     => $product->thumbnail ? asset('storage/' . $product->thumbnail) : null,
            'images'        => $product->images->map(function($image) {
                return [
                    'id' => $image->id,
                    'url' => asset('storage/' . $image->image_path),
                    'path' => $image->image_path
                ];
            })->toArray(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'        => 'required|string|max:255',
            'sku'          => 'required|string|unique:products,sku',
            'price'        => 'required|numeric|min:0',
            'sale_price'   => 'nullable|numeric|min:0|lt:price',
            'stock'        => 'required|integer|min:0',
            'thumbnail'    => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'images.*'     => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'brand_id'     => 'nullable|exists:brands,id',
            'category_id'  => 'nullable|exists:categories,id',
            'description'  => 'nullable|string',
            'product_type' => 'required|in:simple,variable',
            'is_featured'  => 'nullable|boolean',
        ], [
            'sale_price.lt' => 'Sale price must be less than regular price',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $thumbnailPath = null;
            if ($request->hasFile('thumbnail')) {
                $thumbnailPath = $request->file('thumbnail')->store('products', 'public');
            }

            $product = Product::create([
                'title'         => $request->title,
                'sku'           => $request->sku,
                'price'         => $request->price,
                'sale_price'    => $request->sale_price,
                'stock'         => $request->stock,
                'thumbnail'     => $thumbnailPath,
                'description'   => $request->description,
                'product_type'  => $request->product_type,
                'is_featured'   => $request->boolean('is_featured'),
                'brand_id'      => $request->brand_id,
                'category_id'   => $request->category_id,
            ]);

            // Save gallery images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('products/gallery', 'public');
                    $product->images()->create(['image_path' => $path]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'product' => $product
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title'        => 'required|string|max:255',
            'sku'          => 'required|string|unique:products,sku,' . $id,
            'price'        => 'required|numeric|min:0',
            'sale_price'   => 'nullable|numeric|min:0|lt:price',
            'stock'        => 'required|integer|min:0',
            'thumbnail'    => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'images.*'     => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'brand_id'     => 'nullable|exists:brands,id',
            'category_id'  => 'nullable|exists:categories,id',
            'description'  => 'nullable|string',
            'product_type' => 'required|in:simple,variable',
            'is_featured'  => 'nullable|boolean',
        ], [
            'sale_price.lt' => 'Sale price must be less than regular price',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->only([
                'title', 'sku', 'price', 'sale_price', 'stock', 'description',
                'product_type', 'brand_id', 'category_id'
            ]);
            
            $data['is_featured'] = $request->boolean('is_featured');

            if ($request->hasFile('thumbnail')) {
                // Delete old thumbnail
                if ($product->thumbnail) {
                    Storage::disk('public')->delete($product->thumbnail);
                }
                $data['thumbnail'] = $request->file('thumbnail')->store('products', 'public');
            }

            $product->update($data);

            // Handle gallery images - Only add new ones, don't delete old ones
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('products/gallery', 'public');
                    $product->images()->create(['image_path' => $path]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'product' => $product
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);

            // Delete thumbnail
            if ($product->thumbnail) {
                Storage::disk('public')->delete($product->thumbnail);
            }

            // Delete gallery images
            foreach ($product->images as $img) {
                Storage::disk('public')->delete($img->image_path);
            }
            $product->images()->delete();

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteImage($id, $imageId)
    {
        try {
            $product = Product::findOrFail($id);
            $image = $product->images()->findOrFail($imageId);
            
            Storage::disk('public')->delete($image->image_path);
            $image->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image'
            ], 500);
        }
    }
}