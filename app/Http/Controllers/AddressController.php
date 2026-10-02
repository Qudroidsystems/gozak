<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Address;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AddressController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View addresses|Manage addresses', ['only' => ['index', 'data', 'show', 'edit', 'customers']]);
        $this->middleware('permission:Manage addresses', ['only' => ['store', 'update', 'destroy']]);
    }

    public function index(Request $request)
    {
        $pagetitle = 'Address Management';

        $stats = [
            'total'     => Address::count(),
            'customers' => Address::distinct('user_id')->count('user_id'),
            'defaults'  => Address::where('is_default', true)->count(),
        ];

        return view('addresses.index', compact('pagetitle', 'stats'));
    }

    /** Yajra DataTable endpoint. */
    public function data(Request $request)
    {
        $query = Address::query()
            ->leftJoin('users', 'users.id', '=', 'addresses.user_id')
            ->select('addresses.*', 'users.first_name', 'users.last_name', 'users.email as user_email')
            ->when($request->filled('customer_id'), fn ($q) => $q->where('addresses.user_id', $request->customer_id))
            ->when($request->default === '1', fn ($q) => $q->where('addresses.is_default', true));

        $canManage = $request->user()->can('Manage addresses');

        return DataTables::eloquent($query)
            ->addColumn('customer', fn ($a) => '<span class="fw-semibold">' . e(trim($a->first_name . ' ' . $a->last_name) ?: '—') . '</span>'
                . '<small class="d-block text-muted">' . e($a->user_email ?? '') . '</small>')
            ->addColumn('address', fn ($a) => '<span>' . e($a->street) . '</span>'
                . '<small class="d-block text-muted">' . e(collect([$a->city, $a->state, $a->postal_code])->filter()->implode(', ')) . ' · ' . e($a->country) . '</small>')
            ->editColumn('name', fn ($a) => e($a->name ?: '—'))
            ->editColumn('is_default', fn ($a) => $a->is_default
                ? '<span class="status-pill st-success">Default</span>'
                : '<span class="text-muted small">—</span>')
            ->editColumn('created_at', fn ($a) => optional($a->created_at)->format('d M Y'))
            ->addColumn('action', function ($a) use ($canManage) {
                $h = '<div class="gz-actions"><button class="btn btn-sm btn-soft-info view-btn" data-id="' . $a->id . '" title="View"><i class="ph-eye"></i></button>';
                if ($canManage) {
                    $h .= '<button class="btn btn-sm btn-soft-secondary edit-btn" data-id="' . $a->id . '" title="Edit"><i class="ph-pencil"></i></button>'
                        . '<button class="btn btn-sm btn-soft-danger delete-btn" data-id="' . $a->id . '" title="Delete"><i class="ph-trash"></i></button>';
                }
                return $h . '</div>';
            })
            ->filterColumn('customer', function ($q, $k) {
                $q->where(fn ($w) => $w->whereRaw("CONCAT(users.first_name, ' ', users.last_name) LIKE ?", ["%{$k}%"])
                    ->orWhere('users.email', 'like', "%{$k}%"));
            })
            ->filterColumn('address', function ($q, $k) {
                $q->where(fn ($w) => $w->where('addresses.street', 'like', "%{$k}%")
                    ->orWhere('addresses.city', 'like', "%{$k}%")
                    ->orWhere('addresses.state', 'like', "%{$k}%")
                    ->orWhere('addresses.postal_code', 'like', "%{$k}%"));
            })
            ->orderColumn('customer', 'users.first_name $1, users.last_name $1')
            ->orderColumn('address', 'addresses.city $1')
            ->rawColumns(['customer', 'address', 'name', 'is_default', 'action'])
            ->toJson();
    }

    /** Select2 AJAX source for the customer pickers. */
    public function customers(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $users = User::query()
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone_number', 'like', "%{$term}%")
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"])))
            ->orderBy('first_name')
            ->paginate(20, ['id', 'first_name', 'last_name', 'email']);

        return response()->json([
            'results'    => $users->getCollection()->map(fn ($u) => ['id' => $u->id, 'text' => trim("{$u->first_name} {$u->last_name}") . ($u->email ? " ({$u->email})" : '')])->values(),
            'pagination' => ['more' => $users->hasMorePages()],
        ]);
    }

    public function show($id)
    {
        $address = Address::with('user')->findOrFail($id);
        return response()->json(['address' => $address]);
    }

    public function edit($id)
    {
        $address = Address::with('user:id,first_name,last_name,email')->findOrFail($id);
        return response()->json(['address' => $address]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'nullable|string|max:255',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'required|string|max:255',
            'phone_number' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/',
            'is_default' => 'boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            Address::where('user_id', $validated['user_id'])->update(['is_default' => false]);
        }

        Address::create($validated);

        return response()->json(['success' => true, 'message' => 'Address created successfully!']);
    }

    public function update(Request $request, $id)
    {
        $address = Address::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'nullable|string|max:255',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'required|string|max:255',
            'phone_number' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/',
            'is_default' => 'boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            Address::where('user_id', $validated['user_id'])
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        $address->update($validated);

        return response()->json(['success' => true, 'message' => 'Address updated successfully!']);
    }

    public function destroy($id)
    {
        $address = Address::findOrFail($id);
        $userId = $address->user_id;

        $address->delete();

        if ($address->is_default) {
            $newDefault = Address::where('user_id', $userId)->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Address deleted successfully!']);
    }
}
