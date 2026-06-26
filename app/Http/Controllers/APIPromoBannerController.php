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

            $banners = PromoBanner::live()
                ->forScreen($screen)
                ->ordered()
                ->limit($limit)
                ->get()
                ->map(fn ($b) => $this->format($b));

            return response()->json([
                'success' => true,
                'data' => $banners,
                'meta' => [
                    'count' => $banners->count(),
                    'screen' => $screen,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch promo banners: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch promo banners',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Mark banners as shown for analytics.
     */
    public function markShown(Request $request)
    {
        $request->validate([
            'banner_ids' => 'required|array',
            'banner_ids.*' => 'exists:promo_banners,id',
        ]);

        // Client-side tracking with SharedPreferences
        // This endpoint is for analytics tracking

        return response()->json([
            'success' => true,
            'message' => 'Banners marked as shown'
        ]);
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
