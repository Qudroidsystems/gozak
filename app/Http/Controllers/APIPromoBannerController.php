<?php

namespace App\Http\Controllers;

use App\Models\PromoBanner;
use Illuminate\Http\Request;

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
     *       "gradient_start": "#FF4E50",
     *       "gradient_end": "#F9A720",
     *       "accent_color": "#FFD700",
     *       "target_screen": "home",
     *       "starts_at": null,
     *       "ends_at": "2024-12-01T23:59:59.000000Z"
     *     }
     *   ]
     * }
     */
    public function index(Request $request)
    {
        try {
            $screen  = $request->input('screen', 'all');

            $banners = PromoBanner::live()
                ->forScreen($screen)
                ->orderBy('sort_order')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($b) => $this->format($b));

            return response()->json(['success' => true, 'data' => $banners]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch promo banners: ' . $e->getMessage()], 500);
        }
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function format(PromoBanner $b): array
    {
        return [
            'id'             => $b->id,
            'badge_text'     => $b->badge_text,
            'title'          => $b->title,
            'subtitle'       => $b->subtitle,
            'cta_text'       => $b->cta_text,
            'cta_route'      => $b->cta_route,
            'image_url'      => $b->full_image_url,
            'gradient_start' => $b->gradient_start,
            'gradient_end'   => $b->gradient_end,
            'accent_color'   => $b->accent_color,
            'target_screen'  => $b->target_screen,
            'starts_at'      => $b->starts_at?->toISOString(),
            'ends_at'        => $b->ends_at?->toISOString(),
        ];
    }
}
