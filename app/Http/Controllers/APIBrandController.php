<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\BrandCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class APIBrandController extends Controller
{
    /**
     * Display a listing of the brands.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Brand::with('categories')
            ->select('id', 'name', 'logo', 'is_featured');

        // Filter by isFeatured if provided
        if ($request->has('isFeatured')) {
            $query->where('is_featured', $request->input('isFeatured') === 'true');
        }

        // Add pagination
        $perPage = $request->input('per_page', 10); // Default 10 items per page
        $brands = $query->latest()->paginate($perPage);

        $formattedBrands = $brands->map(function ($brand) {
            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'logo' => $brand->logo ? url(Storage::url($brand->logo)) : '',
                'categories' => $brand->categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'image' => $category->image ? url(Storage::url($category->image)) : '',
                        'is_featured' => $category->is_featured,
                    ];
                })->toArray(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $formattedBrands,
            'pagination' => [
                'current_page' => $brands->currentPage(),
                'last_page' => $brands->lastPage(),
                'total' => $brands->total(),
            ],
        ]);
    }

    /**
     * Display the specified brand.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $brand = Brand::with('categories')->find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Brand not found',
            ], 404);
        }

        $brandData = [
            'id' => $brand->id,
            'name' => $brand->name,
            'logo' => $brand->logo ? url(Storage::url($brand->logo)) : '',
            'categories' => $brand->categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'image' => $category->image ? url(Storage::url($category->image)) : '',
                    'is_featured' => $category->is_featured,
                ];
            })->toArray(),
        ];

        return response()->json([
            'success' => true,
            'data' => $brandData,
        ]);
    }

    /**
     * Display brands for a specific category.
     *
     * @param Request $request
     * @param string $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBrandsForCategory(Request $request, $categoryId)
    {
        $query = Brand::with('categories')->whereHas('categories', function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId);
        });

        // Add pagination
        $perPage = $request->input('per_page', 10); // Default 10 items per page
        $brands = $query->latest()->paginate($perPage);

        $formattedBrands = $brands->map(function ($brand) {
            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'logo' => $brand->logo ? url(Storage::url($brand->logo)) : '',
                'categories' => $brand->categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'image' => $category->image ? url(Storage::url($category->image)) : '',
                        'is_featured' => $category->is_featured,
                    ];
                })->toArray(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $formattedBrands,
            'pagination' => [
                'current_page' => $brands->currentPage(),
                'last_page' => $brands->lastPage(),
                'total' => $brands->total(),
            ],
        ]);
    }

    /**
     * Store a newly created brand in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:brands,name',
            'logo' => 'required|file|mimes:jpeg,png,jpg|max:2048',
            'categories' => 'array|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Save the logo directly to storage
            $path = $request->file('logo')->store('public/brands');

            $brand = Brand::create([
                'name' => $request->name,
                'logo' => $path,
            ]);

            // Attach categories if provided
            if ($request->has('categories')) {
                $brand->categories()->sync($request->categories);
            }

            DB::commit();

            // Load categories for response
            $brand->load('categories');

            $brandData = [
                'id' => $brand->id,
                'name' => $brand->name,
                'logo' => $brand->logo ? url(Storage::url($brand->logo)) : '',
                'categories' => $brand->categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'image' => $category->image ? url(Storage::url($category->image)) : '',
                        'is_featured' => $category->is_featured,
                    ];
                })->toArray(),
            ];

            return response()->json([
                'success' => true,
                'data' => $brandData,
                'message' => 'Brand created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create brand',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a brand-category relationship in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeBrandCategory(Request $request)
    {
        $validated = $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'category_id' => 'required|exists:categories,id',
        ]);

        try {
            DB::beginTransaction();

            // Create or ignore to avoid duplicates
            $brandCategory = BrandCategory::firstOrCreate([
                'brand_id' => $validated['brand_id'],
                'category_id' => $validated['category_id'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'brand_id' => $brandCategory->brand_id,
                    'category_id' => $brandCategory->category_id,
                ],
                'message' => 'Brand category relationship created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create brand category relationship',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified brand in storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Brand not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255|unique:brands,name,' . $id,
            'logo' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
            'categories' => 'array|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = [
                'name' => $request->name ?? $brand->name,
            ];

            // Save new logo if provided
            if ($request->hasFile('logo')) {
                $path = $request->file('logo')->store('public/brands');
                $data['logo'] = $path;
            }

            $brand->update($data);

            // Sync categories if provided
            if ($request->has('categories')) {
                $brand->categories()->sync($request->categories);
            }

            DB::commit();

            // Load categories for response
            $brand->load('categories');

            $brandData = [
                'id' => $brand->id,
                'name' => $brand->name,
                'logo' => $brand->logo ? url(Storage::url($brand->logo)) : '',
                'categories' => $brand->categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'image' => $category->image ? url(Storage::url($category->image)) : '',
                        'is_featured' => $category->is_featured,
                    ];
                })->toArray(),
            ];

            return response()->json([
                'success' => true,
                'data' => $brandData,
                'message' => 'Brand updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update brand',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified brand from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Brand not found',
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Detach categories from the pivot table
            $brand->categories()->detach();
            $brand->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Brand deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete brand',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}