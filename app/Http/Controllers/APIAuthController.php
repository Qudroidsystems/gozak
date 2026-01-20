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
     *     "user": {
     *         "id": 1,
     *         "first_name": "John",
     *         "last_name": "Doe",
     *         "username": "user@example.com",
     *         "email": "user@example.com",
     *         "phone_number": "+2348012345678",
     *         "profile_image": null,
     *         "social_provider": null,
     *         "gender": null,
     *         "date_of_birth": null,
     *         "email_verified_at": null,
     *         "created_at": "2025-01-01T10:00:00.000000Z",
     *         "updated_at": "2025-01-01T10:00:00.000000Z",
     *         "addresses": []
     *     },
     *     "message": "Registration successful. Please verify your email."
     * }
     * @response 422 validation errors
     * @response 500 server error
     */
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone_number' => 'nullable|string|max:20',
            ]);

            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'username' => $validated['email'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'] ?? null,
                'password' => bcrypt($validated['password']),
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
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'profile_image' => $user->profile_image,
                    'social_provider' => $user->social_provider,
                    'gender' => $user->gender,
                    'date_of_birth' => $user->date_of_birth?->toDateString(),
                    'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                    'created_at' => $user->created_at?->toIso8601String(),
                    'updated_at' => $user->updated_at?->toIso8601String(),
                    'addresses' => $user->addresses,
                ],
                'message' => 'Registration successful. Please verify your email.',
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
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
                'email' => 'required|email',
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
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'profile_image' => $user->profile_image,
                    'social_provider' => $user->social_provider,
                    'gender' => $user->gender,
                    'date_of_birth' => $user->date_of_birth?->toDateString(),
                    'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                    'created_at' => $user->created_at?->toIso8601String(),
                    'updated_at' => $user->updated_at?->toIso8601String(),
                    'addresses' => $user->addresses,
                ],
                'message' => 'Login successful',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Social login (Google / Facebook)
     *
     * @bodyParam provider string required Must be "google" or "facebook". Example: google
     * @bodyParam access_token string required OAuth access token from the provider
     *
     * @response 200 success with token and user (creates user if not exists)
     * @response 422 validation errors
     * @response 500 provider error or server error
     */
    public function socialLogin(Request $request)
    {
        try {
            $validated = $request->validate([
                'provider' => 'required|string|in:google,facebook',
                'access_token' => 'required|string',
            ]);

            $provider = $validated['provider'];
            $accessToken = $validated['access_token'];

            $userInfo = $this->getSocialUserInfo($provider, $accessToken);

            $user = User::where('email', $userInfo['email'])->first();

            if (!$user) {
                $user = User::create([
                    'first_name' => $userInfo['first_name'] ?? 'User',
                    'last_name' => $userInfo['last_name'] ?? '',
                    'username' => $userInfo['email'],
                    'email' => $userInfo['email'],
                    'social_provider' => $provider,
                    'gender' => $userInfo['gender'] ?? null,
                    'date_of_birth' => $userInfo['date_of_birth'] ?? null,
                    'email_verified_at' => now(),
                ]);
            } else {
                $user->update(['social_provider' => $provider]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'profile_image' => $user->profile_image,
                    'social_provider' => $user->social_provider,
                    'gender' => $user->gender,
                    'date_of_birth' => $user->date_of_birth?->toDateString(),
                    'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                    'created_at' => $user->created_at?->toIso8601String(),
                    'updated_at' => $user->updated_at?->toIso8601String(),
                    'addresses' => $user->addresses,
                ],
                'message' => 'Social login successful',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Social login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Social login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get social provider user information
     *
     * @param string $provider
     * @param string $accessToken
     * @return array
     * @throws \Exception
     */
    protected function getSocialUserInfo(string $provider, string $accessToken)
    {
        if ($provider === 'google') {
            $response = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo');
            if ($response->failed()) {
                throw new \Exception('Failed to fetch Google user info');
            }
            $data = $response->json();
            return [
                'email' => $data['email'],
                'first_name' => $data['given_name'],
                'last_name' => $data['family_name'],
                'gender' => $data['gender'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
            ];
        } elseif ($provider === 'facebook') {
            $response = Http::get("https://graph.facebook.com/me?fields=id,name,email,first_name,last_name,gender,birthday&access_token=$accessToken");
            if ($response->failed()) {
                throw new \Exception('Failed to fetch Facebook user info');
            }
            $data = $response->json();
            return [
                'email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => $data['gender'] ? ucfirst($data['gender']) : null,
                'date_of_birth' => $data['birthday'] ? \Carbon\Carbon::createFromFormat('m/d/Y', $data['birthday'])->toDateString() : null,
            ];
        }

        throw new \Exception('Unsupported provider');
    }

    /**
     * Verify email address (web route - returns view)
     *
     * This endpoint is typically accessed via browser link from email.
     * It marks the email as verified and shows success/failure page.
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
                'user' => $user
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
                'error' => $e->getMessage(),
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
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Password reset email error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset email',
                'error' => $e->getMessage(),
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
                'token' => 'required|string',
                'email' => 'required|email|exists:users,email',
                'password' => 'required|string|min:6|confirmed',
            ]);

            $status = Password::reset(
                $validated,
                function ($user, $password) {
                    $user->forceFill([
                        'password' => bcrypt($password),
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
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Password reset error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout the authenticated user (revoke current token)
     *
     * @authenticated
     *
     * @response 200 {
     *     "success": true,
     *     "message": "Logout successful"
     * }
     * @response 500 server error
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json([
                'success' => true,
                'message' => 'Logout successful',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
