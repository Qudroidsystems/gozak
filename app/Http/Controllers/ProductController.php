<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View product|Create product|Update product|Delete product', ['only' => ['index']]);
        $this->middleware('permission:Create product', ['only' => ['store']]);
        $this->middleware('permission:Update product', ['only' => ['edit','update']]);
        $this->middleware('permission:Delete product', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $products = Product::with(['brand', 'category'])
            ->withCount('variations')
            ->latest()
            ->paginate(12);

        $brands = Brand::orderBy('name')->get();
        $categories = Category::whereNull('parent_id')->with('children')->orderBy('name')->get();

        return view('products.index', compact('products', 'brands', 'categories'));
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
            'is_featured'   => $product->is_featured,
            'brand_id'      => $product->brand_id,
            'category_id'   => $product->category_id,
            'thumbnail'     => $product->thumbnail ? asset('storage/' . $product->thumbnail) : null,
            'images'        => $product->images->pluck('image_path')->map(fn($path) => asset('storage/' . $path))->toArray(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'sku'          => 'required|string|unique:products,sku',
            'price'        => 'required|numeric|min:0',
            'sale_price'   => 'nullable|numeric|min:0',
            'stock'        => 'required|integer|min:0',
            'thumbnail'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'images.*'     => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand_id'     => 'nullable|exists:brands,id',
            'category_id'  => 'nullable|exists:categories,id',
            'description'  => 'nullable|string',
            => 'nullable|string',
            'product_type' => 'required|in:simple,variable',
            'is_featured'  => 'nullable|boolean',
        ]);

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
                $path = $image->store('products', 'public');
                $product->images()->create(['image_path' => $path]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Product created']);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'title'        => 'required|string|max:255',
            'sku'          => 'required|string|unique:products,sku,' . $id,
            'price'        => 'required|numeric|min:0',
            'sale_price'   => 'nullable|numeric|min:0',
            'stock'        => 'required|integer|min:0',
            'thumbnail'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'images.*'     => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand_id'     => 'nullable|exists:brands,id',
            'category_id'  => 'nullable|exists:categories,id',
            'description'  => 'nullable|string',
            'product_type' => 'required|in:simple,variable',
            'is_featured'  => 'nullable|boolean',
        ]);

        $data = $request->only([
            'title','sku','price','sale_price','stock','description',
            'product_type','is_featured','brand_id','category_id'
        ]);

        if ($request->hasFile('thumbnail')) {
            if ($product->thumbnail) {
                Storage::disk('public')->delete($product->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('products', 'public');
        }

        $product->update($data);

        // Handle gallery
        if ($request->hasFile('images')) {
            foreach ($product->images as $img) {
                Storage::disk('public')->delete($img->image_path);
            }
            $product->images()->delete();

            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $product->images()->create(['image_path' => $path]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Product updated']);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Delete images
        if ($product->thumbnail) {
            Storage::disk('public')->delete($product->thumbnail);
        }
        foreach ($product->images as $img) {
            Storage::disk('public')->delete($img->image_path);
        }

        $product->delete();

        return response()->json(['success' => true]);
    }
}