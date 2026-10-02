@extends('layouts.master')
@section('title', 'Address Management')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero title="Addresses" icon="ri-map-pin-line" subtitle="Delivery addresses saved by customers.">
        <x-slot:actions>
            @can('Manage addresses')
                <button class="cb-hero-btn" id="btn-add"><i class="ri-add-circle-line"></i> Add Address</button>
            @endcan
        </x-slot:actions>
    </x-cb.hero>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><x-cb.stat label="Saved addresses" :value="number_format($stats['total'])" icon="ri-map-pin-line" accent="sky" /></div>
        <div class="col-md-4"><x-cb.stat label="Customers with an address" :value="number_format($stats['customers'])" icon="ri-user-location-line" accent="violet" /></div>
        <div class="col-md-4"><x-cb.stat label="Default addresses" :value="number_format($stats['defaults'])" icon="ri-star-line" accent="green" /></div>
    </div>

    <x-cb.card title="All Addresses" icon="ri-list-check" :flush="true">
        <x-slot:tools>
            <div style="min-width:260px;"><select id="f-customer" class="form-select form-select-sm"></select></div>
            <select id="f-default" class="form-select form-select-sm" data-dt-filter="#addressesTable" style="width:auto;">
                <option value="">All addresses</option>
                <option value="1">Default only</option>
            </select>
        </x-slot:tools>
        <div class="p-3 gz-dt-wrap">
            <table id="addressesTable" class="table gz-dt align-middle w-100 mb-0">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Recipient</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Default</th>
                        <th>Added</th>
                        <th style="width:110px;">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-cb.card>

</div>
</div>

{{-- CREATE / EDIT MODAL --}}
<div class="modal fade" id="addressModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addressForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addressModalTitle">Add New Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Customer <span class="text-danger">*</span></label>
                            <select name="user_id" id="f-user_id" class="form-select" required></select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Recipient Name</label><input type="text" name="name" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Phone Number <span class="text-danger">*</span></label><input type="text" name="phone_number" class="form-control" placeholder="+2348012345678" required></div>
                        <div class="col-md-12"><label class="form-label">Street Address <span class="text-danger">*</span></label><input type="text" name="street" class="form-control" required></div>
                        <div class="col-md-5"><label class="form-label">City <span class="text-danger">*</span></label><input type="text" name="city" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">State <span class="text-danger">*</span></label><input type="text" name="state" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label">Postal Code</label><input type="text" name="postal_code" class="form-control"></div>
                        <div class="col-md-8"><label class="form-label">Country <span class="text-danger">*</span></label><input type="text" name="country" class="form-control" value="Nigeria" required></div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="f-is_default">
                                <label class="form-check-label" for="f-is_default">Set as default</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="addressSubmit">Save Address</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- VIEW MODAL --}}
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Address Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4 fw-bold">Recipient Name:</div><div class="col-md-8" id="view-name">-</div>
                    <div class="col-md-4 fw-bold">Customer:</div><div class="col-md-8" id="view-customer">-</div>
                    <div class="col-md-4 fw-bold">Street:</div><div class="col-md-8" id="view-street">-</div>
                    <div class="col-md-4 fw-bold">City, State, Postal code:</div><div class="col-md-8" id="view-city">-</div>
                    <div class="col-md-4 fw-bold">Country:</div><div class="col-md-8" id="view-country">-</div>
                    <div class="col-md-4 fw-bold">Phone:</div><div class="col-md-8" id="view-phone">-</div>
                    <div class="col-md-4 fw-bold">Default:</div><div class="col-md-8" id="view-default">-</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var URLS = {
        data:      @json(route('adminaddresses.data')),
        customers: @json(route('adminaddresses.customers')),
        store:     @json(route('adminaddresses.store')),
        show:      @json(route('adminaddresses.show', '__ID__')),
        edit:      @json(route('adminaddresses.edit', '__ID__')),
        update:    @json(route('adminaddresses.update', '__ID__')),
        destroy:   @json(route('adminaddresses.destroy', '__ID__'))
    };
    var u = function (k, id) { return URLS[k].replace('__ID__', id); };
    var userAjax = {
        url: URLS.customers, dataType: 'json', delay: 250,
        data: function (p) { return { q: p.term || '', page: p.page || 1 }; },
        processResults: function (d) { return d; }
    };

    // Customer filter (Select2, AJAX — never loads every user into the page)
    $('#f-customer').select2({ ajax: userAjax, placeholder: 'All customers', allowClear: true, width: '100%' })
        .on('change', function () { table.ajax.reload(); });

    var table = GZ.dt('#addressesTable', {
        url: URLS.data,
        order: [[5, 'desc']],
        filters: function () { return { customer_id: $('#f-customer').val() || '', default: $('#f-default').val() }; },
        columns: [
            { data: 'customer',     name: 'customer' },
            { data: 'name',         name: 'addresses.name' },
            { data: 'address',      name: 'address' },
            { data: 'phone_number', name: 'addresses.phone_number' },
            { data: 'is_default',   name: 'addresses.is_default', searchable: false },
            { data: 'created_at',   name: 'addresses.created_at', searchable: false },
            { data: 'action',       name: 'action', orderable: false, searchable: false }
        ]
    });

    var modalEl = document.getElementById('addressModal');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var $form = $('#addressForm');
    var $user = $('#f-user_id').select2({ ajax: userAjax, dropdownParent: $(modalEl), placeholder: 'Select customer', width: '100%' });
    var editId = null;

    $('#btn-add').on('click', function () {
        editId = null; $form[0].reset(); $user.val(null).trigger('change');
        $form.find('[name=country]').val('Nigeria');
        $('#addressModalTitle').text('Add New Address'); $('#addressSubmit').text('Save Address');
        modal.show();
    });

    $('#addressesTable').on('click', '.edit-btn', function () {
        $.get(u('edit', $(this).data('id'))).done(function (r) {
            var a = r.address; editId = a.id;
            $form[0].reset();
            ['name', 'street', 'city', 'state', 'postal_code', 'country', 'phone_number'].forEach(function (f) { $form.find('[name=' + f + ']').val(a[f] || ''); });
            $('#f-is_default').prop('checked', !!Number(a.is_default));
            var label = a.user ? (a.user.first_name + ' ' + a.user.last_name + ' (' + a.user.email + ')') : ('User #' + a.user_id);
            $user.empty().append(new Option(label, a.user_id, true, true)).trigger('change');
            $('#addressModalTitle').text('Edit Address'); $('#addressSubmit').text('Update Address');
            modal.show();
        }).fail(function (x) { GZ.toast(GZ.xhrError(x), 'error'); });
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        var data = $form.serializeArray();
        if (editId) { data.push({ name: '_method', value: 'PUT' }); }
        var btn = $('#addressSubmit').prop('disabled', true);
        $.post(editId ? u('update', editId) : URLS.store, $.param(data))
            .done(function (r) { modal.hide(); GZ.toast(r.message || 'Saved'); table.ajax.reload(null, false); })
            .fail(function (x) { Swal.fire({ icon: 'error', title: 'Could not save', text: GZ.xhrError(x) }); })
            .always(function () { btn.prop('disabled', false); });
    });

    $('#addressesTable').on('click', '.view-btn', function () {
        $.get(u('show', $(this).data('id'))).done(function (r) {
            var a = r.address;
            $('#view-name').text(a.name || '—');
            $('#view-customer').text(a.user ? a.user.first_name + ' ' + a.user.last_name + ' (' + a.user.email + ')' : '—');
            $('#view-street').text(a.street);
            $('#view-city').text([a.city, a.state, a.postal_code].filter(Boolean).join(', '));
            $('#view-country').text(a.country);
            $('#view-phone').text(a.phone_number);
            $('#view-default').html(Number(a.is_default) ? '<span class="status-pill st-success">Default</span>' : '<span class="text-muted">No</span>');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('viewModal')).show();
        });
    });

    $('#addressesTable').on('click', '.delete-btn', function () {
        GZ.destroy(u('destroy', $(this).data('id')), 'this address', table);
    });
});
</script>
@endsection
