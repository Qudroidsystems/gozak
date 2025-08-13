<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use DB;
use Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class APIUserController extends Controller
{


    function __construct()
    {
         $this->middleware('permission:user-list|user-create|user-edit|user-delete', ['only' => ['index','store']]);
         $this->middleware('permission:user-create', ['only' => ['create','store']]);
         $this->middleware('permission:user-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:user-delete', ['only' => ['destroy']]);
    }

    public function show(Request $request)
    {
        try {
            $user = $request->user();
            return response()->json([
                'success' => true,
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
                'message' => 'User details retrieved successfully',
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Fetch user error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'username' => 'sometimes|string|unique:users,username,' . $request->user()->id,
                'email' => 'sometimes|email|unique:users,email,' . $request->user()->id,
                'phone_number' => 'nullable|string|max:20',
                'gender' => 'nullable|string|in:Male,Female,Other',
                'date_of_birth' => 'nullable|date',
            ]);

            $user = $request->user();
            $user->update($validated);

            return response()->json([
                'success' => true,
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
                'message' => 'User updated successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Update user error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage(),
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

            $user = $request->user();
            $field = $validated['field'];
            $value = $validated['value'];

            if ($field === 'email' && User::where('email', $value)->where('id', '!=', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already taken',
                ], 422);
            }

            if ($field === 'username' && User::where('username', $value)->where('id', '!=', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Username already taken',
                ], 422);
            }

            if ($field === 'gender' && !in_array($value, ['Male', 'Female', 'Other'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid gender value',
                ], 422);
            }

            if ($field === 'date_of_birth') {
                try {
                    \Carbon\Carbon::parse($value);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid date format',
                    ], 422);
                }
            }

            $user->update([$field => $value]);

            return response()->json([
                'success' => true,
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
                'message' => 'Field updated successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Update field error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update field',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function uploadProfilePicture(Request $request)
    {
        try {
            $validated = $request->validate([
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $user = $request->user();
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }

            $path = $request->file('profile_picture')->store('profile_pictures', 'public');
            $user->update(['profile_image' => $path]);

            return response()->json([
                'success' => true,
                'profile_image' => $path,
                'message' => 'Profile picture uploaded successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Upload profile picture error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload profile picture',
                'error' => $e->getMessage(),
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
            \Log::error('Delete account error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}