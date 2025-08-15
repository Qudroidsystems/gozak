<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\BrandCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class APIBrandController extends Controller
{
    /**
     * Get all brands.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $brands = Brand::with('categories')->get();
            return response()->json($brands, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong. Please try again.'], 500);
        }
    }

    /**
     * Get a single brand by ID.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $brand = Brand::with('categories')->findOrFail($id);
            return response()->json($brand, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Brand not found.'], 404);
        }
    }

    /**
     * Get featured brands.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function featured(Request $request)
    {
        try {
            $limit = $request->query('limit', 4);
            $brands = Brand::where('is_featured', true)->with('categories')->take($limit)->get();
            return response()->json($brands, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong. Please try again.'], 500);
        }
    }

    /**
     * Get brands for a specific category.
     *
     * @param string $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function brandsForCategory($categoryId)
    {
        try {
            $brands = Brand::whereHas('categories', function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })->with('categories')->get();
            return response()->json($brands, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong. Please try again.'], 500);
        }
    }

    /**
     * Store a new brand.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'logo' => 'nullable|string',
                'is_featured' => 'boolean',
            ]);

            $brand = Brand::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'logo' => $validated['logo'] ?? null,
                'slug' => Str::slug($validated['name']),
                'is_featured' => $request->input('is_featured', false),
            ]);

            return response()->json($brand, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create brand.'], 500);
        }
    }

    /**
     * Store brand-category relationships.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeBrandCategory(Request $request)
    {
        try {
            $validated = $request->validate([
                'brand_id' => 'required|exists:brands,id',
                'category_id' => 'required|exists:categories,id',
            ]);

            $brandCategory = BrandCategory::create([
                'brand_id' => $validated['brand_id'],
                'category_id' => $validated['category_id'],
            ]);

            return response()->json($brandCategory, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create brand-category relationship.'], 500);
        }
    }

    /**
     * Upload dummy brand data.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadDummyData(Request $request)
    {
        try {
            $request->validate([
                'brands' => 'required|array',
                'brands.*.name' => 'required|string|max:255',
                'brands.*.description' => 'nullable|string',
                'brands.*.logo' => 'nullable|string',
                'brands.*.is_featured' => 'boolean',
            ]);

            $brands = [];
            DB::transaction(function () use ($request, &$brands) {
                foreach ($request->input('brands') as $brandData) {
                    $brand = Brand::create([
                        'name' => $brandData['name'],
                        'description' => $brandData['description'] ?? null,
                        'logo' => $brandData['logo'] ?? null,
                        'slug' => Str::slug($brandData['name']),
                        'is_featured' => $brandData['is_featured'] ?? false,
                    ]);
                    $brands[] = $brand;
                }
            });

            return response()->json($brands, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to upload dummy brand data.'], 500);
        }
    }

    /**
     * Upload dummy brand-category data.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadDummyBrandCategoryData(Request $request)
    {
        try {
            $request->validate([
                'brand_categories' => 'required|array',
                'brand_categories.*.brand_id' => 'required|exists:brands,id',
                'brand_categories.*.category_id' => 'required|exists:categories,id',
            ]);

            $brandCategories = [];
            DB::transaction(function () use ($request, &$brandCategories) {
                foreach ($request->input('brand_categories') as $data) {
                    $brandCategory = BrandCategory::create([
                        'brand_id' => $data['brand_id'],
                        'category_id' => $data['category_id'],
                    ]);
                    $brandCategories[] = $brandCategory;
                }
            });

            return response()->json($brandCategories, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to upload dummy brand-category data.'], 500);
        }
    }
}