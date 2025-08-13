<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class APIAuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone_number' => 'nullable|string|max:20',
                // 'gender' => 'nullable|string|in:Male,Female,Other',
                // 'date_of_birth' => 'nullable|date',
            ]);

            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'username' => $validated['email'], // Use email as username or generate unique
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'],
                // 'gender' => $validated['gender'],
                // 'date_of_birth' => $validated['date_of_birth'],
                'password' => bcrypt($validated['password']),
            ]);

            event(new Registered($user));

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
            \Log::error('Registration error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

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
            \Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

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
            \Log::error('Social login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Social login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

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
     * Verify the user's email address.
     *
     * @param Request $request
     * @param int $id
     * @param string $hash
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        try {
            $user = User::findOrFail($id);

            // Verify the hash
            if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
                Log::error('Email verification failed: Invalid hash for user ID ' . $id);
                return response()->json([
                    'success' => false,
                    'message' => 'Email verification failed',
                    'error' => 'Invalid verification link',
                ], 403);
            }

            if ($user->hasVerifiedEmail()) {
                Log::info('Email already verified for user ID: ' . $id);
                return response()->json([
                    'success' => false,
                    'message' => 'Email already verified',
                ], 400);
            }

            $user->markEmailAsVerified();
            Log::info('Email verified for user ID: ' . $id);

            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Email verification failed: No user found with ID ' . $id);
            return response()->json([
                'success' => false,
                'message' => 'Email verification failed',
                'error' => 'No query results for model [App\\Models\\User] ' . $id,
            ], 404);
        }
    }
    
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
            \Log::error('Email verification notification error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

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
            \Log::error('Password reset email error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset email',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

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
            \Log::error('Password reset error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json([
                'success' => true,
                'message' => 'Logout successful',
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}