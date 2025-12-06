<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\ProductReview;
use App\Exports\ProductsExport;
use App\Imports\ProductsImport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View product|Create product|Update product|Delete product', ['only' => ['index', 'show']]);
        $this->middleware('permission:Create product', ['only' => ['store']]);
        $this->middleware('permission:Update product', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete product', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $pagetitle = "Product Management";

        $query = Product::with(['brand', 'category', 'images'])
            ->withCount('variations')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('brand', fn($q) => $q->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('category', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('brands')) {
            $brandIds = is_array($request->brands) ? $request->brands : explode(',', $request->brands);
            $query->whereIn('brand_id', $brandIds);
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('stock')) {
            switch ($request->stock) {
                case 'in_stock':     $query->where('stock', '>', 10); break;
                case 'low_stock':    $query->whereBetween('stock', [1, 10]); break;
                case 'out_of_stock': $query->where('stock', 0); break;
            }
        }

        if ($request->filled('featured')) {
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
            'categories' => $categories,
        ]);
    }

    public function edit($id)
    {
        $product = Product::with([
            'brand',
            'category',
            'images',
            'attributes',
            'variations'
        ])->findOrFail($id);

        return response()->json([
            'id'           => $product->id,
            'title'        => $product->title,
            'sku'          => $product->sku,
            'price'        => $product->price,
            'sale_price'   => $product->sale_price,
            'stock'        => $product->stock,
            'description'  => $product->description ?? '',
            'product_type' => $product->product_type ?? 'simple',
            'is_featured'  => (bool) $product->is_featured,
            'brand_id'     => $product->brand_id,
            'category_id'  => $product->category_id,

            // Thumbnail
            'thumbnail' => $product->thumbnail 
                ? asset('storage/' . $product->thumbnail) 
                : null,

            // Gallery Images
            'gallery' => $product->images->map(function ($img) {
                return [
                    'id'  => $img->id,
                    'url' => asset('storage/' . $img->image_path),
                ];
            })->toArray(),

            // Attributes (for variable products)
            'attributes' => $product->attributes->map(function ($attr) {
                return [
                    'name'   => $attr->name,
                    'values' => is_array($attr->values) 
                        ? implode(', ', $attr->values) 
                        : $attr->values
                ];
            })->toArray(),

            // Variations
            'variations' => $product->variations->map(function ($var) {
                return [
                    'id'         => $var->id,
                    'sku'        => $var->sku ?? '',
                    'price'      => $var->price,
                    'sale_price' => $var->sale_price,
                    'stock'      => $var->stock,
                    'image'      => $var->image 
                        ? asset('storage/' . $var->image) 
                        : null,
                    'attributes' => is_array($var->attributes) ? $var->attributes : json_decode($var->attributes, true) ?? []
                ];
            })->toArray(),
        ]);
    }

    public function store(Request $request)
    {
        // ADD THIS LINE
        $request->merge(['is_featured' => $request->has('is_featured')]);
        
        $rules = [
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
            'is_featured'  => 'required|boolean', // now accepts true/false
        ];

        $validator = Validator::make($request->all(), $rules, [
            'sale_price.lt' => 'Sale price must be less than regular price',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $thumbnailPath = null;
            if ($request->hasFile('thumbnail')) {
                $thumbnailPath = $request->file('thumbnail')->store('product', 'public');
            }

            $product = Product::create([
                'title'        => $request->title,
                'sku'          => $request->sku,
                'price'        => $request->price,
                'sale_price'   => $request->sale_price,
                'stock'        => $request->stock,
                'thumbnail'    => $thumbnailPath,
                'description'  => $request->description,
                'product_type' => $request->product_type,
                'is_featured'  => $request->boolean('is_featured'),
                'brand_id'     => $request->brand_id,
                'category_id'  => $request->category_id,
            ]);

            $this->syncImages($product, $request);
            $this->syncAttributesAndVariations($product, $request);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'product' => $product->load('variations')
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

        // ADD THIS LINE
       $request->merge(['is_featured' => $request->has('is_featured')]);

        $rules = [
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
            'is_featured'  => 'required|boolean', // now accepts true/false
        ];

        $validator = Validator::make($request->all(), $rules, [
            'sale_price.lt' => 'Sale price must be less than regular price',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->only([
                'title', 'sku', 'price', 'sale_price', 'stock', 'description',
                'product_type', 'brand_id', 'category_id'
            ]);
            $data['is_featured'] = $request->boolean('is_featured');

            if ($request->hasFile('thumbnail')) {
                if ($product->thumbnail) {
                    Storage::disk('public')->delete($product->thumbnail);
                }
                $data['thumbnail'] = $request->file('thumbnail')->store('product', 'public');
            }

            $product->update($data);

            $this->syncImages($product, $request);
            $this->syncAttributesAndVariations($product, $request);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'product' => $product->load('variations')
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

            if ($product->thumbnail) {
                Storage::disk('public')->delete($product->thumbnail);
            }

            foreach ($product->images as $img) {
                Storage::disk('public')->delete($img->image_path);
            }

            foreach ($product->variations as $variation) {
                if ($variation->image) {
                    Storage::disk('public')->delete($variation->image);
                }
            }

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

    public function show($id)
    {
        $product = Product::with([
            'brand',
            'category',
            'images',
            'reviews.user',
            'reviews' => fn($q) => $q->with(['user' => fn($q) => $q->select('id', 'first_name', 'last_name', 'profile_image')])->latest()
        ])
        ->withCount(['variations', 'reviews', 'orderItems as order_items_count'])
        ->findOrFail($id);

        $totalSold = $product->orderItems()->sum('quantity');
        $averageRating = $product->reviews->avg('rating') ?? 0;
        $ratingBreakdown = [
            '5' => $product->reviews->where('rating', 5)->count(),
            '4' => $product->reviews->where('rating', 4)->count(),
            '3' => $product->reviews->where('rating', 3)->count(),
            '2' => $product->reviews->where('rating', 2)->count(),
            '1' => $product->reviews->where('rating', 1)->count(),
        ];

        $price = $product->sale_price ?? $product->price;
        $revenue = $totalSold * $price;

        $pagetitle = $product->title . ' - Product Details';

        return view('products.show', compact(
            'product', 'pagetitle', 'averageRating', 'ratingBreakdown', 'revenue', 'totalSold'
        ));
    }

    public function storeReview(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'rating'   => 'required|numeric|min:1|max:5',
            'comment'  => 'required|string|max:1000',
            'user_name'=> 'nullable|string|max:255',
        ]);

        $review = ProductReview::create([
            'product_id' => $product->id,
            'user_id'    => auth()->id() ?? null,
            'rating'     => $request->rating,
            'comment'    => $request->comment,
            'user_name'  => $request->user_name ?? (auth()->user()?->name ?? 'Anonymous'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review added successfully',
            'review'  => $review
        ]);
    }

    private function syncImages($product, $request)
    {
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('product/gallery', 'public');
                $product->images()->create(['image_path' => $path]);
            }
        }
    }

    private function syncAttributesAndVariations($product, $request)
    {
        $product->attributes()->delete();
        $product->variations()->delete();

        if ($request->has('attributes') && is_array($request->attributes)) {
            foreach ($request->attributes as $attr) {
                $name = trim($attr['name'] ?? '');
                $valuesInput = trim($attr['values'] ?? '');
                if ($name && $valuesInput) {
                    $values = array_filter(preg_split('/[\s,]+/', $valuesInput));
                    $product->attributes()->create([
                        'name'   => $name,
                        'values' => $values
                    ]);
                }
            }
        }

        if ($request->product_type === 'variable' && $request->has('variations') && is_array($request->variations)) {
            foreach ($request->variations as $var) {
                $imagePath = null;
                if (isset($var['image']) && $var['image'] instanceof \Illuminate\Http\UploadedFile) {
                    $imagePath = $var['image']->store('product/variations', 'public');
                }

                $attributes = [];
                if (isset($var['attributes']) && is_array($var['attributes'])) {
                    foreach ($var['attributes'] as $key => $value) {
                        if ($value !== null && $value !== '') {
                            $attributes[$key] = $value;
                        }
                    }
                }

                $product->variations()->create([
                    'price'      => $var['price'] ?? 0,
                    'sale_price' => $var['sale_price'] ?? null,
                    'stock'      => $var['stock'] ?? 0,
                    'image'      => $imagePath,
                    'attributes' => $attributes
                ]);
            }
        }
    }

    public function search(Request $request)
    {
        $query = $request->q;
        $products = Product::where('title', 'like', "%{$query}%")
            ->orWhere('sku', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'title', 'sku', 'price', 'thumbnail']);

        return response()->json($products);
    }


  

    public function inventoryLog($id)
    {
        try {
            $product = Product::findOrFail($id);
            $logs = $product->inventoryLogs()
                ->with('user:id,name,email')
                ->latest()
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'type' => $log->type,
                        'quantity' => $log->quantity,
                        'previous_stock' => $log->previous_stock,
                        'new_stock' => $log->new_stock,
                        'reference' => $log->reference,
                        'notes' => $log->notes,
                        'user_name' => $log->user ? $log->user->name : 'System',
                        'created_at' => $log->created_at->toDateTimeString(),
                        'formatted_date' => $log->created_at->format('M d, Y h:i A'),
                        'formatted_quantity' => $log->formatted_quantity,
                        'type_name' => $log->type_name,
                        'type_class' => $log->type_class
                    ];
                });
            
            return response()->json($logs);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load inventory data: ' . $e->getMessage()
            ], 500);
        }
    }


    public function template()
    {
        $headers = [
            'title','sku','price','sale_price','stock','description','brand_id','category_id','is_featured'
        ];
        
        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="product_import_template.csv"',
        ]);
    }

    // ADD THIS - for real-time stock
    public function realtimeStock()
    {
        $products = Product::select('id', 'stock')->get();
        return response()->json($products);
    }

    
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:csv,txt']);
        Excel::import(new ProductsImport, $request->file('file'));
        return response()->json(['message' => 'Import completed']);
    }

    public function export()
    {
        return Excel::download(new ProductsExport, 'products_' . now()->format('Y-m-d_His') . '.csv');
    }

    public function bulkUpdate(Request $request)
    {
        $ids = explode(',', $request->product_ids);
        $data = $request->only(['price', 'sale_price', 'stock', 'is_featured', 'category_id']);
        $data = array_filter($data);

        Product::whereIn('id', $ids)->update($data);

        return response()->json(['message' => 'Bulk update completed']);
    }
}