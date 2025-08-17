<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Laravel\Facades\Image;

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
        $query = Banner::query()
            ->select('id', 'image_url', 'target_screen', 'active', 'created_at', 'updated_at');

        // Filter by active status if provided
        if ($request->has('active')) {
            $query->where('active', $request->input('active') === 'true');
        }

        // Add pagination
        $perPage = $request->input('per_page', 10); // Default 10 items per page
        $banners = $query->paginate($perPage);

        $formattedBanners = $banners->map(function ($banner) {
            return [
                'id' => $banner->id,
                'image_url' => $banner->image_url ? url(Storage::url($banner->image_url)) : '',
                'small_image_url' => $banner->image_url ? $this->generateThumbnail($banner->image_url, 300) : '', // Thumbnail for mobile
                'target_screen' => $banner->target_screen,
                'active' => $banner->active,
                'created_at' => $banner->created_at,
                'updated_at' => $banner->updated_at,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $formattedBanners,
            'pagination' => [
                'current_page' => $banners->currentPage(),
                'last_page' => $banners->lastPage(),
                'total' => $banners->total(),
            ],
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
            'image' => 'required|file|mimes:jpeg,png,jpg|max:2048', // Changed to handle file upload
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

        // Compress and save the image
        $image = Image::make($request->file('image'));
        $image->encode('jpg', 80); // Use WebP if desired: ->encode('webp', 80);
        $image->resize(1200, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        $path = 'public/banners/' . $request->file('image')->hashName();
        Storage::put($path, (string) $image);

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
                'small_image_url' => $banner->image_url ? $this->generateThumbnail($banner->image_url, 300) : '',
                'target_screen' => $banner->target_screen,
                'active' => $banner->active,
                'created_at' => $banner->created_at,
                'updated_at' => $banner->updated_at,
            ],
            'message' => 'Banner created successfully',
        ], 201);
    }

    /**
     * Generate a smaller thumbnail for faster mobile loading
     */
    private function generateThumbnail($path, $width = 300)
    {
        $cleanPath = preg_replace('/^storage\//', '', $path);
        $thumbPath = 'public/thumbnails/' . basename($cleanPath);

        // Check if thumbnail already exists to avoid regeneration
        if (!Storage::exists($thumbPath)) {
            $image = Image::make(Storage::get($cleanPath));
            $image->resize($width, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->encode('jpg', 80); // Use WebP if desired: ->encode('webp', 80);
            Storage::put($thumbPath, (string) $image);
        }

        return url(Storage::url($thumbPath));
    }
}