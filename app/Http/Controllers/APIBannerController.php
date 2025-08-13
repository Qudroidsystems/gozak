<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class APIBannerController extends Controller
{
    /**
     * Display a listing of banners.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Banner::query();

        // Filter by active status if provided
        if ($request->has('active')) {
            $query->where('active', $request->input('active') === 'true');
        }

        // Apply limit if provided
        $limit = $request->input('limit', 10);
        $banners = $query->take($limit)->get()->map(function ($banner) {
            return [
                'id' => $banner->id,
                'image_url' => $banner->image_url ? url(Storage::url($banner->image_url)) : '',
                'target_screen' => $banner->target_screen,
                'active' => $banner->active,
                'created_at' => $banner->created_at,
                'updated_at' => $banner->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $banners,
        ]);
    }

    /**
     * Store a newly created banner in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'image_url' => 'required|string|max:255',
            'target_screen' => 'required|string|max:255',
            'active' => 'required|boolean',
        ]);

        $banner = Banner::create($validated);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $banner->id,
                'image_url' => $banner->image_url ? url(Storage::url($banner->image_url)) : '',
                'target_screen' => $banner->target_screen,
                'active' => $banner->active,
                'created_at' => $banner->created_at,
                'updated_at' => $banner->updated_at,
            ],
            'message' => 'Banner created successfully',
        ], 201);
    }
}
