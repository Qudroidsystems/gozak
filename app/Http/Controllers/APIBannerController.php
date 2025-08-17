<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class APIBannerController extends Controller
{
    /**
     * Display a listing of banners in random order.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Banner::query()
            ->select('id', 'image_url', 'target_screen', 'active', 'created_at', 'updated_at');

        // Filter by active status if provided
        if ($request->has('active')) {
            $query->where('active', $request->input('active') === 'true');
        }

        // Apply random order and limit (default to 10 if not provided)
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
     * Store a newly created banner in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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

        // Save the image directly to storage
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