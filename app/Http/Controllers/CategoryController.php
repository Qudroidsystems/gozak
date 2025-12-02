<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View category|Create category|Update category|Delete category', ['only' => ['index']]);
        $this->middleware('permission:Create category', ['only' => ['store']]);
        $this->middleware('permission:Update category', ['only' => ['edit','update']]);
        $this->middleware('permission:Delete category', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $categories = Category::with(['parent', 'children'])
            ->withCount('products')
            ->latest()
            ->paginate(12);

        // Chart data
        $chartData = Category::withCount('products')
            ->orderByDesc('products_count')
            ->limit(10)
            ->get();

        $chart_labels = $chartData->pluck('name')->toArray();
        $chart_data   = $chartData->pluck('products_count')->toArray();

        // For parent dropdown in modal
        $allCategories = Category::whereNull('parent_id')->orderBy('name')->get();

        return view('categories.index', compact('categories', 'allCategories', 'chart_labels', 'chart_data'));
    }

    public function edit($id)
    {
        $category = Category::with('parent')->findOrFail($id);

        return response()->json([
            'id'          => $category->id,
            'name'        => $category->name,
            'parent_id'   => $category->parent_id,
            'is_featured' => $category->is_featured,
            'is_nsfw'     => $category->is_nsfw ?? false,
            'image'       => $category->image ? asset('storage/' . $category->image) : null,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255|unique:categories,name',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'parent_id'   => 'nullable|exists:categories,id',
            'is_featured' => 'nullable|boolean',
            'is_nsfw'     => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('categories', 'public');
            }

            $category = Category::create([
                'name'        => $request->name,
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
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:255|unique:categories,name,' . $id,
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'parent_id'   => 'nullable|exists:categories,id',
            'is_featured' => 'nullable|boolean',
            'is_nsfw'     => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $data = [
                'name'        => $request->name,
                'parent_id'   => $request->parent_id ?: null,
                'is_featured' => $request->boolean('is_featured'),
                'is_nsfw'     => $request->boolean('is_nsfw'),
            ];

            if ($request->hasFile('image')) {
                if ($category->image) {
                    Storage::disk('public')->delete($category->image);
                }
                $data['image'] = $request->file('image')->store('categories', 'public');
            }

            $category->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);

            // Delete image
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }

            // Reassign or delete children? Here we just null parent_id
            Category::where('parent_id', $id)->update(['parent_id' => null]);

            $category->delete();

            return response()->json(['success' => true, 'message' => 'Category deleted']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}