@extends('layouts.master')
@section('title', 'Role Management')
@section('content')
@include('roles.partials.styles')

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="rol-hero">
        <h1><i class="bi bi-shield-lock-fill me-2"></i>Role Management</h1>
        <p>Define roles, assign permissions, and control what staff can access across the Gozak admin.</p>
        <div class="rol-hero-actions">
            @can('Create role')
            <button type="button" class="rol-btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModalgrid">
                <i class="bi bi-plus-circle"></i> Create Role
            </button>
            @endcan
            @can('View permission')
            <a href="{{ route('permissions.index') }}" class="rol-btn rol-btn-back">
                <i class="bi bi-key"></i> Permissions
            </a>
            @endcan
        </div>
    </div>

    {{-- Alerts --}}
    @if ($errors->any())
    <div class="rol-alert-danger mb-3">
        <strong>Whoops!</strong>
        <ul class="mb-0 mt-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif
    @if (session('success') || session('status'))
    <div class="rol-alert-success mb-3">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') ?? session('status') }}
    </div>
    @endif

    {{-- Role cards --}}
    @if($roles->count())
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xxl-4 g-3">
        @foreach ($roles as $role)
        @php
            $roleUsers        = $role->users_count;
            $role_permissions = $role->permissions->pluck('name')->sort()->take(4);
            $icons = [
                'super admin' => 'bi-shield-fill-check', 'admin' => 'bi-shield-check',
                'manager' => 'bi-briefcase', 'staff' => 'bi-person-badge',
                'salesperson' => 'bi-cart-check', 'sales-agent' => 'bi-cart-check', 'sales' => 'bi-cart-check',
                'inventory' => 'bi-box-seam', 'user' => 'bi-person', 'customer' => 'bi-person',
            ];
            $icon = $icons[strtolower($role->name)] ?? 'bi-key';
        @endphp
        <div class="col">
            <div class="rol-card hoverable">
                <div class="rol-card-top">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="rol-card-icon"><i class="bi {{ $icon }}"></i></div>
                        @canany(['Update user-role', 'Add user-role', 'View role', 'Delete role'])
                        <div class="dropdown rol-dropdown">
                            <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-label="Role actions">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @canany(['Update user-role', 'Add user-role'])
                                <li>
                                    <a class="dropdown-item" href="{{ route('roles.show', ['role' => $role->id, 'add' => 1]) }}">
                                        <i class="bi bi-person-plus-fill text-primary"></i> Add Users
                                    </a>
                                </li>
                                @endcanany
                                @can('View role')
                                <li>
                                    <a class="dropdown-item" href="{{ route('roles.show', $role->id) }}">
                                        <i class="bi bi-eye-fill text-success"></i> View Details
                                    </a>
                                </li>
                                @endcan
                                @can('Delete role')
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <form method="POST" action="{{ route('roles.destroy', $role->id) }}" class="js-delete-role" data-name="{{ $role->name }}" data-users="{{ $roleUsers }}">
                                        @csrf @method('DELETE')
                                        <button class="dropdown-item text-danger" type="submit">
                                            <i class="bi bi-trash3-fill"></i> Delete Role
                                        </button>
                                    </form>
                                </li>
                                @endcan
                            </ul>
                        </div>
                        @endcanany
                    </div>
                    <div class="rol-card-name mt-1">{{ $role->name }}</div>
                    <div class="rol-card-count">
                        <strong>{{ $roleUsers }}</strong> assigned user{{ $roleUsers !== 1 ? 's' : '' }}
                    </div>
                </div>

                <div class="rol-card-body">
                    <div style="font-size:10.5px;font-weight:700;color:var(--rol-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">
                        Permissions · {{ $role->permissions->count() }}
                    </div>
                    @forelse($role_permissions as $perm)
                        <span class="rol-perm-tag"><i class="bi bi-check2"></i>{{ $perm }}</span>
                    @empty
                        <span style="font-size:12px;color:var(--rol-muted);">No permissions assigned</span>
                    @endforelse
                    @if($role->permissions->count() > 4)
                        @can('View role')
                        <br><a href="{{ route('roles.show', $role->id) }}" class="rol-perm-more mt-1 d-inline-block">
                            +{{ $role->permissions->count() - 4 }} more →
                        </a>
                        @endcan
                    @endif
                </div>

                <div class="rol-card-footer">
                    <span class="rol-users-badge">
                        <i class="bi bi-people-fill"></i> {{ $roleUsers }} user{{ $roleUsers !== 1 ? 's' : '' }}
                    </span>
                    @can('View role')
                    <a href="{{ route('roles.show', $role->id) }}" class="rol-view-link">
                        View <i class="bi bi-arrow-right"></i>
                    </a>
                    @endcan
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="rol-empty">
        <span class="rol-empty-icon"><i class="bi bi-shield-x"></i></span>
        <h4>No Roles Created Yet</h4>
        <p>Click "Create Role" to define your first role and assign permissions.</p>
    </div>
    @endif

    {{-- ════ ADD ROLE MODAL ════ --}}
    @can('Create role')
    <div class="modal fade rol-modal" id="addRoleModalgrid" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle-fill me-2"></i>Create New Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="kt_modal_add_role_form" action="{{ route('roles.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="rol-form-label">Role Name <span class="text-danger">*</span></label>
                                <input type="text" class="rol-form-control" name="name" value="{{ old('name') }}" placeholder="e.g. Store Manager" required>
                            </div>
                            <div class="col-md-6">
                                <label class="rol-form-label">Role Badge Colour</label>
                                <select name="badge" class="rol-form-control">
                                    <option value="">Select colour…</option>
                                    <option value="badge bg-light">Light Grey</option>
                                    <option value="badge bg-dark">Dark</option>
                                    <option value="badge bg-primary">Blue</option>
                                    <option value="badge bg-secondary">Light Blue</option>
                                    <option value="badge bg-success">Green</option>
                                    <option value="badge bg-info">Purple</option>
                                    <option value="badge bg-warning">Yellow</option>
                                    <option value="badge bg-danger">Red</option>
                                </select>
                            </div>
                        </div>

                        <div class="rol-info-banner" style="background:#f0f4ff;border-color:#c7d2fe;color:#3730a3;">
                            <i class="bi bi-info-circle-fill me-1"></i> Tick the permissions this role should have. Use a group's checkbox to select everything under it at once, or the search box to find one quickly.
                        </div>

                        @include('roles.partials.permission-picker', ['pickerId' => 'createPermPicker', 'checkedIds' => old('permission', [])])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="rol-btn-primary">
                            <i class="bi bi-check-circle"></i> Create Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan

</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Re-open the create modal after a validation error
    @if ($errors->any() && old('name') !== null)
        var m = document.getElementById('addRoleModalgrid');
        if (m && window.bootstrap) { bootstrap.Modal.getOrCreateInstance(m).show(); }
    @endif

    // Confirm role deletion
    document.querySelectorAll('form.js-delete-role').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var users = parseInt(form.dataset.users || '0', 10);
            var text  = users > 0
                ? users + ' user(s) currently have this role and will lose its permissions.'
                : 'This action cannot be undone.';
            Swal.fire({
                title: 'Delete role "' + form.dataset.name + '"?',
                text: text, icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, delete', reverseButtons: true
            }).then(function (r) { if (r.isConfirmed) { form.submit(); } });
        });
    });
});
</script>
@endsection
