<?php

namespace App\Http\Controllers;

use App\Exports\OrdersExport;
use App\Mail\InvoiceMail;
use App\Models\InvoiceNumber;
use App\Models\InvoiceSetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Refund;
use App\Models\User;
use App\Services\OrderNotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Support\Money;
use Yajra\DataTables\Facades\DataTables;

class OrderController extends Controller
{
    protected OrderNotificationService $orderNotificationService;

    public function __construct(OrderNotificationService $orderNotificationService)
    {
        $this->orderNotificationService = $orderNotificationService;

        $this->middleware('permission:View order|Manage order', ['only' => ['index', 'show', 'data']]);
        $this->middleware('permission:Manage order', ['only' => ['updateStatus']]);
    }

    public function index(Request $request)
    {
        $pagetitle = "Order Management";

        $now        = Carbon::now();
        $last30Days = $now->clone()->subDays(30);

        $totalRevenue  = Order::where('payment_status', 'paid')->sum('total_amount');
        $totalOrders   = Order::count();
        $paidOrders    = Order::where('payment_status', 'paid')->count();
        $avgOrderValue = $paidOrders > 0 ? $totalRevenue / $paidOrders : 0;

        $thisMonthRevenue = Order::where('payment_status', 'paid')
            ->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)
            ->sum('total_amount');

        $lastMonthRevenue = Order::where('payment_status', 'paid')
            ->whereMonth('created_at', $now->clone()->subMonth()->month)
            ->whereYear('created_at', $now->clone()->subMonth()->year)
            ->sum('total_amount');

        $revenueGrowth = $lastMonthRevenue > 0
            ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : ($thisMonthRevenue > 0 ? 100 : 0);

        $newCustomers = User::whereHas('orders', fn($q) => $q->where('created_at', '>=', $last30Days))
            ->where('created_at', '>=', $last30Days)->count();

        $topProducts = OrderItem::with('product')
            ->selectRaw('product_id, SUM(quantity) as total_sold')
            ->whereHas('order', fn($q) => $q->where('payment_status', 'paid')->where('created_at', '>=', $last30Days))
            ->groupBy('product_id')->orderByDesc('total_sold')->limit(5)->get();

        $dailySales = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $last30Days)
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
            ->groupBy('date')->orderBy('date')
            ->pluck('total', 'date');

        $labels = $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date    = $now->clone()->subDays($i);
            $labels[] = $date->format('d M');
            $data[]   = $dailySales->get($date->format('Y-m-d'), 0);
        }

        $stats = [
            'total'      => $totalOrders,
            'pending'    => Order::where('status', 'pending')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'shipped'    => Order::where('status', 'shipped')->count(),
            'delivered'  => Order::where('status', 'delivered')->count(),
            'cancelled'  => Order::where('status', 'cancelled')->count(),
            'paid'       => $paidOrders,
            'unpaid'     => Order::where('payment_status', '!=', 'paid')->count(),
        ];

        $analytics = [
            'total_revenue'   => $totalRevenue,
            'avg_order_value' => $avgOrderValue,
            'revenue_growth'  => $revenueGrowth,
            'new_customers'   => $newCustomers,
            'top_products'    => $topProducts,
            'sales_chart'     => ['labels' => $labels, 'data' => $data],
        ];

        return view('orders.index', compact('pagetitle', 'stats', 'analytics'));
    }

    /** Yajra DataTable endpoint (filters: status, payment_status, from, to). */
    public function data(Request $request)
    {
        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

        $query = Order::query()
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->select('orders.*', 'users.first_name', 'users.last_name', 'users.email as user_email')
            ->withCount('items')
            ->when($request->filled('status'), fn ($q) => $q->where('orders.status', $request->status))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('orders.payment_status', $request->payment_status))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('orders.created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('orders.created_at', '<=', $request->to));

        $canManage = $request->user()->can('Manage order');

        return DataTables::eloquent($query)
            ->setRowClass(fn ($o) => $o->status === 'delivered' ? 'table-success' : ($o->status === 'pending' ? 'table-warning' : ''))
            ->setRowAttr(['data-order-id' => fn ($o) => $o->id, 'data-status' => fn ($o) => $o->status])
            ->addColumn('invoice', fn ($o) => '<a href="' . route('adminorders.show', $o->id) . '" class="fw-bold text-primary">' . e($o->invoice_number ?? ('#' . $o->id)) . '</a>')
            ->addColumn('customer', function ($o) {
                $name = trim($o->first_name . ' ' . $o->last_name) ?: 'Guest';
                return '<div class="d-flex align-items-center gap-2"><span class="gz-avatar">' . e(strtoupper(substr($name, 0, 1))) . '</span>'
                    . '<div><span class="fw-semibold">' . e($name) . '</span><small class="d-block text-muted">' . e($o->user_email ?? 'N/A') . '</small></div></div>';
            })
            ->editColumn('created_at', fn ($o) => optional($o->created_at)->format('d M Y, H:i'))
            ->editColumn('total_amount', fn ($o) => '<span class="fw-bold text-success">' . e(Money::fmt($o->total_amount)) . '</span>')
            ->editColumn('payment_status', fn ($o) => $o->payment_status === 'paid'
                ? '<span class="status-pill st-paid">Paid</span>'
                : '<span class="status-pill st-unpaid">' . e(ucfirst($o->payment_status ?? 'unpaid')) . '</span>')
            ->editColumn('status', function ($o) use ($statuses, $canManage) {
                if (!$canManage) {
                    return '<span class="status-pill st-' . e($o->status) . '">' . e(ucfirst($o->status)) . '</span>';
                }
                $opts = collect($statuses)->map(fn ($s) => '<option value="' . $s . '"' . ($o->status === $s ? ' selected' : '') . '>' . ucfirst($s) . '</option>')->implode('');
                return '<select class="form-select form-select-sm status-select" style="min-width:130px" data-id="' . $o->id . '" data-current="' . e($o->status) . '">' . $opts . '</select>';
            })
            ->editColumn('items_count', fn ($o) => '<span class="badge bg-primary-subtle text-primary">' . (int) $o->items_count . '</span>')
            ->addColumn('action', fn ($o) => '<div class="dropdown"><button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>'
                . '<ul class="dropdown-menu dropdown-menu-end">'
                . '<li><a class="dropdown-item" href="' . route('adminorders.show', $o->id) . '">View Details</a></li>'
                . '<li><a class="dropdown-item" href="' . route('adminorders.invoice', $o->id) . '" target="_blank">PDF Invoice</a></li>'
                . '<li><a class="dropdown-item" href="' . route('adminorders.packing-slip', $o->id) . '" target="_blank">Packing Slip</a></li>'
                . '<li><a class="dropdown-item email-invoice" href="javascript:void(0)" data-id="' . $o->id . '">Email Invoice</a></li>'
                . '</ul></div>')
            ->filterColumn('invoice', fn ($q, $k) => $q->where(fn ($w) => $w->where('orders.invoice_number', 'like', "%{$k}%")->orWhere('orders.id', ltrim($k, '#'))))
            ->filterColumn('customer', function ($q, $k) {
                $q->where(fn ($w) => $w->whereRaw("CONCAT(users.first_name, ' ', users.last_name) LIKE ?", ["%{$k}%"])
                    ->orWhere('users.email', 'like', "%{$k}%"));
            })
            ->orderColumn('invoice', 'orders.id $1')
            ->orderColumn('customer', 'users.first_name $1')
            ->rawColumns(['invoice', 'customer', 'total_amount', 'payment_status', 'status', 'items_count', 'action'])
            ->toJson();
    }

    public function export(Request $request)
    {
        $format   = $request->get('format', 'xlsx');
        $filename = 'orders_' . now()->format('Y-m-d_His');

        return Excel::download(
            new OrdersExport,
            "{$filename}.{$format}",
            $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
        );
    }

public function show($id)
{
    $order = Order::with([
        'user:id,first_name,last_name,email,phone_number',
        'items.product',
        'shippingAddress',
        'billingAddress',
        'transactions',
        'refunds', // Add this if you want to show refunds
        'notes',   // Add this if you want to show notes
    ])->withCount('items') // Add this line to get the items count
    ->findOrFail($id);

    $invoiceDisplay = $order->invoice_number ?? substr($order->id, 0, 8);
    $pagetitle      = "Order #{$invoiceDisplay}";

    return view('orders.show', compact('order', 'pagetitle'));
}

    /**
     * Admin updates order status from the web panel.
     * Sends FCM push notification to the customer automatically.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        $order     = Order::with('user')->findOrFail($id);
        $newStatus = $request->status;

        $order->update(['status' => $newStatus]);

        // ── FCM push to the customer ──────────────────────────────────────────
        try {
            $fcmResult = $this->orderNotificationService->notifyOrderStatusUpdate($order, $newStatus);
            \Illuminate\Support\Facades\Log::info('Admin status change: FCM sent', [
                'order_id'  => $order->id,
                'status'    => $newStatus,
                'fcm_sent'  => $fcmResult['sent'],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Admin status change: FCM failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success'     => true,
            'message'     => 'Status updated',
            'badge_class' => $this->getStatusBadgeClass($newStatus),
        ]);
    }

    public function invoice($id)
    {
        $order = Order::with(['user', 'items', 'shippingAddress', 'billingAddress'])->findOrFail($id);

        if (!$order->invoice_number) {
            $order->invoice_number = InvoiceNumber::generate();
            $order->invoiced_at    = now();
            $order->save();
        }

        $settings = InvoiceSetting::getSettings();
        app()->setLocale($settings->language);

        $pdf = Pdf::loadView('orders.invoice', compact('order', 'settings'))
                  ->setPaper('a4', 'portrait');

        return $pdf->stream("invoice-{$order->invoice_number}.pdf");
    }

    public function emailInvoice($id)
    {
        $order = Order::findOrFail($id);

        if (!$order->invoice_number) {
            $order->invoice_number = InvoiceNumber::generate();
            $order->invoiced_at    = now();
            $order->save();
        }

        Mail::to($order->user->email)->send(new InvoiceMail($order));

        return response()->json(['success' => true, 'message' => 'Invoice sent!']);
    }

    private function getStatusBadgeClass($status): string
    {
        return match ($status) {
            'pending'    => 'bg-warning-subtle text-warning',
            'processing' => 'bg-info-subtle text-info',
            'shipped'    => 'bg-primary-subtle text-primary',
            'delivered'  => 'bg-success-subtle text-success',
            'cancelled'  => 'bg-danger-subtle text-danger',
            default      => 'bg-secondary-subtle text-secondary',
        };
    }

    public function addNote(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        OrderNote::create([
            'order_id'            => $order->id,
            'user_id'             => auth()->id(),
            'note'                => $request->note,
            'is_customer_visible' => $request->boolean('is_customer_visible'),
        ]);
        return back()->with('success', 'Note added');
    }

    public function refund(Request $request, $id)
    {
        $order  = Order::findOrFail($id);
        $amount = $request->amount;

        if ($amount > $order->refundableAmount()) {
            return back()->with('error', 'Refund amount too high');
        }

        Refund::create([
            'order_id'     => $order->id,
            'user_id'      => auth()->id(),
            'amount'       => $amount,
            'reason'       => $request->reason,
            'status'       => 'processed',
            'processed_at' => now(),
        ]);

        return back()->with('success', 'Refund processed');
    }

    public function packingSlip($id)
    {
        $order = Order::with('items', 'shippingAddress')->findOrFail($id);
        $pdf   = Pdf::loadView('orders.packing-slip', compact('order'));
        return $pdf->stream("packing-slip-{$order->invoice_number}.pdf");
    }
}
