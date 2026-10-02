@extends('layouts.master')
@section('title', $role->name . ' — Role')
@section('content')
@include('roles.partials.styles')

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="rol-hero">
        <h1><i class="bi bi-shield-fill-check me-2"></i>{{ $role->name }} — Role Details</h1>
        <p>Manage the permissions and users assigned to this role.</p>
        <div class="rol-hero-actions">
            <a href="{{ route('roles.index') }}" class="rol-btn rol-btn-back">
                <i class="bi bi-arrow-left"></i> Back to Roles
            </a>
            @canany(['Update user-role', 'Add user-role'])
            <button type="button" class="rol-btn rol-btn-success" data-bs-toggle="modal" data-bs-target="#addUserModalgrid">
                <i class="bi bi-person-plus-fill"></i> Add Users
            </button>
            @endcanany
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

    <div class="row g-3">

        {{-- ── Permissions sidebar ── --}}
        <div class="col-xl-3 col-lg-4">
            <div class="rol-card">
                <div class="rol-card-header">
                    <h5><i class="bi bi-key-fill me-2" style="color:var(--rol-accent)"></i>Permissions</h5>
                    <span class="rol-perm-tag m-0">{{ $rolePermissions->count() }}</span>
                </div>
                <div class="rol-card-body">
                    @can('Update role')
                    <button type="button" class="edit-role-btn" data-bs-toggle="modal" data-bs-target="#editRoleModalgrid">
                        <i class="bi bi-pencil-square"></i> Edit Role &amp; Permissions
                    </button>
                    @endcan
                    <div class="perm-list-scroll">
                        @forelse($rolePermissions->groupBy(fn ($p) => $p->title ?: 'Other')->sortKeys() as $group => $perms)
                            <div class="text-uppercase fw-bold mt-2 mb-1" style="font-size:10.5px;color:var(--rol-muted);letter-spacing:.5px;">{{ $group }}</div>
                            @foreach($perms as $rm)
                                <div class="perm-list-item">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>{{ $rm->name }}</span>
                                </div>
                            @endforeach
                        @empty
                            <p style="font-size:13px;color:var(--rol-muted);text-align:center;padding:20px 0;">No permissions assigned</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Users DataTable ── --}}
        <div class="col-xl-9 col-lg-8">
            <div class="rol-card">
                <div class="rol-card-header">
                    <h5>
                        <i class="bi bi-people-fill me-2" style="color:var(--rol-accent)"></i>
                        Assigned Users <span class="rol-count" id="totalUsersCount">{{ $userRoleCount }}</span>
                    </h5>
                    <div class="d-flex gap-2 align-items-center">
                        <select class="form-select form-select-sm" id="f-user-type" data-dt-filter="#roleUsersTable" style="width:auto;">
                            <option value="">All users</option>
                            <option value="staff">Staff</option>
                            <option value="customer">Customers</option>
                        </select>
                        @canany(['Remove user-role', 'Delete role'])
                        <button type="button" class="rol-btn rol-btn-danger" id="bulkRemoveBtn" style="display:none;padding:7px 14px;font-size:12.5px;">
                            <i class="bi bi-person-x-fill"></i> Remove Selected
                        </button>
                        @endcanany
                    </div>
                </div>
                <div class="rol-card-body gz-dt-wrap">
                    <table id="roleUsersTable" class="table gz-dt align-middle w-100 mb-0">
                        <thead>
                            <tr>
                                <th style="width:36px;"><input type="checkbox" class="form-check-input gz-check-all"></th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Joined</th>
                                <th style="width:70px;">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ════ ADD USERS MODAL (AJAX search — works with thousands of customers) ════ --}}
    @canany(['Update user-role', 'Add user-role'])
    <div class="modal fade rol-modal" id="addUserModalgrid" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i>Add Users to "{{ $role->name }}"</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addUserRoleForm" action="{{ route('roles.updateuserrole') }}" method="POST">
                    @csrf
                    <input type="hidden" name="roleid" value="{{ $role->id }}">
                    <div class="modal-body" style="min-height:320px;">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="rol-form-label">Search in</label>
                                <select id="candidate-type" class="rol-form-control">
                                    <option value="staff">Staff</option>
                                    <option value="customer">Customers</option>
                                    <option value="">Everyone</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="rol-form-label">Users <span class="text-danger">*</span></label>
                                <select id="candidate-users" name="users[]" multiple="multiple" class="rol-form-control"></select>
                            </div>
                        </div>
                        <div class="rol-info-banner mt-3 mb-0">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            Type a name, email or phone number. Only users who don't already have this role are listed.
                            Selected: <strong id="selected-count">0</strong>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="rol-btn rol-btn-primary" id="submit-add-btn">
                            <i class="bi bi-person-check-fill"></i> Add Selected Users
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcanany

    {{-- ════ EDIT ROLE MODAL ════ --}}
    @can('Update role')
    <div class="modal fade rol-modal" id="editRoleModalgrid" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Role: {{ $role->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('roles.update', $role->id) }}" method="POST">
                    @csrf @method('PATCH')
                    <div class="modal-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="rol-form-label">Role Name</label>
                                <input type="text" class="rol-form-control" name="name" value="{{ old('name', $role->name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="rol-form-label">Badge Colour</label>
                                <select name="badge" class="rol-form-control">
                                    <option value="">None</option>
                                    @foreach(['badge bg-light' => 'Light Grey', 'badge bg-dark' => 'Dark', 'badge bg-primary' => 'Blue', 'badge bg-secondary' => 'Light Blue', 'badge bg-success' => 'Green', 'badge bg-info' => 'Purple', 'badge bg-warning' => 'Yellow', 'badge bg-danger' => 'Red'] as $val => $label)
                                        <option value="{{ $val }}" @selected($role->badge === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <label class="rol-form-label mb-2">Permissions Assignment</label>
                        <div class="rol-info-banner" style="background:#f0f4ff;border-color:#c7d2fe;color:#3730a3;">
                            <i class="bi bi-info-circle-fill me-1"></i> Tick the permissions for this role. A group's checkbox selects everything under it at once.
                        </div>

                        @include('roles.partials.permission-picker', ['pickerId' => 'editPermPicker', 'checkedIds' => $role->permissions->pluck('id')->all()])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="rol-btn rol-btn-primary">
                            <i class="bi bi-check-circle"></i> Save Changes
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
    var roleId    = @json($role->id);
    var roleName  = @json($role->name);
    var tableSel  = '#roleUsersTable';

    // ── Users DataTable (server-side) ─────────────────────────────
    var table = GZ.dt(tableSel, {
        url: @json(route('roles.users', $role->id)),
        order: [[4, 'desc']],
        filters: function () { return { type: $('#f-user-type').val() }; },
        columns: [
            { data: 'checkbox',   name: 'checkbox',   orderable: false, searchable: false },
            { data: 'user_info',  name: 'user_info' },
            { data: 'email',      name: 'users.email' },
            { data: 'type',       name: 'users.role', searchable: false },
            { data: 'created_at', name: 'users.created_at', searchable: false },
            { data: 'action',     name: 'action',     orderable: false, searchable: false }
        ],
        onDraw: function (s) {
            if (s.json) { $('#totalUsersCount').text(s.json.recordsFiltered); }
            updateBulkBtn();
        }
    });

    // ── Single remove ─────────────────────────────────────────────
    $(tableSel).on('click', '.remove-user-btn', function () {
        var url = $(this).data('url'), name = $(this).data('name');
        Swal.fire({
            title: 'Remove user?', html: 'Remove <b>' + $('<i>').text(name).html() + '</b> from <b>' + $('<i>').text(roleName).html() + '</b>?',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, remove', reverseButtons: true
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({ url: url, type: 'POST', data: { _method: 'DELETE' } })
                .done(function (d) { GZ.toast(d.message || 'Removed'); table.ajax.reload(null, false); })
                .fail(function (x) { GZ.toast(GZ.xhrError(x), 'error'); });
        });
    });

    // ── Bulk remove ───────────────────────────────────────────────
    function updateBulkBtn() {
        var n = GZ.selected(tableSel).length, btn = $('#bulkRemoveBtn');
        btn.toggle(n > 0).html('<i class="bi bi-person-x-fill"></i> Remove Selected (' + n + ')');
    }
    $(tableSel).on('change gz:check', '.gz-row-check, .gz-check-all', updateBulkBtn);

    $('#bulkRemoveBtn').on('click', function () {
        var ids = GZ.selected(tableSel);
        if (!ids.length) return;
        Swal.fire({
            title: 'Remove ' + ids.length + ' user(s)?', text: 'They will lose every permission granted by "' + roleName + '".',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444',
            confirmButtonText: 'Remove all', reverseButtons: true
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.post(@json(route('roles.bulkremoveusers')), { role_id: roleId, selected_users: ids })
                .done(function (d) { GZ.toast(d.message, d.success ? 'success' : 'info'); table.ajax.reload(null, false); })
                .fail(function (x) { GZ.toast(GZ.xhrError(x), 'error'); });
        });
    });

    // ── Add users (Select2 AJAX) ──────────────────────────────────
    var $cand = $('#candidate-users');
    if ($cand.length) {
        $cand.select2({
            dropdownParent: $('#addUserModalgrid'),
            placeholder: 'Search users…',
            minimumInputLength: 0,
            closeOnSelect: false,
            ajax: {
                url: @json(route('roles.candidates', $role->id)),
                dataType: 'json', delay: 250,
                data: function (p) { return { q: p.term || '', page: p.page || 1, type: $('#candidate-type').val() }; },
                processResults: function (d) { return d; }
            },
            templateResult: function (u) {
                if (!u.id) return u.text;
                return $('<span>').text(u.text).append(u.type ? $('<small class="ms-2 text-muted">').text('· ' + u.type) : '');
            }
        }).on('change', function () { $('#selected-count').text(($cand.val() || []).length); });

        $('#candidate-type').on('change', function () { $cand.val(null).trigger('change'); });

        $('#addUserRoleForm').on('submit', function (e) {
            if (!($cand.val() || []).length) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'No users selected', text: 'Please select at least one user.', confirmButtonColor: '#6366f1' });
                return;
            }
            $('#submit-add-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Adding…');
        });

        // Opened from the roles list ("Add Users") → /roles/{id}?add=1
        if (new URLSearchParams(location.search).get('add') === '1') {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('addUserModalgrid')).show();
        }
    }

    // Re-open the edit modal after a validation error
    @if ($errors->has('name') || $errors->has('permission'))
        var em = document.getElementById('editRoleModalgrid');
        if (em) { bootstrap.Modal.getOrCreateInstance(em).show(); }
    @endif
});
</script>
@endsection
