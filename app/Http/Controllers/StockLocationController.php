<?php

namespace App\Http\Controllers;

use App\Models\Product;
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
        $pagetitle = "Stock Locations";
        $locations = StockLocation::orderBy('sort_order')->orderBy('name')->get();
        
        // Get products if needed for any dropdown (optional)
        $products = Product::orderBy('title')->get(['id', 'title', 'sku']);
        
        return view('inventory.locations.index', compact('locations', 'pagetitle', 'products'));
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
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // If this is set as default, unset any existing default
            if ($request->is_default) {
                StockLocation::where('is_default', true)->update(['is_default' => false]);
            }

            $location = StockLocation::create([
                'name' => $request->name,
                'code' => $request->code,
                'address' => $request->address,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'email' => $request->email,
                'is_default' => $request->is_default ?? false,
                'is_active' => $request->is_active ?? true,
                'notes' => $request->notes,
                'sort_order' => $request->sort_order ?? 0,
            ]);

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Stock location created successfully',
                'location' => $location
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create location: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $location = StockLocation::withCount(['stocks as total_transactions'])
                ->with(['stocks' => function($query) {
                    $query->latest()->limit(10);
                }, 'stocks.product'])
                ->findOrFail($id);
            
            // Get stock summary for this location
            $stockSummary = [
                'total_in' => $location->stocks()->where('type', 'in')->sum('quantity'),
                'total_out' => $location->stocks()->where('type', 'out')->sum('quantity'),
                'total_adjustments' => $location->stocks()->where('type', 'adjustment')->count(),
                'total_transfers' => $location->stocks()->where('type', 'transfer')->count(),
                'total_value' => $location->stocks()->where('type', 'in')->sum(DB::raw('quantity * COALESCE(unit_cost, 0)')),
            ];
            
            // Get top products by stock value
            $topProducts = $location->stocks()
                ->select('product_id', DB::raw('SUM(quantity * COALESCE(unit_cost, 0)) as total_value'))
                ->where('type', 'in')
                ->groupBy('product_id')
                ->orderBy('total_value', 'desc')
                ->limit(5)
                ->with('product')
                ->get();
            
            return response()->json([
                'success' => true,
                'location' => $location,
                'stock_summary' => $stockSummary,
                'top_products' => $topProducts
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Location not found'
            ], 404);
        }
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
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // If this is set as default and it wasn't default before, unset any existing default
            if ($request->is_default && !$stock_location->is_default) {
                StockLocation::where('is_default', true)->update(['is_default' => false]);
            }
            
            // If unsetting default and this was the default, set another location as default if available
            if (!$request->is_default && $stock_location->is_default && StockLocation::where('id', '!=', $stock_location->id)->exists()) {
                $newDefault = StockLocation::where('id', '!=', $stock_location->id)
                    ->where('is_active', true)
                    ->first();
                
                if ($newDefault) {
                    $newDefault->update(['is_default' => true]);
                }
            }

            $stock_location->update([
                'name' => $request->name,
                'code' => $request->code,
                'address' => $request->address,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'email' => $request->email,
                'is_default' => $request->is_default ?? false,
                'is_active' => $request->is_active ?? true,
                'notes' => $request->notes,
                'sort_order' => $request->sort_order ?? $stock_location->sort_order,
            ]);

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Stock location updated successfully',
                'location' => $stock_location
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update location: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(StockLocation $stock_location)
    {
        DB::beginTransaction();
        try {
            // Check if location has stock transactions
            if ($stock_location->stocks()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete location that has stock transactions. Please transfer or delete the transactions first.'
                ], 400);
            }

            // If this is the default location and there are other locations, set another as default
            if ($stock_location->is_default && StockLocation::where('id', '!=', $stock_location->id)->where('is_active', true)->exists()) {
                $newDefault = StockLocation::where('id', '!=', $stock_location->id)
                    ->where('is_active', true)
                    ->first();
                
                if ($newDefault) {
                    $newDefault->update(['is_default' => true]);
                }
            }

            $stock_location->delete();

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Stock location deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete location: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateSortOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'locations' => 'required|array',
            'locations.*.id' => 'required|exists:stock_locations,id',
            'locations.*.sort_order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($request->locations as $item) {
                StockLocation::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
            }

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Sort order updated successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sort order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getLocationStock($locationId)
    {
        try {
            $location = StockLocation::findOrFail($locationId);
            
            $stockData = $location->stocks()
                ->select('product_id', DB::raw('SUM(
                    CASE 
                        WHEN type IN ("in", "adjustment", "transfer_in") THEN quantity 
                        WHEN type IN ("out", "transfer") THEN -quantity 
                        ELSE 0 
                    END
                ) as current_stock'))
                ->groupBy('product_id')
                ->with('product')
                ->having('current_stock', '>', 0)
                ->orderBy('current_stock', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'location' => $location,
                'stock_data' => $stockData,
                'total_items' => $stockData->sum('current_stock'),
                'total_value' => $stockData->sum(function($item) {
                    return $item->current_stock * ($item->product->price ?? 0);
                })
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get location stock: ' . $e->getMessage()
            ], 500);
        }
    }

    public function setAsDefault($id)
    {
        DB::beginTransaction();
        try {
            $location = StockLocation::findOrFail($id);
            
            // Unset any existing default
            StockLocation::where('is_default', true)->update(['is_default' => false]);
            
            // Set this location as default
            $location->update(['is_default' => true]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Location set as default successfully',
                'location' => $location
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to set as default: ' . $e->getMessage()
            ], 500);
        }
    }

    public function toggleStatus($id)
    {
        DB::beginTransaction();
        try {
            $location = StockLocation::findOrFail($id);
            
            // If deactivating the default location, we need to set another as default first
            if ($location->is_default && !$location->is_active) {
                $newDefault = StockLocation::where('id', '!=', $location->id)
                    ->where('is_active', true)
                    ->first();
                
                if (!$newDefault) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot deactivate the default location without another active location'
                    ], 400);
                }
                
                $newDefault->update(['is_default' => true]);
                $location->update(['is_default' => false]);
            }
            
            $location->update(['is_active' => !$location->is_active]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Location status updated successfully',
                'location' => $location->fresh()
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportLocations()
    {
        $locations = StockLocation::orderBy('sort_order')->orderBy('name')->get();
        
        $filename = 'stock-locations-' . date('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($locations) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'Name', 'Code', 'Address', 'Contact Person', 'Phone', 'Email',
                'Default', 'Active', 'Notes', 'Sort Order', 'Created At'
            ]);
            
            // Add data rows
            foreach ($locations as $location) {
                fputcsv($file, [
                    $location->name,
                    $location->code ?? '',
                    $location->address ?? '',
                    $location->contact_person ?? '',
                    $location->phone ?? '',
                    $location->email ?? '',
                    $location->is_default ? 'Yes' : 'No',
                    $location->is_active ? 'Active' : 'Inactive',
                    $location->notes ?? '',
                    $location->sort_order,
                    $location->created_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}