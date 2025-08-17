<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class APICategoryController extends Controller
{
    /**
     * Get a paginated list of categories with optional filters.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Category::query()
            ->select('id', 'name', 'image', 'parent_id', 'is_featured');

        // Apply filters
        if ($request->has('is_featured') && $request->is_featured === 'true') {
            $query->where('is_featured', true);
        }

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        // Validate per_page
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        try {
            $categories = $query->paginate($perPage);

            $formattedCategories = $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name ?? '',
                    'image' => $category->image ? url(Storage::url($category->image)) : null,
                    'parent_id' => $category->parent_id,
                    'is_featured' => $category->is_featured ?? false,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $formattedCategories,
                // 'pagination' => [
                //     'current_page' => $categories->currentPage(),
                //     'last_page' => $categories->lastPage(),
                //     'total' => $categories->total(),
                // ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new category.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_featured' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $category = Category::create([
                'name' => $request->name,
                'image' => $request->image,
                'parent_id' => $request->parent_id,
                'is_featured' => $request->is_featured ?? false,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'image' => $category->image ? url(Storage::url($category->image)) : null,
                    'parent_id' => $category->parent_id,
                    'is_featured' => $category->is_featured,
                ],
                'message' => 'Category created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category: ' . $e->getMessage(),
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

    /**
     * Upload an image file.
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
                $path = $file->store('categories', 'public');
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
}