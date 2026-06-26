<?php
// app/Http/Controllers/APIPromoBannerController.php

namespace App\Http\Controllers;

use App\Models\PromoBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @group Promo Banners
 * Login-time promotional overlay banners for the mobile app.
 */
class APIPromoBannerController extends Controller
{
    /**
     * Get live promo banners
     *
     * Returns all active, in-schedule promo banners for a given screen.
     * Called once right after login. Flutter caches the "shown today" flag locally.
     *
     * @queryParam screen string Target screen filter. Default: all.
     *   Example: home
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "badge_text": "⚡ TODAY ONLY",
     *       "title": "Flash Sale — Up to 70% Off",
     *       "subtitle": "Grab the best deals before they're gone.",
     *       "cta_text": "Shop Now",
     *       "cta_route": "all_products",
     *       "image_url": "https://example.com/storage/promo_banners/flash.png",
     *       "lottie_asset": null,
     *       "gradient_start": "#FF4E50",
     *       "gradient_end": "#F9A720",
     *       "accent_color": "#FFD700",
     *       "target_screen": "home",
     *       "show_once_daily": true,
     *       "starts_at": null,
     *       "ends_at": "2024-12-01T23:59:59.000000Z"
     *     }
     *   ]
     * }
     */
    public function index(Request $request)
    {
        try {
            $screen = $request->input('screen', 'all');
            $limit = $request->input('limit', 10);

            $banners = PromoBanner::live()
                ->forScreen($screen)
                ->orderBy('sort_order')
                ->orderBy('created_at', 'desc')
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
     * Mark a banner as shown for the user today
     *
     * This helps track which banners have been shown to prevent
     * showing the same ones repeatedly.
     */
    public function markShown(Request $request)
    {
        $request->validate([
            'banner_ids' => 'required|array',
            'banner_ids.*' => 'exists:promo_banners,id',
        ]);

        // The actual tracking happens on the client side with SharedPreferences
        // This endpoint can be used for analytics tracking
        return response()->json(['success' => true, 'message' => 'Banners marked as shown']);
    }

    // ── Private ───────────────────────────────────────────────────────────────

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
