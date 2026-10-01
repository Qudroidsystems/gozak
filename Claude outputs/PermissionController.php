<?php

namespace App\Http\Controllers;

use App\Support\PermissionMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Yajra\DataTables\Facades\DataTables;

/**
 * Permission management — Yajra server-side table with group (title) and
 * description support, ported from the CSS Kabba portal.
 */
class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View permission|Create permission|Update permission|Delete permission', ['only' => ['index', 'data', 'show']]);
        $this->middleware('permission:Create permission', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update permission', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete permission', ['only' => ['destroy']]);
    }

    public function index()
    {
        $pagetitle = 'Permission Management';

        $groups = Permission::query()->whereNotNull('title')->distinct()->orderBy('title')->pluck('title');
        $stats  = [
            'total'      => Permission::count(),
            'groups'     => $groups->count(),
            'unassigned' => Permission::doesntHave('roles')->count(),
        ];

        return view('permissions.index', compact('pagetitle', 'groups', 'stats'));
    }

    /** Yajra DataTable endpoint. */
    public function data(Request $request)
    {
        $query = Permission::query()
            ->select(['permissions.id', 'permissions.name', 'permissions.title', 'permissions.description', 'permissions.guard_name', 'permissions.created_at'])
            ->with('roles:id,name,badge')
            ->when($request->filled('group'), fn ($q) => $request->group === '__none'
                ? $q->whereNull('permissions.title')
                : $q->where('permissions.title', $request->group));

        $user = $request->user();

        return DataTables::eloquent($query)
            ->addColumn('checkbox', fn ($p) => '<input type="checkbox" class="form-check-input gz-row-check" value="' . $p->id . '">')
            ->editColumn('name', function ($p) {
                $m = PermissionMeta::for($p->name);
                return '<div class="d-flex align-items-center gap-2">'
                    . '<span class="pm-chip ' . PermissionMeta::chipClass($m['action']) . '">' . e($m['action']) . '</span>'
                    . '<div><span class="fw-semibold">' . e($p->name) . '</span>'
                    . '<small class="d-block text-muted">' . e($p->description ?: $m['desc']) . '</small></div></div>';
            })
            ->editColumn('title', fn ($p) => $p->title
                ? '<span class="session-badge">' . e($p->title) . '</span>'
                : '<span class="text-muted small">Ungrouped</span>')
            ->addColumn('roles_list', function ($p) {
                if ($p->roles->isEmpty()) {
                    return '<span class="text-muted small">—</span>';
                }
                return $p->roles->map(fn ($r) => '<a href="' . route('roles.show', $r->id) . '" class="' . e($r->badge ?: 'badge bg-primary-subtle text-primary') . ' me-1 mb-1">' . e($r->name) . '</a>')->implode('');
            })
            ->editColumn('created_at', fn ($p) => optional($p->created_at)->format('d M Y'))
            ->addColumn('action', function ($p) use ($user) {
                $b = '<div class="gz-actions">';
                if ($user->can('Update permission')) {
                    $b .= '<button type="button" class="btn btn-sm btn-soft-secondary edit-perm-btn" title="Edit" data-bs-toggle="tooltip"'
                        . ' data-id="' . $p->id . '" data-name="' . e($p->name) . '" data-title="' . e($p->title) . '" data-description="' . e($p->description) . '"'
                        . ' data-url="' . route('permissions.update', $p->id) . '"><i class="ph-pencil"></i></button>';
                }
                if ($user->can('Delete permission')) {
                    $b .= '<button type="button" class="btn btn-sm btn-soft-danger delete-perm-btn" title="Delete" data-bs-toggle="tooltip"'
                        . ' data-name="' . e($p->name) . '" data-url="' . route('permissions.destroy', $p->id) . '"><i class="ph-trash"></i></button>';
                }
                return $b . '</div>';
            })
            ->filterColumn('roles_list', function ($q, $keyword) {
                $q->whereHas('roles', fn ($r) => $r->where('name', 'like', "%{$keyword}%"));
            })
            ->rawColumns(['checkbox', 'name', 'title', 'roles_list', 'action'])
            ->toJson();
    }

    public function create()
    {
        return redirect()->route('permissions.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:125|unique:permissions,name',
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $permission = Permission::create($data + ['guard_name' => 'web']);
        $this->flushPermissionCache();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Permission created successfully.', 'id' => $permission->id]);
        }

        return redirect()->route('permissions.index')->with('success', 'Permission created successfully.');
    }

    public function show($id)
    {
        return redirect()->route('permissions.index');
    }

    public function edit($id)
    {
        return redirect()->route('permissions.index');
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);

        $data = $request->validate([
            'name'        => 'required|string|max:125|unique:permissions,name,' . $permission->id,
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $permission->update($data);
        $this->flushPermissionCache();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Permission updated successfully.']);
        }

        return redirect()->route('permissions.index')->with('success', 'Permission updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);

        DB::transaction(fn () => $permission->delete());
        $this->flushPermissionCache();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Permission deleted successfully.']);
        }

        return redirect()->route('permissions.index')->with('success', 'Permission deleted successfully.');
    }

    protected function flushPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
