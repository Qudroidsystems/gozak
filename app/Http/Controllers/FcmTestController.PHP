<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\FcmService;
use App\Services\LightningDealNotificationService;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FcmTestController extends Controller
{
    protected FcmService $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Test connection endpoint
     * GET /api/fcm/test
     */
    public function testConnection(): JsonResponse
    {
        return response()->json([
            'success'   => true,
            'message'   => 'FCM Test Controller is working!',
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Send a raw test notification to a specific device token
     * POST /api/fcm/send
     *
     * Body: {
     *   "device_token": "...",
     *   "title": "Hello",
     *   "body": "Test message",
     *   "data": { "key": "value" }   // optional
     * }
     */
    public function sendTestNotification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string',
            'title'        => 'required|string',
            'body'         => 'required|string',
            'data'         => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->fcmService->sendToToken(
                $request->device_token,
                $request->title,
                $request->body,
                $request->input('data', [])
            );

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Notification sent successfully' : 'Failed to send notification',
                'data'    => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Simulate a SINGLE lightning deal notification.
     * Sends to one device token if provided, otherwise broadcasts to ALL users.
     *
     * POST /api/fcm/test-lightning-single
     *
     * Body: {
     *   "product_id": 1,
     *   "device_token": "..."   // optional — omit to broadcast to all users
     * }
     */
    public function testLightningSingle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id'   => 'required|exists:products,id',
            'device_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $product = Product::findOrFail($request->product_id);

            $originalPrice   = (float) $product->price;
            $discountedPrice = round($originalPrice * 0.80, 2); // 20% off for test
            $thumbnail       = $this->buildThumbnailUrl($product->thumbnail);

            // ── Direct send to a specific device (for testing your own phone) ──
            if ($request->filled('device_token')) {
                $data = [
                    'type'                => 'lightning_deal_single',
                    'product_id'          => (string) $product->id,
                    'deal_id'             => '0',
                    'title'               => $product->title,
                    'discounted_price'    => (string) $discountedPrice,
                    'discount_percentage' => '20',
                    'thumbnail'           => $thumbnail ?? '',
                    'route'               => '/lightning-deals/product',
                ];

                $result = $this->fcmService->sendToToken(
                    $request->device_token,
                    '⚡ Lightning Deal!',
                    "{$product->title} — 20% OFF! Now ₦" . number_format($discountedPrice, 0),
                    $data
                );

                return response()->json([
                    'success' => $result['success'],
                    'message' => $result['success'] ? 'Test notification sent to device' : 'Failed',
                    'data'    => $result,
                ]);
            }

            // ── Broadcast to all users ──────────────────────────────────────
            /** @var LightningDealNotificationService $service */
            $service = app(LightningDealNotificationService::class);

            $result = $service->notifyNewDeals([[
                'deal_id'             => 0,
                'product_id'          => $product->id,
                'title'               => $product->title,
                'discounted_price'    => $discountedPrice,
                'discount_percentage' => 20,
                'thumbnail'           => $thumbnail,
            ]]);

            return response()->json([
                'success'             => true,
                'message'             => "Broadcast complete: {$result['sent']} sent, {$result['failed']} failed",
                'notification_result' => $result,
                'product'             => [
                    'id'    => $product->id,
                    'title' => $product->title,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Simulate a MULTIPLE lightning deals notification.
     * Always broadcasts to all users (shows the "X New Lightning Deals" notification).
     *
     * POST /api/fcm/test-lightning-multiple
     *
     * Body: {
     *   "count": 3   // number of products to simulate as deals (default 3, max 20)
     * }
     */
    public function testLightningMultiple(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'count' => 'nullable|integer|min:2|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $count    = $request->input('count', 3);
            $products = Product::take($count)->get();

            if ($products->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No products found in the database',
                ], 404);
            }

            $deals = $products->values()->map(function ($product, $i) {
                $originalPrice   = (float) $product->price;
                $discountPct     = min(($i + 1) * 5 + 10, 70); // 15%, 20%, 25% ...
                $discountedPrice = round($originalPrice * (1 - $discountPct / 100), 2);

                return [
                    'deal_id'             => 0,
                    'product_id'          => $product->id,
                    'title'               => $product->title,
                    'discounted_price'    => $discountedPrice,
                    'discount_percentage' => $discountPct,
                    'thumbnail'           => $this->buildThumbnailUrl($product->thumbnail),
                ];
            })->toArray();

            /** @var LightningDealNotificationService $service */
            $service = app(LightningDealNotificationService::class);
            $result  = $service->notifyNewDeals($deals);

            return response()->json([
                'success'             => true,
                'message'             => "Broadcast complete: {$result['sent']} sent, {$result['failed']} failed",
                'deals_simulated'     => count($deals),
                'notification_result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function buildThumbnailUrl(?string $thumbnail): ?string
    {
        if (!$thumbnail) return null;
        return url(Storage::url(preg_replace('/^storage\//', '', $thumbnail)));
    }
}
