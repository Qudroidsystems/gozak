<?php

namespace App\Http\Controllers;

use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class FcmTestController extends Controller
{
    protected $fcmService;

    public function __construct()
    {
        $this->fcmService = new FcmService();
    }

    /**
     * Test connection endpoint
     */
    public function testConnection(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'FCM Test Controller is working!',
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send test notification
     */
    public function sendTestNotification(Request $request): JsonResponse
    {
        // Simple validation
        if (!$request->device_token || !$request->title || !$request->body) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required fields: device_token, title, body'
            ], 422);
        }

        try {
            $result = $this->fcmService->send(
                $request->device_token,
                $request->title,
                $request->body,
                $request->data ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Notification sent successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send to multiple devices
     */
    public function sendTestMulticast(Request $request): JsonResponse
    {
        if (!$request->device_tokens || !$request->title || !$request->body) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required fields: device_tokens, title, body'
            ], 422);
        }

        try {
            $results = [];
            foreach ($request->device_tokens as $token) {
                $results[$token] = $this->fcmService->send(
                    $token,
                    $request->title,
                    $request->body,
                    $request->data ?? []
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Multicast notification sent successfully',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send multicast notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}