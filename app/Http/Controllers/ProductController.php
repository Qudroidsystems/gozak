<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View banner|Create banner|Update banner|Delete banner', ['only' => ['index']]);
        $this->middleware('permission:Create banner', ['only' => ['store']]);
        $this->middleware('permission:Update banner', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete banner', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $pagetitle = "Banners Management";
        
        // Get banners with product relationship
        $banners = Banner::with('product')
            ->latest()
            ->paginate(10);
        
        // Get products for dropdown
        $products = Product::where('active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'sku']);
        
        return view('banners.index', compact('banners', 'products', 'pagetitle'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'target_screen' => 'required|string|in:home,category,product,offers,all',
            'product_id' => 'nullable|required_if:target_screen,product|exists:products,id',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'link' => 'nullable|string|max:500',
            'active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Handle image upload
            $imagePath = $request->file('image')->store('banners', 'public');
            
            // Optimize image if needed
            $this->optimizeImage($imagePath);

            $bannerData = [
                'image_url' => $imagePath,
                'target_screen' => $request->target_screen,
                'product_id' => $request->product_id,
                'title' => $request->title,
                'subtitle' => $request->subtitle,
                'link' => $request->link,
                'active' => $request->boolean('active'),
                'order' => $request->order ?? 0,
            ];

            // Only add product_id if target_screen is 'product'
            if ($request->target_screen !== 'product') {
                $bannerData['product_id'] = null;
            }

            $banner = Banner::create($bannerData);

            return response()->json([
                'success' => true,
                'message' => 'Banner created successfully',
                'data' => $banner
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create banner: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $banner = Banner::with('product')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $banner->id,
                    'image_url' => $banner->image_url ? asset('storage/' . $banner->image_url) : '',
                    'target_screen' => $banner->target_screen,
                    'product_id' => $banner->product_id,
                    'title' => $banner->title,
                    'subtitle' => $banner->subtitle,
                    'link' => $banner->link,
                    'active' => $banner->active,
                    'order' => $banner->order,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Banner not found'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'target_screen' => 'required|string|in:home,category,product,offers,all',
            'product_id' => 'nullable|required_if:target_screen,product|exists:products,id',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'link' => 'nullable|string|max:500',
            'active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = [
                'target_screen' => $request->target_screen,
                'title' => $request->title,
                'subtitle' => $request->subtitle,
                'link' => $request->link,
                'active' => $request->boolean('active'),
                'order' => $request->order ?? $banner->order,
            ];

            // Handle product_id based on target screen
            if ($request->target_screen === 'product') {
                $data['product_id'] = $request->product_id;
                $data['link'] = null; // Clear link if product is selected
            } else {
                $data['product_id'] = null;
                $data['link'] = $request->link;
            }

            // Handle image update
            if ($request->hasFile('image')) {
                // Delete old image
                if ($banner->image_url) {
                    Storage::disk('public')->delete($banner->image_url);
                }
                
                // Store new image
                $imagePath = $request->file('image')->store('banners', 'public');
                $this->optimizeImage($imagePath);
                $data['image_url'] = $imagePath;
            }

            $banner->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Banner updated successfully',
                'data' => $banner->fresh('product')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update banner: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $banner = Banner::findOrFail($id);

            // Delete image file
            if ($banner->image_url) {
                Storage::disk('public')->delete($banner->image_url);
            }

            $banner->delete();

            return response()->json([
                'success' => true,
                'message' => 'Banner deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete banner: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:banners,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $banners = Banner::whereIn('id', $request->ids)->get();

            foreach ($banners as $banner) {
                if ($banner->image_url) {
                    Storage::disk('public')->delete($banner->image_url);
                }
            }

            Banner::whereIn('id', $request->ids)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Selected banners deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete banners: ' . $e->getMessage()
            ], 500);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $banner = Banner::findOrFail($id);
            $banner->update(['active' => !$banner->active]);

            return response()->json([
                'success' => true,
                'message' => 'Banner status updated',
                'active' => $banner->active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }

    private function optimizeImage($path)
    {
        // You can implement image optimization here
        // Example using intervention/image if installed
        // Image::make(storage_path('app/public/' . $path))->resize(1200, 600)->save();
        
        return true;
    }
}