@extends('layouts.master')
@section('title', 'User Management')
@section('content')

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero title="Users" icon="ri-user-settings-line" subtitle="Admin staff and app customers, with the roles that control what they can do.">
        <x-slot:actions>
            @can('View role')
                <a href="{{ route('roles.index') }}" class="cb-hero-btn"><i class="ri-shield-keyhole-line"></i> Roles</a>
            @endcan
            @can('Create user')
                <button type="button" class="cb-hero-btn add-btn"><i class="ri-user-add-line"></i> Add User</button>
            @endcan
        </x-slot:actions>
    </x-cb.hero>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6"><x-cb.stat label="All users" :value="number_format($stats['total'])" icon="ri-group-line" accent="sky" /></div>
        <div class="col-xl-3 col-md-6"><x-cb.stat label="Staff" :value="number_format($stats['staff'])" icon="ri-user-star-line" accent="violet" /></div>
        <div class="col-xl-3 col-md-6"><x-cb.stat label="Customers" :value="number_format($stats['customers'])" icon="ri-user-heart-line" accent="green" /></div>
        <div class="col-xl-3 col-md-6"><x-cb.stat label="Without a role" :value="number_format($stats['no_role'])" icon="ri-user-unfollow-line" accent="amber" /></div>
    </div>

    <x-cb.card title="Users by Role" icon="ri-bar-chart-2-line">
        <div style="height:220px;"><canvas id="usersByRoleChart"></canvas></div>
    </x-cb.card>

    <x-cb.card title="All Users" icon="ri-list-check" :flush="true">
        <x-slot:tools>
            <select id="f-role" class="form-select form-select-sm" data-dt-filter="#usersTable" style="width:auto;">
                <option value="">All roles</option>
                @foreach ($roles as $role => $name)<option value="{{ $role }}">{{ $name }}</option>@endforeach
                <option value="__none">No role</option>
            </select>
            <select id="f-type" class="form-select form-select-sm" data-dt-filter="#usersTable" style="width:auto;">
                <option value="">Staff &amp; customers</option>
                <option value="staff">Staff</option>
                <option value="customer">Customers</option>
            </select>
            @can('Delete user')
                <button class="btn btn-sm btn-danger d-none" id="remove-actions"><i class="ri-delete-bin-2-line me-1"></i> Delete selected</button>
            @endcan
        </x-slot:tools>
        <div class="p-3 gz-dt-wrap">
            <table id="usersTable" class="table gz-dt align-middle w-100 mb-0">
                <thead>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" class="form-check-input gz-check-all"></th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th>Type</th>
                        <th>Registered</th>
                        <th style="width:110px;">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-cb.card>

</div>
</div>

<!-- Add User Modal -->
<div id="showModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form autocomplete="off" id="add-user-form">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full name</label>
                        <input type="text" name="name" class="form-control" placeholder="First and last name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Roles</label>
                        <select id="add-roles" name="roles[]" class="form-select" multiple required>
                            @foreach ($roles as $role => $name)<option value="{{ $role }}">{{ $name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" minlength="8" required></div>
                        <div class="col-md-6"><label class="form-label">Confirm password</label><input type="password" name="password_confirmation" class="form-control" minlength="8" required></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="add-btn">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form autocomplete="off" id="edit-user-form">
                <input type="hidden" id="edit-id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">First name</label><input type="text" name="first_name" id="edit-first_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Last name</label><input type="text" name="last_name" id="edit-last_name" class="form-control"></div>
                        <div class="col-md-7"><label class="form-label">Email</label><input type="email" name="email" id="edit-email" class="form-control" required></div>
                        <div class="col-md-5"><label class="form-label">Phone</label><input type="text" name="phone_number" id="edit-phone_number" class="form-control"></div>
                        @canany(['Update user-role', 'Update role'])
                        <div class="col-12">
                            <label class="form-label">Roles</label>
                            <select id="edit-roles" name="roles[]" class="form-select" multiple>
                                @foreach ($roles as $role => $name)<option value="{{ $role }}">{{ $name }}</option>@endforeach
                            </select>
                        </div>
                        @endcanany
                        <div class="col-md-6"><label class="form-label">New password <small class="text-muted">(optional)</small></label><input type="password" name="password" class="form-control" minlength="8"></div>
                        <div class="col-md-6"><label class="form-label">Confirm password</label><input type="password" name="password_confirmation" class="form-control" minlength="8"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="update-btn">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var URLS = {
        data:    @json(route('users.data')),
        store:   @json(route('users.store')),
        edit:    @json(route('users.edit', '__ID__')),
        update:  @json(route('users.update', '__ID__')),
        destroy: @json(route('users.destroy', '__ID__'))
    };
    var u = function (k, id) { return URLS[k].replace('__ID__', id); };

    new Chart(document.getElementById('usersByRoleChart'), {
        type: 'bar',
        data: { labels: @json(array_keys($role_counts)), datasets: [{ label: 'Users', data: @json(array_values($role_counts)), backgroundColor: '#0d9488', borderRadius: 6 }] },
        options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { display: false } } }
    });

    var table = GZ.dt('#usersTable', {
        url: URLS.data,
        order: [[5, 'desc']],
        filters: function () { return { role: $('#f-role').val(), type: $('#f-type').val() }; },
        columns: [
            { data: 'checkbox',   name: 'checkbox', orderable: false, searchable: false },
            { data: 'user',       name: 'user' },
            { data: 'email',      name: 'users.email' },
            { data: 'roles_list', name: 'roles_list', orderable: false },
            { data: 'type',       name: 'users.role', searchable: false },
            { data: 'created_at', name: 'users.created_at', searchable: false },
            { data: 'action',     name: 'action', orderable: false, searchable: false }
        ],
        onDraw: toggleBulk
    });
    function toggleBulk() { $('#remove-actions').toggleClass('d-none', GZ.selected('#usersTable').length === 0); }
    $('#usersTable').on('change gz:check', '.gz-row-check, .gz-check-all', toggleBulk);

    // Role pickers
    var addModal  = bootstrap.Modal.getOrCreateInstance(document.getElementById('showModal'));
    var editModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal'));
    $('#add-roles').select2({ dropdownParent: $('#showModal'), width: '100%', placeholder: 'Select roles' });
    $('#edit-roles').select2({ dropdownParent: $('#editModal'), width: '100%', placeholder: 'Select roles' });

    function sendForm($form, url, method, modal, $btn) {
        var data = $form.serializeArray();
        if (method !== 'POST') { data.push({ name: '_method', value: method }); }
        $btn.prop('disabled', true);
        $.post(url, $.param(data))
            .done(function (r) { modal.hide(); GZ.toast(r.message || 'Saved'); table.ajax.reload(null, false); })
            .fail(function (x) { Swal.fire({ icon: 'error', title: 'Could not save', text: GZ.xhrError(x) }); })
            .always(function () { $btn.prop('disabled', false); });
    }

    $('.add-btn').on('click', function () {
        $('#add-user-form')[0].reset(); $('#add-roles').val(null).trigger('change');
        addModal.show();
    });
    $('#add-user-form').on('submit', function (e) { e.preventDefault(); sendForm($(this), URLS.store, 'POST', addModal, $('#add-btn')); });

    $('#usersTable').on('click', '.edit-item-btn', function () {
        $.ajax({ url: u('edit', $(this).data('id')), headers: { Accept: 'application/json' } }).done(function (r) {
            $('#edit-user-form')[0].reset();
            $('#edit-id').val(r.id);
            ['first_name', 'last_name', 'email', 'phone_number'].forEach(function (f) { $('#edit-' + f).val(r[f] || ''); });
            $('#edit-roles').val(r.roles || []).trigger('change');
            editModal.show();
        }).fail(function (x) { GZ.toast(GZ.xhrError(x), 'error'); });
    });
    $('#edit-user-form').on('submit', function (e) {
        e.preventDefault();
        if ($('#edit-roles').length && !($('#edit-roles').val() || []).length) {
            // send an explicit empty list so "remove all roles" is possible
            $(this).append('<input type="hidden" name="roles" value="" class="tmp-empty-roles">');
        }
        sendForm($(this), u('update', $('#edit-id').val()), 'PUT', editModal, $('#update-btn'));
        $(this).find('.tmp-empty-roles').remove();
    });

    $('#usersTable').on('click', '.remove-item-btn', function () {
        GZ.destroy(u('destroy', $(this).data('id')), $(this).data('name'), table);
    });

    $('#remove-actions').on('click', function () {
        var ids = GZ.selected('#usersTable');
        if (!ids.length) return;
        Swal.fire({ title: 'Delete ' + ids.length + ' user(s)?', text: 'This action cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#e11d48', confirmButtonText: 'Yes, delete' })
            .then(function (r) {
                if (!r.isConfirmed) return;
                $.when.apply($, ids.map(function (id) { return $.ajax({ url: u('destroy', id), type: 'POST', data: { _method: 'DELETE' } }); }))
                    .always(function () { GZ.toast('Selected users deleted'); table.ajax.reload(null, false); });
            });
    });
});
</script>
@endsection
