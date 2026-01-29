<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\FcmService;

class FcmTestController extends Controller
{
    protected FcmService $fcmService;

    public function __construct()
    {
        $this->fcmService = new FcmService();
    }

    /**
     * Test connection endpoint
     */
    public function testConnection(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'FCM Test Controller is working!',
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Send test notification
     */
    public function sendTestNotification(Request $request): JsonResponse
    {
        $validator = \Validator::make($request->all(), [
            'device_token' => 'required|string',
            'title' => 'required|string',
            'body' => 'required|string',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->fcmService->sendToToken(
                $request->device_token,
                $request->title,
                $request->body,
                $request->data ?? []
            );

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Notification sent' : 'Failed to send notification',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ], 500);
        }
    }
}
