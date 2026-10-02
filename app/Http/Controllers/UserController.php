<?php

namespace App\Http\Controllers;

use Hash;
use App\Models\User;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View user|Create user|Update user|Delete user', ['only' => ['index', 'data']]);
        $this->middleware('permission:Create user', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update user', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete user', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $pagetitle = "User Management";

        $roles = Role::orderBy('name')->pluck('name', 'name')->toArray();

        // One grouped query instead of one count per role
        $role_counts = Role::withCount('users')->orderBy('name')->pluck('users_count', 'name')->toArray();
        $role_counts['No Role'] = User::doesntHave('roles')->count();

        $stats = [
            'total'     => User::count(),
            'staff'     => User::where(fn ($q) => $q->where('role', '!=', 'user')->orWhereNull('role'))->count(),
            'customers' => User::where('role', 'user')->count(),
            'no_role'   => $role_counts['No Role'],
        ];

        return view('users.index', compact('roles', 'pagetitle', 'role_counts', 'stats'));
    }

    /** Yajra DataTable endpoint. */
    public function data(Request $request)
    {
        $query = User::query()
            ->select(['users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.phone_number', 'users.role', 'users.profile_image', 'users.created_at'])
            ->with('roles:id,name,badge')
            ->when($request->filled('role'), fn ($q) => $request->role === '__none'
                ? $q->doesntHave('roles')
                : $q->whereHas('roles', fn ($r) => $r->where('name', $request->role)))
            ->when($request->type === 'customer', fn ($q) => $q->where('users.role', 'user'))
            ->when($request->type === 'staff', fn ($q) => $q->where(fn ($w) => $w->where('users.role', '!=', 'user')->orWhereNull('users.role')));

        $me = $request->user();

        return DataTables::eloquent($query)
            ->addColumn('checkbox', fn ($u) => $u->id === $me->id ? '' : '<input type="checkbox" class="form-check-input gz-row-check" value="' . $u->id . '">')
            ->addColumn('user', function ($u) {
                $initials = strtoupper(substr($u->first_name ?: 'U', 0, 1) . substr($u->last_name ?: '', 0, 1));
                $src = $u->profile_image ? (str_starts_with($u->profile_image, 'http') ? $u->profile_image : asset('storage/' . $u->profile_image)) : null;
                $avatar = $src
                    ? '<img src="' . e($src) . '" class="gz-avatar" style="object-fit:cover;" alt="">'
                    : '<span class="gz-avatar">' . e($initials) . '</span>';
                return '<div class="d-flex align-items-center gap-2">' . $avatar
                    . '<div><a href="' . route('users.show', $u->id) . '" class="fw-semibold text-reset">' . e(trim($u->first_name . ' ' . $u->last_name) ?: '—') . '</a>'
                    . '<small class="d-block text-muted">' . e($u->phone_number ?? '') . '</small></div></div>';
            })
            ->addColumn('roles_list', function ($u) {
                if ($u->roles->isEmpty()) {
                    return '<span class="badge bg-secondary-subtle text-secondary">No role</span>';
                }
                return $u->roles->map(fn ($r) => '<span class="' . e($r->badge ?: 'badge bg-primary-subtle text-primary') . ' me-1">' . e($r->name) . '</span>')->implode('');
            })
            ->addColumn('type', fn ($u) => $u->role === 'user'
                ? '<span class="status-pill st-info">Customer</span>'
                : '<span class="status-pill st-violet">' . e(ucfirst($u->role ?: 'staff')) . '</span>')
            ->editColumn('created_at', fn ($u) => optional($u->created_at)->format('d M Y'))
            ->addColumn('action', function ($u) use ($me) {
                $h = '<div class="gz-actions">';
                if ($me->can('View user')) {
                    $h .= '<a href="' . route('users.show', $u->id) . '" class="btn btn-sm btn-soft-primary" title="View"><i class="ph-eye"></i></a>';
                }
                if ($me->can('Update user')) {
                    $h .= '<button class="btn btn-sm btn-soft-secondary edit-item-btn" data-id="' . $u->id . '" title="Edit"><i class="ph-pencil"></i></button>';
                }
                if ($me->can('Delete user') && $u->id !== $me->id) {
                    $h .= '<button class="btn btn-sm btn-soft-danger remove-item-btn" data-id="' . $u->id . '" data-name="' . e($u->name) . '" title="Delete"><i class="ph-trash"></i></button>';
                }
                return $h . '</div>';
            })
            ->filterColumn('user', function ($q, $k) {
                $q->where(fn ($w) => $w->where('users.first_name', 'like', "%{$k}%")
                    ->orWhere('users.last_name', 'like', "%{$k}%")
                    ->orWhere('users.phone_number', 'like', "%{$k}%")
                    ->orWhereRaw("CONCAT(users.first_name, ' ', users.last_name) LIKE ?", ["%{$k}%"]));
            })
            ->filterColumn('roles_list', fn ($q, $k) => $q->whereHas('roles', fn ($r) => $r->where('name', 'like', "%{$k}%")))
            ->orderColumn('user', 'users.first_name $1, users.last_name $1')
            ->rawColumns(['checkbox', 'user', 'roles_list', 'type', 'action'])
            ->toJson();
    }

    public function create(): View
    {
        $title = "Create User";
        $roles = Role::pluck('name', 'name')->all();
        return view('users.create', compact('roles', 'title'));
    }

    public function show($id): View
    {
        $pagetitle = "User Management";
        $user = User::with('roles')->findOrFail($id);

        return view('users.show', compact('user','pagetitle'));
    }

        public function overview($id): View
    {
        $pagetitle = "User Management";
        $user = User::with('roles')->findOrFail($id);

        return view('users.overview', compact('user','pagetitle'));
    }

    public function store(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermissionTo('Create user')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create users',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|email:rfc,dns|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'roles'    => 'required|array|min:1',
                'roles.*'  => 'exists:roles,name',
            ]);

            // Split full name into first_name and last_name
            $nameParts = explode(' ', trim($validated['name']), 2);
            $firstName = $nameParts[0] ?? '';
            $lastName  = isset($nameParts[1]) ? $nameParts[1] : '';

            $user = User::create([
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'username'   => $validated['email'], // Set username = email on create
                'email'      => $validated['email'],
                'password'   => Hash::make($validated['password']),
            ]);

            $user->assignRole($validated['roles']);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                ],
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error("User creation failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
            ], 500);
        }
    }

    public function edit(Request $request, $id)
    {
        $user = User::with('roles:id,name')->findOrFail($id);

        if ($request->expectsJson()) {
            return response()->json([
                'id'           => $user->id,
                'first_name'   => $user->first_name,
                'last_name'    => $user->last_name,
                'email'        => $user->email,
                'phone_number' => $user->phone_number,
                'roles'        => $user->roles->pluck('name'),
            ]);
        }

        return redirect()->route('users.show', $user->id);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            // Validation rules
            $validated = $request->validate([
                'first_name'       => 'required|string|max:255',
                'last_name'        => 'nullable|string|max:255',
                'email'            => 'required|email|unique:users,email,' . $id,
                'phone_number'     => 'nullable|string|max:20',
                'gender'           => 'nullable|in:male,female,other',
                'date_of_birth'    => 'nullable|date|before:today',
                'password'         => 'nullable|confirmed|min:8',
                'profile_image'    => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // 2MB max
            ]);

            // Prepare input data
            $input = [
                'first_name'   => $validated['first_name'],
                'last_name'    => $validated['last_name'] ?? '',
                'email'        => $validated['email'],
                'username'     => $validated['email'], // Keep username = email
                'phone_number' => $validated['phone_number'] ?? null,
                'gender'       => $validated['gender'] ?? null,
                'date_of_birth'=> $validated['date_of_birth'] ?? null,
            ];

            // Handle password update
            if ($request->filled('password')) {
                $input['password'] = Hash::make($validated['password']);
            }

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                // Delete old image if exists
                if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                    Storage::disk('public')->delete($user->profile_image);
                }

                // Store new image
                $path = $request->file('profile_image')->store('profile_images', 'public');
                $input['profile_image'] = $path;
            }

            // Update user
            $user->update($input);

            // Role changes from the admin Users table (sent only by that modal)
            if ($request->has('roles') && (auth()->user()->can('Update user-role') || auth()->user()->can('Update role'))) {
                $roles = array_values(array_filter((array) $request->input('roles', [])));
                $known = Role::whereIn('name', $roles)->pluck('name')->all();
                if (count($known) !== count($roles)) {
                    return response()->json(['success' => false, 'message' => 'One or more roles do not exist.'], 422);
                }
                if ($user->id === auth()->id() && $user->hasRole(['Super Admin', 'Admin'])
                    && !collect($roles)->intersect(['Super Admin', 'Admin'])->count()) {
                    return response()->json(['success' => false, 'message' => 'You cannot remove your own admin role.'], 422);
                }
                $user->syncRoles($roles);
            }

            // Reload roles for response
            $user->load('roles');

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => [
                    'id'             => $user->id,
                    'name'           => $user->name,
                    'first_name'     => $user->first_name,
                    'last_name'      => $user->last_name,
                    'email'          => $user->email,
                    'phone_number'   => $user->phone_number,
                    'gender'         => $user->gender,
                    'date_of_birth'  => $user->date_of_birth?->format('Y-m-d'),
                    'profile_image'  => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                    'roles'          => $user->roles->pluck('name')->toArray(),
                    'updated_at'     => $user->updated_at->format('d M Y, H:i'),
                ],
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error("User profile update failed (ID: $id): " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile. Please try again.',
            ], 500);
        }
    }


    public function destroy($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            if ($user->id === auth()->id()) {
                return response()->json(['success' => false, 'message' => 'You cannot delete your own account.'], 422);
            }
            $user->roles()->detach();
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error("User delete failed (ID: $id): " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
            ], 500);
        }
    }
}
