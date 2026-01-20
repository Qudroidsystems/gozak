<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * @group User Addresses
 * Manage shipping and billing addresses for the authenticated user.
 *
 * All endpoints in this group require Sanctum authentication (Bearer Token).
 */
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
     * @return void
     */
    private function setDefaultAddress($user, $addressId = null)
    {
        $query = $user->addresses();
        if ($addressId) {
            $query->where('id', '!=', $addressId);
        }
        $updated = $query->update(['is_default' => false]);
        if ($updated > 0) {
            Log::info('Default addresses reset for user: ' . $user->id . ', excluded ID: ' . ($addressId ?? 'none'));
        }
    }

    /**
     * Fetch all addresses for the authenticated user
     *
     * Returns a list of all addresses associated with the currently authenticated user.
     *
     * @authenticated
     *
     * @response 200 {
     *     "success": true,
     *     "addresses": [
     *         {
     *             "id": 1,
     *             "name": "Home",
     *             "street": "123 Main Street",
     *             "city": "Lagos",
     *             "state": "Lagos",
     *             "postal_code": "100001",
     *             "country": "Nigeria",
     *             "phone_number": "+2348012345678",
     *             "is_default": true,
     *             "created_at": "2025-01-01T10:00:00.000000Z",
     *             "updated_at": "2025-01-01T10:00:00.000000Z"
     *         }
     *     ],
     *     "message": "Addresses retrieved successfully"
     * }
     * @response 401 {
     *     "message": "Unauthenticated."
     * }
     * @response 500 {
     *     "success": false,
     *     "message": "Failed to fetch addresses: ..."
     * }
     */
    public function index(Request $request)
    {
        try {
            $addresses = $request->user()->addresses()->get();
            Log::info('Addresses fetched for user: ' . $request->user()->id . ', count: ' . $addresses->count());

            return response()->json([
                'success' => true,
                'addresses' => $addresses,
                'message' => 'Addresses retrieved successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Addresses fetch error for user ' . $request->user()->id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch addresses: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new address
     *
     * Creates a new address for the authenticated user.
     * If `is_default` is true, all other addresses will have `is_default` set to false.
     *
     * @authenticated
     *
     * @bodyParam name string|null Optional friendly name for the address (e.g. "Home", "Office"). Example: Home
     * @bodyParam street string required Full street address. Example: 123 Marina Road
     * @bodyParam city string required City name. Example: Lagos
     * @bodyParam state string required State or province. Example: Lagos State
     * @bodyParam postal_code string required Postal/ZIP code. Example: 100001
     * @bodyParam country string required Country name. Example: Nigeria
     * @bodyParam phone_number string required Phone number in E.164 format. Example: +2348012345678
     * @bodyParam is_default boolean Whether this should become the default address. Example: true
     *
     * @response 201 {
     *     "success": true,
     *     "address": { ... address object ... },
     *     "message": "Address created successfully"
     * }
     * @response 422 {
     *     "success": false,
     *     "message": "Validation failed",
     *     "errors": { ... validation errors ... }
     * }
     * @response 500 server error
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'street' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'state' => 'required|string|max:255',
                'postal_code' => 'required|string|regex:/^\d{5}(-\d{4})?$/',
                'country' => 'required|string|max:255',
                'phone_number' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/',
                'is_default' => 'boolean',
            ]);

            $user = $request->user();
            if ($validated['is_default'] ?? false) {
                $this->setDefaultAddress($user);
            }

            $address = $user->addresses()->create($validated);
            Log::info('Address created for user: ' . $user->id . ', ID: ' . $address->id);

            return response()->json([
                'success' => true,
                'address' => $address,
                'message' => 'Address created successfully',
            ], 201);
        } catch (ValidationException $e) {
            Log::warning('Address validation failed for user: ' . $request->user()->id, [
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Address creation error for user ' . $request->user()->id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create address: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing address (full update)
     *
     * @authenticated
     *
     * @urlParam id integer required The ID of the address to update. Example: 1
     *
     * @bodyParam name string|null Optional
     * @bodyParam street string required
     * @bodyParam city string required
     * @bodyParam state string required
     * @bodyParam postal_code string required
     * @bodyParam country string required
     * @bodyParam phone_number string required
     * @bodyParam is_default boolean optional
     *
     * @response 200 updated address object
     * @response 403/404 address not found or not owned
     * @response 422 validation errors
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
                'postal_code' => 'required|string|regex:/^\d{5}(-\d{4})?$/',
                'country' => 'required|string|max:255',
                'phone_number' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/',
                'is_default' => 'boolean',
            ]);

            if ($validated['is_default'] ?? false) {
                $this->setDefaultAddress($request->user(), $id);
            }

            $address->update($validated);
            Log::info('Address updated for user: ' . $request->user()->id . ', ID: ' . $id);

            return response()->json([
                'success' => true,
                'address' => $address->fresh(),
                'message' => 'Address updated successfully',
            ], 200);
        } catch (ValidationException $e) {
            Log::warning('Address update validation failed for user: ' . $request->user()->id . ', ID: ' . $id, [
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Address update error for user ' . $request->user()->id . ', ID: ' . $id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update address: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Partially update an address (only is_default field)
     *
     * @authenticated
     *
     * @urlParam id integer required The ID of the address
     * @bodyParam is_default boolean required Whether to set this address as default
     *
     * @response 200 updated address
     * @response 422 invalid input
     */
    public function patch(Request $request, $id)
    {
        try {
            $address = Address::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

            $validated = $request->validate([
                'is_default' => 'required|boolean',
            ]);

            if ($validated['is_default']) {
                $this->setDefaultAddress($request->user(), $id);
            }

            $address->update($validated);
            $updatedAddress = $address->fresh();

            Log::info('Address selection updated for user: ' . $request->user()->id . ', ID: ' . $id . ' to is_default: ' . ($validated['is_default'] ? 'true' : 'false'));

            return response()->json([
                'success' => true,
                'address' => $updatedAddress,
                'message' => 'Address selection updated successfully',
            ], 200);
        } catch (ValidationException $e) {
            Log::warning('Address patch validation failed for user: ' . $request->user()->id . ', ID: ' . $id, [
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Address patch error for user ' . $request->user()->id . ', ID: ' . $id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update address selection: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an address
     *
     * @authenticated
     *
     * @urlParam id integer required The ID of the address to delete
     *
     * @response 200 {
     *     "success": true,
     *     "message": "Address deleted successfully"
     * }
     * @response 404 address not found or not owned
     */
    public function destroy(Request $request, $id)
    {
        try {
            $address = Address::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

            $wasDefault = $address->is_default;
            $address->forceDelete();

            Log::info('Address deleted for user: ' . $request->user()->id . ', ID: ' . $id . ', was default: ' . ($wasDefault ? 'true' : 'false'));

            return response()->json([
                'success' => true,
                'message' => 'Address deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Address deletion error for user ' . $request->user()->id . ', ID: ' . $id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete address: ' . $e->getMessage(),
            ], 500);
        }
    }
}
