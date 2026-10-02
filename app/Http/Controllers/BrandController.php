<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class BrandController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View brand|Create brand|Update brand|Delete brand', ['only' => ['index', 'data']]);
        $this->middleware('permission:Create brand', ['only' => ['create','store']]);
        $this->middleware('permission:Update brand', ['only' => ['edit','update']]);
        $this->middleware('permission:Delete brand', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $pagetitle = "Brand Management";

        $categories = Category::orderBy('name')->pluck('name', 'id');

        // Chart: top 15 brands by product count
        $brand_counts = Brand::withCount('products')->orderByDesc('products_count')->limit(15)->get();
        $chart_labels = $brand_counts->pluck('name')->toArray();
        $chart_data   = $brand_counts->pluck('products_count')->toArray();

        $stats = [
            'total'    => Brand::count(),
            'featured' => Brand::where('is_featured', true)->count(),
            'empty'    => Brand::doesntHave('products')->count(),
        ];

        return view('brands.index', compact('categories', 'pagetitle', 'chart_labels', 'chart_data', 'stats'));
    }

    /** Yajra DataTable endpoint. */
    public function data(Request $request)
    {
        $query = Brand::query()
            ->select('brands.*')
            ->with('categories:id,name')
            ->withCount('products')
            ->when($request->featured === '1', fn ($q) => $q->where('brands.is_featured', true))
            ->when($request->featured === '0', fn ($q) => $q->where('brands.is_featured', false))
            ->when($request->filled('category'), fn ($q) => $q->whereHas('categories', fn ($c) => $c->where('categories.id', $request->category)));

        $user = $request->user();

        return DataTables::eloquent($query)
            ->addColumn('checkbox', fn ($b) => '<input type="checkbox" class="form-check-input gz-row-check" value="' . $b->id . '">')
            ->addColumn('brand', function ($b) {
                $logo = $b->logo
                    ? '<img src="' . e(asset('storage/' . $b->logo)) . '" class="gz-thumb" alt="">'
                    : '<span class="gz-avatar">' . e(strtoupper(substr($b->name, 0, 2))) . '</span>';
                return '<div class="d-flex align-items-center gap-2">' . $logo
                    . '<div><span class="fw-semibold">' . e($b->name) . '</span><small class="d-block text-muted">ID ' . $b->id . '</small></div></div>';
            })
            ->addColumn('categories_list', function ($b) {
                if ($b->categories->isEmpty()) {
                    return '<span class="text-muted">—</span>';
                }
                $html = $b->categories->take(3)->map(fn ($c) => '<span class="badge bg-info-subtle text-info me-1">' . e($c->name) . '</span>')->implode('');
                return $html . ($b->categories->count() > 3 ? '<small class="text-muted">+' . ($b->categories->count() - 3) . '</small>' : '');
            })
            ->editColumn('products_count', fn ($b) => '<span class="badge bg-primary-subtle text-primary">' . (int) $b->products_count . '</span>')
            ->editColumn('is_featured', fn ($b) => $b->is_featured
                ? '<span class="status-pill st-success">Featured</span>'
                : '<span class="status-pill st-muted">No</span>')
            ->addColumn('action', function ($b) use ($user) {
                $h = '<div class="gz-actions">';
                if ($user->can('Update brand')) {
                    $h .= '<button class="btn btn-sm btn-soft-secondary edit-item-btn" data-id="' . $b->id . '" title="Edit"><i class="ph-pencil"></i></button>';
                }
                if ($user->can('Delete brand')) {
                    $h .= '<button class="btn btn-sm btn-soft-danger remove-item-btn" data-id="' . $b->id . '" data-name="' . e($b->name) . '" title="Delete"><i class="ph-trash"></i></button>';
                }
                return $h . '</div>';
            })
            ->filterColumn('brand', fn ($q, $k) => $q->where('brands.name', 'like', "%{$k}%"))
            ->orderColumn('brand', 'brands.name $1')
            ->rawColumns(['checkbox', 'brand', 'categories_list', 'products_count', 'is_featured', 'action'])
            ->toJson();
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