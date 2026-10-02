<?php

namespace App\Http\Controllers;

use Yajra\DataTables\Facades\DataTables;
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
            'products',
            'locations',
            'users',
            'summary',
            'recentActivity'
        ));
    }

    /** Yajra DataTable endpoint — inventory transactions (filters as the old GET form). */
    public function transactionsData(Request $request)
    {
        $query = Stock::query()
            ->with(['product:id,title,sku,thumbnail', 'user:id,first_name,last_name', 'stockLocation:id,name', 'destinationLocation:id,name'])
            ->select('stocks.*')
            ->when($request->filled('type'), fn ($q) => $q->where('stocks.type', $request->type))
            ->when($request->filled('product_id'), fn ($q) => $q->where('stocks.product_id', $request->product_id))
            ->when($request->filled('location_id'), fn ($q) => $q->where('stocks.stock_location_id', $request->location_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('stocks.transaction_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('stocks.transaction_date', '<=', $request->date_to))
            ->when($request->filled('user_id'), fn ($q) => $q->where('stocks.user_id', $request->user_id));

        $colors = ['in' => 'success', 'out' => 'danger', 'adjustment' => 'warning', 'transfer' => 'info', 'transfer_in' => 'info', 'return' => 'primary', 'damage' => 'dark'];
        $labels = ['in' => 'Stock In', 'out' => 'Stock Out', 'adjustment' => 'Adjustment', 'transfer' => 'Transfer', 'transfer_in' => 'Transfer In', 'return' => 'Return', 'damage' => 'Damage'];
        $user = $request->user();

        return DataTables::eloquent($query)
            ->editColumn('transaction_date', fn ($t) => optional($t->transaction_date)->format('M d, Y h:i A'))
            ->editColumn('type', function ($t) use ($colors, $labels) {
                $c = $colors[$t->type] ?? 'secondary';
                return '<span class="badge bg-' . $c . '-subtle text-' . $c . ' border border-' . $c . '-subtle">' . e($labels[$t->type] ?? ucfirst($t->type)) . '</span>';
            })
            ->addColumn('product', function ($t) {
                $p = $t->product;
                if (!$p) {
                    return '<span class="text-muted">Deleted product</span>';
                }
                return '<div class="d-flex align-items-center gap-2">'
                    . ($p->thumbnail ? '<img src="' . e(asset('storage/' . $p->thumbnail)) . '" class="gz-thumb" alt="">' : '')
                    . '<div><div class="fw-semibold">' . e($p->title) . '</div><small class="text-muted">' . e($p->sku) . '</small></div></div>';
            })
            ->addColumn('location', fn ($t) => '<div class="fw-semibold">' . e($t->stockLocation->name ?? '—') . '</div>'
                . ($t->type === 'transfer' && $t->destinationLocation ? '<small class="text-muted">→ ' . e($t->destinationLocation->name) . '</small>' : ''))
            ->editColumn('quantity', function ($t) {
                $plus = in_array($t->type, ['in', 'adjustment', 'return', 'transfer_in'], true);
                return '<span class="fw-bold ' . ($plus ? 'text-success' : 'text-danger') . '">' . ($plus ? '+' : '-') . abs($t->quantity) . '</span>';
            })
            ->addColumn('reference', fn ($t) => '<div>' . e($t->reference_number) . '</div>'
                . ($t->adjustment_reason ? '<small class="text-muted">' . e($t->adjustment_reason) . '</small>' : ''))
            ->addColumn('user_name', fn ($t) => e($t->user->name ?? 'System'))
            ->addColumn('action', function ($t) use ($user) {
                $h = '<div class="dropdown"><button class="btn btn-soft-secondary btn-sm" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button><ul class="dropdown-menu dropdown-menu-end">'
                    . '<li><a class="dropdown-item view-transaction-btn" href="javascript:void(0);" data-id="' . $t->id . '"><i class="bi bi-eye me-2"></i> View Details</a></li>';
                if ($user->can('Manage inventory') && ($t->created_at?->diffInHours(now()) <= 24 || $user->hasRole('Admin'))) {
                    $h .= '<li><a class="dropdown-item text-danger delete-transaction-btn" href="javascript:void(0);" data-id="' . $t->id . '"><i class="bi bi-trash me-2"></i> Delete</a></li>';
                }
                return $h . '</ul></div>';
            })
            ->filterColumn('product', fn ($q, $k) => $q->whereHas('product', fn ($p) => $p->where('title', 'like', "%{$k}%")->orWhere('sku', 'like', "%{$k}%")))
            ->filterColumn('reference', fn ($q, $k) => $q->where('stocks.reference_number', 'like', "%{$k}%"))
            ->rawColumns(['type', 'product', 'location', 'quantity', 'reference', 'action'])
            ->toJson();
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


    /** SQL fragment: signed quantity of a stock row (in/adjustment/transfer_in/return add, out/damage/transfer subtract). */
    private const SIGNED_QTY = "CASE WHEN type IN ('in','adjustment','transfer_in','return') THEN quantity WHEN type IN ('out','damage','transfer') THEN -quantity ELSE 0 END";

    /**
     * Products joined with their computed stock (total + one column per location).
     * Shared by the Stock Levels page, its DataTable and its totals.
     */
    private function stockLevelsQuery(Request $request, $locations)
    {
        $sub = Stock::query()->selectRaw('product_id, SUM(' . self::SIGNED_QTY . ') as total_stock');
        foreach ($locations as $loc) {
            $sub->selectRaw('SUM(CASE WHEN stock_location_id = ? THEN ' . self::SIGNED_QTY . ' ELSE 0 END) as loc_' . (int) $loc->id, [$loc->id]);
        }
        $sub->groupBy('product_id');

        $stockExpr = 'GREATEST(COALESCE(st.total_stock, 0), 0)';

        $query = Product::query()
            ->leftJoinSub($sub, 'st', 'st.product_id', '=', 'products.id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->select('products.*', 'categories.name as category_name', 'brands.name as brand_name')
            ->selectRaw("{$stockExpr} as total_stock")
            ->when($request->filled('category_id'), fn ($q) => $q->where('products.category_id', $request->category_id))
            ->when($request->filled('brand_id'), fn ($q) => $q->where('products.brand_id', $request->brand_id))
            ->when($request->stock_status === 'in_stock', fn ($q) => $q->whereRaw("{$stockExpr} > 10"))
            ->when($request->stock_status === 'low_stock', fn ($q) => $q->whereRaw("{$stockExpr} BETWEEN 1 AND 10"))
            ->when($request->stock_status === 'out_of_stock', fn ($q) => $q->whereRaw("{$stockExpr} = 0"));

        foreach ($locations as $loc) {
            $query->selectRaw('GREATEST(COALESCE(st.loc_' . (int) $loc->id . ', 0), 0) as loc_' . (int) $loc->id);
        }

        return $query;
    }

    /** Totals for the value cards / margin chart over the whole filtered set (not just one page). */
    private function stockLevelsTotals($query): array
    {
        $sell   = 'COALESCE(NULLIF(products.sale_price, 0), products.price, 0)';
        $cost   = 'COALESCE(products.cost_price, 0)';
        $stock  = 'GREATEST(COALESCE(st.total_stock, 0), 0)';
        $margin = "CASE WHEN {$cost} > 0 THEN (({$sell} - {$cost}) / {$cost}) * 100 ELSE 0 END";

        $row = DB::query()->fromSub($query->toBase()->cloneWithout(['columns', 'orders'])->cloneWithoutBindings(['select', 'order'])->select('products.*')
                ->selectRaw("{$stock} as s_stock, {$sell} as s_sell, {$cost} as s_cost, {$margin} as s_margin"), 't')
            ->selectRaw('COUNT(*) as products')
            ->selectRaw('SUM(s_stock) as total_stock')
            ->selectRaw('SUM(s_stock * s_cost) as cost_value')
            ->selectRaw('SUM(s_stock * s_sell) as selling_value')
            ->selectRaw('AVG(CASE WHEN s_cost > 0 THEN s_margin END) as avg_margin')
            ->selectRaw('SUM(CASE WHEN s_margin > 50 THEN 1 ELSE 0 END) as m_high')
            ->selectRaw('SUM(CASE WHEN s_margin >= 30 AND s_margin <= 50 THEN 1 ELSE 0 END) as m_good')
            ->selectRaw('SUM(CASE WHEN s_margin >= 20 AND s_margin < 30 THEN 1 ELSE 0 END) as m_avg')
            ->selectRaw('SUM(CASE WHEN s_margin >= 10 AND s_margin < 20 THEN 1 ELSE 0 END) as m_low')
            ->selectRaw('SUM(CASE WHEN s_margin > 0 AND s_margin < 10 THEN 1 ELSE 0 END) as m_vlow')
            ->selectRaw('SUM(CASE WHEN s_margin = 0 THEN 1 ELSE 0 END) as m_none')
            ->selectRaw('SUM(CASE WHEN s_margin < 0 THEN 1 ELSE 0 END) as m_loss')
            ->first();

        return [
            'products'      => (int) ($row->products ?? 0),
            'total_stock'   => (int) ($row->total_stock ?? 0),
            'cost_value'    => (float) ($row->cost_value ?? 0),
            'selling_value' => (float) ($row->selling_value ?? 0),
            'profit'        => (float) ($row->selling_value ?? 0) - (float) ($row->cost_value ?? 0),
            'avg_margin'    => round((float) ($row->avg_margin ?? 0), 2),
            'margin_ranges' => [
                'High (>50%)'      => (int) $row->m_high,
                'Good (30-50%)'    => (int) $row->m_good,
                'Average (20-30%)' => (int) $row->m_avg,
                'Low (10-20%)'     => (int) $row->m_low,
                'Very Low (<10%)'  => (int) $row->m_vlow,
                'No Margin'        => (int) $row->m_none,
                'Loss'             => (int) $row->m_loss,
            ],
        ];
    }

    public function stockLevels(Request $request)
    {
        $pagetitle = "Stock Levels Report";

        $locations  = StockLocation::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $brands     = Brand::orderBy('name')->get();

        // Status counts over all products (cheap: one grouped query)
        $base = $this->stockLevelsQuery(new Request(), $locations);
        $counts = DB::query()->fromSub($base->toBase(), 'x')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN total_stock > 10 THEN 1 ELSE 0 END) as in_stock')
            ->selectRaw('SUM(CASE WHEN total_stock BETWEEN 1 AND 10 THEN 1 ELSE 0 END) as low_stock')
            ->selectRaw('SUM(CASE WHEN total_stock = 0 THEN 1 ELSE 0 END) as out_of_stock')
            ->first();

        $summary = [
            'total_products' => (int) ($counts->total ?? 0),
            'in_stock'       => (int) ($counts->in_stock ?? 0),
            'low_stock'      => (int) ($counts->low_stock ?? 0),
            'out_of_stock'   => (int) ($counts->out_of_stock ?? 0),
        ];

        // Stock per location for the bar chart / table footer
        $locationStockTotals = Stock::query()
            ->selectRaw('stock_location_id, SUM(' . self::SIGNED_QTY . ') as total')
            ->groupBy('stock_location_id')
            ->pluck('total', 'stock_location_id')
            ->map(fn ($v) => max(0, (int) $v))
            ->toArray();

        return view('inventory.stock-levels', compact(
            'pagetitle', 'locations', 'categories', 'brands', 'summary', 'locationStockTotals'
        ));
    }

    /** Yajra DataTable endpoint for the Stock Levels page. Totals ride along in the JSON. */
    public function stockLevelsData(Request $request)
    {
        $locations = StockLocation::orderBy('name')->get();
        $query     = $this->stockLevelsQuery($request, $locations);
        $totals    = $this->stockLevelsTotals($this->stockLevelsQuery($request, $locations));
        $naira     = fn ($v) => '₦' . number_format((float) $v, 2);
        $canManage = $request->user()->can('Manage inventory');

        $dt = DataTables::eloquent($query)
            ->addColumn('checkbox', fn ($p) => '<input type="checkbox" class="form-check-input product-checkbox" value="' . $p->id . '">')
            ->addColumn('product', fn ($p) => '<div class="d-flex align-items-center gap-2">'
                . ($p->thumbnail ? '<img src="' . e(asset('storage/' . $p->thumbnail)) . '" class="gz-thumb" alt="">' : '')
                . '<div><div class="fw-semibold">' . e($p->title) . '</div>'
                . ($p->barcode ? '<small class="text-muted">Barcode: ' . e($p->barcode) . '</small>' : '') . '</div></div>')
            ->addColumn('category', fn ($p) => e($p->category_name ?? '-'))
            ->addColumn('brand', fn ($p) => e($p->brand_name ?? '-'))
            ->editColumn('cost_price', fn ($p) => $p->cost_price > 0 ? '<span class="fw-bold text-info">' . $naira($p->cost_price) . '</span>' : '<span class="text-muted">-</span>')
            ->editColumn('price', fn ($p) => ($p->sale_price && $p->sale_price < $p->price)
                ? '<del class="text-muted small">' . $naira($p->price) . '</del>'
                : '<span class="fw-bold">' . $naira($p->price) . '</span>')
            ->addColumn('selling', function ($p) use ($naira) {
                $sell = ($p->sale_price ?: $p->price) ?? 0;
                return '<span class="fw-bold ' . (($p->sale_price && $p->sale_price < $p->price) ? 'text-danger' : 'text-success') . '">' . $naira($sell) . '</span>';
            })
            ->addColumn('discount', function ($p) use ($naira) {
                if (!($p->sale_price && $p->sale_price < $p->price && $p->price > 0)) {
                    return '<span class="badge bg-secondary-subtle text-secondary">No discount</span>';
                }
                $pct = round((($p->price - $p->sale_price) / $p->price) * 100, 1);
                $c = $pct >= 20 ? 'danger' : ($pct >= 10 ? 'warning' : 'info');
                return '<span class="badge bg-' . $c . '-subtle text-' . $c . '">-' . $pct . '%</span><br><small class="text-muted">Save ' . $naira($p->price - $p->sale_price) . '</small>';
            })
            ->addColumn('profit', function ($p) use ($naira) {
                $m = (($p->sale_price ?: $p->price) ?? 0) - ($p->cost_price ?? 0);
                return '<span class="fw-bold ' . ($m > 0 ? 'text-primary' : ($m < 0 ? 'text-danger' : 'text-muted')) . '">' . $naira($m) . '</span>';
            })
            ->addColumn('margin', function ($p) {
                $cost = (float) ($p->cost_price ?? 0);
                $pct  = $cost > 0 ? round(((($p->sale_price ?: $p->price) - $cost) / $cost) * 100, 1) : 0;
                $c = $pct >= 50 ? 'success' : ($pct >= 20 ? 'warning' : ($pct >= 10 ? 'info' : 'danger'));
                return '<span class="badge bg-' . $c . '-subtle text-' . $c . '">' . number_format($pct, 1) . '%</span>';
            })
            ->editColumn('total_stock', function ($p) {
                $s = (int) $p->total_stock;
                return '<span class="fw-bold ' . ($s > 10 ? 'text-success' : ($s > 0 ? 'text-warning' : 'text-secondary')) . '">' . $s . '</span>';
            })
            ->addColumn('stock_value', function ($p) use ($naira) {
                $sell = ($p->sale_price ?: $p->price) ?? 0;
                $value = $p->total_stock * $sell;
                $cost  = $p->total_stock * ($p->cost_price ?? 0);
                return '<div class="text-end"><div class="fw-bold text-success">' . $naira($value) . '</div>'
                    . '<small class="text-muted">Cost: ' . $naira($cost) . '</small>'
                    . '<div class="small ' . ($value - $cost >= 0 ? 'text-primary' : 'text-danger') . '">Profit: ' . $naira($value - $cost) . '</div></div>';
            })
            ->addColumn('status', function ($p) {
                $s = (int) $p->total_stock;
                [$c, $t] = $s > 10 ? ['success', 'In Stock'] : ($s > 0 ? ['warning', 'Low Stock'] : ['danger', 'Out of Stock']);
                return '<span class="badge bg-' . $c . '-subtle text-' . $c . '">' . $t . '</span>';
            })
            ->addColumn('action', function ($p) use ($canManage) {
                $h = '<div class="dropdown"><button class="btn btn-soft-secondary btn-sm" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button><ul class="dropdown-menu dropdown-menu-end">'
                    . '<li><a class="dropdown-item" href="' . route('web.products.show', $p->id) . '"><i class="bi bi-eye me-2"></i> View Product</a></li>'
                    . '<li><a class="dropdown-item" href="#" onclick="showStockHistory(' . $p->id . ');return false;"><i class="bi bi-clock-history me-2"></i> View History</a></li>';
                if ($canManage) {
                    $h .= '<li><a class="dropdown-item" href="#" onclick="quickAdjust(' . $p->id . ', ' . e(json_encode($p->title)) . ');return false;"><i class="bi bi-plus-slash-minus me-2"></i> Adjust Stock</a></li>';
                }
                return $h . '</ul></div>';
            })
            ->filterColumn('product', fn ($q, $k) => $q->where(fn ($w) => $w->where('products.title', 'like', "%{$k}%")->orWhere('products.barcode', 'like', "%{$k}%")))
            ->filterColumn('category', fn ($q, $k) => $q->where('categories.name', 'like', "%{$k}%"))
            ->filterColumn('brand', fn ($q, $k) => $q->where('brands.name', 'like', "%{$k}%"))
            ->orderColumn('product', 'products.title $1')
            ->orderColumn('total_stock', 'total_stock $1')
            ->orderColumn('selling', 'COALESCE(NULLIF(products.sale_price, 0), products.price) $1');

        $raw = ['checkbox', 'product', 'cost_price', 'price', 'selling', 'discount', 'profit', 'margin', 'total_stock', 'stock_value', 'status', 'action'];
        foreach ($locations as $loc) {
            $col = 'loc_' . (int) $loc->id;
            $dt->editColumn($col, function ($p) use ($col) {
                $s = (int) $p->$col;
                $c = $s > 10 ? 'success' : ($s > 0 ? 'warning' : 'secondary');
                return '<span class="badge bg-' . $c . '-subtle text-' . $c . '">' . $s . '</span>';
            });
            $dt->orderColumn($col, $col . ' $1');
            $raw[] = $col;
        }

        return $dt->rawColumns($raw)->with('totals', $totals)->toJson();
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
