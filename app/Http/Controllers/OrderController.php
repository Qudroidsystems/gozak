<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Mail\InvoiceMail;
use Illuminate\Http\Request;
use App\Helpers\InvoiceHelper;
use App\Models\InvoiceSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View order|Manage order', ['only' => ['index', 'show']]);
        $this->middleware('permission:Manage order', ['only' => ['updateStatus']]);
    }

    public function index(Request $request)
    {
        $pagetitle = "Order Management";

        $query = Order::with(['user:id,first_name,last_name,email', 'items.product', 'shippingAddress'])
            ->withCount('items')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($q) => $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]));
            });
        }

        $orders = $query->paginate(15)->appends($request->all());

        $stats = [
            'total' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'shipped' => Order::where('status', 'shipped')->count(),
            'delivered' => Order::where('status', 'delivered')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
            'paid' => Order::where('payment_status', 'paid')->count(),
            'unpaid' => Order::where('payment_status', 'unpaid')->count(),
        ];

        return view('orders.index', compact('orders', 'pagetitle', 'stats'));
    }

    public function show($id)
    {
        $order = Order::with([
                'user:id,first_name,last_name,email,phone',
                'items.product',
                'items.variation',
                'shippingAddress',
                'billingAddress',
                'transactions'
            ])
            ->findOrFail($id);

        $pagetitle = "Order #{$order->invoice_number ?? substr($order->id, 0, 8)}";

        return view('orders.show', compact('order', 'pagetitle'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:pending,processing,shipped,delivered,cancelled']);

        $order = Order::findOrFail($id);
        $order->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => "Status updated to " . ucfirst($request->status),
            'badge_class' => $this->getStatusBadgeClass($request->status)
        ]);
    }

    public function invoice($id)
    {
        $order = Order::with(['user', 'items', 'shippingAddress', 'billingAddress'])->findOrFail($id);

        // Generate invoice number if not exists
        if (!$order->invoice_number) {
            $order->invoice_number = InvoiceHelper::generate();
            $order->invoiced_at = now();
            $order->save();
        }

        $settings = InvoiceSetting::firstOrCreate([], [
            'company_name' => config('app.name'),
            'company_address' => '123 Business St, City, Country',
            'company_phone' => '+1 234 567 890',
            'company_email' => 'sales@yourstore.com',
            'logo' => 'img/logo.png',
            'primary_color' => '#0d6efd',
            'currency' => 'USD',
            'language' => 'en',
            'tax_name' => 'VAT',
            'tax_rate' => 15.00,
        ]);

        app()->setLocale($settings->language);

        $pdf = Pdf::loadView('orders.invoice', [
            'order' => $order,
            'invoiceNumber' => $order->invoice_number,
            'settings' => $settings,
            'lang' => $settings->language
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("invoice-{$order->invoice_number}.pdf");
    }

    public function emailInvoice($id)
    {
        $order = Order::findOrFail($id);

        if (!$order->invoice_number) {
            $order->invoice_number = InvoiceHelper::generate();
            $order->invoiced_at = now();
            $order->save();
        }

        Mail::to($order->user->email)->send(new InvoiceMail($order));

        return response()->json([
            'success' => true,
            'message' => 'Invoice emailed successfully!'
        ]);
    }

    private function getStatusBadgeClass($status)
    {
        return match ($status) {
            'pending' => 'bg-warning-subtle text-warning',
            'processing' => 'bg-info-subtle text-info',
            'shipped' => 'bg-primary-subtle text-primary',
            'delivered' => 'bg-success-subtle text-success',
            'cancelled' => 'bg-danger-subtle text-danger',
            default => 'bg-secondary-subtle text-secondary',
        };
    }
}