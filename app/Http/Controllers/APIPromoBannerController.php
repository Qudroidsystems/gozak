<?php
// app/Http/Controllers/APIPromoBannerController.php

namespace App\Http\Controllers;

use App\Models\PromoBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class APIPromoBannerController extends Controller
{
    /**
     * Get live promo banners for the mobile app.
     */
    public function index(Request $request)
    {
        try {
            $screen = $request->input('screen', 'all');
            $limit = $request->input('limit', 10);

            Log::info('📢 [APIPromoBannerController] Fetching banners', [
                'screen' => $screen,
                'limit' => $limit,
                'user_id' => auth()->id(),
            ]);

            $banners = PromoBanner::live()
                ->forScreen($screen)
                ->ordered()
                ->limit($limit)
                ->get();

            Log::info('📢 [APIPromoBannerController] Found ' . $banners->count() . ' banners');

            $formattedBanners = $banners->map(fn ($b) => $this->format($b));

            Log::info('📢 [APIPromoBannerController] Formatted banners', [
                'banners' => $formattedBanners->toArray()
            ]);

            return response()->json([
                'success' => true,
                'data' => $formattedBanners,
                'meta' => [
                    'count' => $formattedBanners->count(),
                    'screen' => $screen,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('❌ [APIPromoBannerController] Failed to fetch promo banners: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch promo banners',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Format banner for API response.
     */
    private function format(PromoBanner $b): array
    {
        return [
            'id' => $b->id,
            'badge_text' => $b->badge_text,
            'title' => $b->title,
            'subtitle' => $b->subtitle,
            'cta_text' => $b->cta_text,
            'cta_route' => $b->cta_route,
            'image_url' => $b->full_image_url,
            'lottie_asset' => $b->lottie_asset,
            'gradient_start' => $b->gradient_start,
            'gradient_end' => $b->gradient_end,
            'accent_color' => $b->accent_color,
            'target_screen' => $b->target_screen,
            'show_once_daily' => $b->show_once_daily,
            'starts_at' => $b->starts_at?->toISOString(),
            'ends_at' => $b->ends_at?->toISOString(),
        ];
    }
}
