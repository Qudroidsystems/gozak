<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View category|Create category|Update category|Delete category', ['only' => ['index']]);
        $this->middleware('permission:Create category', ['only' => ['store']]);
        $this->middleware('permission:Update category', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete category', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $pagetitle = "Category Management";

        $query = Category::with('parent')
            ->withCount(['products', 'children']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('parent', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('parent_filter')) {
            switch ($request->parent_filter) {
                case 'top':   $query->whereNull('parent_id'); break;
                case 'child': $query->whereNotNull('parent_id'); break;
                default:      $query->where('parent_id', $request->parent_filter); break;
            }
        }

        if ($request->filled('featured')) {
            $query->where('is_featured', (bool) $request->featured);
        }

        if ($request->filled('nsfw')) {
            $query->where('is_nsfw', (bool) $request->nsfw);
        }

        if ($request->filled('stock_filter')) {
            switch ($request->stock_filter) {
                case 'empty':     $query->doesntHave('products'); break;
                case 'has_stock': $query->has('products'); break;
            }
        }

        switch ($request->get('sort', 'name_asc')) {
            case 'name_desc':     $query->orderByDesc('name'); break;
            case 'most_products': $query->orderByDesc('products_count'); break;
            case 'newest':        $query->latest(); break;
            case 'oldest':        $query->oldest(); break;
            default:               $query->orderBy('name'); break;
        }

        $categories = $query->paginate(15)->appends($request->query());

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

        $chart_labels = $chartData->pluck('name')->toArray();
        $chart_data   = $chartData->pluck('products_count')->toArray();

        $allCategories = Category::whereNull('parent_id')->orderBy('name')->get();

        return view('categories.index', compact(
            'categories',
            'allCategories',
            'pagetitle',
            'analytics',
            'chart_labels',
            'chart_data'
        ));
    }

    public function edit($id)
    {
        $category = Category::with('parent')->findOrFail($id);

        return response()->json([
            'id'            => $category->id,
            'name'          => $category->name,
            'parent_id'     => $category->parent_id,
            'is_featured'   => (bool) $category->is_featured,
            'is_nsfw'       => (bool) $category->is_nsfw,
            'image'         => $category->image ? asset('storage/' . $category->image) : null,
            // A category can't become its own parent, nor the parent of one
            // of its own ancestors -- the dropdown on the frontend removes
            // these ids so the user can't even attempt it.
            'excluded_ids'  => array_merge([$category->id], $this->getDescendantIds($category->id)),
        ]);
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
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
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

        } catch (\Exception $e) {
            DB::rollBack();

            // Don't leave an orphaned upload behind if the DB write failed
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255|unique:categories,name,' . $id,
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'parent_id'   => 'nullable|exists:categories,id',
            'is_featured' => 'nullable|boolean',
            'is_nsfw'     => 'nullable|boolean',
        ]);

        // Block circular references: a category can't be parented to
        // itself or to any of its own descendants.
        $validator->after(function ($validator) use ($request, $category) {
            if ($request->filled('parent_id')) {
                $blocked = array_merge([$category->id], $this->getDescendantIds($category->id));
                if (in_array((int) $request->parent_id, $blocked)) {
                    $validator->errors()->add('parent_id', 'A category cannot be set as its own parent or descendant.');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
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

        } catch (\Exception $e) {
            DB::rollBack();

            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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

            // Promote direct children to top-level rather than letting the
            // parent_id FK's ON DELETE CASCADE take them out too.
            Category::where('parent_id', $id)->update(['parent_id' => null]);

            // Products keep existing via ON DELETE SET NULL, but we surface
            // the affected count so the frontend can warn before this runs.
            $affectedProducts = $category->products_count;

            $category->delete();

            DB::commit();

            return response()->json([
                'success'            => true,
                'message'            => 'Category deleted successfully',
                'affected_products'  => $affectedProducts,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Walk the parent/child tree to collect every descendant id of a
     * category, so the UI can prevent circular parent assignments.
     */
    private function getDescendantIds(int $id): array
    {
        $ids   = [];
        $stack = [$id];

        while (!empty($stack)) {
            $current = array_pop($stack);
            $childIds = Category::where('parent_id', $current)->pluck('id')->all();

            foreach ($childIds as $childId) {
                if (!in_array($childId, $ids, true)) {
                    $ids[]   = $childId;
                    $stack[] = $childId;
                }
            }
        }

        return $ids;
    }
}
