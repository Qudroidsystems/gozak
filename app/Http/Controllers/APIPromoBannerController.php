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

            return response()->json([
                'success' => true,
                'data' => $formattedBanners,
                'meta' => [
                    'count' => $formattedBanners->count(),
                    'screen' => $screen,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Failed to fetch promo banners: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch promo banners',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Mark banners as shown for analytics tracking.
     */
    public function markShown(Request $request)
    {
        try {
            $request->validate([
                'banner_ids' => 'required|array',
                'banner_ids.*' => 'exists:promo_banners,id',
            ]);

            $bannerIds = $request->input('banner_ids', []);
            $userId = auth()->id();

            Log::info('📢 Banners marked as shown', [
                'user_id' => $userId,
                'banner_ids' => $bannerIds,
                'count' => count($bannerIds)
            ]);

            // ✅ Return success without requiring a model
            return response()->json([
                'success' => true,
                'message' => 'Banners marked as shown successfully',
                'data' => [
                    'banner_ids' => $bannerIds,
                    'user_id' => $userId,
                    'count' => count($bannerIds),
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('❌ Failed to mark banners as shown: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark banners as shown: ' . $e->getMessage()
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
