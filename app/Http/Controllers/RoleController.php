<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Yajra\DataTables\Facades\DataTables;

/**
 * Role management — ported from the CSS Kabba portal (improved version):
 *  - role cards with user counts (single query, no N+1)
 *  - grouped, searchable permission picker (roles.partials.permission-picker)
 *  - server-side DataTable of the users in a role, with single + bulk removal
 *  - searchable (AJAX) user picker for adding users, so large customer tables
 *    are never loaded into the page
 */
class RoleController extends Controller
{
    /** Roles that may never be deleted from the UI. */
    protected const PROTECTED_ROLES = ['Super Admin', 'Admin', 'App Users'];

    public function __construct()
    {
        $this->middleware('permission:View role|Create role|Update role|Delete role|Add user-role|Update user-role|Remove user-role', ['only' => ['index', 'show', 'users']]);
        $this->middleware('permission:Create role', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update role', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete role', ['only' => ['destroy']]);
        $this->middleware('permission:Update user-role|Add user-role', ['only' => ['adduser', 'updateuserrole', 'candidates']]);
        $this->middleware('permission:Remove user-role|Delete role', ['only' => ['removeuserrole', 'bulkRemoveUsers']]);
    }

    // =========================================================================
    // INDEX — role cards
    // =========================================================================

    public function index(Request $request): View
    {
        $pagetitle = 'Role Management';

        $roles = Role::withCount('users')
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get();

        return view('roles.index', compact('roles', 'pagetitle'));
    }

    public function create(): RedirectResponse
    {
        // Creation happens in the modal on the index page.
        return redirect()->route('roles.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'         => 'required|string|max:125|unique:roles,name',
            'permission'   => 'required|array',
            'permission.*' => 'exists:permissions,id',
            'title'        => 'nullable|string|max:255',
            'badge'        => 'nullable|string|max:125',
        ]);

        DB::transaction(function () use ($request) {
            $role = Role::create([
                'name'       => trim($request->input('name')),
                'guard_name' => 'web',
                'title'      => $request->input('title'),
                'badge'      => $request->input('badge'),
            ]);

            $role->syncPermissions(Permission::whereIn('id', $request->input('permission'))->get());
        });

        $this->flushPermissionCache();

        return redirect()->route('roles.index')->with('success', 'Role created successfully');
    }

    // =========================================================================
    // SHOW — role details, permissions, users DataTable
    // =========================================================================

    public function show($id): View
    {
        $pagetitle = 'Role Management';

        $role            = Role::with('permissions')->findOrFail($id);
        $rolePermissions = $role->permissions->sortBy('name')->values();
        $userRoleCount   = $role->users()->count();

        Session::put('role_url', request()->fullUrl());

        return view('roles.show', compact('role', 'rolePermissions', 'userRoleCount', 'pagetitle'));
    }

    /** Yajra DataTable — users assigned to a role. */
    public function users(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $query = User::query()
            ->select(['users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.phone_number', 'users.role', 'users.profile_image', 'users.created_at'])
            ->whereHas('roles', fn ($q) => $q->where('roles.id', $role->id))
            ->when($request->get('type') === 'customer', fn ($q) => $q->where('users.role', 'user'))
            ->when($request->get('type') === 'staff', fn ($q) => $q->where(fn ($w) => $w->where('users.role', '!=', 'user')->orWhereNull('users.role')));

        $canRemove = $request->user()->can('Remove user-role') || $request->user()->can('Delete role');

        return DataTables::eloquent($query)
            ->addColumn('checkbox', fn ($u) => '<input type="checkbox" class="form-check-input gz-row-check" value="' . $u->id . '" data-name="' . e($u->name) . '">')
            ->addColumn('user_info', function ($u) {
                $initials = collect(explode(' ', trim($u->name) ?: 'U'))->map(fn ($w) => strtoupper(substr($w, 0, 1)))->take(2)->implode('');
                $avatar   = $u->profile_image
                    ? '<img src="' . e(str_starts_with($u->profile_image, 'http') ? $u->profile_image : asset('storage/' . ltrim($u->profile_image, '/'))) . '" class="gz-avatar" style="object-fit:cover;" onerror="this.replaceWith(Object.assign(document.createElement(\'span\'),{className:\'gz-avatar\',textContent:\'' . e($initials) . '\'}))">'
                    : '<span class="gz-avatar">' . e($initials) . '</span>';

                return '<div class="d-flex align-items-center gap-2">' . $avatar .
                    '<div><a href="' . route('users.overview', $u->id) . '" class="fw-semibold text-reset">' . e($u->name ?: '—') . '</a>' .
                    '<small class="d-block text-muted">' . e($u->phone_number ?? '') . '</small></div></div>';
            })
            ->addColumn('type', fn ($u) => $u->role === 'user'
                ? '<span class="status-pill st-info">Customer</span>'
                : '<span class="status-pill st-violet">' . e(ucfirst($u->role ?: 'staff')) . '</span>')
            ->editColumn('created_at', fn ($u) => optional($u->created_at)->format('d M Y'))
            ->addColumn('action', function ($u) use ($role, $canRemove) {
                if (!$canRemove) {
                    return '';
                }
                return '<button type="button" class="btn btn-sm btn-soft-danger remove-user-btn" title="Remove from role" data-bs-toggle="tooltip"'
                    . ' data-url="' . route('roles.removeuserrole', ['userid' => $u->id, 'roleid' => $role->id]) . '"'
                    . ' data-name="' . e($u->name) . '"><i class="bi bi-person-x-fill"></i></button>';
            })
            ->filterColumn('user_info', function ($q, $keyword) {
                $q->where(function ($w) use ($keyword) {
                    $w->where('users.first_name', 'like', "%{$keyword}%")
                      ->orWhere('users.last_name', 'like', "%{$keyword}%")
                      ->orWhereRaw("CONCAT(users.first_name, ' ', users.last_name) like ?", ["%{$keyword}%"])
                      ->orWhere('users.phone_number', 'like', "%{$keyword}%");
                });
            })
            ->orderColumn('user_info', 'users.first_name $1, users.last_name $1')
            ->rawColumns(['checkbox', 'user_info', 'type', 'action'])
            ->toJson();
    }

    /** Select2 AJAX source — users NOT yet in the role. ?q=search&type=staff|customer&page=n */
    public function candidates(Request $request, $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $term = trim((string) $request->get('q', ''));
        $type = $request->get('type');

        $users = User::query()
            ->whereDoesntHave('roles', fn ($q) => $q->where('roles.id', $role->id))
            ->when($type === 'customer', fn ($q) => $q->where('role', 'user'))
            ->when($type === 'staff', fn ($q) => $q->where(fn ($w) => $w->where('role', '!=', 'user')->orWhereNull('role')))
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($w) use ($term) {
                    $w->where('first_name', 'like', "%{$term}%")
                      ->orWhere('last_name', 'like', "%{$term}%")
                      ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$term}%"])
                      ->orWhere('email', 'like', "%{$term}%")
                      ->orWhere('phone_number', 'like', "%{$term}%");
                });
            })
            ->orderBy('first_name')
            ->paginate(20, ['id', 'first_name', 'last_name', 'email', 'phone_number', 'role']);

        return response()->json([
            'results' => $users->getCollection()->map(fn ($u) => [
                'id'   => $u->id,
                'text' => trim($u->name) . ($u->email ? ' — ' . $u->email : ($u->phone_number ? ' — ' . $u->phone_number : '')),
                'type' => $u->role === 'user' ? 'Customer' : ucfirst($u->role ?: 'staff'),
            ])->values(),
            'pagination' => ['more' => $users->hasMorePages()],
        ]);
    }

    public function edit($id): RedirectResponse
    {
        // Editing happens in the modal on the show page.
        return redirect()->route('roles.show', $id);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name'         => 'required|string|max:125|unique:roles,name,' . $role->id,
            'permission'   => 'required|array',
            'permission.*' => 'exists:permissions,id',
            'badge'        => 'nullable|string|max:125',
        ]);

        if (in_array($role->name, self::PROTECTED_ROLES, true) && $request->input('name') !== $role->name) {
            return back()->withErrors(['name' => "The {$role->name} role cannot be renamed."]);
        }

        DB::transaction(function () use ($request, $role) {
            $role->update([
                'name'  => trim($request->input('name')),
                'badge' => $request->input('badge'),
            ]);
            $role->syncPermissions(Permission::whereIn('id', $request->input('permission'))->get());
        });

        $this->flushPermissionCache();

        return redirect(session('role_url') ?: route('roles.show', $role->id))
            ->with('success', 'Role updated successfully');
    }

    public function adduser($id): RedirectResponse
    {
        // The add-user picker now lives on the role page; ?add=1 opens it.
        return redirect()->route('roles.show', ['role' => $id, 'add' => 1]);
    }

    public function updateuserrole(Request $request): RedirectResponse
    {
        $request->validate([
            'users'   => 'required|array|min:1',
            'users.*' => 'exists:users,id',
            'roleid'  => 'required|exists:roles,id',
        ]);

        $role  = Role::findOrFail($request->input('roleid'));
        $added = 0;

        User::whereIn('id', $request->input('users'))->get()->each(function (User $user) use ($role, &$added) {
            if (!$user->hasRole($role)) {
                $user->assignRole($role);
                $added++;
            }
        });

        $this->flushPermissionCache();

        return redirect()->route('roles.show', $role->id)
            ->with('success', "{$added} user(s) added to the {$role->name} role");
    }

    public function removeuserrole(Request $request, $userid, $roleid): JsonResponse
    {
        try {
            $user = User::findOrFail($userid);
            $role = Role::findOrFail($roleid);

            if ($user->id === $request->user()->id && in_array($role->name, self::PROTECTED_ROLES, true)) {
                return response()->json(['success' => false, 'message' => "You cannot remove yourself from the {$role->name} role."], 422);
            }

            $user->removeRole($role);
            $this->flushPermissionCache();

            return response()->json(['success' => true, 'message' => "{$user->name} removed from {$role->name}"]);
        } catch (\Throwable $e) {
            Log::error('Remove user role failed', ['user_id' => $userid, 'role_id' => $roleid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error removing user role'], 500);
        }
    }

    public function bulkRemoveUsers(Request $request): JsonResponse
    {
        $request->validate([
            'role_id'          => 'required|exists:roles,id',
            'selected_users'   => 'required|array|min:1',
            'selected_users.*' => 'exists:users,id',
        ]);

        try {
            $role    = Role::findOrFail($request->role_id);
            $removed = [];

            User::whereIn('id', $request->selected_users)->get()->each(function (User $user) use ($role, $request, &$removed) {
                if ($user->id === $request->user()->id && in_array($role->name, self::PROTECTED_ROLES, true)) {
                    return; // never lock yourself out
                }
                if ($user->hasRole($role)) {
                    $user->removeRole($role);
                    $removed[] = $user->name;
                }
            });

            $this->flushPermissionCache();

            return response()->json([
                'success'       => count($removed) > 0,
                'message'       => count($removed)
                    ? 'Removed ' . count($removed) . " user(s) from the {$role->name} role."
                    : 'No users were removed.',
                'removed_count' => count($removed),
            ]);
        } catch (\Throwable $e) {
            Log::error('Bulk remove users error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to remove users'], 500);
        }
    }

    public function destroy($id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return redirect()->route('roles.index')->withErrors(['role' => "The {$role->name} role cannot be deleted."]);
        }

        $role->delete();
        $this->flushPermissionCache();

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully');
    }

    protected function flushPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
