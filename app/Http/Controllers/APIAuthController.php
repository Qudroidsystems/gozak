<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

/**
 * @group Authentication
 * User registration, login, social login, email verification, password reset and logout.
 */
class APIAuthController extends Controller
{
    /**
     * Register a new user
     *
     * Creates a new user account and returns an authentication token.
     * A verification email is sent (if mail is configured).
     *
     * @bodyParam email string required Unique email address. Example: user@example.com
     * @bodyParam password string required Minimum 6 characters. Example: password123
     * @bodyParam first_name string required User's first name. Example: John
     * @bodyParam last_name string required User's last name. Example: Doe
     * @bodyParam phone_number string|null Optional phone number. Example: +2348012345678
     *
     * @response 201 {
     *     "success": true,
     *     "token": "1|randomsanctumtokenhere",
     *     "user": { ... },
     *     "message": "Registration successful. Please verify your email."
     * }
     * @response 422 validation errors
     * @response 500 server error
     */
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'email'        => 'required|email|unique:users,email',
                'password'     => 'required|string|min:6',
                'first_name'   => 'required|string|max:255',
                'last_name'    => 'required|string|max:255',
                'phone_number' => 'nullable|string|max:20',
            ]);

            $user = User::create([
                'first_name'                        => $validated['first_name'],
                'last_name'                         => $validated['last_name'],
                'username'                          => $validated['email'],
                'email'                             => $validated['email'],
                'phone_number'                      => $validated['phone_number'] ?? null,
                'password'                          => bcrypt($validated['password']),
                // Default all notifications ON for new users
                'push_notifications_enabled'        => true,
                'order_updates_enabled'             => true,
                'promotional_notifications_enabled' => true,
                'security_alerts_enabled'           => true,
                'email_notifications_enabled'       => true,
            ]);

            try {
                event(new Registered($user));
                Log::info('Verification email sent successfully for user: ' . $user->email);
            } catch (\Exception $emailError) {
                Log::error('Failed to send verification email for user ' . $user->email . ': ' . $emailError->getMessage());
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'token'   => $token,
                'user'    => $this->formatUser($user),
                'message' => 'Registration successful. Please verify your email.',
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login user with email and password
     *
     * @bodyParam email string required Example: user@example.com
     * @bodyParam password string required
     *
     * @response 200 success with token and user data
     * @response 401 invalid credentials
     * @response 403 email not verified
     * @response 422 validation errors
     */
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email'    => 'required|email',
                'password' => 'required|string',
            ]);

            if (!Auth::attempt($validated)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            $user = Auth::user();

            if (!$user->hasVerifiedEmail()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not verified. Please verify your email to log in.',
                ], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'token'   => $token,
                'user'    => $this->formatUser($user),
                'message' => 'Login successful',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Social login (Google only)
     *
     * @bodyParam provider string required Must be "google". Example: google
     * @bodyParam access_token string required OAuth access token from Google
     *
     * @response 200 success with token and user (creates user if not exists)
     * @response 422 validation errors
     * @response 500 provider error or server error
     */
    public function socialLogin(Request $request)
    {
        try {
            $validated = $request->validate([
                'provider'     => 'required|string|in:google',
                'access_token' => 'required|string',
            ]);

            $provider    = $validated['provider'];
            $accessToken = $validated['access_token'];

            $userInfo = $this->getGoogleUserInfo($accessToken);

            $user = User::where('email', $userInfo['email'])->first();

            if (!$user) {
                $user = User::create([
                    'first_name'                        => $userInfo['first_name'] ?? explode('@', $userInfo['email'])[0],
                    'last_name'                         => $userInfo['last_name'] ?? '',
                    'username'                          => $userInfo['email'],
                    'email'                             => $userInfo['email'],
                    'profile_image'                     => $userInfo['profile_image'] ?? null,
                    'social_provider'                   => $provider,
                    'email_verified_at'                 => now(),
                    // Default all notifications ON for new social users
                    'push_notifications_enabled'        => true,
                    'order_updates_enabled'             => true,
                    'promotional_notifications_enabled' => true,
                    'security_alerts_enabled'           => true,
                    'email_notifications_enabled'       => true,
                ]);

                Log::info('New user created via Google signup: ' . $user->email);
            } else {
                $user->update([
                    'social_provider'   => $provider,
                    'profile_image'     => $userInfo['profile_image'] ?? $user->profile_image,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);

                Log::info('Existing user logged in via Google: ' . $user->email);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'token'   => $token,
                'user'    => $this->formatUser($user),
                'message' => 'Google login successful',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Google login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Google login failed',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Google user information from access token
     */
    protected function getGoogleUserInfo(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if ($response->failed()) {
                throw new \Exception('Failed to fetch Google user info');
            }

            $data = $response->json();

            return [
                'email'         => $data['email'],
                'first_name'    => $data['given_name']  ?? $data['name'] ?? '',
                'last_name'     => $data['family_name'] ?? '',
                'profile_image' => $data['picture']     ?? null,
                'gender'        => $data['gender']      ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Google user info fetch error: ' . $e->getMessage());
            throw new \Exception('Failed to retrieve user information from Google: ' . $e->getMessage());
        }
    }

    /**
     * Verify email address (web route - returns view)
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        try {
            $user = User::findOrFail($id);

            if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
                Log::error('Email verification failed: Invalid hash for user ID ' . $id);
                return view('auth.verify-email-failed', ['message' => 'Invalid verification link']);
            }

            $user->markEmailAsVerified();
            event(new Verified($user));
            Log::info('Email verified for user ID: ' . $id);

            $request->session()->flash('verified', true);
            $request->session()->flash('email', $user->email);

            return view('auth.verify-email-success', [
                'email' => $user->email,
                'user'  => $user,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Email verification failed: No user found with ID ' . $id);
            return view('auth.verify-email-failed', ['message' => 'User not found']);
        } catch (\Exception $e) {
            Log::error('Email verification error: ' . $e->getMessage());
            return view('auth.verify-email-failed', ['message' => 'Verification failed. Please try again.']);
        }
    }

    /**
     * Resend email verification notification
     *
     * @authenticated
     *
     * @response 200 verification email sent
     * @response 400 email already verified
     * @response 500 server error
     */
    public function sendEmailVerificationNotification(Request $request)
    {
        try {
            $user = $request->user();

            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already verified',
                ], 400);
            }

            $user->sendEmailVerificationNotification();

            return response()->json([
                'success' => true,
                'message' => 'Verification email sent',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Email verification notification error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send password reset link
     *
     * @bodyParam email string required Must exist in the users table. Example: user@example.com
     *
     * @response 200 reset link sent
     * @response 422 invalid email
     * @response 400 other failure
     */
    public function sendPasswordResetEmail(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);

            $status = Password::sendResetLink($validated);

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password reset email sent',
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset email',
            ], 400);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Password reset email error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset email',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset password using reset token
     *
     * @bodyParam token string required Password reset token from email
     * @bodyParam email string required
     * @bodyParam password string required min:6 confirmed
     *
     * @response 200 password reset successful
     * @response 422 invalid data
     * @response 400 reset failed
     */
    public function resetPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'token'    => 'required|string',
                'email'    => 'required|email|exists:users,email',
                'password' => 'required|string|min:6|confirmed',
            ]);

            $status = Password::reset(
                $validated,
                function ($user, $password) {
                    $user->forceFill([
                        'password'       => bcrypt($password),
                        'remember_token' => Str::random(60),
                    ])->save();

                    event(new PasswordReset($user));
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password reset successful',
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
            ], 400);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Password reset error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout the authenticated user
     * Clears the FCM token so push notifications stop immediately.
     *
     * @authenticated
     *
     * @response 200 { "success": true, "message": "Logout successful" }
     * @response 500 server error
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            // Clear FCM token — stops push notifications after logout
            $user->update(['fcm_token' => null]);

            // Revoke the current Sanctum token
            $user->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout successful',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ── FCM token endpoint ────────────────────────────────────────────────────

    /**
     * Save or refresh the user's FCM device token.
     * Called by Flutter after every login and on token refresh.
     *
     * @authenticated
     *
     * @bodyParam fcm_token string required The FCM device token. Example: dGhpcyBpcyBhIHRlc3Q...
     * @bodyParam platform string nullable android|ios|web. Example: android
     * @bodyParam app_version string nullable App version string. Example: 1.0.0
     *
     * @response 200 { "success": true, "message": "FCM token saved successfully" }
     * @response 422 validation errors
     */
    public function updateFcmToken(Request $request)
    {
        try {
            $validated = $request->validate([
                'fcm_token'   => 'required|string|max:500',
                'platform'    => 'nullable|in:android,ios,web',
                'app_version' => 'nullable|string|max:20',
            ]);

            $updateData = ['fcm_token' => $validated['fcm_token']];

            if (!empty($validated['platform'])) {
                $updateData['last_device_platform'] = $validated['platform'];
            }

            if (!empty($validated['app_version'])) {
                $updateData['last_app_version'] = $validated['app_version'];
            }

            $request->user()->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'FCM token saved successfully',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('FCM token update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save FCM token',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update notification preferences
     *
     * @authenticated
     *
     * @bodyParam push_notifications_enabled boolean
     * @bodyParam order_updates_enabled boolean
     * @bodyParam promotional_notifications_enabled boolean
     * @bodyParam security_alerts_enabled boolean
     * @bodyParam email_notifications_enabled boolean
     *
     * @response 200 preferences updated
     */
    public function updateNotificationPreferences(Request $request)
    {
        try {
            $validated = $request->validate([
                'push_notifications_enabled'        => 'nullable|boolean',
                'order_updates_enabled'             => 'nullable|boolean',
                'promotional_notifications_enabled' => 'nullable|boolean',
                'security_alerts_enabled'           => 'nullable|boolean',
                'email_notifications_enabled'       => 'nullable|boolean',
            ]);

            $user       = $request->user();
            $updateData = [];

            $fields = [
                'push_notifications_enabled',
                'order_updates_enabled',
                'promotional_notifications_enabled',
                'security_alerts_enabled',
                'email_notifications_enabled',
            ];

            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->boolean($field);
                }
            }

            // If push notifications are disabled, clear the FCM token
            // so the user genuinely stops receiving notifications
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
     * Format user data consistently across all auth responses
     */
    private function formatUser(User $user): array
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
            'push_notifications_enabled'        => (bool) $user->push_notifications_enabled,
            'order_updates_enabled'             => (bool) $user->order_updates_enabled,
            'promotional_notifications_enabled' => (bool) $user->promotional_notifications_enabled,
            'security_alerts_enabled'           => (bool) $user->security_alerts_enabled,
            'email_notifications_enabled'       => (bool) $user->email_notifications_enabled,
            'created_at'                        => $user->created_at?->toIso8601String(),
            'updated_at'                        => $user->updated_at?->toIso8601String(),
            'addresses'                         => $user->addresses ?? [],
        ];
    }
}
