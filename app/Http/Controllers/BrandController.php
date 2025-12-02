<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View brand|Create brand|Update brand|Delete brand', ['only' => ['index']]);
        $this->middleware('permission:Create brand', ['only' => ['create','store']]);
        $this->middleware('permission:Update brand', ['only' => ['edit','update']]);
        $this->middleware('permission:Delete brand', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $pagetitle = "Brand Management";

        $data = Brand::with(['categories', 'products'])
            ->withCount('products')
            ->latest()
            ->paginate(10);

        $categories = Category::orderBy('name')->pluck('name', 'id');

        // Chart data
        $brand_counts = Brand::withCount('products')->get();
        $chart_labels = $brand_counts->pluck('name')->toArray();
        $chart_data = $brand_counts->pluck('products_count')->toArray();

        return view('brands.index', compact('data', 'categories', 'pagetitle', 'chart_labels', 'chart_data'))
            ->with('i', ($request->input('page', 1) - 1) * 10);
    }

    public function edit($id)
    {
        try {
            $brand = Brand::with('categories')->findOrFail($id);
            
            return response()->json([
                'id' => $brand->id,
                'name' => $brand->name,
                'is_featured' => $brand->is_featured,
                'logo' => $brand->logo ? asset('storage/' . $brand->logo) : null,
                'categories' => $brand->categories->map(function($cat) {
                    return [
                        'id' => $cat->id,
                        'name' => $cat->name
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Brand not found: ' . $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:brands,name',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_featured' => 'nullable|boolean',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ]);

        try {
            DB::beginTransaction();

            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('brand', 'public');
            }

            $brand = Brand::create([
                'name' => $request->name,
                'logo' => $logoPath,
                'is_featured' => $request->boolean('is_featured'),
            ]);

            if ($request->categories) {
                $brand->categories()->sync($request->categories);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Brand created successfully',
                'brand' => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'logo' => $brand->logo ? asset('storage/' . $brand->logo) : null,
                    'is_featured' => $brand->is_featured,
                    'products_count' => 0,
                    'categories' => $brand->categories->pluck('name')->implode(', '),
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:brands,name,' . $id,
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_featured' => 'nullable|boolean',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ]);

        try {
            DB::beginTransaction();

            $data = [
                'name' => $request->name,
                'is_featured' => $request->boolean('is_featured'),
            ];

            if ($request->hasFile('logo')) {
                // Delete old logo
                if ($brand->logo && Storage::disk('public')->exists($brand->logo)) {
                    Storage::disk('public')->delete($brand->logo);
                }
                // Store new logo
                $data['logo'] = $request->file('logo')->store('brand', 'public');
            }

            $brand->update($data);

            if ($request->has('categories')) {
                $brand->categories()->sync($request->categories ?? []);
            }

            $brand->load('categories');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Brand updated successfully',
                'brand' => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'logo' => $brand->logo ? asset('storage/' . $brand->logo) : null,
                    'is_featured' => $brand->is_featured,
                    'products_count' => $brand->products_count ?? 0,
                    'categories' => $brand->categories->pluck('name')->implode(', '),
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $brand = Brand::findOrFail($id);

            // Delete logo if exists
            if ($brand->logo && Storage::disk('public')->exists($brand->logo)) {
                Storage::disk('public')->delete($brand->logo);
            }

            $brand->categories()->detach();
            $brand->delete();

            return response()->json(['success' => true, 'message' => 'Brand deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}