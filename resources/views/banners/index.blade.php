{{-- resources/views/banners/index.blade.php --}}
@extends('layouts.master')

@section('title', 'Banners Management')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero title="Banners" icon="ri-image-2-line" subtitle="Slider banners shown in the GozakMart app, by target screen.">
        <x-slot:actions>
            @can('Create banner')
                <button type="button" class="cb-hero-btn add-btn"><i class="ri-add-circle-line"></i> Add Banner</button>
            @endcan
        </x-slot:actions>
    </x-cb.hero>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><x-cb.stat label="Total banners" :value="number_format($stats['total'])" icon="ri-image-2-line" accent="sky" /></div>
        <div class="col-md-4"><x-cb.stat label="Active" :value="number_format($stats['active'])" icon="ri-eye-line" accent="green" /></div>
        <div class="col-md-4"><x-cb.stat label="Inactive" :value="number_format($stats['inactive'])" icon="ri-eye-off-line" accent="rose" /></div>
    </div>

    <x-cb.card title="All Banners" icon="ri-list-check" :flush="true">
        <x-slot:tools>
            <select id="f-screen" class="form-select form-select-sm" data-dt-filter="#bannersTable" style="width:auto;">
                <option value="">All screens</option>
                <option value="home">Home Screen</option>
                <option value="category">Category Page</option>
                <option value="product">Product Detail</option>
                <option value="offers">Offers Page</option>
                <option value="all">All Pages</option>
            </select>
            <select id="f-status" class="form-select form-select-sm" data-dt-filter="#bannersTable" style="width:auto;">
                <option value="">Any status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            @can('Delete banner')
                <button class="btn btn-sm btn-danger d-none" id="remove-actions"><i class="ri-delete-bin-2-line me-1"></i> Delete selected</button>
            @endcan
        </x-slot:tools>
        <div class="p-3 gz-dt-wrap">
            <table id="bannersTable" class="table gz-dt align-middle w-100 mb-0">
                <thead>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" class="form-check-input gz-check-all"></th>
                        <th>Image</th>
                        <th>Target Screen</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th style="width:90px;">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-cb.card>

</div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="showModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="bannerForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" id="banner_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-lg-8">
                            <div class="mb-3">
                                <label class="form-label">Banner Image <span class="text-danger" id="img-required">*</span></label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <small class="text-muted">Recommended: 1200×600px, max 3MB</small>
                            </div>
                            <div class="text-center mb-3">
                                <img id="image_preview" class="rounded shadow" style="max-width:100%; max-height:300px; display:none;" alt="Preview">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label class="form-label">Target Screen</label>
                                <select class="form-select" name="target_screen" id="target_screen" required>
                                    <option value="home">Home Screen</option>
                                    <option value="category">Category Page</option>
                                    <option value="product">Product Detail</option>
                                    <option value="offers">Offers Page</option>
                                    <option value="all">All Pages</option>
                                </select>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" value="1" id="active" checked>
                                <label class="form-check-label" for="active">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Save Banner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var URLS = {
        data:    @json(route('web.banners.data')),
        store:   @json(route('web.banners.store')),
        update:  @json(route('web.banners.update', '__ID__')),
        destroy: @json(route('web.banners.destroy', '__ID__'))
    };
    var u = function (k, id) { return URLS[k].replace('__ID__', id); };

    var table = GZ.dt('#bannersTable', {
        url: URLS.data,
        order: [[4, 'desc']],
        filters: function () { return { screen: $('#f-screen').val(), status: $('#f-status').val() }; },
        columns: [
            { data: 'checkbox',      name: 'checkbox', orderable: false, searchable: false },
            { data: 'image',         name: 'image', orderable: false, searchable: false },
            { data: 'target_screen', name: 'target_screen' },
            { data: 'active',        name: 'active', searchable: false },
            { data: 'created_at',    name: 'created_at', searchable: false },
            { data: 'action',        name: 'action', orderable: false, searchable: false }
        ],
        onDraw: toggleBulk
    });
    function toggleBulk() { $('#remove-actions').toggleClass('d-none', GZ.selected('#bannersTable').length === 0); }
    $('#bannersTable').on('change gz:check', '.gz-row-check, .gz-check-all', toggleBulk);

    var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('showModal'));
    var form = document.getElementById('bannerForm');
    var preview = document.getElementById('image_preview');

    $('.add-btn').on('click', function () {
        form.reset();
        $('#banner_id').val(''); $('#active').prop('checked', true); $('#img-required').show();
        $('#modalTitle').text('Add Banner'); $('#submitBtn').text('Save Banner');
        preview.style.display = 'none';
        modal.show();
    });

    $('#bannersTable').on('click', '.edit-item-btn', function () {
        var d = this.dataset;
        form.reset();
        $('#banner_id').val(d.id); $('#target_screen').val(d.screen); $('#active').prop('checked', d.active === '1'); $('#img-required').hide();
        if (d.image) { preview.src = d.image; preview.style.display = 'block'; } else { preview.style.display = 'none'; }
        $('#modalTitle').text('Edit Banner'); $('#submitBtn').text('Update Banner');
        modal.show();
    });

    form.querySelector('[name="image"]').addEventListener('change', function (e) {
        var f = e.target.files[0]; if (f) { preview.src = URL.createObjectURL(f); preview.style.display = 'block'; }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var id = $('#banner_id').val(), fd = new FormData(form), btn = $('#submitBtn').prop('disabled', true);
        fd.append('active', $('#active').is(':checked') ? '1' : '0');
        if (id) { fd.append('_method', 'PUT'); }
        $.ajax({ url: id ? u('update', id) : URLS.store, type: 'POST', data: fd, processData: false, contentType: false })
            .done(function (r) { modal.hide(); GZ.toast(r.message || 'Saved'); table.ajax.reload(null, false); })
            .fail(function (x) { Swal.fire({ icon: 'error', title: 'Error', text: GZ.xhrError(x) }); })
            .always(function () { btn.prop('disabled', false); });
    });

    $('#bannersTable').on('click', '.remove-item-btn', function () {
        GZ.destroy(u('destroy', $(this).data('id')), 'this banner', table);
    });

    $('#remove-actions').on('click', function () {
        var ids = GZ.selected('#bannersTable');
        if (!ids.length) return;
        Swal.fire({ title: 'Delete ' + ids.length + ' banner(s)?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#e11d48', confirmButtonText: 'Yes, delete' })
            .then(function (r) {
                if (!r.isConfirmed) return;
                $.when.apply($, ids.map(function (id) { return $.ajax({ url: u('destroy', id), type: 'POST', data: { _method: 'DELETE' } }); }))
                    .always(function () { GZ.toast('Selected banners deleted'); table.ajax.reload(null, false); });
            });
    });
});
</script>
@endsection
