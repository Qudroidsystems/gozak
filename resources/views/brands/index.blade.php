{{-- resources/views/brands/index.blade.php --}}
@extends('layouts.master')

@section('title', 'Brands Management')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero title="Brands" icon="ri-price-tag-3-line" subtitle="Manage product brands, their logos and the categories they appear in.">
        <x-slot:actions>
            @can('Create brand')
                <button type="button" class="cb-hero-btn add-btn"><i class="ri-add-circle-line"></i> Add Brand</button>
            @endcan
        </x-slot:actions>
    </x-cb.hero>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><x-cb.stat label="Total brands" :value="number_format($stats['total'])" icon="ri-price-tag-3-line" accent="sky" /></div>
        <div class="col-md-4"><x-cb.stat label="Featured" :value="number_format($stats['featured'])" icon="ri-star-line" accent="amber" /></div>
        <div class="col-md-4"><x-cb.stat label="Without products" :value="number_format($stats['empty'])" icon="ri-inbox-line" accent="rose" /></div>
    </div>

    <x-cb.card title="Products per Brand (top 15)" icon="ri-bar-chart-2-line">
        <div style="height:240px;"><canvas id="brandChart"></canvas></div>
    </x-cb.card>

    <x-cb.card title="All Brands" icon="ri-list-check" :flush="true">
        <x-slot:tools>
            <select id="f-category" class="form-select form-select-sm" data-dt-filter="#brandsTable" style="width:auto;min-width:180px;">
                <option value="">All categories</option>
                @foreach($categories as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
            </select>
            <select id="f-featured" class="form-select form-select-sm" data-dt-filter="#brandsTable" style="width:auto;">
                <option value="">Featured: any</option>
                <option value="1">Featured only</option>
                <option value="0">Not featured</option>
            </select>
            @can('Delete brand')
                <button class="btn btn-sm btn-danger d-none" id="remove-actions"><i class="ri-delete-bin-2-line me-1"></i> Delete selected</button>
            @endcan
        </x-slot:tools>
        <div class="p-3 gz-dt-wrap">
            <table id="brandsTable" class="table gz-dt align-middle w-100 mb-0">
                <thead>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" class="form-check-input gz-check-all"></th>
                        <th>Brand</th>
                        <th>Categories</th>
                        <th>Products</th>
                        <th>Featured</th>
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="brandForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" id="brand_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Brand Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo</label>
                        <input type="file" class="form-control" name="logo" accept="image/*">
                        <img id="logo_preview" class="mt-2 rounded" style="max-height:120px; display:none;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categories</label>
                        <select class="form-select" name="categories[]" id="categories_select" multiple>
                            @foreach($categories as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured">
                        <label class="form-check-label" for="is_featured">Featured Brand</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Save Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var URLS = {
        data:    @json(route('web.brands.data')),
        store:   @json(route('web.brands.store')),
        edit:    @json(route('web.brands.edit', '__ID__')),
        update:  @json(route('web.brands.update', '__ID__')),
        destroy: @json(route('web.brands.destroy', '__ID__'))
    };
    var u = function (k, id) { return URLS[k].replace('__ID__', id); };

    // Chart
    new Chart(document.getElementById('brandChart'), {
        type: 'bar',
        data: { labels: @json($chart_labels), datasets: [{ label: 'Products', data: @json($chart_data), backgroundColor: '#0d9488', borderRadius: 6 }] },
        options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { display: false } } }
    });

    // DataTable
    var table = GZ.dt('#brandsTable', {
        url: URLS.data,
        order: [[1, 'asc']],
        filters: function () { return { category: $('#f-category').val(), featured: $('#f-featured').val() }; },
        columns: [
            { data: 'checkbox',        name: 'checkbox', orderable: false, searchable: false },
            { data: 'brand',           name: 'brand' },
            { data: 'categories_list', name: 'categories_list', orderable: false, searchable: false },
            { data: 'products_count',  name: 'products_count', searchable: false },
            { data: 'is_featured',     name: 'brands.is_featured', searchable: false },
            { data: 'action',          name: 'action', orderable: false, searchable: false }
        ],
        onDraw: toggleBulk
    });

    function toggleBulk() { $('#remove-actions').toggleClass('d-none', GZ.selected('#brandsTable').length === 0); }
    $('#brandsTable').on('change gz:check', '.gz-row-check, .gz-check-all', toggleBulk);

    // Form / modal
    var choices = new Choices('#categories_select', { removeItemButton: true, searchEnabled: true, placeholder: true, placeholderValue: 'Select categories' });
    var modal   = bootstrap.Modal.getOrCreateInstance(document.getElementById('showModal'));
    var form    = document.getElementById('brandForm');
    var preview = document.getElementById('logo_preview');

    $('.add-btn').on('click', function () {
        form.reset();
        $('#brand_id').val('');
        $('#modalTitle').text('Add Brand'); $('#submitBtn').text('Save Brand');
        preview.style.display = 'none'; preview.src = '';
        choices.removeActiveItems();
        modal.show();
    });

    $('#brandsTable').on('click', '.edit-item-btn', function () {
        $.get(u('edit', $(this).data('id'))).done(function (b) {
            form.reset();
            $('#brand_id').val(b.id);
            form.querySelector('[name="name"]').value = b.name;
            $('#is_featured').prop('checked', !!Number(b.is_featured));
            if (b.logo) { preview.src = b.logo; preview.style.display = 'block'; } else { preview.style.display = 'none'; }
            choices.removeActiveItems();
            if (b.categories && b.categories.length) { choices.setChoiceByValue(b.categories.map(function (c) { return String(c.id); })); }
            $('#modalTitle').text('Edit Brand'); $('#submitBtn').text('Update Brand');
            modal.show();
        }).fail(function (x) { GZ.toast(GZ.xhrError(x), 'error'); });
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var id = $('#brand_id').val(), fd = new FormData(form), btn = $('#submitBtn').prop('disabled', true).text('Saving…');
        if (!$('#is_featured').is(':checked')) { fd.append('is_featured', '0'); }
        if (id) { fd.append('_method', 'PUT'); }
        $.ajax({ url: id ? u('update', id) : URLS.store, type: 'POST', data: fd, processData: false, contentType: false })
            .done(function (r) { modal.hide(); GZ.toast(r.message || 'Saved'); table.ajax.reload(null, false); })
            .fail(function (x) { Swal.fire({ icon: 'error', title: 'Error', text: GZ.xhrError(x) }); })
            .always(function () { btn.prop('disabled', false).text(id ? 'Update Brand' : 'Save Brand'); });
    });

    form.querySelector('[name="logo"]').addEventListener('change', function (e) {
        var f = e.target.files[0]; if (!f) return;
        var r = new FileReader(); r.onload = function (ev) { preview.src = ev.target.result; preview.style.display = 'block'; }; r.readAsDataURL(f);
    });

    // Delete (single + bulk)
    $('#brandsTable').on('click', '.remove-item-btn', function () {
        GZ.destroy(u('destroy', $(this).data('id')), $(this).data('name'), table);
    });

    $('#remove-actions').on('click', function () {
        var ids = GZ.selected('#brandsTable');
        if (!ids.length) return;
        Swal.fire({ title: 'Delete ' + ids.length + ' brand(s)?', text: 'This action cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#e11d48', confirmButtonText: 'Yes, delete them' })
            .then(function (r) {
                if (!r.isConfirmed) return;
                $.when.apply($, ids.map(function (id) { return $.ajax({ url: u('destroy', id), type: 'POST', data: { _method: 'DELETE' } }); }))
                    .done(function () { GZ.toast('Selected brands deleted'); })
                    .fail(function () { GZ.toast('Some brands could not be deleted', 'error'); })
                    .always(function () { table.ajax.reload(null, false); });
            });
    });
});
</script>
@endsection
