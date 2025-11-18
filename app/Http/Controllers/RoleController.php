<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\JsonResponse;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View role|Create role|Update role|Delete role|Add user-role|Update user-role|Remove user-role', ['only' => ['index', 'show']]);
        $this->middleware('permission:Create role', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update role', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete role', ['only' => ['destroy', 'removeuserrole']]);
        $this->middleware('permission:Update user-role', ['only' => ['adduser', 'updateuserrole']]);
    }

    public function index(Request $request): View
    {
        $pagetitle = "Role Management";
        $roles = Role::orderBy('name', 'DESC')->get();
        $permission = Permission::get();

        $perm_title = Permission::pluck('title')->implode(',');
        $ex = explode(',', $perm_title);

        return view('roles.index', compact('roles', 'permission'))
            ->with('perm_title', $ex)
            ->with('pagetitle', $pagetitle);
    }

    public function create(): View
    {
        $permission = Permission::get();
        return view('roles.create', compact('permission'));
    }

    public function store(Request $request): RedirectResponse
    {
        $pagetitle = "Role Management";

        $request->validate([
            'name'       => 'required|unique:roles,name',
            'permission' => 'required|array',
            'permission.*' => 'exists:permissions,id',
            'title'      => 'nullable|string',
            'badge'      => 'nullable|string',
        ]);

        $role = Role::create($request->only(['name', 'title', 'badge']));
        $permissions = Permission::whereIn('id', $request->permission)->pluck('name')->toArray();
        $role->syncPermissions($permissions);

        return redirect()->route('roles.index')
            ->with('success', 'Role created successfully')
            ->with('pagetitle', $pagetitle);
    }

    public function show($id)
    {
        $pagetitle = "Role Management";
        $role = Role::findOrFail($id);

        // CRITICAL FIX: Do NOT select 'name' → it doesn't exist in DB
        // Just use whereHas + paginate → accessor will handle $user->name
        $usersWithRole = User::whereHas('roles', fn($q) => $q->where('id', $id))
            ->paginate(5);

        $userRoleCount = $usersWithRole->total();

        $rolePermissions  = $role->permissions;
        $rolePermissions2 = $role->permissions->pluck('id')->toArray();

        $permission = Permission::get();
        $perm_title = Permission::pluck('title')->implode(',');
        $ex = explode(',', $perm_title);

        Session::put('role_url', request()->fullUrl());

        return view('roles.show', compact(
            'role',
            'usersWithRole',
            'userRoleCount',
            'rolePermissions',
            'rolePermissions2',
            'pagetitle'
        ))->with('perm_title', $ex);
    }

    public function edit($id): View
    {
        $pagetitle = "Role Management";
        $role = Role::findOrFail($id);
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        $permission = Permission::get();

        $perm_title = Permission::pluck('title')->implode(',');
        $ex = explode(',', $perm_title);

        return view('roles.edit', compact('role', 'permission', 'rolePermissions'))
            ->with('perm_title', $ex)
            ->with('pagetitle', $pagetitle);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $pagetitle = "Role Management";

        $request->validate([
            'name'       => 'required|unique:roles,name,' . $id,
            'permission' => 'required|array',
            'permission.*' => 'exists:permissions,id',
            'badge'      => 'nullable|string',
        ]);

        $role = Role::findOrFail($id);
        $role->update($request->only(['name', 'badge']));

        $permissions = Permission::whereIn('id', $request->permission)->pluck('name')->toArray();
        $role->syncPermissions($permissions);

        return redirect(session('role_url') ?? route('roles.index'))
            ->with('success', 'Role updated successfully')
            ->with('pagetitle', $pagetitle);
    }

    public function adduser($id): View
    {
        $pagetitle = "Role Management";
        $role = Role::findOrFail($id);

        $users = User::whereDoesntHave('roles', function ($q) use ($role) {
            $q->where('name', $role->name);
        })->get();

        return view('roles.adduser', compact('role', 'users', 'pagetitle'));
    }

    public function updateuserrole(Request $request): RedirectResponse
    {
        $pagetitle = "Role Management";

        $request->validate([
            'users'   => 'required|array',
            'users.*' => 'exists:users,id',
            'roleid'  => 'required|exists:roles,id',
        ]);

        $role = Role::findOrFail($request->roleid);

        foreach ($request->users as $userId) {
            User::findOrFail($userId)->assignRole($role->name);
        }

        return redirect()->route('roles.show', $role->id)
            ->with('success', 'Users added to role successfully')
            ->with('pagetitle', $pagetitle);
    }

    public function removeuserrole($userid, $roleid): JsonResponse
    {
        try {
            $user = User::findOrFail($userid);
            $role = Role::findOrFail($roleid);
            $user->removeRole($role->name);

            return response()->json(['success' => true, 'message' => 'User role removed successfully']);
        } catch (\Exception $e) {
            \Log::error("Remove user role failed", [
                'user_id' => $userid,
                'role_id' => $roleid,
                'error'   => $e->getMessage()
            ]);

            return response()->json(['message' => 'Error removing user role'], 500);
        }
    }

    public function destroy($id): RedirectResponse
    {
        $pagetitle = "Role Management";
        Role::findOrFail($id)->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully')
            ->with('pagetitle', $pagetitle);
    }
}