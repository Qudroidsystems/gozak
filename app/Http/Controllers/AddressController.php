<?php

// app/Http/Controllers/AddressController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Address;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AddressController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Manage addresses|View addresses');
    }

    public function index(Request $request)
    {
        $pagetitle = "Address Management";

        $query = Address::with('user:id,first_name,last_name,email')
            ->latest();

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('street', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('postal_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q) use ($search) {
                      $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('customer_id')) {
            $query->where('user_id', $request->customer_id);
        }

        $addresses = $query->paginate(20)->appends($request->all());

        $customers = User::select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')
            ->get();

        return view('addresses.index', compact('addresses', 'pagetitle', 'customers'));
    }

    public function show($id)
    {
        $address = Address::with('user')->findOrFail($id);
        $pagetitle = "Address Details";

        return view('addresses.show', compact('address', 'pagetitle'));
    }

    public function edit($id)
    {
        $address = Address::with('user')->findOrFail($id);
        $pagetitle = "Edit Address";
        $customers = User::select('id', 'first_name', 'last_name', 'email')->get();

        return view('addresses.edit', compact('address', 'pagetitle', 'customers'));
    }

    public function update(Request $request, $id)
    {
        $address = Address::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
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
            // Reset all other addresses for this user
            Address::where('user_id', $validated['user_id'])
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        $address->update($validated);

        return redirect()->route('addresses.index')
            ->with('success', 'Address updated successfully');
    }

    public function create()
    {
        $pagetitle = "Add New Address";
        $customers = User::select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')
            ->get();

        return view('addresses.create', compact('pagetitle', 'customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
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
            Address::where('user_id', $validated['user_id'])
                ->update(['is_default' => false]);
        }

        Address::create($validated);

        return redirect()->route('addresses.index')
            ->with('success', 'Address created successfully');
    }

    public function destroy($id)
    {
        $address = Address::findOrFail($id);
        $userId = $address->user_id;

        $address->delete(); // Soft delete is safer

        // Optional: if it was default, promote another
        if ($address->is_default) {
            $newDefault = Address::where('user_id', $userId)->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        return back()->with('success', 'Address deleted successfully');
    }
}
