<?php

namespace App\Http\Controllers;

use DB;
use Hash;
use App\Models\User;
use App\Models\Setting;
use Illuminate\View\View;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class APIUserController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:user-list|user-create|user-edit|user-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:user-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:user-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:user-delete', ['only' => ['destroy']]);
    }

    public function show(Request $request)
    {
        try {
            $user = $request->user();

            $settings    = Setting::where('user_id', $user->id)->first();
            $userSettings = null;
            if ($settings) {
                $userSettings = [
                    'dark_mode'              => $settings->dark_mode,
                    'language'               => $settings->language,
                    'notifications_enabled'  => $settings->notifications_enabled,
                    'updated_at'             => $settings->updated_at?->toIso8601String(),
                ];
            }

            return response()->json([
                'success' => true,
                'user'    => $this->formatUser($user, $userSettings),
                'message' => 'User details retrieved successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Fetch user error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user details',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name'   => 'sometimes|string|max:255',
                'last_name'    => 'sometimes|string|max:255',
                'username'     => 'sometimes|string|unique:users,username,' . $request->user()->id,
                'email'        => 'sometimes|email|unique:users,email,' . $request->user()->id,
                'phone_number' => 'nullable|string|max:20',
                'gender'       => 'nullable|string|in:Male,Female,Other',
                'date_of_birth'=> 'nullable|date',
            ]);

            $user = $request->user();
            $user->update($validated);

            return response()->json([
                'success' => true,
                'user'    => $this->formatUser($user->fresh()),
                'message' => 'User updated successfully',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Update user error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function updateField(Request $request)
    {
        try {
            $validated = $request->validate([
                'field' => 'required|string|in:first_name,last_name,username,email,phone_number,profile_image,gender,date_of_birth',
                'value' => 'required',
            ]);

            $user  = $request->user();
            $field = $validated['field'];
            $value = $validated['value'];

            if ($field === 'email' && User::where('email', $value)->where('id', '!=', $user->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Email already taken'], 422);
            }

            if ($field === 'username' && User::where('username', $value)->where('id', '!=', $user->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Username already taken'], 422);
            }

            if ($field === 'gender' && !in_array($value, ['Male', 'Female', 'Other'])) {
                return response()->json(['success' => false, 'message' => 'Invalid gender value'], 422);
            }

            if ($field === 'date_of_birth') {
                try {
                    \Carbon\Carbon::parse($value);
                } catch (\Exception $e) {
                    return response()->json(['success' => false, 'message' => 'Invalid date format'], 422);
                }
            }

            $user->update([$field => $value]);

            return response()->json([
                'success' => true,
                'user'    => $this->formatUser($user->fresh()),
                'message' => 'Field updated successfully',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Update field error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update field',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function uploadProfilePicture(Request $request)
    {
        try {
            $request->validate([
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $user = $request->user();

            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }

            $path = $request->file('profile_picture')->store('profile_pictures', 'public');
            $user->update(['profile_image' => $path]);

            return response()->json([
                'success'       => true,
                'profile_image' => $path,
                'message'       => 'Profile picture uploaded successfully',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Upload profile picture error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload profile picture',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $user = $request->user();

            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Delete account error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ── FCM / Notification endpoints ──────────────────────────────────────────

    /**
     * POST /api/user/fcm-token
     * Save or refresh the user's FCM device token.
     * Flutter calls this after every login and whenever Firebase rotates the token.
     */


// ══════════════════════════════════════════════════════════════════════════════
// Replace ONLY the updateFcmToken() method in your APIUserController.php
// ══════════════════════════════════════════════════════════════════════════════

    public function updateFcmToken(Request $request)
    {
        try {
            $userId     = $request->user()->id;
            $token      = trim($request->input('fcm_token', ''));
            $platform   = $request->input('platform', 'android');
            $appVersion = $request->input('app_version', '');

            // Log everything so you can check storage/logs/laravel.log
            \Log::info('FCM updateFcmToken called', [
                'user_id'          => $userId,
                'token_received'   => $token ? substr($token, 0, 30) . '...' : 'EMPTY',
                'token_length'     => strlen($token),
                'platform'         => $platform,
                'app_version'      => $appVersion,
                'all_request_keys' => array_keys($request->all()),
            ]);

            if (empty($token)) {
                \Log::warning('FCM updateFcmToken: empty token received', [
                    'user_id'   => $userId,
                    'raw_input' => $request->all(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'fcm_token is required and cannot be empty',
                ], 422);
            }

            $updateData = [
                'fcm_token'  => $token,
                'updated_at' => now(),
            ];

            if (!empty($platform)) {
                $updateData['last_device_platform'] = $platform;
            }

            if (!empty($appVersion)) {
                $updateData['last_app_version'] = $appVersion;
            }

            // Raw DB — bypasses Eloquent $fillable / $hidden / casts completely
            $affected = \DB::table('users')
                ->where('id', $userId)
                ->update($updateData);

            \Log::info('FCM token DB update result', [
                'user_id'       => $userId,
                'rows_affected' => $affected,
                'token_preview' => substr($token, 0, 30) . '...',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'FCM token saved successfully',
            ], 200);

        } catch (\Exception $e) {
            \Log::error('FCM updateFcmToken exception', [
                'user_id' => $request->user()->id ?? null,
                'error'   => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to save FCM token: ' . $e->getMessage(),
            ], 500);
        }
    }
    /**
     * PUT /api/user/notification-preferences
     * Toggle individual notification preference flags.
     * If push is turned OFF, the FCM token is cleared immediately
     * so the user genuinely stops receiving notifications.
     */
    public function updateNotificationPreferences(Request $request)
    {
        try {
            $request->validate([
                'push_notifications_enabled'        => 'nullable|boolean',
                'order_updates_enabled'             => 'nullable|boolean',
                'promotional_notifications_enabled' => 'nullable|boolean',
                'security_alerts_enabled'           => 'nullable|boolean',
                'email_notifications_enabled'       => 'nullable|boolean',
            ]);

            $user   = $request->user();
            $fields = [
                'push_notifications_enabled',
                'order_updates_enabled',
                'promotional_notifications_enabled',
                'security_alerts_enabled',
                'email_notifications_enabled',
            ];

            $updateData = [];
            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->boolean($field);
                }
            }

            // Clear FCM token if push is disabled so notifications stop immediately
            if (isset($updateData['push_notifications_enabled']) &&
                !$updateData['push_notifications_enabled']) {
                $updateData['fcm_token'] = null;
            }

            $user->update($updateData);

            return response()->json([
                'success'     => true,
                'message'     => 'Notification preferences updated',
                'preferences' => $user->fresh()->only($fields),
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Notification preferences update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update preferences',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Format user data consistently across all responses.
     * Pass $userSettings if you've already fetched them (avoids extra query).
     */
    private function formatUser(User $user, ?array $userSettings = null): array
    {
        return [
            'id'                                => $user->id,
            'first_name'                        => $user->first_name,
            'last_name'                         => $user->last_name,
            'username'                          => $user->username,
            'email'                             => $user->email,
            'phone_number'                      => $user->phone_number,
            'profile_image'                     => $user->profile_image,
            'social_provider'                   => $user->social_provider,
            'gender'                            => $user->gender,
            'date_of_birth'                     => $user->date_of_birth?->toDateString(),
            'email_verified_at'                 => $user->email_verified_at?->toIso8601String(),
            // Notification preferences
            'push_notifications_enabled'        => (bool) $user->push_notifications_enabled,
            'order_updates_enabled'             => (bool) $user->order_updates_enabled,
            'promotional_notifications_enabled' => (bool) $user->promotional_notifications_enabled,
            'security_alerts_enabled'           => (bool) $user->security_alerts_enabled,
            'email_notifications_enabled'       => (bool) $user->email_notifications_enabled,
            'created_at'                        => $user->created_at?->toIso8601String(),
            'updated_at'                        => $user->updated_at?->toIso8601String(),
            'addresses'                         => $user->addresses ?? [],
            'settings'                          => $userSettings,
        ];
    }
}
