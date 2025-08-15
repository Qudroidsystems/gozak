<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\BrandCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        $query = Brand::with('categories');

        // Filter by isFeatured if provided
        if ($request->has('isFeatured')) {
            $query->where('is_featured', $request->input('isFeatured') === 'true');
        }

        // Apply limit if provided, default to 10
        $limit = $request->input('limit', 10);
        $brands = $query->latest()->take($limit)->get()->map(function ($brand) {
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
        });

        return response()->json([
            'success' => true,
            'data' => $brands,
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
     * @param string $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBrandsForCategory($categoryId)
    {
        $query = Brand::with('categories')->whereHas('categories', function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId);
        });

        $brands = $query->latest()->get()->map(function ($brand) {
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
        });

        return response()->json([
            'success' => true,
            'data' => $brands,
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
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name',
            'logo' => 'required|string|max:255',
            'categories' => 'array|exists:categories,id',
        ]);

        try {
            DB::beginTransaction();

            $brand = Brand::create([
                'name' => $validated['name'],
                'logo' => $validated['logo'],
            ]);

            // Attach categories if provided
            if (isset($validated['categories'])) {
                $brand->categories()->sync($validated['categories']);
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

        $validated = $request->validate([
            'name' => 'string|max:255|unique:brands,name,' . $id,
            'logo' => 'string|max:255',
            'categories' => 'array|exists:categories,id',
        ]);

        try {
            DB::beginTransaction();

            $brand->update([
                'name' => $validated['name'] ?? $brand->name,
                'logo' => $validated['logo'] ?? $brand->logo,
            ]);

            // Sync categories if provided
            if (isset($validated['categories'])) {
                $brand->categories()->sync($validated['categories']);
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