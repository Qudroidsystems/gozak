<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockLocation;
use Illuminate\Support\Facades\Validator;

class StockLocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Manage stock locations', ['only' => ['store', 'update', 'destroy']]);
        $this->middleware('permission:View inventory|Manage inventory', ['only' => ['index', 'show']]);
    }

    public function index()
    {
        $locations = StockLocation::orderBy('sort_order')->orderBy('name')->get();
        $pagetitle = "Stock Locations";
        
        return view('inventory.locations.index', compact('locations', 'pagetitle'));
    }

    public function create()
    {
        $pagetitle = "Add Stock Location";
        return view('inventory.locations.create', compact('pagetitle'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:stock_locations,name',
            'code' => 'nullable|string|max:50|unique:stock_locations,code',
            'address' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // If this is set as default, unset any existing default
        if ($request->is_default) {
            StockLocation::where('is_default', true)->update(['is_default' => false]);
        }

        $location = StockLocation::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Stock location created successfully',
            'location' => $location
        ]);
    }

    public function edit(StockLocation $stock_location)
    {
        return response()->json([
            'success' => true,
            'location' => $stock_location
        ]);
    }

    public function update(Request $request, StockLocation $stock_location)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:stock_locations,name,' . $stock_location->id,
            'code' => 'nullable|string|max:50|unique:stock_locations,code,' . $stock_location->id,
            'address' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // If this is set as default, unset any existing default
        if ($request->is_default && !$stock_location->is_default) {
            StockLocation::where('is_default', true)->update(['is_default' => false]);
        }

        $stock_location->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Stock location updated successfully',
            'location' => $stock_location
        ]);
    }

    public function destroy(StockLocation $stock_location)
    {
        // Check if location has stock transactions
        if ($stock_location->stocks()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete location that has stock transactions'
            ], 400);
        }

        // If this is the default location and there are other locations, set another as default
        if ($stock_location->is_default && StockLocation::count() > 1) {
            $newDefault = StockLocation::where('id', '!=', $stock_location->id)
                ->where('is_active', true)
                ->first();
            
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        $stock_location->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stock location deleted successfully'
        ]);
    }
}