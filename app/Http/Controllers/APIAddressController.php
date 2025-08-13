<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class APIAddressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Fetch all addresses for the authenticated user
     */
    public function index(Request $request)
    {
        try {
            $addresses = $request->user()->addresses;
            return response()->json([
                'success' => true,
                'addresses' => $addresses,
                'message' => 'Addresses retrieved successfully',
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Addresses fetch error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch addresses',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new address
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'street' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'state' => 'required|string|max:255',
                'postal_code' => 'required|string|max:20',
                'country' => 'required|string|max:255',
                'phone_number' => 'nullable|string|max:20',
                'is_default' => 'boolean',
            ]);

            $user = $request->user();
            if ($validated['is_default']) {
                $user->addresses()->update(['is_default' => false]);
            }

            $address = $user->addresses()->create($validated);

            return response()->json([
                'success' => true,
                'address' => $address,
                'message' => 'Address created successfully',
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Address creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create address',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing address
     */
    public function update(Request $request, $id)
    {
        try {
            $address = Address::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'street' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'state' => 'required|string|max:255',
                'postal_code' => 'required|string|max:20',
                'country' => 'required|string|max:255',
                'phone_number' => 'nullable|string|max:20',
                'is_default' => 'boolean',
            ]);

            if ($validated['is_default']) {
                $request->user()->addresses()->where('id', '!=', $id)->update(['is_default' => false]);
            }

            $address->update($validated);

            return response()->json([
                'success' => true,
                'address' => $address,
                'message' => 'Address updated successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Address update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update address',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the is_default field for an address
     */
    public function patch(Request $request, $id)
    {
        try {
            $address = Address::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

            $validated = $request->validate([
                'is_default' => 'required|boolean',
            ]);

            if ($validated['is_default']) {
                $request->user()->addresses()->where('id', '!=', $id)->update(['is_default' => false]);
            }

            $address->update($validated);

            return response()->json([
                'success' => true,
                'address' => $address,
                'message' => 'Address selection updated successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Address selection update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update address selection',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an address
     */
    public function destroy(Request $request, $id)
    {
        try {
            $address = Address::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();
            $address->delete();

            return response()->json([
                'success' => true,
                'message' => 'Address deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Address deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete address',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}