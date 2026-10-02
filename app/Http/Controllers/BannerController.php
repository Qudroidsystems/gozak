<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class BannerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View banner|Create banner|Update banner|Delete banner', ['only' => ['index', 'data']]);
        $this->middleware('permission:Create banner', ['only' => ['store']]);
        $this->middleware('permission:Update banner', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete banner', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $pagetitle = "Banner Management";

        $stats = [
            'total'    => Banner::count(),
            'active'   => Banner::where('active', true)->count(),
            'inactive' => Banner::where('active', false)->count(),
        ];

        return view('banners.index', compact('pagetitle', 'stats'));
    }

    /** Yajra DataTable endpoint. */
    public function data(Request $request)
    {
        $query = Banner::query()
            ->when($request->filled('screen'), fn ($q) => $q->where('target_screen', $request->screen))
            ->when($request->status === 'active', fn ($q) => $q->where('active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('active', false));

        $user = $request->user();

        return DataTables::eloquent($query)
            ->addColumn('checkbox', fn ($b) => '<input type="checkbox" class="form-check-input gz-row-check" value="' . $b->id . '">')
            ->addColumn('image', function ($b) {
                if ($b->image_url && Storage::disk('public')->exists($b->image_url)) {
                    return '<a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageViewModal" data-image="' . e(asset('storage/' . $b->image_url)) . '">'
                        . '<img src="' . e(asset('storage/' . $b->image_url)) . '" class="rounded shadow-sm" style="width:200px;height:100px;object-fit:cover;" alt="Banner"></a>';
                }
                return '<div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:200px;height:100px;"><i class="bi bi-image text-muted fs-3"></i></div>';
            })
            ->editColumn('target_screen', fn ($b) => '<span class="badge bg-info-subtle text-info">' . e(ucwords(str_replace('_', ' ', $b->target_screen))) . '</span>')
            ->editColumn('active', fn ($b) => $b->active
                ? '<span class="status-pill st-success">Active</span>'
                : '<span class="status-pill st-muted">Inactive</span>')
            ->editColumn('created_at', fn ($b) => optional($b->created_at)->format('d M Y'))
            ->addColumn('action', function ($b) use ($user) {
                $h = '<div class="gz-actions">';
                if ($user->can('Update banner')) {
                    $h .= '<button class="btn btn-sm btn-soft-secondary edit-item-btn" title="Edit" data-id="' . $b->id . '"'
                        . ' data-image="' . e($b->image_url ? asset('storage/' . $b->image_url) : '') . '"'
                        . ' data-screen="' . e($b->target_screen) . '" data-active="' . (int) $b->active . '"><i class="ph-pencil"></i></button>';
                }
                if ($user->can('Delete banner')) {
                    $h .= '<button class="btn btn-sm btn-soft-danger remove-item-btn" title="Delete" data-id="' . $b->id . '"><i class="ph-trash"></i></button>';
                }
                return $h . '</div>';
            })
            ->rawColumns(['checkbox', 'image', 'target_screen', 'active', 'action'])
            ->toJson();
    }

    public function edit($id)
    {
        $banner = Banner::findOrFail($id);

        return response()->json([
            'id'            => $banner->id,
            'target_screen' => $banner->target_screen,
            'active'        => $banner->active,
            'image_url'     => $banner->image_url ? asset('storage/' . $banner->image_url) : null,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'image'         => 'required|image|mimes:jpeg,png,jpg,gif|max:3072',
            'target_screen' => 'required|in:home,category,product,offers,all',
            'active'        => 'required|boolean',
        ]);

        $path = $request->file('image')->store('banner', 'public');

        Banner::create([
            'image_url'     => $path,
            'target_screen' => $request->target_screen,
            'active'        => $request->boolean('active'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Banner created successfully'
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        $request->validate([
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3072',
            'target_screen' => 'required|in:home,category,product,offers,all',
            'active'        => 'required|boolean',
        ]);

        $data = [
            'target_screen' => $request->target_screen,
            'active'        => $request->boolean('active'),
        ];

        if ($request->hasFile('image')) {
            // Delete old image
            if ($banner->image_url) {
                Storage::disk('public')->delete($banner->image_url);
            }
            $data['image_url'] = $request->file('image')->store('banner', 'public');
        }

        $banner->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Banner updated successfully'
        ]);
    }

    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);

        if ($banner->image_url) {
            Storage::disk('public')->delete($banner->image_url);
        }

        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Banner deleted successfully'
        ]);
    }
}