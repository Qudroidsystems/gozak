<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @group Banners
 * Manage promotional banners displayed on the homepage / app.
 */
class APIBannerController extends Controller
{
    /**
     * Display a listing of banners in random order
     *
     * Returns a random selection of active banners (useful for homepage carousel).
     *
     * @queryParam active boolean Filter by active status. Example: true
     * @queryParam limit integer Number of banners to return (default 10). Example: 5
     *
     * @response 200 {
     *     "success": true,
     *     "data": [
     *         {
     *             "id": 1,
     *             "image_url": "https://domain.com/storage/banners/banner1.jpg",
     *             "target_screen": "home",
     *             "active": true,
     *             "created_at": "2025-01-01T10:00:00.000000Z",
     *             "updated_at": "2025-01-01T10:00:00.000000Z"
     *         }
     *     ]
     * }
     */
    public function index(Request $request)
    {
        $query = Banner::query()
            ->select('id', 'image_url', 'target_screen', 'active', 'created_at', 'updated_at');

        if ($request->has('active')) {
            $query->where('active', $request->input('active') === 'true');
        }

        $limit = $request->input('limit', 10);
        $banners = $query->inRandomOrder()->take($limit)->get();

        $formattedBanners = $banners->map(function ($banner) {
            return [
                'id' => $banner->id,
                'image_url' => $banner->image_url ? url(Storage::url($banner->image_url)) : '',
                'target_screen' => $banner->target_screen,
                'active' => $banner->active,
                'created_at' => $banner->created_at,
                'updated_at' => $banner->updated_at,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $formattedBanners,
        ]);
    }

    /**
     * Store a newly created banner
     *
     * Requires admin authentication (not shown in code - add middleware if needed).
     *
     * @bodyParam image file required JPEG/PNG ≤ 2MB
     * @bodyParam target_screen string required Where the banner should appear (e.g. "home", "products"). Example: home
     * @bodyParam active boolean required Whether the banner is visible. Example: true
     *
     * @response 201 {
     *     "success": true,
     *     "data": { ... banner object ... },
     *     "message": "Banner created successfully"
     * }
     * @response 422 validation errors
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|mimes:jpeg,png,jpg|max:2048',
            'target_screen' => 'required|string|max:255',
            'active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $path = $request->file('image')->store('public/banners');

        $banner = Banner::create([
            'image_url' => $path,
            'target_screen' => $request->target_screen,
            'active' => $request->active,
        ]);

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
