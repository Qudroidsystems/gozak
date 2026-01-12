<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\StoreSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class InventoryController extends Controller
{
    public function __construct()
    {
        // Permissions (uncomment when ready)
        // $this->middleware('permission:View inventory', ['only' => ['index', 'show']]);
        // $this->middleware('permission:View stock levels', ['only' => ['stockLevels']]);
        // $this->middleware('permission:Manage inventory', ['only' => ['adjustStock', 'transferStock', 'bulkAdjust']]);
    }

    private function calculateProductStockFromInventory($productId)
    {
        $totalStock = Stock::where('product_id', $productId)
            ->selectRaw('
                SUM(CASE
                    WHEN type IN ("in", "adjustment", "transfer_in", "return") THEN quantity
                    WHEN type IN ("out", "damage", "transfer") THEN -quantity
                    ELSE 0
                END) as total
            ')
            ->value('total') ?? 0;

        return max(0, $totalStock);
    }

    private function updateProductStock($productId)
    {
        try {
            $product = Product::find($productId);
            if (!$product) {
                Log::error("Product {$productId} not found when updating stock");
                return 0;
            }

            $calculatedStock = $this->calculateProductStockFromInventory($productId);
            $oldStock = $product->stock;

            Product::where('id', $productId)->update(['stock' => $calculatedStock]);
            $product->refresh();

            Log::info("Updated product {$productId} stock: {$oldStock} → {$calculatedStock}");

            return $calculatedStock;
        } catch (\Exception $e) {
            Log::error("Failed to update product stock for {$productId}: " . $e->getMessage());
            return 0;
        }
    }

    public function index(Request $request)
    {
        $pagetitle = "Inventory Management";

        $query = Stock::with(['product', 'user', 'stockLocation', 'destinationLocation'])
            ->latest('transaction_date');

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

        $products = Product::orderBy('title')->get(['id', 'title', 'sku', 'price']);
        $locations = StockLocation::orderBy('name')->get();

        $users = \App\Models\User::whereHas('stocks')
            ->select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $summary = [
            'total_in' => Stock::where('type', 'in')->sum('quantity'),
            'total_out' => Stock::where('type', 'out')->sum('quantity'),
            'total_adjustments' => Stock::where('type', 'adjustment')->count(),
            'total_transfers' => Stock::where('type', 'transfer')->count(),
            'total_returns' => Stock::where('type', 'return')->count(),
            'total_damages' => Stock::where('type', 'damage')->count(),
            'total_value' => Stock::where('type', 'in')->sum(DB::raw('COALESCE(quantity * unit_cost, 0)')),
        ];

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
        $totalLocations = StockLocation::count();

        $locations = StockLocation::withCount(['stocks as total_items' => function($query) {
            $query->select(DB::raw('SUM(CASE WHEN type IN ("in", "adjustment", "transfer") THEN quantity ELSE -quantity END)'));
        }])->get();

        $lowStockProducts = Product::where('stock', '>', 0)
            ->where('stock', '<=', 10)
            ->orderBy('stock')
            ->limit(10)
            ->get();

        $recentTransactions = Stock::with(['product', 'stockLocation'])
            ->latest()
            ->limit(10)
            ->get();

        $monthlyMovements = Stock::select(
                DB::raw('DATE_FORMAT(transaction_date, "%Y-%m") as month'),
                DB::raw('SUM(CASE WHEN type IN ("in", "adjustment", "transfer") THEN quantity ELSE 0 END) as stock_in'),
                DB::raw('SUM(CASE WHEN type IN ("out", "damage") THEN quantity ELSE 0 END) as stock_out')
            )
            ->where('transaction_date', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $stockValueByLocation = StockLocation::select('stock_locations.*')
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

    // Generate a unique cache key based on all request parameters
    $cacheKey = 'stock_levels_' . md5(serialize([
        'category_id' => $request->category_id,
        'brand_id' => $request->brand_id,
        'stock_status' => $request->stock_status,
        'search' => $request->search,
        'sort_by' => $request->sort_by,
        'sort_order' => $request->sort_order,
        'page' => $request->page,
        'user_id' => auth()->id() // Include user ID for permission-based caching
    ]));

    $cacheTime = 300; // 5 minutes - adjust based on your needs

    // Check if we have cached results
    if (Cache::has($cacheKey)) {
        return Cache::get($cacheKey);
    }

    // Base query for products
    $query = Product::with(['category', 'brand'])->select('products.*');

    // Apply filters
    if ($request->filled('category_id')) {
        $query->where('category_id', $request->category_id);
    }

    if ($request->filled('brand_id')) {
        $query->where('brand_id', $request->brand_id);
    }

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('barcode', 'like', "%{$search}%")
              ->orWhereHas('category', function($q) use ($search) {
                  $q->where('name', 'like', "%{$search}%");
              })
              ->orWhereHas('brand', function($q) use ($search) {
                  $q->where('name', 'like', "%{$search}%");
              });
        });
    }

    // Get all filtered products (without stock status filter yet)
    $allProducts = $query->get();

    // Cache stock calculations for all products to avoid N+1 queries
    $productIds = $allProducts->pluck('id')->toArray();
    $stockCalculationsCache = [];

    // Pre-calculate stock for all products using a single query
    $stockSums = Stock::whereIn('product_id', $productIds)
        ->selectRaw('
            product_id,
            SUM(CASE
                WHEN type IN ("in", "adjustment", "transfer_in", "return") THEN quantity
                WHEN type IN ("out", "damage", "transfer") THEN -quantity
                ELSE 0
            END) as total_stock
        ')
        ->groupBy('product_id')
        ->pluck('total_stock', 'product_id')
        ->toArray();

    // Process products with calculated values
    $productsWithStock = $allProducts->map(function($product) use ($stockSums) {
        $totalStock = $stockSums[$product->id] ?? 0;
        $totalStock = max(0, $totalStock); // Ensure no negative stock

        // Calculate profit margin
        $costPrice = $product->cost_price ?? 0;
        $sellingPrice = $product->sale_price ?? $product->price ?? 0;
        $marginPercent = 0;

        if ($costPrice > 0 && $sellingPrice > 0) {
            $marginPercent = (($sellingPrice - $costPrice) / $costPrice) * 100;
        }

        return [
            'product' => $product,
            'total_stock' => $totalStock,
            'margin_percent' => $marginPercent,
            'price' => $product->price,
            'sale_price' => $product->sale_price,
            'cost_price' => $product->cost_price,
            'title' => $product->title,
            'sku' => $product->sku,
            'category_name' => $product->category->name ?? '',
            'brand_name' => $product->brand->name ?? ''
        ];
    });

    // Apply stock status filter
    if ($request->filled('stock_status')) {
        $productsWithStock = $productsWithStock->filter(function($item) use ($request) {
            $stock = $item['total_stock'];

            switch ($request->stock_status) {
                case 'in_stock':
                    return $stock > 10;
                case 'low_stock':
                    return $stock > 0 && $stock <= 10;
                case 'out_of_stock':
                    return $stock == 0;
                case 'negative_stock':
                    return $stock < 0;
                default:
                    return true;
            }
        });
    }

    // Apply sorting
    $sortBy = $request->get('sort_by', 'title');
    $sortOrder = $request->get('sort_order', 'asc');

    $productsWithStock = $productsWithStock->sortBy(function($item) use ($sortBy) {
        switch ($sortBy) {
            case 'total_stock':
                return $item['total_stock'];
            case 'margin_percent':
                return $item['margin_percent'];
            case 'price':
                return $item['price'];
            case 'sale_price':
                return $item['sale_price'];
            case 'cost_price':
                return $item['cost_price'];
            case 'sku':
                return $item['sku'];
            case 'title':
            default:
                return $item['title'];
        }
    }, SORT_REGULAR, $sortOrder === 'desc');

    // Create pagination
    $page = $request->get('page', 1);
    $perPage = 25;
    $offset = ($page - 1) * $perPage;

    $paginatedProducts = new \Illuminate\Pagination\LengthAwarePaginator(
        $productsWithStock->slice($offset, $perPage)->values(),
        $productsWithStock->count(),
        $perPage,
        $page,
        ['path' => $request->url(), 'query' => $request->query()]
    );

    $products = $paginatedProducts;

    // Get all locations
    $locations = StockLocation::orderBy('name')->get();

    // Cache location stock calculations
    $locationStockCache = [];
    $locationStockData = [];

    // Pre-calculate stock for all product-location combinations
    if (count($productIds) > 0 && count($locations) > 0) {
        $locationStockSums = Stock::whereIn('product_id', $productIds)
            ->whereIn('stock_location_id', $locations->pluck('id'))
            ->selectRaw('
                product_id,
                stock_location_id,
                SUM(CASE
                    WHEN type IN ("in", "adjustment", "transfer_in", "return") THEN quantity
                    WHEN type IN ("out", "damage", "transfer") THEN -quantity
                    ELSE 0
                END) as location_stock
            ')
            ->groupBy('product_id', 'stock_location_id')
            ->get()
            ->groupBy('product_id');

        // Build location stock data array
        foreach ($products as $data) {
            $product = $data['product'];
            $productStocks = $locationStockSums[$product->id] ?? collect();

            foreach ($locations as $location) {
                $stock = $productStocks->where('stock_location_id', $location->id)->first();
                $locationStockData[$product->id][$location->id] = $stock ? max(0, $stock->location_stock) : 0;
            }
        }
    }

    // Calculate summary statistics
    $summaryCounts = [
        'total_products' => $productsWithStock->count(),
        'in_stock_count' => $productsWithStock->filter(fn($item) => $item['total_stock'] > 10)->count(),
        'low_stock_count' => $productsWithStock->filter(fn($item) => $item['total_stock'] > 0 && $item['total_stock'] <= 10)->count(),
        'out_of_stock_count' => $productsWithStock->filter(fn($item) => $item['total_stock'] == 0)->count()
    ];

    $summary = [
        'total_products' => $summaryCounts['total_products'],
        'in_stock'       => $summaryCounts['in_stock_count'],
        'low_stock'      => $summaryCounts['low_stock_count'],
        'out_of_stock'   => $summaryCounts['out_of_stock_count'],
    ];

    // Calculate total stock per location for bar chart
    $locationStockTotals = [];
    if (isset($locationStockSums)) {
        foreach ($locations as $location) {
            $total = 0;
            // Sum across all products for this location
            foreach ($locationStockSums as $productId => $stocks) {
                $locationStock = $stocks->where('stock_location_id', $location->id)->first();
                if ($locationStock) {
                    $total += max(0, $locationStock->location_stock);
                }
            }
            $locationStockTotals[$location->id] = $total;
        }
    }

    $categories = Category::orderBy('name')->get();
    $brands = Brand::orderBy('name')->get();

    // Prepare view data
    $viewData = compact(
        'pagetitle',
        'products',
        'locations',
        'locationStockData',
        'categories',
        'brands',
        'summary',
        'locationStockTotals'
    );

    // Render the view
    $view = view('inventory.stock-levels', $viewData)->render();

    // Cache the rendered view
    Cache::put($cacheKey, $view, $cacheTime);

    return $view;
}

    public function stockHistory($id)
    {
        $product = Product::with(['category', 'brand'])->findOrFail($id);
        $pagetitle = "Stock History - {$product->title}";

        $history = Stock::with(['user', 'stockLocation', 'destinationLocation'])
            ->where('product_id', $id)
            ->latest('transaction_date')
            ->paginate(20);

        $locations = StockLocation::get();
        $locationStock = [];

        foreach ($locations as $location) {
            $locationStock[$location->id] = [
                'name' => $location->name,
                'stock' => $location->getProductStock($id)
            ];
        }

        $movementData = StockMovement::where('product_id', $id)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(CASE WHEN movement_type IN ("in", "adjustment", "transfer_in") THEN quantity ELSE -quantity END) as daily_change'),
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
        Log::info('Adjust stock request received:', $request->all());

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'location_id' => 'required|exists:stock_locations,id',
            'adjustment_type' => 'required|in:add,remove,set',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            Log::error('Adjust stock validation failed:', $validator->errors()->toArray());
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
                $quantity = abs($adjustment);
                $previousQuantity = $currentStock;
                $newQuantity = $request->quantity;
                $type = $adjustment >= 0 ? 'in' : 'out';
            } else if ($request->adjustment_type === 'add') {
                $type = 'in';
                $quantity = $request->quantity;
                $previousQuantity = $currentStock;
                $newQuantity = $currentStock + $quantity;
            } else {
                $type = 'out';
                $quantity = $request->quantity;
                $previousQuantity = $currentStock;
                $newQuantity = $currentStock - $quantity;

                if ($currentStock < $quantity) {
                    Log::warning("Insufficient stock. Available: {$currentStock}, Requested: {$quantity}");
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot remove stock. Available: {$currentStock}, Requested: {$quantity}"
                    ], 400);
                }
            }

            $unitCost = $request->filled('unit_cost') ? $request->unit_cost : $product->price;
            $totalCost = $unitCost * $quantity;

            $referenceNumber = 'ADJ-' . date('YmdHis') . rand(100, 999);

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
                'reference_number' => $referenceNumber,
                'reference_type' => 'adjustment',
                'adjustment_reason' => $request->reason,
                'notes' => $request->notes,
                'transaction_date' => now(),
            ]);

            $movementType = ($type === 'in' || $type === 'out') ? 'adjustment' : $type;

            StockMovement::create([
                'stock_id' => $stock->id,
                'product_id' => $product->id,
                'stock_location_id' => $location->id,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'balance' => $newQuantity,
                'reference' => $referenceNumber,
                'description' => $request->reason . ': ' . ($request->notes ?? ''),
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);

            $newProductStock = $this->updateProductStock($product->id);

            DB::commit();
            Log::info('Stock adjustment completed successfully');

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully',
                'stock' => $stock->load(['product', 'user', 'stockLocation']),
                'product_stock' => $newProductStock
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to adjust stock: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust stock: ' . $e->getMessage()
            ], 500);
        }
    }

    public function transferStock(Request $request)
    {
        Log::info('Transfer stock request received:', $request->all());

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
            Log::error('Transfer stock validation failed:', $validator->errors()->toArray());
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

            $availableStock = $fromLocation->getProductStock($product->id);
            if ($availableStock < $request->quantity) {
                Log::warning("Insufficient stock for transfer. Available: {$availableStock}, Requested: {$request->quantity}");
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient stock. Available: {$availableStock}, Requested: {$request->quantity}"
                ], 400);
            }

            $unitCost = $request->filled('unit_cost') ? $request->unit_cost : $product->price;
            $totalCost = $unitCost * $request->quantity;
            $referenceNumber = $request->reference_number ?? 'TRF-' . date('YmdHis') . rand(100, 999);

            $fromCurrentStock = $fromLocation->getProductStock($product->id);
            $toCurrentStock = $toLocation->getProductStock($product->id);

            $stockOut = Stock::create([
                'product_id' => $product->id,
                'stock_location_id' => $fromLocation->id,
                'destination_location_id' => $toLocation->id,
                'user_id' => auth()->id(),
                'type' => 'transfer',
                'quantity' => $request->quantity,
                'previous_quantity' => $fromCurrentStock,
                'new_quantity' => $fromCurrentStock - $request->quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'reference_number' => $referenceNumber,
                'reference_type' => 'transfer',
                'notes' => $request->notes,
                'transaction_date' => now(),
            ]);

            $stockIn = Stock::create([
                'product_id' => $product->id,
                'stock_location_id' => $toLocation->id,
                'destination_location_id' => null,
                'user_id' => auth()->id(),
                'type' => 'transfer_in',
                'quantity' => $request->quantity,
                'previous_quantity' => $toCurrentStock,
                'new_quantity' => $toCurrentStock + $request->quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'reference_number' => $referenceNumber,
                'reference_type' => 'transfer',
                'notes' => $request->notes . ' (Transferred from ' . $fromLocation->name . ')',
                'transaction_date' => now(),
            ]);

            StockMovement::create([
                'stock_id' => $stockOut->id,
                'product_id' => $product->id,
                'stock_location_id' => $fromLocation->id,
                'movement_type' => 'transfer_out',
                'quantity' => $request->quantity,
                'balance' => $fromCurrentStock - $request->quantity,
                'reference' => $referenceNumber,
                'description' => 'Transfer to ' . $toLocation->name . ': ' . ($request->notes ?? ''),
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);

            StockMovement::create([
                'stock_id' => $stockIn->id,
                'product_id' => $product->id,
                'stock_location_id' => $toLocation->id,
                'movement_type' => 'transfer_in',
                'quantity' => $request->quantity,
                'balance' => $toCurrentStock + $request->quantity,
                'reference' => $referenceNumber,
                'description' => 'Transfer from ' . $fromLocation->name . ': ' . ($request->notes ?? ''),
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);

            $newProductStock = $this->updateProductStock($product->id);

            DB::commit();
            Log::info('Stock transfer completed successfully');

            return response()->json([
                'success' => true,
                'message' => 'Stock transferred successfully',
                'stock' => $stockOut->load(['product', 'user', 'stockLocation', 'destinationLocation']),
                'product_stock' => $newProductStock
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to transfer stock: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to transfer stock: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkAdjust(Request $request)
    {
        Log::info('Bulk adjust request received:', $request->all());

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
            Log::error('Bulk adjust validation failed:', $validator->errors()->toArray());
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
            $updatedProducts = [];

            foreach ($request->products as $item) {
                $product = Product::find($item['id']);
                if (!$product) continue;

                $currentStock = $location->getProductStock($product->id);

                if ($request->adjustment_type === 'set') {
                    $adjustment = $item['quantity'] - $currentStock;
                    $quantity = abs($adjustment);
                    $previousQuantity = $currentStock;
                    $newQuantity = $item['quantity'];
                    $type = $adjustment >= 0 ? 'in' : 'out';
                } else {
                    $type = 'in';
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
                    'reference_type' => 'adjustment',
                    'adjustment_reason' => $request->reason,
                    'notes' => $request->notes,
                    'transaction_date' => now(),
                ]);

                $movementType = ($type === 'in' || $type === 'out') ? 'adjustment' : $type;

                StockMovement::create([
                    'stock_id' => $stock->id,
                    'product_id' => $product->id,
                    'stock_location_id' => $location->id,
                    'movement_type' => $movementType,
                    'quantity' => $quantity,
                    'balance' => $newQuantity,
                    'reference' => 'BULK-' . date('YmdHis') . rand(100, 999),
                    'description' => $request->reason . ': ' . ($request->notes ?? ''),
                    'user_id' => auth()->id(),
                    'created_at' => now(),
                ]);

                $newProductStock = $this->updateProductStock($product->id);

                $createdStocks[] = $stock;
                $updatedProducts[] = [
                    'id' => $product->id,
                    'title' => $product->title,
                    'stock' => $newProductStock
                ];
            }

            DB::commit();
            Log::info('Bulk adjustment completed successfully. Count: ' . count($createdStocks));

            return response()->json([
                'success' => true,
                'message' => 'Bulk adjustment completed successfully',
                'count' => count($createdStocks),
                'stocks' => $createdStocks,
                'products' => $updatedProducts
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to perform bulk adjustment: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk adjustment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $stock = Stock::with(['product', 'user', 'stockLocation', 'destinationLocation'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'stock' => $stock
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch stock transaction: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transaction'
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $stock = Stock::findOrFail($id);
            $productId = $stock->product_id;

            $hoursOld = now()->diffInHours($stock->created_at);
            if ($hoursOld > 24 && !auth()->user()->hasRole('Admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete transactions older than 24 hours'
                ], 400);
            }

            StockMovement::where('stock_id', $id)->delete();

            $stock->delete();

            $newProductStock = $this->updateProductStock($productId);

            DB::commit();
            Log::info("Stock transaction {$id} deleted successfully");

            return response()->json([
                'success' => true,
                'message' => 'Transaction deleted successfully',
                'product_stock' => $newProductStock
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete transaction: ' . $e->getMessage());
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

            return response()->json([
                'success' => true,
                'stock' => $stock,
                'product_total_stock' => $product->stock,
                'product' => [
                    'id' => $product->id,
                    'title' => $product->title,
                    'sku' => $product->sku,
                    'price' => $product->price
                ],
                'location' => [
                    'id' => $location->id,
                    'name' => $location->name
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get stock level: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get stock level: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportTransactions(Request $request)
    {
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

            fputcsv($file, [
                'Date', 'Type', 'Product', 'SKU', 'Location',
                'Quantity', 'Unit Cost', 'Total Cost', 'Reference',
                'Reason', 'User', 'Notes'
            ]);

            foreach ($transactions as $transaction) {
                $userName = $transaction->user ?
                    $transaction->user->first_name . ' ' . $transaction->user->last_name :
                    'System';

                fputcsv($file, [
                    $transaction->transaction_date->format('Y-m-d H:i:s'),
                    ucfirst($transaction->type),
                    $transaction->product->title,
                    $transaction->product->sku,
                    $transaction->stockLocation->name,
                    $transaction->quantity,
                    $transaction->unit_cost ? '$' . number_format($transaction->unit_cost, 2) : '',
                    $transaction->total_cost ? '$' . number_format($transaction->total_cost, 2) : '',
                    $transaction->reference_number,
                    $transaction->adjustment_reason ?? '',
                    $userName,
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
            ->orderBy('stock')
            ->get();

        $locations = StockLocation::get();

        $filename = 'stock-levels-' . date('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($products, $locations) {
            $file = fopen('php://output', 'w');

            $headerRow = ['Product', 'SKU', 'Category', 'Brand', 'Price', 'Total Stock'];
            foreach ($locations as $location) {
                $headerRow[] = $location->name;
            }
            $headerRow[] = 'Status';

            fputcsv($file, $headerRow);

            foreach ($products as $product) {
                $row = [
                    $product->title,
                    $product->sku,
                    $product->category->name ?? '',
                    $product->brand->name ?? '',
                    '$' . number_format($product->price, 2),
                    $product->stock
                ];

                foreach ($locations as $location) {
                    $row[] = $location->getProductStock($product->id);
                }

                $stock = $product->stock;
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

        return response()->json([
            'success' => true,
            'message' => 'Import functionality to be implemented'
        ]);
    }

    public function getLowStockAlerts()
    {
        $lowStockProducts = Product::where('stock', '>', 0)
            ->where('stock', '<=', 10)
            ->orderBy('stock')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $lowStockProducts->count(),
            'products' => $lowStockProducts
        ]);
    }

    public function getStockValueReport()
    {
        $locations = StockLocation::get();
        $report = [];

        foreach ($locations as $location) {
            $value = $location->total_value ?? 0;
            if ($value > 0) {
                $report[] = [
                    'location' => $location->name,
                    'value' => $value,
                    'formatted_value' => '$' . number_format($value, 2),
                    'product_count' => $location->total_products ?? 0
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

    public function lowStockAlerts(Request $request)
    {
        $pagetitle = "Low Stock Alerts";

        $threshold = $request->filled('threshold') ? (int)$request->threshold : 10;
        $forceRecalculate = $request->has('recalculate');

        $query = Product::with(['category', 'brand'])
            ->select('products.*');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
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

        $allProducts = $query->get();

        $debugInfo = [];

        $lowStockProducts = collect();

        foreach ($allProducts as $product) {
            $calculatedStock = $product->calculateCurrentStock();

            if (count($debugInfo) < 5) {
                $debugInfo[] = [
                    'id' => $product->id,
                    'name' => $product->title,
                    'db_stock' => $product->stock,
                    'calculated_stock' => $calculatedStock,
                    'is_low' => ($calculatedStock > 0 && $calculatedStock <= $threshold)
                ];
            }

            if ($calculatedStock > 0 && $calculatedStock <= $threshold) {
                $product->current_calculated_stock = $calculatedStock;
                $product->is_critical = ($calculatedStock <= 3);
                $product->is_action_required = ($calculatedStock <= 5);

                $lowStockProducts->push($product);
            }
        }

        $lowStockProducts = $lowStockProducts->sortBy('current_calculated_stock');

        $page = $request->get('page', 1);
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $paginatedProducts = new \Illuminate\Pagination\LengthAwarePaginator(
            $lowStockProducts->slice($offset, $perPage)->values(),
            $lowStockProducts->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $products = $paginatedProducts;

        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $locations = StockLocation::orderBy('name')->get();

        $locationStockData = [];
        foreach ($products as $product) {
            $locationStockData[$product->id] = [];
            foreach ($locations as $location) {
                $locationStockData[$product->id][$location->id] = $product->getStockByLocation($location->id);
            }
        }

        $totalLowStock = $lowStockProducts->count();
        $criticalCount = $lowStockProducts->where('is_critical', true)->count();
        $actionRequiredCount = $lowStockProducts->where('is_action_required', true)->count();

        $totalProducts = $allProducts->count();
        $productsWithStock = $allProducts->filter(function($p) {
            return $p->calculateCurrentStock() > 0;
        })->count();

        return view('inventory.low-stock-alerts', compact(
            'pagetitle',
            'products',
            'categories',
            'brands',
            'locations',
            'locationStockData',
            'threshold',
            'totalLowStock',
            'criticalCount',
            'actionRequiredCount',
            'totalProducts',
            'productsWithStock',
            'debugInfo',
            'forceRecalculate'
        ));
    }

    public function recalculateStock(Request $request)
    {
        try {
            Artisan::call('products:sync-stock');

            return redirect()->route('inventory.low-stock-alerts')
                ->with('success', 'All product stocks have been recalculated from inventory transactions!');

        } catch (\Exception $e) {
            Log::error('Failed to recalculate stock: ' . $e->getMessage());

            return redirect()->route('inventory.low-stock-alerts')
                ->with('error', 'Failed to recalculate stock: ' . $e->getMessage());
        }
    }

    public function stockValueReport(Request $request)
    {
        $pagetitle = "Stock Value Report";

        $locations = StockLocation::get();
        $report = [];

        foreach ($locations as $location) {
            $value = $location->total_value ?? 0;
            if ($value > 0) {
                $report[] = [
                    'location' => $location,
                    'value' => $value,
                    'formatted_value' => '$' . number_format($value, 2),
                    'product_count' => $location->total_products ?? 0
                ];
            }
        }

        usort($report, function($a, $b) {
            return $b['value'] <=> $a['value'];
        });

        $totalValue = array_sum(array_column($report, 'value'));

        return view('inventory.stock-value-report', compact(
            'pagetitle',
            'report',
            'totalValue'
        ));
    }

    public function getLocationStock($productId, $locationId)
    {
        try {
            $product = Product::findOrFail($productId);
            $location = StockLocation::findOrFail($locationId);

            $stock = $location->getProductStock($productId);

            return response()->json([
                'success' => true,
                'stock' => $stock,
                'product_total_stock' => $product->stock,
                'product' => [
                    'id' => $product->id,
                    'title' => $product->title,
                    'sku' => $product->sku,
                    'price' => $product->price
                ],
                'location' => [
                    'id' => $location->id,
                    'name' => $location->name
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get stock level: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get stock level: ' . $e->getMessage()
            ], 500);
        }
    }

    public function realtimeProductStock()
    {
        $products = Product::select('id', 'title', 'sku', 'stock')->get();
        return response()->json($products);
    }

    public function syncAllProductStocks()
    {
        try {
            $products = Product::all();
            $updatedCount = 0;

            foreach ($products as $product) {
                $calculatedStock = $this->calculateProductStockFromInventory($product->id);

                if ($product->stock != $calculatedStock) {
                    Product::where('id', $product->id)->update(['stock' => $calculatedStock]);
                    $updatedCount++;
                    Log::info("Synced product {$product->id} stock: {$product->stock} → {$calculatedStock}");
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Synced {$updatedCount} product stocks from inventory",
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync product stocks: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync product stocks: ' . $e->getMessage()
            ], 500);
        }
    }


    public function getStockHistory($id, Request $request)
{
    $product = Product::with(['category', 'brand'])->findOrFail($id);

    $query = Stock::with(['user', 'stockLocation', 'destinationLocation'])
        ->where('product_id', $id);

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('reference_number', 'like', "%{$search}%")
              ->orWhere('adjustment_reason', 'like', "%{$search}%")
              ->orWhere('notes', 'like', "%{$search}%")
              ->orWhereHas('user', function($q) use ($search) {
                  $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
              });
        });
    }

    if ($request->filled('date_from')) {
        $query->whereDate('transaction_date', '>=', $request->date_from);
    }

    if ($request->filled('date_to')) {
        $query->whereDate('transaction_date', '<=', $request->date_to);
    }

    $history = $query->latest('transaction_date')->paginate(20);

    return response()->json([
        'success' => true,
        'product' => [
            'id' => $product->id,
            'title' => $product->title,
            'sku' => $product->sku,
            'stock' => $product->stock
        ],
        'history' => $history
    ]);
}



public function exportStockLevelsPDF(Request $request)
{
    // Reuse filtering logic from stockLevels()
    $query = Product::with(['category', 'brand'])->select('products.*');

    if ($request->filled('category_id')) {
        $query->where('category_id', $request->category_id);
    }
    if ($request->filled('brand_id')) {
        $query->where('brand_id', $request->brand_id);
    }
    if ($request->filled('stock_status')) {
        switch ($request->stock_status) {
            case 'in_stock':
                $query->where('stock', '>', 10);
                break;
            case 'low_stock':
                $query->where('stock', '>', 0)->where('stock', '<=', 10);
                break;
            case 'out_of_stock':
                $query->where('stock', '=', 0);
                break;
            case 'negative_stock':
                $query->where('stock', '<', 0);
                break;
        }
    }
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhereHas('category', fn($q) => $q->where('name', 'like', "%{$search}%"))
              ->orWhereHas('brand', fn($q) => $q->where('name', 'like', "%{$search}%"));
        });
    }

    $sortBy = $request->get('sort_by', 'title');
    $sortOrder = $request->get('sort_order', 'asc');
    $query->orderBy($sortBy, $sortOrder);

    $products = $query->get();

    $locations = StockLocation::orderBy('name')->get();

    $locationStockData = [];
    foreach ($products as $product) {
        foreach ($locations as $location) {
            $locationStockData[$product->id][$location->id] = $location->getProductStock($product->id);
        }
    }

    // Store settings with logo
    $settings = StoreSetting::getSettings();

    // Date information
    $generatedAt = now()->format('F j, Y \a\t g:i A');
    $stockAsOfDate = now()->format('F j, Y'); // Stock levels are current as of today

    $data = [
        'products'          => $products,
        'locations'         => $locations,
        'locationStockData' => $locationStockData,
        'settings'          => $settings,
        'filters'           => $request->query(),
        'totalProducts'     => $products->count(),
        'generatedAt'       => $generatedAt,
        'stockAsOfDate'     => $stockAsOfDate,
        'generatedBy'       => auth()->user()->name ?? 'System',
    ];

    $pdf = Pdf::loadView('inventory.pdf.stock-levels', $data)
              ->setPaper('a4', 'landscape');

    return $pdf->download('stock-levels-' . now()->format('Y-m-d-His') . '.pdf');
}
}
