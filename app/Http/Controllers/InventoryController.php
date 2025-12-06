<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Brand;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\StockLocation;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    public function __construct()
    {
        // View permissions
        $this->middleware('permission:View inventory', ['only' => ['index', 'show']]);
        $this->middleware('permission:View inventory dashboard', ['only' => ['dashboard']]);
        $this->middleware('permission:View stock levels', ['only' => ['stockLevels']]);
        $this->middleware('permission:View stock history', ['only' => ['stockHistory']]);
        
        // Management permissions
        $this->middleware('permission:Manage inventory', ['only' => ['store', 'update', 'destroy']]);
        $this->middleware('permission:Adjust stock', ['only' => ['adjustStock', 'bulkAdjust']]);
        $this->middleware('permission:Transfer stock', ['only' => ['transferStock']]);
        $this->middleware('permission:Import inventory', ['only' => ['import']]);
        $this->middleware('permission:Export inventory', ['only' => ['export']]);
    
    }

    public function index(Request $request)
    {
        $pagetitle = "Inventory Management";
        
        $query = Stock::with(['product', 'user', 'stockLocation', 'destinationLocation', 'variant'])
            ->latest('transaction_date');
        
        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        
        if ($request->filled('location_id')) {
            $query->where('stock_location_id', $request->location_id);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }
        
        if ($request->filled('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }
        
        if ($request->filled('reference_number')) {
            $query->where('reference_number', 'like', "%{$request->reference_number}%");
        }
        
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        $transactions = $query->paginate(25)->withQueryString();
        
        $products = Product::active()->orderBy('title')->get(['id', 'title', 'sku']);
        $locations = StockLocation::active()->orderBy('name')->get();
        $users = \App\Models\User::whereHas('stocks')->orderBy('name')->get(['id', 'name', 'email']);
        
        // Summary statistics
        $summary = [
            'total_in' => Stock::incoming()->sum('quantity'),
            'total_out' => Stock::outgoing()->sum('quantity'),
            'total_adjustments' => Stock::adjustments()->count(),
            'total_transfers' => Stock::transfers()->count(),
            'total_returns' => Stock::returns()->count(),
            'total_damages' => Stock::damages()->count(),
            'total_value' => Stock::incoming()->sum(DB::raw('quantity * unit_cost')),
        ];
        
        // Recent activity
        $recentActivity = Stock::with(['product', 'user'])
            ->latest()
            ->limit(10)
            ->get();
        
        return view('inventory.index', compact(
            'pagetitle',
            'transactions',
            'products',
            'locations',
            'users',
            'summary',
            'recentActivity'
        ));
    }

    public function dashboard()
    {
        $pagetitle = "Inventory Dashboard";
        
        $totalProducts = Product::count();
        $totalLocations = StockLocation::active()->count();
        
        // Stock summary by location
        $locations = StockLocation::active()->withCount(['stocks as total_items' => function($query) {
            $query->select(DB::raw('SUM(CASE WHEN type IN ("in", "adjustment", "transfer") THEN quantity ELSE -quantity END)'));
        }])->get();
        
        // Low stock products
        $lowStockProducts = Product::select('products.*')
            ->selectSub(function($query) {
                $query->selectRaw('SUM(CASE WHEN stocks.type IN ("in", "adjustment", "transfer") THEN stocks.quantity ELSE -stocks.quantity END)')
                    ->from('stocks')
                    ->whereColumn('stocks.product_id', 'products.id');
            }, 'current_stock')
            ->having('current_stock', '>', 0)
            ->having('current_stock', '<=', 10)
            ->orderBy('current_stock')
            ->limit(10)
            ->get();
        
        // Recent transactions
        $recentTransactions = Stock::with(['product', 'stockLocation'])
            ->latest()
            ->limit(10)
            ->get();
        
        // Monthly stock movements
        $monthlyMovements = Stock::select(
                DB::raw('DATE_FORMAT(transaction_date, "%Y-%m") as month'),
                DB::raw('SUM(CASE WHEN type IN ("in", "adjustment", "transfer") THEN quantity ELSE 0 END) as stock_in'),
                DB::raw('SUM(CASE WHEN type IN ("out", "damage") THEN quantity ELSE 0 END) as stock_out')
            )
            ->where('transaction_date', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        // Stock value by location
        $stockValueByLocation = StockLocation::active()
            ->select('stock_locations.*')
            ->selectSub(function($query) {
                $query->selectRaw('SUM(
                    CASE 
                        WHEN stocks.type IN ("in", "adjustment", "transfer") THEN stocks.quantity * COALESCE(stocks.unit_cost, products.price)
                        WHEN stocks.type IN ("out", "damage") THEN -stocks.quantity * COALESCE(stocks.unit_cost, products.price)
                        ELSE 0
                    END
                )')
                ->from('stocks')
                ->join('products', 'stocks.product_id', '=', 'products.id')
                ->whereColumn('stocks.stock_location_id', 'stock_locations.id');
            }, 'total_value')
            ->orderBy('total_value', 'desc')
            ->get();
        
        return view('inventory.dashboard', compact(
            'pagetitle',
            'totalProducts',
            'totalLocations',
            'locations',
            'lowStockProducts',
            'recentTransactions',
            'monthlyMovements',
            'stockValueByLocation'
        ));
    }

    public function stockLevels(Request $request)
    {
        $pagetitle = "Stock Levels Report";
        
        $query = Product::with(['category', 'brand'])
            ->select('products.*')
            ->selectSub(function($query) {
                $query->selectRaw('SUM(CASE WHEN stocks.type IN ("in", "adjustment", "transfer") THEN stocks.quantity ELSE -stocks.quantity END)')
                    ->from('stocks')
                    ->whereColumn('stocks.product_id', 'products.id');
            }, 'total_stock');
        
        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        
        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'in_stock':
                    $query->having('total_stock', '>', 10);
                    break;
                case 'low_stock':
                    $query->having('total_stock', '>', 0)
                          ->having('total_stock', '<=', 10);
                    break;
                case 'out_of_stock':
                    $query->having('total_stock', '<=', 0);
                    break;
                case 'negative_stock':
                    $query->having('total_stock', '<', 0);
                    break;
            }
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhereHas('category', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('brand', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        $sortBy = $request->get('sort_by', 'total_stock');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if ($sortBy === 'stock') {
            $query->orderBy('total_stock', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }
        
        $products = $query->paginate(25)->withQueryString();
        
        $locations = StockLocation::active()->get();
        
        // Get stock by location for each product
        foreach ($products as $product) {
            $product->location_stock = [];
            foreach ($locations as $location) {
                $product->location_stock[$location->id] = $location->getProductStock($product->id);
            }
        }
        
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        
        return view('inventory.stock-levels', compact(
            'pagetitle',
            'products',
            'locations',
            'categories',
            'brands'
        ));
    }

    public function stockHistory($id)
    {
        $product = Product::with(['category', 'brand'])->findOrFail($id);
        $pagetitle = "Stock History - {$product->title}";
        
        $history = Stock::with(['user', 'stockLocation', 'destinationLocation'])
            ->where('product_id', $id)
            ->latest('transaction_date')
            ->paginate(20);
            
        $locations = StockLocation::active()->get();
        $locationStock = [];
        
        foreach ($locations as $location) {
            $locationStock[$location->id] = [
                'name' => $location->name,
                'stock' => $location->getProductStock($id)
            ];
        }
        
        // Stock movement chart data
        $movementData = StockMovement::where('product_id', $id)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(CASE WHEN movement_type IN ("in", "transfer_in") THEN quantity ELSE -quantity END) as daily_change'),
                DB::raw('MAX(balance) as closing_balance')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return view('inventory.stock-history', compact(
            'pagetitle',
            'product',
            'history',
            'locations',
            'locationStock',
            'movementData'
        ));
    }

    public function adjustStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'location_id' => 'required|exists:stock_locations,id',
            'adjustment_type' => 'required|in:add,remove,set',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'expiry_date' => 'nullable|date|after_or_equal:today',
            'batch_number' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
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
            $product = Product::findOrFail($request->product_id);
            $location = StockLocation::findOrFail($request->location_id);
            
            $currentStock = $location->getProductStock($product->id);
            
            if ($request->adjustment_type === 'set') {
                $adjustment = $request->quantity - $currentStock;
                $type = $adjustment >= 0 ? Stock::TYPE_ADJUSTMENT : Stock::TYPE_ADJUSTMENT;
                $quantity = abs($adjustment);
                $previousQuantity = $currentStock;
                $newQuantity = $request->quantity;
            } else {
                $type = $request->adjustment_type === 'add' ? Stock::TYPE_IN : Stock::TYPE_OUT;
                $quantity = $request->quantity;
                $previousQuantity = $currentStock;
                $newQuantity = $request->adjustment_type === 'add' 
                    ? $currentStock + $quantity 
                    : $currentStock - $quantity;
            }
            
            $unitCost = $request->unit_cost ?? $product->price;
            $totalCost = $unitCost * $quantity;
            
            $stock = Stock::create([
                'product_id' => $product->id,
                'stock_location_id' => $location->id,
                'user_id' => auth()->id(),
                'type' => $type,
                'quantity' => $quantity,
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $newQuantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'reference_number' => 'ADJ-' . date('YmdHis') . rand(100, 999),
                'reference_type' => Stock::REFERENCE_ADJUSTMENT,
                'adjustment_reason' => $request->reason,
                'notes' => $request->notes,
                'expiry_date' => $request->expiry_date,
                'batch_number' => $request->batch_number,
                'serial_number' => $request->serial_number,
                'transaction_date' => now(),
            ]);
            
            // Create stock movement
            $stock->createMovement();
            
            // Update product's main stock if this is the default location
            if ($location->is_default) {
                if ($type === Stock::TYPE_IN) {
                    $product->increment('stock', $quantity);
                } elseif ($type === Stock::TYPE_OUT) {
                    $product->decrement('stock', $quantity);
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully',
                'stock' => $stock->load(['product', 'user', 'stockLocation'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust stock: ' . $e->getMessage()
            ], 500);
        }
    }

    public function transferStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'from_location_id' => 'required|exists:stock_locations,id',
            'to_location_id' => 'required|exists:stock_locations,id|different:from_location_id',
            'quantity' => 'required|integer|min:1',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'unit_cost' => 'nullable|numeric|min:0',
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
            $product = Product::findOrFail($request->product_id);
            $fromLocation = StockLocation::findOrFail($request->from_location_id);
            $toLocation = StockLocation::findOrFail($request->to_location_id);
            
            // Check if source location has enough stock
            $availableStock = $fromLocation->getProductStock($product->id);
            if ($availableStock < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient stock. Available: {$availableStock}, Requested: {$request->quantity}"
                ], 400);
            }
            
            $unitCost = $request->unit_cost ?? $product->price;
            $totalCost = $unitCost * $request->quantity;
            $referenceNumber = $request->reference_number ?? 'TRF-' . date('YmdHis') . rand(100, 999);
            
            // Get current stock at source location
            $fromCurrentStock = $fromLocation->getProductStock($product->id);
            
            // Create transfer record (outgoing from source)
            $stock = Stock::create([
                'product_id' => $product->id,
                'stock_location_id' => $fromLocation->id,
                'destination_location_id' => $toLocation->id,
                'user_id' => auth()->id(),
                'type' => Stock::TYPE_TRANSFER,
                'quantity' => $request->quantity,
                'previous_quantity' => $fromCurrentStock,
                'new_quantity' => $fromCurrentStock - $request->quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'reference_number' => $referenceNumber,
                'reference_type' => Stock::REFERENCE_TRANSFER,
                'notes' => $request->notes,
                'transaction_date' => now(),
            ]);
            
            // Create stock movement
            $stock->createMovement();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Stock transferred successfully',
                'stock' => $stock->load(['product', 'user', 'stockLocation', 'destinationLocation'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to transfer stock: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkAdjust(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:0',
            'location_id' => 'required|exists:stock_locations,id',
            'adjustment_type' => 'required|in:add,set',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
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
            $location = StockLocation::findOrFail($request->location_id);
            $createdStocks = [];
            
            foreach ($request->products as $item) {
                $product = Product::find($item['id']);
                if (!$product) continue;
                
                $currentStock = $location->getProductStock($product->id);
                
                if ($request->adjustment_type === 'set') {
                    $adjustment = $item['quantity'] - $currentStock;
                    $type = $adjustment >= 0 ? Stock::TYPE_ADJUSTMENT : Stock::TYPE_ADJUSTMENT;
                    $quantity = abs($adjustment);
                    $previousQuantity = $currentStock;
                    $newQuantity = $item['quantity'];
                } else {
                    $type = Stock::TYPE_IN;
                    $quantity = $item['quantity'];
                    $previousQuantity = $currentStock;
                    $newQuantity = $currentStock + $quantity;
                }
                
                if ($quantity <= 0) continue;
                
                $stock = Stock::create([
                    'product_id' => $product->id,
                    'stock_location_id' => $location->id,
                    'user_id' => auth()->id(),
                    'type' => $type,
                    'quantity' => $quantity,
                    'previous_quantity' => $previousQuantity,
                    'new_quantity' => $newQuantity,
                    'unit_cost' => $product->price,
                    'total_cost' => $product->price * $quantity,
                    'reference_number' => 'BULK-' . date('YmdHis') . rand(100, 999),
                    'reference_type' => Stock::REFERENCE_ADJUSTMENT,
                    'adjustment_reason' => $request->reason,
                    'notes' => $request->notes,
                    'transaction_date' => now(),
                ]);
                
                $stock->createMovement();
                
                // Update product's main stock if this is the default location
                if ($location->is_default) {
                    $product->increment('stock', $quantity);
                }
                
                $createdStocks[] = $stock;
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Bulk adjustment completed successfully',
                'count' => count($createdStocks),
                'stocks' => $createdStocks
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk adjustment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $stock = Stock::with(['product', 'user', 'stockLocation', 'destinationLocation', 'variant'])
            ->findOrFail($id);
            
        return response()->json([
            'success' => true,
            'stock' => $stock
        ]);
    }

    public function destroy($id)
    {
        try {
            $stock = Stock::findOrFail($id);
            
            // Check if this is a recent transaction that can be deleted
            $hoursOld = now()->diffInHours($stock->created_at);
            if ($hoursOld > 24 && !auth()->user()->hasRole('Admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete transactions older than 24 hours'
                ], 400);
            }
            
            // Delete associated movement
            $stock->movement()->delete();
            
            $stock->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Transaction deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getProductStock($productId, $locationId)
    {
        try {
            $product = Product::findOrFail($productId);
            $location = StockLocation::findOrFail($locationId);
            
            $stock = $location->getProductStock($productId);
            $recentTransactions = Stock::where('product_id', $productId)
                ->where('stock_location_id', $locationId)
                ->latest()
                ->limit(10)
                ->get();
            
            return response()->json([
                'success' => true,
                'stock' => $stock,
                'product' => [
                    'id' => $product->id,
                    'title' => $product->title,
                    'sku' => $product->sku,
                    'price' => $product->price
                ],
                'location' => [
                    'id' => $location->id,
                    'name' => $location->name
                ],
                'recent_transactions' => $recentTransactions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get stock level: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportTransactions(Request $request)
    {
        // Implementation for exporting transactions
        // You can use Laravel Excel package here
        $query = Stock::with(['product', 'stockLocation', 'user'])
            ->latest('transaction_date');
        
        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }
        
        $transactions = $query->get();
        
        $filename = 'inventory-transactions-' . date('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'Date', 'Type', 'Product', 'SKU', 'Location', 
                'Quantity', 'Unit Cost', 'Total Cost', 'Reference',
                'Reason', 'User', 'Notes'
            ]);
            
            // Add data rows
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->transaction_date->format('Y-m-d H:i:s'),
                    $transaction->type_label,
                    $transaction->product->title,
                    $transaction->product->sku,
                    $transaction->stockLocation->name,
                    $transaction->formatted_quantity,
                    $transaction->unit_cost ? '$' . number_format($transaction->unit_cost, 2) : '',
                    $transaction->total_cost ? '$' . number_format($transaction->total_cost, 2) : '',
                    $transaction->reference_number,
                    $transaction->adjustment_reason ?? '',
                    $transaction->user->name ?? 'System',
                    $transaction->notes ?? ''
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    public function exportStockLevels(Request $request)
    {
        $products = Product::with(['category', 'brand'])
            ->select('products.*')
            ->selectSub(function($query) {
                $query->selectRaw('SUM(CASE WHEN stocks.type IN ("in", "adjustment", "transfer") THEN stocks.quantity ELSE -stocks.quantity END)')
                    ->from('stocks')
                    ->whereColumn('stocks.product_id', 'products.id');
            }, 'total_stock')
            ->orderBy('total_stock')
            ->get();
        
        $locations = StockLocation::active()->get();
        
        $filename = 'stock-levels-' . date('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($products, $locations) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            $headerRow = ['Product', 'SKU', 'Category', 'Brand', 'Price', 'Total Stock'];
            foreach ($locations as $location) {
                $headerRow[] = $location->name;
            }
            $headerRow[] = 'Status';
            
            fputcsv($file, $headerRow);
            
            // Add data rows
            foreach ($products as $product) {
                $row = [
                    $product->title,
                    $product->sku,
                    $product->category->name ?? '',
                    $product->brand->name ?? '',
                    '$' . number_format($product->price, 2),
                    $product->total_stock ?? 0
                ];
                
                foreach ($locations as $location) {
                    $row[] = $location->getProductStock($product->id);
                }
                
                // Status
                $stock = $product->total_stock ?? 0;
                if ($stock > 10) {
                    $status = 'In Stock';
                } elseif ($stock > 0) {
                    $status = 'Low Stock';
                } else {
                    $status = 'Out of Stock';
                }
                $row[] = $status;
                
                fputcsv($file, $row);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:csv,txt,xlsx,xls',
            'location_id' => 'required|exists:stock_locations,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Implementation for importing stock data
        // You can use Laravel Excel package here
        
        return response()->json([
            'success' => true,
            'message' => 'Import functionality to be implemented'
        ]);
    }

    public function getLowStockAlerts()
    {
        $lowStockProducts = Product::select('products.*')
            ->selectSub(function($query) {
                $query->selectRaw('SUM(CASE WHEN stocks.type IN ("in", "adjustment", "transfer") THEN stocks.quantity ELSE -stocks.quantity END)')
                    ->from('stocks')
                    ->whereColumn('stocks.product_id', 'products.id');
            }, 'current_stock')
            ->having('current_stock', '>', 0)
            ->having('current_stock', '<=', 10)
            ->orderBy('current_stock')
            ->get();
        
        return response()->json([
            'success' => true,
            'count' => $lowStockProducts->count(),
            'products' => $lowStockProducts
        ]);
    }

    public function getStockValueReport()
    {
        $locations = StockLocation::active()->get();
        $report = [];
        
        foreach ($locations as $location) {
            $value = $location->total_value;
            if ($value > 0) {
                $report[] = [
                    'location' => $location->name,
                    'value' => $value,
                    'formatted_value' => '$' . number_format($value, 2),
                    'product_count' => $location->total_products
                ];
            }
        }
        
        usort($report, function($a, $b) {
            return $b['value'] <=> $a['value'];
        });
        
        $totalValue = array_sum(array_column($report, 'value'));
        
        return response()->json([
            'success' => true,
            'total_value' => $totalValue,
            'formatted_total' => '$' . number_format($totalValue, 2),
            'locations' => $report
        ]);
    }
}