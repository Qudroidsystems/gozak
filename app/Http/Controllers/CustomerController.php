<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Exports\CustomersExport;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View customer|Manage customer');
    }

    public function index(Request $request)
    {
        $pagetitle = "Customer Management";

        // Main query with stats
        $query = User::customers()
            ->withOrderStats()
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        $customers = $query->paginate(15)->withQueryString();

        // Total revenue from customers (correct way)
        $totalSpent = User::customers()
            ->withSum('orders', 'total_amount')
            ->get()
            ->sum('orders_sum_total_amount');

        $stats = [
            'total'       => User::customers()->count(),
            'active'      => User::customers()->whereNotNull('email_verified_at')->count(),
            'total_spent' => $totalSpent,
        ];

        return view('customers.index', compact('customers', 'pagetitle', 'stats'));
    }

    public function export()
    {
        return Excel::download(new CustomersExport, 'customers_' . now()->format('Y-m-d') . '.xlsx');
    }
}