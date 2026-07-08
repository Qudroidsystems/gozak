<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\CategoryTreeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CategoryController extends Controller
{
    public function __construct(private CategoryTreeService $tree)
    {
        $this->middleware('permission:View category|Create category|Update category|Delete category', ['only' => ['index']]);
        $this->middleware('permission:Create category', ['only' => ['store']]);
        $this->middleware('permission:Update category', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete category', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $pagetitle = 'Category Management';

        $query = Category::with('parent')->withCount(['products', 'children']);

        // Search filter
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('parent', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        // Parent filter
        if ($request->filled('parent_filter')) {
            match ($request->parent_filter) {
                'top'   => $query->whereNull('parent_id'),
                'child' => $query->whereNotNull('parent_id'),
                default => $query->where('parent_id', $request->parent_filter),
            };
        }

        // Featured filter
        if ($request->filled('featured')) {
            $query->where('is_featured', $request->boolean('featured'));
        }

        // NSFW filter
        if ($request->filled('nsfw')) {
            $query->where('is_nsfw', $request->boolean('nsfw'));
        }

        // Stock filter
        if ($request->filled('stock_filter')) {
            match ($request->stock_filter) {
                'empty'     => $query->doesntHave('products'),
                'has_stock' => $query->has('products'),
                default     => null,
            };
        }

        // Sort
        match ($request->get('sort', 'name_asc')) {
            'name_desc'     => $query->orderByDesc('name'),
            'most_products' => $query->orderByDesc('products_count'),
            'newest'        => $query->latest(),
            'oldest'        => $query->oldest(),
            default         => $query->orderBy('name'),
        };

        // Get per_page from request or default to 15
        $perPage = $request->get('per_page', 15);

        // Validate per_page to prevent abuse (allow only specific values)
        $allowedPerPage = [10, 15, 25, 50, 100, 250, 500];
        if (!in_array((int)$perPage, $allowedPerPage)) {
            $perPage = 15;
        }

        $categories = $query->paginate((int)$perPage)->appends($request->query());

        $analytics = [
            'total_categories' => Category::count(),
            'top_level_count'  => Category::whereNull('parent_id')->count(),
            'featured_count'   => Category::where('is_featured', true)->count(),
            'empty_count'      => Category::doesntHave('products')->count(),
        ];

        $chartData = Category::withCount('products')
            ->orderByDesc('products_count')
            ->limit(8)
            ->get();

        $allCategories = Category::whereNull('parent_id')->orderBy('name')->get();

        return view('categories.index', [
            'categories'    => $categories,
            'allCategories' => $allCategories,
            'pagetitle'     => $pagetitle,
            'analytics'     => $analytics,
            'chart_labels'  => $chartData->pluck('name'),
            'chart_data'    => $chartData->pluck('products_count'),
        ]);
    }

    public function edit($id)
    {
        try {
            $category = Category::with('parent')->findOrFail($id);

            return response()->json([
                'success'      => true,
                'id'           => $category->id,
                'name'         => $category->name,
                'parent_id'    => $category->parent_id,
                'is_featured'  => (bool) $category->is_featured,
                'is_nsfw'      => (bool) $category->is_nsfw,
                'image'        => $category->image ? asset('storage/' . $category->image) : null,
                'excluded_ids' => array_merge([$category->id], $this->tree->descendantIds($category->id)),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'That category no longer exists — it may have just been deleted.',
            ], 404);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Unable to load this category. Please try again.',
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255|unique:categories,name',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
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

        $imagePath = null;

        try {
            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('category', 'public');
            }

            Category::create([
                'name'        => trim($request->name),
                'image'       => $imagePath,
                'parent_id'   => $request->parent_id ?: null,
                'is_featured' => $request->boolean('is_featured'),
                'is_nsfw'     => $request->boolean('is_nsfw'),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
            ], 201);
        } catch (Throwable $e) {
            DB::rollBack();

            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            report($e);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Unable to create category. Please try again.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $category = Category::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'That category no longer exists — it may have just been deleted.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255|unique:categories,name,' . $id,
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'parent_id'   => 'nullable|exists:categories,id',
            'is_featured' => 'nullable|boolean',
            'is_nsfw'     => 'nullable|boolean',
        ]);

        // Block circular references
        $validator->after(function ($validator) use ($request, $category) {
            if ($request->filled('parent_id')) {
                $blocked = array_merge([$category->id], $this->tree->descendantIds($category->id));
                if (in_array((int) $request->parent_id, $blocked)) {
                    $validator->errors()->add('parent_id', 'A category cannot be set as its own parent or descendant.');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $newImagePath = null;

        try {
            DB::beginTransaction();

            $data = [
                'name'        => trim($request->name),
                'parent_id'   => $request->parent_id ?: null,
                'is_featured' => $request->boolean('is_featured'),
                'is_nsfw'     => $request->boolean('is_nsfw'),
            ];

            if ($request->hasFile('image')) {
                $newImagePath = $request->file('image')->store('category', 'public');
                $data['image'] = $newImagePath;
            }

            $oldImage = $category->image;
            $category->update($data);

            if ($newImagePath && $oldImage) {
                Storage::disk('public')->delete($oldImage);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
            ]);
        } catch (Throwable $e) {
            DB::rollBack();

            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }

            report($e);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Unable to update category. Please try again.',
            ], 500);
        }
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:categories,id',
            'field' => 'required|in:featured,nsfw',
            'value' => 'required|in:0,1'
        ]);

        try {
            $columnMap = [
                'featured' => 'is_featured',
                'nsfw' => 'is_nsfw'
            ];

            $column = $columnMap[$request->field];

            $updated = Category::whereIn('id', $request->ids)
                ->update([$column => $request->value]);

            return response()->json([
                'success' => true,
                'message' => "{$updated} categories updated successfully.",
                'updated' => $updated
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Failed to update categories.'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $category = Category::withCount('products')->findOrFail($id);

            DB::beginTransaction();

            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }

            // Promote direct children to top-level
            Category::where('parent_id', $id)->update(['parent_id' => null]);

            $affectedProducts = $category->products_count;

            $category->delete();

            DB::commit();

            return response()->json([
                'success'           => true,
                'message'           => 'Category deleted successfully',
                'affected_products' => $affectedProducts,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'That category no longer exists — it may have already been deleted.',
            ], 404);
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Unable to delete category. Please try again.',
            ], 500);
        }
    }
}
