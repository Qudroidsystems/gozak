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

        $query = User::role('customer')
            ->withCount(['orders'])
            ->withSum('orders', 'total_amount')
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

        $customers = $query->paginate(15);

        $stats = [
            'total' => User::role('customer')->count(),
            'active' => User::role('customer')->where('status', 'active')->count(),
            'blocked' => User::role('customer')->where('status', 'blocked')->count(),
            'total_spent' => User::role('customer')->withSum('orders', 'total_amount')->sum('orders_sum_total_amount'),
        ];

        return view('customers.index', compact('customers', 'pagetitle', 'stats'));
    }

    public function export()
    {
        return Excel::download(new CustomersExport, 'customers_' . now()->format('Y-m-d') . '.xlsx');
    }
}