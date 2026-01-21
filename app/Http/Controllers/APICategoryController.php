<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @group Categories
 *
 * Endpoints for viewing and managing product categories.
 * Most endpoints are public; creating/updating usually requires admin privileges.
 */
class APICategoryController extends Controller
{
    /**
     * Get a paginated list of categories with optional filters
     *
     * Public endpoint — no authentication required.
     *
     * @queryParam per_page integer Number of items per page (max 100). Default: 20. Example: 30
     * @queryParam is_featured boolean Only return featured categories. Example: true
     * @queryParam parent_id integer Show only sub-categories of this parent. Example: 5
     * @queryParam safe_mode boolean Hide NSFW categories (when enabled globally). Example: true
     *
     * @response 200 {
     *     "success": true,
     *     "data": [
     *         {
     *             "id": 1,
     *             "name": "Electronics",
     *             "image": "https://example.com/storage/categories/electronics.jpg",
     *             "parent_id": null,
     *             "is_featured": true,
     *             "is_nsfw": false
     *         }
     *     ]
     * }
     * @response 500 {
     *     "success": false,
     *     "message": "Failed to fetch categories: ..."
     * }
     */
    public function index(Request $request)
    {
        $query = Category::query()
            ->select('id', 'name', 'image', 'parent_id', 'is_featured', 'is_nsfw');

        // Apply filters
        if ($request->has('is_featured') && $request->is_featured === 'true') {
            $query->where('is_featured', true);
        }

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        if ($request->has('safe_mode') && $request->safe_mode === 'true') {
            $query->where('is_nsfw', false);
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        try {
            $categories = $query->paginate($perPage);

            $formattedCategories = $categories->map(function ($category) {
                return [
                    'id'          => $category->id,
                    'name'        => $category->name ?? '',
                    'image'       => $category->image ? url(Storage::url($category->image)) : null,
                    'parent_id'   => $category->parent_id,
                    'is_featured' => $category->is_featured ?? false,
                    'is_nsfw'     => $category->is_nsfw ?? false,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data'    => $formattedCategories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new category
     *
     * Typically requires admin authentication (middleware not shown here).
     *
     * @bodyParam name string required Category name. Example: Smartphones
     * @bodyParam image string|null Image path or URL
     * @bodyParam parent_id integer|null Parent category ID (for sub-categories). Example: 3
     * @bodyParam is_featured boolean Featured on homepage/sections. Default: false. Example: true
     * @bodyParam is_nsfw boolean Mark as adult content (filtered in safe_mode). Default: false
     *
     * @response 201 {
     *     "success": true,
     *     "data": {
     *         "id": 42,
     *         "name": "Smartphones",
     *         "image": "https://...",
     *         "parent_id": 3,
     *         "is_featured": true,
     *         "is_nsfw": false
     *     },
     *     "message": "Category created successfully"
     * }
     * @response 422 {
     *     "success": false,
     *     "message": "Validation failed",
     *     "errors": { ... }
     * }
     * @response 500 server error
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'image'       => 'nullable|string',
            'parent_id'   => 'nullable|exists:categories,id',
            'is_featured' => 'nullable|boolean',
            'is_nsfw'     => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $categoryData = [
                'name'        => $request->name,
                'image'       => $request->image,
                'parent_id'   => $request->parent_id,
                'is_featured' => $request->is_featured ?? false,
                'is_nsfw'     => $request->is_nsfw ?? false,
            ];

            $category = Category::create($categoryData);

            return response()->json([
                'success' => true,
                'data'    => [
                    'id'          => $category->id,
                    'name'        => $category->name,
                    'image'       => $category->image ? url(Storage::url($category->image)) : null,
                    'parent_id'   => $category->parent_id,
                    'is_featured' => $category->is_featured,
                    'is_nsfw'     => $category->is_nsfw,
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
     * Upload an image file for a category
     *
     * @bodyParam image file required JPEG, PNG, JPG or GIF. Max 2MB
     *
     * @response 200 {
     *     "success": true,
     *     "url": "https://example.com/storage/categories/abc123.jpg",
     *     "message": "Image uploaded successfully"
     * }
     * @response 422 validation errors (wrong type, size, missing file)
     * @response 400 no file provided
     * @response 500 upload failed
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
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $path = $file->store('categories', 'public');
                $url  = url(Storage::url($path));

                return response()->json([
                    'success' => true,
                    'url'     => $url,
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
