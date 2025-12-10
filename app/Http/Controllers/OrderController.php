<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Mail\InvoiceMail;
use Illuminate\Http\Request;
use App\Models\InvoiceNumber;
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

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
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
        ];

        return view('orders.index', compact('orders', 'pagetitle', 'stats'));
    }

    public function show($id)
    {
        $order = Order::with([
            'user:id,first_name,last_name,email,phone',
            'items.product',
            'shippingAddress',
            'billingAddress',
            'transactions'
        ])->findOrFail($id);

        $invoiceDisplay = $order->invoice_number ?? substr($order->id, 0, 8);
        $pagetitle = "Order #{$invoiceDisplay}";

        return view('orders.show', compact('order', 'pagetitle'));
    }
    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:pending,processing,shipped,delivered,cancelled']);
        $order = Order::findOrFail($id);
        $order->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated',
            'badge_class' => $this->getStatusBadgeClass($request->status)
        ]);
    }

    public function invoice($id)
    {
        $order = Order::with(['user', 'items', 'shippingAddress', 'billingAddress'])->findOrFail($id);

        if (!$order->invoice_number) {
            $order->invoice_number = InvoiceNumber::generate();
            $order->invoiced_at = now();
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
            $order->invoiced_at = now();
            $order->save();
        }

        Mail::to($order->user->email)->send(new InvoiceMail($order));

        return response()->json(['success' => true, 'message' => 'Invoice sent!']);
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