<?php

namespace App\Http\Controllers;

use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class FcmTestController extends Controller
{
    protected $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Send test notification to single device
     */
    public function sendTestNotification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'data' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
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
     * Send test notification to multiple devices
     */
    public function sendTestMulticast(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_tokens' => 'required|array',
            'device_tokens.*' => 'string',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'data' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $results = $this->fcmService->sendMulticast(
                $request->device_tokens,
                $request->title,
                $request->body,
                $request->data ?? []
            );

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

    /**
     * Send test notification with advanced options
     */
    public function sendTestWithOptions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'image' => 'sometimes|url',
            'click_action' => 'sometimes|string',
            'data' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->fcmService->sendWithOptions(
                $request->device_token,
                $request->title,
                $request->body,
                [
                    'image' => $request->image,
                    'click_action' => $request->click_action,
                    'data' => $request->data ?? [],
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Notification with options sent successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification with options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test FCM service connection
     */
    public function testConnection(): JsonResponse
    {
        try {
            // Try to get access token to test connection
            $reflection = new \ReflectionClass($this->fcmService);
            $method = $reflection->getMethod('getAccessToken');
            $method->setAccessible(true);
            $token = $method->invoke($this->fcmService);

            return response()->json([
                'success' => true,
                'message' => 'FCM service is connected successfully',
                'token_obtained' => !empty($token)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'FCM service connection failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}