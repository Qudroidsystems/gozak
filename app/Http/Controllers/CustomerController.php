<?php

namespace App\Http\Controllers;

use App\Exports\CustomersExport;
use App\Models\User;
use App\Support\Money;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View customer|Manage customer');
    }

    public function index(Request $request)
    {
        $pagetitle = 'Customer Management';

        $totalSpent = (float) \App\Models\Order::whereIn('user_id', User::customers()->select('id'))->sum('total_amount');

        $stats = [
            'total'       => User::customers()->count(),
            'active'      => User::customers()->whereNotNull('email_verified_at')->count(),
            'new_month'   => User::customers()->where('created_at', '>=', now()->startOfMonth())->count(),
            'total_spent' => $totalSpent,
        ];

        return view('customers.index', compact('pagetitle', 'stats'));
    }

    /** Yajra DataTable endpoint. */
    public function data(Request $request)
    {
        // select() must come BEFORE withOrderStats() or it would drop the count/sum sub-selects
        $query = User::customers()
            ->select(['users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.phone_number',
                      'users.profile_image', 'users.email_verified_at', 'users.created_at'])
            ->withOrderStats()
            ->when($request->status === 'verified', fn ($q) => $q->whereNotNull('users.email_verified_at'))
            ->when($request->status === 'unverified', fn ($q) => $q->whereNull('users.email_verified_at'))
            ->when($request->status === 'buyers', fn ($q) => $q->has('orders'));

        return DataTables::eloquent($query)
            ->addColumn('customer', function ($u) {
                $initials = strtoupper(substr($u->first_name ?: 'U', 0, 1) . substr($u->last_name ?: '', 0, 1));
                return '<div class="d-flex align-items-center gap-2"><span class="gz-avatar">' . e($initials) . '</span>'
                    . '<div><span class="fw-semibold">' . e(trim($u->first_name . ' ' . $u->last_name) ?: '—') . '</span>'
                    . '<small class="d-block text-muted">' . e($u->phone_number ?? '') . '</small></div></div>';
            })
            ->editColumn('orders_count', fn ($u) => '<span class="badge bg-primary-subtle text-primary">' . (int) $u->orders_count . '</span>')
            ->editColumn('orders_sum_total_amount', fn ($u) => Money::fmt($u->orders_sum_total_amount ?? 0))
            ->addColumn('status', fn ($u) => $u->email_verified_at
                ? '<span class="status-pill st-success">Verified</span>'
                : '<span class="status-pill st-warning">Unverified</span>')
            ->editColumn('created_at', fn ($u) => optional($u->created_at)->format('d M Y'))
            ->filterColumn('customer', function ($q, $k) {
                $q->where(fn ($w) => $w->where('users.first_name', 'like', "%{$k}%")
                    ->orWhere('users.last_name', 'like', "%{$k}%")
                    ->orWhere('users.phone_number', 'like', "%{$k}%")
                    ->orWhereRaw("CONCAT(users.first_name, ' ', users.last_name) LIKE ?", ["%{$k}%"]));
            })
            ->orderColumn('customer', 'users.first_name $1, users.last_name $1')
            ->rawColumns(['customer', 'orders_count', 'status'])
            ->toJson();
    }

    public function export()
    {
        return Excel::download(new CustomersExport, 'customers_' . now()->format('Y-m-d') . '.xlsx');
    }
}
