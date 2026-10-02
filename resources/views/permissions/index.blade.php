@extends('layouts.master')
@section('title', 'Permissions')
@section('content')
@include('roles.partials.styles')
<style>
.pm-chip { font-size:9.5px; font-weight:800; letter-spacing:.4px; text-transform:uppercase; padding:2px 7px; border-radius:5px; white-space:nowrap; }
.pm-chip-view{background:#e0f2fe;color:#0369a1} .pm-chip-create{background:#dcfce7;color:#15803d}
.pm-chip-edit{background:#fef9c3;color:#a16207} .pm-chip-manage{background:#ede9fe;color:#6d28d9}
.pm-chip-delete{background:#fee2e2;color:#b91c1c} .pm-chip-approve{background:#cffafe;color:#0e7490}
.pm-chip-money{background:#fae8ff;color:#a21caf} .pm-chip-other{background:#e2e8f0;color:#475569}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="rol-hero">
        <h1><i class="bi bi-key-fill me-2"></i>Permissions</h1>
        <p>Every action in the admin is guarded by a permission. Group them so the role editor stays tidy.</p>
        <div class="rol-hero-actions">
            <a href="{{ route('roles.index') }}" class="rol-btn rol-btn-back"><i class="bi bi-shield-lock"></i> Roles</a>
            @can('Create permission')
            <button type="button" class="rol-btn-primary" id="btn-new-perm"><i class="bi bi-plus-circle"></i> New Permission</button>
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-sm-4"><x-cb.stat label="Total permissions" :value="number_format($stats['total'])" icon="bi bi-key" accent="violet" /></div>
        <div class="col-sm-4"><x-cb.stat label="Groups" :value="number_format($stats['groups'])" icon="bi bi-collection" accent="sky" /></div>
        <div class="col-sm-4"><x-cb.stat label="Not used by any role" :value="number_format($stats['unassigned'])" icon="bi bi-exclamation-diamond" accent="amber" /></div>
    </div>

    <x-cb.card title="All Permissions" icon="bi bi-list-check" :flush="true">
        <x-slot:tools>
            <select class="form-select form-select-sm" id="f-group" data-dt-filter="#permissionsTable" style="width:auto;min-width:200px;">
                <option value="">All groups</option>
                @foreach($groups as $g)<option value="{{ $g }}">{{ $g }}</option>@endforeach
                <option value="__none">Ungrouped</option>
            </select>
        </x-slot:tools>
        <div class="p-3 gz-dt-wrap">
            <table id="permissionsTable" class="table gz-dt align-middle w-100 mb-0">
                <thead>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" class="form-check-input gz-check-all"></th>
                        <th>Permission</th>
                        <th>Group</th>
                        <th>Roles</th>
                        <th>Created</th>
                        <th style="width:90px;">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-cb.card>

    {{-- Create / edit modal --}}
    <div class="modal fade rol-modal" id="permModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="permModalTitle"><i class="bi bi-key me-2"></i>New Permission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="permForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="rol-form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="rol-form-control" placeholder="e.g. View product" required>
                            <small class="text-muted">Start with a verb (View, Create, Update, Delete, Manage…) so it is described automatically.</small>
                        </div>
                        <div class="mb-3">
                            <label class="rol-form-label">Group</label>
                            <input type="text" name="title" class="rol-form-control" list="perm-groups" placeholder="e.g. Product Management">
                            <datalist id="perm-groups">@foreach($groups as $g)<option value="{{ $g }}">@endforeach</datalist>
                        </div>
                        <div>
                            <label class="rol-form-label">Description</label>
                            <input type="text" name="description" class="rol-form-control" placeholder="Optional — shown in the role editor">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="rol-btn-primary" id="permSubmit"><i class="bi bi-check-circle"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var table = GZ.dt('#permissionsTable', {
        url: @json(route('permissions.data')),
        order: [[2, 'asc']],
        filters: function () { return { group: $('#f-group').val() }; },
        columns: [
            { data: 'checkbox',   name: 'checkbox', orderable: false, searchable: false },
            { data: 'name',       name: 'permissions.name' },
            { data: 'title',      name: 'permissions.title' },
            { data: 'roles_list', name: 'roles_list', orderable: false },
            { data: 'created_at', name: 'permissions.created_at', searchable: false },
            { data: 'action',     name: 'action', orderable: false, searchable: false }
        ]
    });

    var modalEl = document.getElementById('permModal');
    var modal   = bootstrap.Modal.getOrCreateInstance(modalEl);
    var $form   = $('#permForm');
    var saveUrl = null, saveMethod = 'POST';

    $('#btn-new-perm').on('click', function () {
        $form[0].reset();
        saveUrl = @json(route('permissions.store')); saveMethod = 'POST';
        $('#permModalTitle').html('<i class="bi bi-key me-2"></i>New Permission');
        modal.show();
    });

    $('#permissionsTable').on('click', '.edit-perm-btn', function () {
        var b = $(this);
        $form.find('[name=name]').val(b.data('name'));
        $form.find('[name=title]').val(b.data('title'));
        $form.find('[name=description]').val(b.data('description'));
        saveUrl = b.data('url'); saveMethod = 'PUT';
        $('#permModalTitle').html('<i class="bi bi-pencil-square me-2"></i>Edit Permission');
        modal.show();
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        var btn = $('#permSubmit').prop('disabled', true);
        var data = $form.serializeArray(); data.push({ name: '_method', value: saveMethod });
        $.post(saveUrl, $.param(data))
            .done(function (r) { modal.hide(); GZ.toast(r.message); table.ajax.reload(null, false); })
            .fail(function (x) { Swal.fire({ icon: 'error', title: 'Could not save', text: GZ.xhrError(x) }); })
            .always(function () { btn.prop('disabled', false); });
    });

    $('#permissionsTable').on('click', '.delete-perm-btn', function () {
        GZ.destroy($(this).data('url'), $(this).data('name'), table);
    });
});
</script>
@endsection
