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
use Yajra\DataTables\Facades\DataTables;

class CategoryController extends Controller
{
    public function __construct(private CategoryTreeService $tree)
    {
        $this->middleware('permission:View category|Create category|Update category|Delete category', ['only' => ['index', 'data']]);
        $this->middleware('permission:Create category', ['only' => ['store']]);
        $this->middleware('permission:Update category', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete category', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $pagetitle = 'Category Management';

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
            'allCategories' => $allCategories,
            'pagetitle'     => $pagetitle,
            'analytics'     => $analytics,
            'chart_labels'  => $chartData->pluck('name'),
            'chart_data'    => $chartData->pluck('products_count'),
        ]);
    }

    /** Yajra DataTable endpoint (filters: parent_filter, featured, nsfw, stock_filter). */
    public function data(Request $request)
    {
        $query = Category::query()
            ->leftJoin('categories as parent', 'parent.id', '=', 'categories.parent_id')
            ->select('categories.*', 'parent.name as parent_name')
            ->withCount(['products', 'children'])
            ->when($request->parent_filter === 'top', fn ($q) => $q->whereNull('categories.parent_id'))
            ->when($request->parent_filter === 'child', fn ($q) => $q->whereNotNull('categories.parent_id'))
            ->when(is_numeric($request->parent_filter), fn ($q) => $q->where('categories.parent_id', $request->parent_filter))
            ->when($request->filled('featured'), fn ($q) => $q->where('categories.is_featured', $request->boolean('featured')))
            ->when($request->filled('nsfw'), fn ($q) => $q->where('categories.is_nsfw', $request->boolean('nsfw')))
            ->when($request->stock_filter === 'empty', fn ($q) => $q->doesntHave('products'))
            ->when($request->stock_filter === 'has_stock', fn ($q) => $q->has('products'));

        $user = $request->user();

        return DataTables::eloquent($query)
            ->addColumn('checkbox', fn ($c) => '<input type="checkbox" class="row-select form-check-input" value="' . $c->id . '">')
            ->addColumn('category', function ($c) {
                $img = ($c->image && Storage::disk('public')->exists($c->image))
                    ? '<img src="' . e(asset('storage/' . $c->image)) . '" class="gz-thumb" alt="">'
                    : '<span class="gz-thumb d-inline-flex align-items-center justify-content-center"><i class="bi bi-image text-muted"></i></span>';
                return '<div class="d-flex align-items-center gap-2">' . $img . '<div>'
                    . '<a href="' . route('web.products.index', ['category_id' => $c->id]) . '" class="fw-semibold text-reset">' . e($c->name) . '</a>'
                    . '<small class="d-block text-muted">ID: ' . $c->id . '</small></div></div>';
            })
            ->addColumn('parent', fn ($c) => $c->parent_name
                ? '<span class="badge bg-primary-subtle text-primary">' . e($c->parent_name) . '</span>'
                : '<span class="text-muted">— Top Level</span>')
            ->editColumn('children_count', fn ($c) => '<span class="badge bg-secondary-subtle text-secondary">' . (int) $c->children_count . '</span>')
            ->editColumn('products_count', fn ($c) => $c->products_count > 0
                ? '<span class="badge bg-success-subtle text-success">' . (int) $c->products_count . '</span>'
                : '<span class="badge bg-danger-subtle text-danger">Empty</span>')
            ->editColumn('is_featured', fn ($c) => '<span class="badge ' . ($c->is_featured ? 'bg-warning-subtle text-warning">Featured' : 'bg-secondary-subtle text-secondary">Regular') . '</span>')
            ->editColumn('is_nsfw', fn ($c) => $c->is_nsfw
                ? '<span class="badge bg-danger-subtle text-danger">NSFW</span>'
                : '<span class="badge bg-success-subtle text-success">Safe</span>')
            ->addColumn('action', function ($c) use ($user) {
                $h = '<div class="dropdown"><button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button><ul class="dropdown-menu dropdown-menu-end">';
                if ($user->can('Update category')) {
                    $h .= '<li><a class="dropdown-item edit-item-btn" href="javascript:void(0);" data-id="' . $c->id . '">Edit</a></li>';
                }
                if ($user->can('Delete category')) {
                    $h .= '<li><a class="dropdown-item remove-item-btn text-danger" href="javascript:void(0);" data-id="' . $c->id . '" data-name="' . e($c->name) . '"'
                        . ' data-products="' . (int) $c->products_count . '" data-children="' . (int) $c->children_count . '">Delete</a></li>';
                }
                return $h . '</ul></div>';
            })
            ->filterColumn('category', fn ($q, $k) => $q->where('categories.name', 'like', "%{$k}%"))
            ->filterColumn('parent', fn ($q, $k) => $q->where('parent.name', 'like', "%{$k}%"))
            ->orderColumn('category', 'categories.name $1')
            ->orderColumn('parent', 'parent.name $1')
            ->rawColumns(['checkbox', 'category', 'parent', 'children_count', 'products_count', 'is_featured', 'is_nsfw', 'action'])
            ->toJson();
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
