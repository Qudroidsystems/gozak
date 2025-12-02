<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

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
        $pagetitle = "Banner Management";

        $banners = Banner::latest()->paginate(12);

        return view('banners.index', compact('banners', 'pagetitle'));
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