<?php
// app/Http/Controllers/PromoBannerController.php

namespace App\Http\Controllers;

use App\Models\PromoBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PromoBannerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View promo_banner|Create promo_banner|Update promo_banner|Delete promo_banner', ['only' => ['index']]);
        $this->middleware('permission:Create promo_banner', ['only' => ['store']]);
        $this->middleware('permission:Update promo_banner', ['only' => ['update']]);
        $this->middleware('permission:Delete promo_banner', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $pagetitle = 'Promo Banners';
        $banners = PromoBanner::orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('promo_banners.index', compact('banners', 'pagetitle'));
    }

    public function store(Request $request)
    {
        try {
            $data = $this->validated($request);

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('promo_banners', 'public');
                $data['image_url'] = $path;
            }

            $banner = PromoBanner::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Promo banner created successfully',
                'data' => $banner
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create promo banner: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create promo banner: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $banner = PromoBanner::findOrFail($id);
            $data = $this->validated($request, $id);

            if ($request->hasFile('image')) {
                // Delete old image
                if ($banner->image_url) {
                    Storage::disk('public')->delete($banner->image_url);
                }
                $path = $request->file('image')->store('promo_banners', 'public');
                $data['image_url'] = $path;
            }

            $banner->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Promo banner updated successfully',
                'data' => $banner
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update promo banner: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update promo banner: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $banner = PromoBanner::findOrFail($id);

            if ($banner->image_url) {
                Storage::disk('public')->delete($banner->image_url);
            }

            $banner->delete();

            return response()->json([
                'success' => true,
                'message' => 'Promo banner deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete promo banner: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete promo banner: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reorder(Request $request)
    {
        try {
            $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

            foreach ($request->ids as $order => $id) {
                PromoBanner::where('id', $id)->update(['sort_order' => $order]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Banners reordered successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reorder banners: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder banners: ' . $e->getMessage()
            ], 500);
        }
    }

    private function validated(Request $request, $ignoreId = null): array
    {
        return $request->validate([
            'badge_text' => 'required|string|max:80',
            'title' => 'required|string|max:120',
            'subtitle' => 'required|string|max:300',
            'cta_text' => 'required|string|max:60',
            'cta_route' => 'nullable|string|max:120',
            'image' => ($ignoreId ? 'nullable' : 'nullable') . '|image|mimes:jpeg,png,jpg,gif,webp|max:3072',
            'lottie_asset' => 'nullable|string|max:255',
            'gradient_start' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'gradient_end' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'target_screen' => 'required|in:home,category,product,offers,all',
            'active' => 'required|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'sort_order' => 'nullable|integer|min:0',
            'show_once_daily' => 'boolean',
        ]);
    }
}
