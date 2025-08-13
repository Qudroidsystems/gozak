<?php

namespace App\Http\Controllers;

use App\Models\GlobalSetting;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class APISettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Fetch settings
     */
    public function show(Request $request)
    {
        try {
            $setting = Setting::where('user_id', $request->user()->id)->firstOrCreate([
                'user_id' => $request->user()->id,
            ], [
                'dark_mode' => false,
                'language' => 'en',
                'notifications_enabled' => true,
            ]);

            return response()->json([
                'success' => true,
                'settings' => [
                    'id' => $setting->id,
                    'dark_mode' => $setting->dark_mode,
                    'language' => $setting->language,
                    'notifications_enabled' => $setting->notifications_enabled,
                    'updated_at' => $setting->updated_at?->toIso8601String(),
                ],
                'message' => 'Settings retrieved successfully',
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Settings fetch error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create settings
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'dark_mode' => 'required|boolean',
                'language' => 'required|string',
                'notifications_enabled' => 'required|boolean',
            ]);

            $setting = Setting::create([
                'user_id' => $request->user()->id,
                'dark_mode' => $validated['dark_mode'],
                'language' => $validated['language'],
                'notifications_enabled' => $validated['notifications_enabled'],
            ]);

            return response()->json([
                'success' => true,
                'settings' => [
                    'id' => $setting->id,
                    'dark_mode' => $setting->dark_mode,
                    'language' => $setting->language,
                    'notifications_enabled' => $setting->notifications_enabled,
                    'updated_at' => $setting->updated_at?->toIso8601String(),
                ],
                'message' => 'Settings created successfully',
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Settings creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'dark_mode' => 'required|boolean',
                'language' => 'required|string',
                'notifications_enabled' => 'required|boolean',
            ]);

            $setting = Setting::where('user_id', $request->user()->id)->firstOrFail();
            $setting->update($validated);

            return response()->json([
                'success' => true,
                'settings' => [
                    'id' => $setting->id,
                    'dark_mode' => $setting->dark_mode,
                    'language' => $setting->language,
                    'notifications_enabled' => $setting->notifications_enabled,
                    'updated_at' => $setting->updated_at?->toIso8601String(),
                ],
                'message' => 'Settings updated successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Settings update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update single field
     */
    public function updateField(Request $request)
    {
        try {
            $validated = $request->validate([
                'dark_mode' => 'sometimes|boolean',
                'language' => 'sometimes|string',
                'notifications_enabled' => 'sometimes|boolean',
            ]);

            $setting = Setting::where('user_id', $request->user()->id)->firstOrFail();
            $setting->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Settings field updated successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Settings field update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings field',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


     /**
     * Fetch global settings
     */
    public function global(Request $request)
    {
        try {
            $setting = GlobalSetting::firstOrCreate(
                [],
                [
                    'tax_rate' => 0.0,
                    'shipping_cost' => 0.0,
                    'free_shipping_threshold' => null,
                    'app_name' => '',
                    'app_logo' => '',
                ]
            );

            return response()->json([
                'success' => true,
                'settings' => [
                    'id' => $setting->id,
                    'tax_rate' => $setting->tax_rate,
                    'shipping_cost' => $setting->shipping_cost,
                    'free_shipping_threshold' => $setting->free_shipping_threshold,
                    'app_name' => $setting->app_name,
                    'app_logo' => $setting->app_logo,
                    'updated_at' => $setting->updated_at?->toIso8601String(),
                ],
                'message' => 'Global settings retrieved successfully',
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Global settings fetch error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch global settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}