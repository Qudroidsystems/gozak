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
        $this->middleware(['auth:sanctum']);
    }

    /**
     * Set the default address by resetting others
     *
     * @param \App\Models\User $user
     * @param string|null $addressId
     */
    private function setDefaultAddress($user, $addressId = null)
    {
        $query = $user->addresses();
        if ($addressId) {
            $query->where('id', '!=', $addressId);
        }
        $query->update(['is_default' => false]);
    }

    /**
     * Fetch all addresses for the authenticated user
     */
    public function index(Request $request)
    {
        try {
            $addresses = $request->user()->addresses()->get();
            return response()->json([
                'success' => true,
                'addresses' => $addresses,
                'message' => 'Addresses retrieved successfully',
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Addresses fetch error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch addresses: ' . $e->getMessage(),
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
                'postal_code' => 'required|string|regex:/^\d{5}(-\d{4})?$/', // Matches US ZIP code format
                'country' => 'required|string|max:255',
                'phone_number' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/', // Matches E.164 phone format
                'is_default' => 'boolean',
            ]);

            $user = $request->user();
            if ($validated['is_default'] ?? false) {
                $this->setDefaultAddress($user);
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
                'message' => 'Failed to create address: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing address (Full update)
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
                'postal_code' => 'required|string|regex:/^\d{5}(-\d{4})?$/', // Matches US ZIP code format
                'country' => 'required|string|max:255',
                'phone_number' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/', // Matches E.164 phone format
                'is_default' => 'boolean',
            ]);

            if ($validated['is_default'] ?? false) {
                $this->setDefaultAddress($request->user(), $id);
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
                'message' => 'Failed to update address: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update ONLY the is_default field for an address (Partial update)
     */
    public function patch(Request $request, $id)
    {
        try {
            $address = Address::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

            // Only validate the is_default field for PATCH requests
            $validated = $request->validate([
                'is_default' => 'required|boolean',
            ]);

            if ($validated['is_default']) {
                // If setting this address as default, unset all others
                $this->setDefaultAddress($request->user(), $id);
            }

            $address->update($validated);

            return response()->json([
                'success' => true,
                'address' => $address->fresh(),
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
                'message' => 'Failed to update address selection: ' . $e->getMessage(),
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
            $address->forceDelete(); // Hard delete

            return response()->json([
                'success' => true,
                'message' => 'Address deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Address deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete address: ' . $e->getMessage(),
            ], 500);
        }
    }
}