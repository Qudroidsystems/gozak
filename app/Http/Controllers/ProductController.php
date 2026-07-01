<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\LightningDeal;
use Illuminate\Http\Request;
use App\Models\ProductReview;
use App\Exports\ProductsExport;
use App\Imports\ProductsImport;
use App\Http\Controllers\Controller;
use App\Services\LightningDealNotificationService;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View product|Create product|Update product|Delete product', ['only' => ['index', 'show']]);
        $this->middleware('permission:Create product', ['only' => ['store']]);
        $this->middleware('permission:Update product', ['only' => ['edit', 'update', 'updateFlags', 'bulkUpdateFlags']]);
        $this->middleware('permission:Delete product', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $pagetitle = "Product Management";

        $query = Product::with(['brand', 'category', 'images', 'units'])
            ->withCount('variations')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('brand', fn($q) => $q->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('category', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('stock')) {
            switch ($request->stock) {
                case 'in_stock':     $query->where('stock', '>', 10); break;
                case 'low_stock':    $query->whereBetween('stock', [1, 10]); break;
                case 'out_of_stock': $query->where('stock', 0); break;
            }
        }

        if ($request->filled('app_filter')) {
            switch ($request->app_filter) {
                case 'new':       $query->where('is_new', true); break;
                case 'trending':  $query->where('is_trending', true); break;
                case 'top_rated': $query->where('is_top_rated', true); break;
                case 'on_sale':   $query->whereNotNull('sale_price')->where('sale_price', '>', 0)->whereColumn('sale_price', '<', 'price'); break;
            }
        }

        $products  = $query->paginate(12)->appends($request->all());
        $brands    = Brand::orderBy('name')->get();
        $categories = Category::whereNull('parent_id')->with('children')->orderBy('name')->get();
        $units     = Unit::orderBy('name')->get();

        $analytics = [
            'total_products'   => Product::count(),
            'total_revenue'    => Product::sum('price'),
            'total_cost_value' => Product::sum('cost_price'),
            'low_stock_count'  => Product::where('stock', '<=', 10)->where('stock', '>', 0)->count(),
            'top_products'     => Product::orderBy('sold_quantity', 'desc')->limit(5)->get(),
        ];

        return view('products.index', compact('products', 'brands', 'categories', 'units', 'pagetitle', 'analytics'));
    }

    public function create()
    {
        $brands     = Brand::orderBy('name')->get();
        $categories = Category::whereNull('parent_id')->with('children')->orderBy('name')->get();
        $units      = Unit::orderBy('name')->get();

        return response()->json([
            'brands'     => $brands,
            'categories' => $categories,
            'units'      => $units,
        ]);
    }

    public function edit($id)
    {
        $product = Product::with([
            'brand', 'category', 'images', 'attributes', 'variations', 'units'
        ])->findOrFail($id);

        $units = Unit::orderBy('name')->get();

        // Get the primary unit (first unit with quantity_per_unit = 1 or simply the first one)
        $primaryUnit = $product->units->first();

        $attributes = $product->attributes->map(function ($attr) {
            $values = is_array($attr->values)
                ? implode(', ', $attr->values)
                : ($attr->values ?? '');
            return ['name' => $attr->name, 'values' => $values];
        })->toArray();

        $variations = $product->variations->map(function ($var) {
            $attributes = is_array($var->attributes)
                ? $var->attributes
                : (json_decode($var->attributes, true) ?? []);
            return [
                'id'         => $var->id,
                'sku'        => $var->sku ?? '',
                'barcode'    => $var->barcode ?? '',
                'price'      => $var->price ?? 0,
                'cost_price' => $var->cost_price ?? 0,
                'sale_price' => $var->sale_price ?? null,
                'image'      => $var->image ? asset('storage/' . $var->image) : null,
                'attributes' => $attributes,
            ];
        })->toArray();

        // Get additional units (excluding primary)
        $additionalUnits = $product->units->skip(1)->map(function ($unit) {
            return [
                'unit_id'           => $unit->id,
                'quantity_per_unit' => $unit->pivot->quantity_per_unit,
            ];
        })->toArray();

        return response()->json([
            'id'               => $product->id,
            'title'            => $product->title,
            'sku'              => $product->sku,
            'barcode'          => $product->barcode ?? '',
            'price'            => $product->price,
            'cost_price'       => $product->cost_price ?? 0,
            'sale_price'       => $product->sale_price ?? null,
            'description'      => $product->description ?? '',
            'product_type'     => $product->product_type ?? 'simple',
            'is_featured'      => (bool) $product->is_featured,
            'is_new'           => (bool) $product->is_new,
            'is_trending'      => (bool) $product->is_trending,
            'is_top_rated'     => (bool) $product->is_top_rated,
            'brand_id'         => $product->brand_id,
            'category_id'      => $product->category_id,
            'primary_unit_id'  => $primaryUnit?->id,
            'additional_units' => $additionalUnits,
            'thumbnail'  => $product->thumbnail ? asset('storage/' . $product->thumbnail) : null,
            'gallery'    => $product->images->map(function ($img) {
                return ['id' => $img->id, 'url' => asset('storage/' . $img->image_path)];
            })->toArray(),
            'attributes' => $attributes,
            'variations' => $variations,
            'units'      => $units,
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'is_featured'  => $request->has('is_featured'),
            'is_new'       => $request->has('is_new'),
            'is_trending'  => $request->has('is_trending'),
            'is_top_rated' => $request->has('is_top_rated'),
        ]);

        $rules = [
            'title'                      => 'required|string|max:255',
            'sku'                        => 'required|string|unique:products,sku',
            'barcode'                    => 'nullable|string|unique:products,barcode',
            'price'                      => 'required|numeric|min:0',
            'cost_price'                 => 'nullable|numeric|min:0',
            'sale_price'                 => 'nullable|numeric|min:0|lt:price',
            'thumbnail'                  => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'images.*'                   => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'brand_id'                   => 'nullable|exists:brands,id',
            'category_id'                => 'nullable|exists:categories,id',
            'primary_unit_id'            => 'required|exists:units,id',
            'description'                => 'nullable|string',
            'product_type'               => 'required|in:simple,variable',
            'is_featured'                => 'boolean',
            'is_new'                     => 'boolean',
            'is_trending'                => 'boolean',
            'is_top_rated'               => 'boolean',
            'units.*.unit_id'            => 'nullable|exists:units,id|distinct',
            'units.*.quantity_per_unit'  => 'nullable|numeric|min:0.01',
            'variations.*.sku'           => 'nullable|string',
            'variations.*.barcode'       => 'nullable|string|max:50',
            'variations.*.price'         => 'required|numeric|min:0',
            'variations.*.cost_price'    => 'nullable|numeric|min:0',
            'variations.*.sale_price'    => 'nullable|numeric|min:0',
            'variations.*.image'         => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $thumbnailPath = null;
            if ($request->hasFile('thumbnail')) {
                $thumbnailPath = $request->file('thumbnail')->store('product', 'public');
            }

            $product = Product::create([
                'title'        => $request->title,
                'sku'          => $request->sku,
                'barcode'      => $request->barcode ?? $this->generateMainBarcode($request->sku),
                'price'        => $request->price,
                'cost_price'   => $request->cost_price,
                'sale_price'   => $request->sale_price,
                'stock'        => 0,
                'thumbnail'    => $thumbnailPath,
                'description'  => $request->description,
                'product_type' => $request->product_type,
                'is_featured'  => $request->boolean('is_featured'),
                'is_new'       => $request->boolean('is_new'),
                'is_trending'  => $request->boolean('is_trending'),
                'is_top_rated' => $request->boolean('is_top_rated'),
                'brand_id'     => $request->brand_id,
                'category_id'  => $request->category_id,
            ]);

            $this->syncUnits($product, $request);
            $this->syncImages($product, $request);
            $this->syncAttributesAndVariations($product, $request);

            return response()->json(['success' => true, 'message' => 'Product created successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create product: ' . $e->getMessage()], 500);
        }
    }


    public function update(Request $request, $id)
{
    $product = Product::findOrFail($id);

    $request->merge([
        'is_featured'  => $request->has('is_featured'),
        'is_new'       => $request->has('is_new'),
        'is_trending'  => $request->has('is_trending'),
        'is_top_rated' => $request->has('is_top_rated'),
    ]);

    // Build validation rules
    $rules = [
        'title'                      => 'required|string|max:255',
        'price'                      => 'required|numeric|min:0',
        'cost_price'                 => 'nullable|numeric|min:0',
        'sale_price'                 => 'nullable|numeric|min:0|lt:price',
        'thumbnail'                  => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        'images.*'                   => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        'brand_id'                   => 'nullable|exists:brands,id',
        'category_id'                => 'nullable|exists:categories,id',
        'primary_unit_id'            => 'required|exists:units,id',
        'description'                => 'nullable|string',
        'product_type'               => 'required|in:simple,variable',
        'is_featured'                => 'boolean',
        'is_new'                     => 'boolean',
        'is_trending'                => 'boolean',
        'is_top_rated'               => 'boolean',
        'units.*.unit_id'            => 'nullable|exists:units,id|distinct',
        'units.*.quantity_per_unit'  => 'nullable|numeric|min:0.01',
        'variations.*.sku'           => 'nullable|string',
        'variations.*.barcode'       => 'nullable|string|max:50',
        'variations.*.price'         => 'required|numeric|min:0',
        'variations.*.cost_price'    => 'nullable|numeric|min:0',
        'variations.*.sale_price'    => 'nullable|numeric|min:0',
        'variations.*.image'         => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
    ];

    // SKU and Barcode validation with ignore
    $rules['sku'] = [
        'required',
        'string',
        Rule::unique('products')->ignore($product->id)
    ];

    $rules['barcode'] = [
        'nullable',
        'string',
        Rule::unique('products')->ignore($product->id)
    ];

    $validator = Validator::make($request->all(), $rules);

    // Additional check to ensure validation is working
    if ($validator->fails()) {
        \Log::error('Validation failed:', $validator->errors()->toArray());
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        $data = $request->only(['title', 'sku', 'barcode', 'price', 'cost_price', 'sale_price', 'description', 'product_type', 'brand_id', 'category_id']);
        $data['is_featured']  = $request->boolean('is_featured');
        $data['is_new']       = $request->boolean('is_new');
        $data['is_trending']  = $request->boolean('is_trending');
        $data['is_top_rated'] = $request->boolean('is_top_rated');

        // If barcode is empty, generate one
        if (empty($data['barcode'])) {
            $data['barcode'] = $this->generateMainBarcode($data['sku']);
        }

        // Handle thumbnail
        if ($request->hasFile('thumbnail')) {
            if ($product->thumbnail) {
                Storage::disk('public')->delete($product->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('product', 'public');
        }

        // Update product
        $product->update($data);

        // Sync relationships
        $this->syncUnits($product, $request);
        $this->syncImages($product, $request);
        $this->syncAttributesAndVariations($product, $request);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully'
        ]);

    } catch (\Exception $e) {
        \Log::error('Product update error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to update product: ' . $e->getMessage()
        ], 500);
    }
}
    /**
     * Update single flag via AJAX (is_new, is_trending, is_top_rated)
     * PATCH /web/products/{product}/flags
     */
    public function updateFlags(Request $request, Product $product)
    {
        try {
            $validated = $request->validate([
                'flag'  => 'required|string|in:is_new,is_trending,is_top_rated',
                'value' => 'required|boolean',
            ]);

            // Update the flag
            $product->update([
                $validated['flag'] => $validated['value']
            ]);

            $flagName = str_replace('_', ' ', $validated['flag']);
            $status = $validated['value'] ? 'enabled' : 'disabled';

            return response()->json([
                'success' => true,
                'message' => ucfirst($flagName) . ' flag ' . $status . ' successfully',
                'data' => [
                    'flag' => $validated['flag'],
                    'value' => $validated['value']
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Flag update error: ' . $e->getMessage(), [
                'product_id' => $product->id,
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update flag: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk Update Flags
     * POST /web/products/bulk-flags
     */
    public function bulkUpdateFlags(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_ids' => 'required|array|min:1',
                'product_ids.*' => 'exists:products,id',
                'flag'        => 'required|string|in:is_new,is_trending,is_top_rated',
                'value'       => 'required|boolean',
            ]);

            $updated = Product::whereIn('id', $validated['product_ids'])
                              ->update([$validated['flag'] => $validated['value']]);

            $flagName = str_replace('_', ' ', $validated['flag']);
            $status = $validated['value'] ? 'enabled' : 'disabled';

            return response()->json([
                'success' => true,
                'message' => "{$updated} product(s) updated: " . ucfirst($flagName) . " flag {$status}",
                'updated_count' => $updated
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Bulk flag update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update flags: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateMainBarcode($sku)
    {
        $random = strtoupper(substr(md5(microtime()), 0, 8));
        return substr("{$sku}-{$random}", 0, 20);
    }

    private function syncUnits($product, $request)
    {
        $product->units()->detach();

        // Always attach primary unit
        if ($request->primary_unit_id) {
            $product->units()->attach($request->primary_unit_id, ['quantity_per_unit' => 1]);
        }

        // Attach additional units
        if ($request->has('units') && is_array($request->units)) {
            foreach ($request->units as $u) {
                if (!empty($u['unit_id']) && !empty($u['quantity_per_unit']) && $u['unit_id'] != $request->primary_unit_id) {
                    $product->units()->attach($u['unit_id'], ['quantity_per_unit' => (float) $u['quantity_per_unit']]);
                }
            }
        }
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
                $name        = trim($attr['name'] ?? '');
                $valuesInput = trim($attr['values'] ?? '');
                if ($name && $valuesInput) {
                    $values = array_filter(preg_split('/[\s,]+/', $valuesInput));
                    $product->attributes()->create(['name' => $name, 'values' => $values]);
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
                        if ($value !== null && $value !== '') $attributes[$key] = $value;
                    }
                }

                $barcode = $var['barcode'] ?? null;
                if (!$barcode) {
                    $baseSku     = $product->sku ?? 'PROD';
                    $attrString  = collect($attributes)->values()->join('-')->strtoupper()->replace(' ', '');
                    $random      = strtoupper(substr(md5(microtime()), 0, 6));
                    $barcode     = substr("{$baseSku}-{$attrString}-{$random}", 0, 20);
                }

                $product->variations()->create([
                    'sku'        => $var['sku'] ?? null,
                    'barcode'    => $barcode,
                    'price'      => $var['price'] ?? 0,
                    'cost_price' => $var['cost_price'] ?? null,
                    'sale_price' => $var['sale_price'] ?? null,
                    'image'      => $imagePath,
                    'attributes' => $attributes,
                ]);
            }
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);

            if ($product->thumbnail) Storage::disk('public')->delete($product->thumbnail);
            foreach ($product->images    as $img) Storage::disk('public')->delete($img->image_path);
            foreach ($product->variations as $v)   if ($v->image) Storage::disk('public')->delete($v->image);

            $product->images()->delete();
            $product->attributes()->delete();
            $product->variations()->delete();
            $product->units()->detach();
            $product->delete();

            return response()->json(['success' => true, 'message' => 'Product deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete product: ' . $e->getMessage()], 500);
        }
    }

    public function deleteImage($id, $imageId)
    {
        try {
            $product = Product::findOrFail($id);
            $image   = $product->images()->findOrFail($imageId);
            Storage::disk('public')->delete($image->image_path);
            $image->delete();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete image'], 500);
        }
    }

    public function show($id)
    {
        $product = Product::with([
            'brand', 'category', 'images', 'units',
            'reviews.user',
            'reviews' => fn($q) => $q->with(['user' => fn($q) => $q->select('id', 'first_name', 'last_name', 'profile_image')])->latest(),
        ])
        ->withCount(['variations', 'reviews', 'orderItems as order_items_count'])
        ->findOrFail($id);

        $totalSold      = $product->orderItems()->sum('quantity');
        $averageRating  = $product->reviews->avg('rating') ?? 0;
        $ratingBreakdown = [
            '5' => $product->reviews->where('rating', 5)->count(),
            '4' => $product->reviews->where('rating', 4)->count(),
            '3' => $product->reviews->where('rating', 3)->count(),
            '2' => $product->reviews->where('rating', 2)->count(),
            '1' => $product->reviews->where('rating', 1)->count(),
        ];
        $price     = $product->sale_price ?? $product->price;
        $revenue   = $totalSold * $price;
        $pagetitle = $product->title . ' - Product Details';

        return view('products.show', compact('product', 'pagetitle', 'averageRating', 'ratingBreakdown', 'revenue', 'totalSold'));
    }

    public function storeReview(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $request->validate([
            'rating'    => 'required|numeric|min:1|max:5',
            'comment'   => 'required|string|max:1000',
            'user_name' => 'nullable|string|max:255',
        ]);

        $review = ProductReview::create([
            'product_id' => $product->id,
            'user_id'    => auth()->id() ?? null,
            'rating'     => $request->rating,
            'comment'    => $request->comment,
            'user_name'  => $request->user_name ?? (auth()->user()?->name ?? 'Anonymous'),
        ]);

        return response()->json(['success' => true, 'message' => 'Review added successfully', 'review' => $review]);
    }

    public function search(Request $request)
    {
        $query       = $request->q;
        $defaultUnit = Unit::where('is_default', true)->first();

        if (!$defaultUnit) {
            $defaultUnit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'is_default' => true, 'is_active' => true]);
        }

        $products = Product::with('units')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%")
                  ->orWhere('barcode', 'like', "%{$query}%");
            })
            ->limit(10)->get()
            ->map(function ($product) use ($defaultUnit) {
                if ($product->units->isEmpty()) {
                    $product->units()->attach($defaultUnit->id, ['quantity_per_unit' => 1]);
                    $product->load('units');
                }
                $primaryUnit = $product->units->first();
                return [
                    'id'              => $product->id,
                    'title'           => $product->title,
                    'sku'             => $product->sku,
                    'barcode'         => $product->barcode,
                    'price'           => $product->price,
                    'cost_price'      => $product->cost_price,
                    'thumbnail'       => $product->thumbnail ? asset('storage/' . $product->thumbnail) : null,
                    'current_stock'   => $product->current_stock,
                    'primary_unit_id' => $primaryUnit->id,
                    'primary_unit'    => $primaryUnit->name,
                ];
            });

        return response()->json($products);
    }

    public function inventoryLog($id)
    {
        try {
            $product = Product::findOrFail($id);
            $logs    = $product->inventoryLogs()->with('user:id,name,email')->latest()->get()
                ->map(function ($log) {
                    return [
                        'id'                 => $log->id,
                        'type'               => $log->type,
                        'quantity'           => $log->quantity,
                        'previous_stock'     => $log->previous_stock,
                        'new_stock'          => $log->new_stock,
                        'reference'          => $log->reference,
                        'notes'              => $log->notes,
                        'user_name'          => $log->user ? $log->user->name : 'System',
                        'created_at'         => $log->created_at->toDateTimeString(),
                        'formatted_date'     => $log->created_at->format('M d, Y h:i A'),
                        'formatted_quantity' => $log->formatted_quantity,
                        'type_name'          => $log->type_name,
                        'type_class'         => $log->type_class,
                    ];
                });
            return response()->json($logs);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load inventory data: ' . $e->getMessage()], 500);
        }
    }

    public function template()
    {
        $headers  = ['title','sku','barcode','price','cost_price','sale_price','description','brand_id','category_id','is_featured','is_new','is_trending','is_top_rated','primary_unit_id'];
        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        };
        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="product_import_template.csv"',
        ]);
    }

    public function realtimeStock()
    {
        return response()->json(Product::select('id', 'stock')->get());
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
        $ids  = explode(',', $request->product_ids);
        $data = array_filter($request->only(['price', 'cost_price', 'sale_price', 'is_featured', 'category_id']));
        Product::whereIn('id', $ids)->update($data);
        return response()->json(['message' => 'Bulk update completed']);
    }

    public function getUnits()
    {
        return response()->json(Unit::orderBy('name')->get());
    }

    // ── Lightning Deals — Admin CRUD ─────────────────────────────────────────

    public function lightningDealsIndex(Request $request)
    {
        $pagetitle = 'Lightning Deals';

        $deals = LightningDeal::with(['product:id,title,sku,thumbnail,price,stock'])
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(20);

        $availableProducts = Product::whereDoesntHave('lightningDeal')
            ->select('id', 'title', 'sku', 'price', 'stock', 'thumbnail')
            ->orderBy('title')
            ->get();

        return view('products.lightning-deals', compact('deals', 'availableProducts', 'pagetitle'));
    }

    public function lightningDealStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id'          => 'required|exists:products,id',
            'discount_percentage' => 'required|integer|min:1|max:99',
            'stock_limit'         => 'nullable|integer|min:1',
            'starts_at'           => 'nullable|date',
            'ends_at'             => 'nullable|date|after:starts_at',
            'is_active'           => 'nullable|boolean',
            'sort_order'          => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $isNewDeal = !LightningDeal::where('product_id', $request->product_id)->exists();

            $deal = LightningDeal::updateOrCreate(
                ['product_id' => $request->product_id],
                [
                    'discount_percentage' => $request->discount_percentage,
                    'stock_limit'         => $request->stock_limit ?: null,
                    'starts_at'           => $request->starts_at  ?: null,
                    'ends_at'             => $request->ends_at    ?: null,
                    'is_active'           => $request->boolean('is_active', true),
                    'sort_order'          => $request->input('sort_order', 0),
                ]
            );

            $notifyResult = ['sent' => 0, 'failed' => 0, 'skipped' => true];

            if ($isNewDeal && $deal->is_active) {
                $product = Product::find($request->product_id);

                if ($product) {
                    $originalPrice   = (float) $product->price;
                    $discountedPrice = round($originalPrice * (1 - $deal->discount_percentage / 100), 2);
                    $thumbnail       = $product->thumbnail
                        ? url(Storage::url(preg_replace('/^storage\//', '', $product->thumbnail)))
                        : null;

                    try {
                        $notifier     = app(LightningDealNotificationService::class);
                        $notifyResult = $notifier->notifyNewDeals([[
                            'deal_id'             => $deal->id,
                            'product_id'          => $product->id,
                            'title'               => $product->title,
                            'discounted_price'    => $discountedPrice,
                            'discount_percentage' => $deal->discount_percentage,
                            'thumbnail'           => $thumbnail,
                        ]]);

                        Log::info('Lightning deal notification sent', [
                            'deal_id'    => $deal->id,
                            'product_id' => $product->id,
                            'sent'       => $notifyResult['sent'],
                            'failed'     => $notifyResult['failed'],
                        ]);
                    } catch (\Exception $notifEx) {
                        Log::error('Lightning deal notification failed', [
                            'deal_id' => $deal->id,
                            'error'   => $notifEx->getMessage(),
                        ]);
                        $notifyResult = ['sent' => 0, 'failed' => 0, 'error' => $notifEx->getMessage()];
                    }
                }
            }

            return response()->json([
                'success'      => true,
                'message'      => 'Lightning deal saved successfully',
                'data'         => $deal,
                'notification' => $notifyResult,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save deal: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function lightningDealToggle($id)
    {
        try {
            $deal = LightningDeal::findOrFail($id);
            $deal->update(['is_active' => !$deal->is_active]);

            return response()->json([
                'success'   => true,
                'is_active' => $deal->is_active,
                'message'   => $deal->is_active ? 'Deal activated' : 'Deal deactivated',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function lightningDealDestroy($id)
    {
        try {
            LightningDeal::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Lightning deal removed']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
