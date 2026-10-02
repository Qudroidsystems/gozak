@extends('layouts.master')

@section('title', 'Inventory Management')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- PAGE TITLE -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle ?? 'Inventory Management' }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0)">Inventory</a></li>
                                <li class="breadcrumb-item active">Transactions</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SUMMARY CARDS -->
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-success-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-success mb-0">Stock In</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ number_format($summary['total_in'] ?? 0) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-success rounded-circle fs-3">
                                        <i class="bi bi-box-arrow-in-down"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-danger-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-danger mb-0">Stock Out</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ number_format($summary['total_out'] ?? 0) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-danger rounded-circle fs-3">
                                        <i class="bi bi-box-arrow-up"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-warning-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-warning mb-0">Adjustments</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ number_format($summary['total_adjustments'] ?? 0) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-warning rounded-circle fs-3">
                                        <i class="bi bi-arrow-left-right"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-info-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-info mb-0">Transfers</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ number_format($summary['total_transfers'] ?? 0) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-info rounded-circle fs-3">
                                        <i class="bi bi-truck"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TRANSACTIONS (server-side DataTable) -->
            <x-cb.card title="Inventory Transactions" icon="ri-exchange-box-line" :flush="true" class="mt-4">
                <x-slot:tools>
                    @can('Manage inventory')
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#adjustStockModal"><i class="bi bi-plus-circle me-1"></i> Adjust Stock</button>
                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#transferStockModal"><i class="bi bi-arrow-left-right me-1"></i> Transfer Stock</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="exportTransactions"><i class="bi bi-download me-1"></i> Export</button>
                    @endcan
                    <a href="{{ route('inventory.stock-levels') }}" class="btn btn-sm btn-warning"><i class="bi bi-box-seam me-1"></i> Stock Levels</a>
                </x-slot:tools>

                <div class="gz-filter-bar px-3 pt-3" id="filterForm">
                    <select class="form-select form-select-sm" id="f-type" data-dt-filter="#transactionsTable">
                        <option value="">All Types</option>
                        <option value="in" @selected(request('type') == 'in')>Stock In</option>
                        <option value="out" @selected(request('type') == 'out')>Stock Out</option>
                        <option value="adjustment" @selected(request('type') == 'adjustment')>Adjustment</option>
                        <option value="transfer" @selected(request('type') == 'transfer')>Transfer</option>
                        <option value="return" @selected(request('type') == 'return')>Return</option>
                        <option value="damage" @selected(request('type') == 'damage')>Damage</option>
                    </select>
                    <div style="min-width:260px;">
                        <select class="form-select form-select-sm" id="f-product">
                            <option value="">All Products</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>{{ $product->title }} ({{ $product->sku }})</option>
                            @endforeach
                        </select>
                    </div>
                    <select class="form-select form-select-sm" id="f-location" data-dt-filter="#transactionsTable">
                        <option value="">All Locations</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" @selected(request('location_id') == $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm" id="f-user" data-dt-filter="#transactionsTable">
                        <option value="">All Users</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <input type="date" class="form-control form-control-sm" id="f-from" data-dt-filter="#transactionsTable" value="{{ request('date_from') }}" title="From" style="max-width:150px;">
                    <input type="date" class="form-control form-control-sm" id="f-to" data-dt-filter="#transactionsTable" value="{{ request('date_to') }}" title="To" style="max-width:150px;">
                </div>

                <div class="px-3 pb-3 gz-dt-wrap">
                    <table class="table gz-dt align-middle w-100 mb-0" id="transactionsTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Product</th>
                                <th>Location</th>
                                <th>Quantity</th>
                                <th>Reference</th>
                                <th>User</th>
                                <th style="width:60px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </x-cb.card>

        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function invFilters() {
        return {
            type: $('#f-type').val(), product_id: $('#f-product').val(), location_id: $('#f-location').val(),
            user_id: $('#f-user').val(), date_from: $('#f-from').val(), date_to: $('#f-to').val()
        };
    }
    window.transactionsTable = GZ.dt('#transactionsTable', {
        url: @json(route('inventory.transactions.data')),
        order: [[0, 'desc']],
        filters: invFilters,
        columns: [
            { data: 'transaction_date', name: 'stocks.transaction_date', searchable: false },
            { data: 'type',             name: 'stocks.type', searchable: false },
            { data: 'product',          name: 'product', orderable: false },
            { data: 'location',         name: 'location', orderable: false, searchable: false },
            { data: 'quantity',         name: 'stocks.quantity', searchable: false },
            { data: 'reference',        name: 'reference', orderable: false },
            { data: 'user_name',        name: 'user_name', orderable: false, searchable: false },
            { data: 'action',           name: 'action', orderable: false, searchable: false }
        ]
    });
    $('#f-product').select2({ width: '100%', placeholder: 'All Products', allowClear: true })
        .on('change', function () { window.transactionsTable.ajax.reload(); });

    $('#exportTransactions').on('click', function () {
        var f = invFilters(), q = new URLSearchParams();
        Object.keys(f).forEach(function (k) { if (f[k]) q.append(k, f[k]); });
        window.location = @json(route('inventory.export.transactions')) + '?' + q.toString();
    });
});
</script>


<!-- ADJUST STOCK MODAL -->
<div class="modal fade" id="adjustStockModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="adjustStockForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Adjust Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Product <span class="text-danger">*</span></label>
                        <select name="product_id" id="adjust_product_id" class="form-control" required>
                            <option value="">Select Product</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                                    {{ $product->title }} ({{ $product->sku }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location <span class="text-danger">*</span></label>
                        <select name="location_id" id="adjust_location_id" class="form-control" required>
                            <option value="">Select Location</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ $location->is_default ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="mt-1">
                            <small class="text-muted" id="currentStockDisplay"></small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adjustment Type <span class="text-danger">*</span></label>
                        <select name="adjustment_type" id="adjustment_type" class="form-control" required>
                            <option value="add">Add Stock</option>
                            <option value="remove">Remove Stock</option>
                            <option value="set">Set Stock to</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="adjust_quantity" class="form-control" required min="1" step="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit Cost</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" name="unit_cost" id="unit_cost" class="form-control" min="0">
                        </div>
                        <small class="text-muted">Leave blank to use product price</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control" required placeholder="e.g., Restock, Damage, etc.">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Additional information..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="adjustStockBtn">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="adjustSpinner"></span>
                        Apply Adjustment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- TRANSFER STOCK MODAL -->
<div class="modal fade" id="transferStockModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="transferStockForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Transfer Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Product <span class="text-danger">*</span></label>
                        <select name="product_id" id="transfer_product_id" class="form-control" required>
                            <option value="">Select Product</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->title }} ({{ $product->sku }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">From Location <span class="text-danger">*</span></label>
                            <select name="from_location_id" id="from_location_id" class="form-control" required>
                                <option value="">Select Location</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}">
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted" id="fromStockDisplay"></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">To Location <span class="text-danger">*</span></label>
                            <select name="to_location_id" id="to_location_id" class="form-control" required>
                                <option value="">Select Location</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}">
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted" id="toStockDisplay"></small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="transfer_quantity" class="form-control" required min="1" step="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reference Number</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="e.g., TRF-001">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Transfer details..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="transferStockBtn">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="transferSpinner"></span>
                        Transfer Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- VIEW TRANSACTION MODAL -->
<div class="modal fade" id="viewTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transaction Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="transactionDetails">
                <!-- Details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inventory management script loaded');

    // Setup axios defaults
    axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    // DEBUG: Check if routes are available
    console.log('CSRF Token:', axios.defaults.headers.common['X-CSRF-TOKEN']);

    // Check current stock when product/location changes in adjust modal
    document.getElementById('adjust_product_id')?.addEventListener('change', checkCurrentStock);
    document.getElementById('adjust_location_id')?.addEventListener('change', checkCurrentStock);

    // Check stock for transfer modal
    document.getElementById('transfer_product_id')?.addEventListener('change', checkTransferStock);
    document.getElementById('from_location_id')?.addEventListener('change', checkTransferStock);
    document.getElementById('to_location_id')?.addEventListener('change', checkTransferStock);

    // Update unit cost when product changes
    document.getElementById('adjust_product_id')?.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const price = selectedOption.getAttribute('data-price');
        console.log('Selected product price:', price);
        if (price) {
            document.getElementById('unit_cost').value = parseFloat(price).toFixed(2);
        }
    });

    // Update adjustment type behavior
    document.getElementById('adjustment_type')?.addEventListener('change', function() {
        const type = this.value;
        const quantityInput = document.getElementById('adjust_quantity');
        const quantityLabel = quantityInput.previousElementSibling;

        if (type === 'set') {
            quantityLabel.textContent = 'Set Stock To Quantity *';
            quantityInput.placeholder = 'Enter target stock quantity';
        } else if (type === 'add') {
            quantityLabel.textContent = 'Add Quantity *';
            quantityInput.placeholder = 'Enter quantity to add';
        } else {
            quantityLabel.textContent = 'Remove Quantity *';
            quantityInput.placeholder = 'Enter quantity to remove';
        }
    });

    function checkCurrentStock() {
        const productId = document.getElementById('adjust_product_id')?.value;
        const locationId = document.getElementById('adjust_location_id')?.value;

        console.log('Checking current stock:', { productId, locationId });

        if (productId && locationId) {
            // Construct URL manually to avoid route issues
            const url = `/inventory/stock-level/${productId}/${locationId}`;
            console.log('Fetching stock from:', url);

            axios.get(url)
                .then(response => {
                    console.log('Stock response:', response.data);
                    if (response.data.success) {
                        const stock = response.data.stock || 0;
                        document.getElementById('currentStockDisplay').innerHTML =
                            `<span class="text-info">Current stock: ${stock}</span>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching stock:', error);
                    document.getElementById('currentStockDisplay').innerHTML =
                        '<span class="text-muted">Current stock: 0</span>';
                });
        } else {
            document.getElementById('currentStockDisplay').innerHTML = '';
        }
    }

    function checkTransferStock() {
        const productId = document.getElementById('transfer_product_id')?.value;
        const fromLocationId = document.getElementById('from_location_id')?.value;
        const toLocationId = document.getElementById('to_location_id')?.value;

        console.log('Checking transfer stock:', { productId, fromLocationId, toLocationId });

        if (productId && fromLocationId) {
            const url = `/inventory/stock-level/${productId}/${fromLocationId}`;
            axios.get(url)
                .then(response => {
                    if (response.data.success) {
                        const availableStock = response.data.stock || 0;
                        document.getElementById('fromStockDisplay').innerHTML =
                            `<span class="text-info">Available: ${availableStock}</span>`;

                        // Update max quantity
                        const quantityInput = document.getElementById('transfer_quantity');
                        if (quantityInput) {
                            quantityInput.max = availableStock;

                            // Validate quantity in real-time
                            quantityInput.addEventListener('input', function() {
                                const requested = parseInt(this.value) || 0;
                                if (requested > availableStock) {
                                    this.classList.add('is-invalid');
                                    document.getElementById('fromStockDisplay').innerHTML =
                                        `<span class="text-danger">Available: ${availableStock} (Insufficient)</span>`;
                                } else {
                                    this.classList.remove('is-invalid');
                                    document.getElementById('fromStockDisplay').innerHTML =
                                        `<span class="text-info">Available: ${availableStock}</span>`;
                                }
                            });
                        }
                    }
                })
                .catch(() => {
                    document.getElementById('fromStockDisplay').innerHTML =
                        '<span class="text-danger">Available: 0</span>';
                });
        } else {
            document.getElementById('fromStockDisplay').innerHTML = '';
        }

        if (productId && toLocationId) {
            const url = `/inventory/stock-level/${productId}/${toLocationId}`;
            axios.get(url)
                .then(response => {
                    if (response.data.success) {
                        document.getElementById('toStockDisplay').innerHTML =
                            `<span class="text-info">Current: ${response.data.stock || 0}</span>`;
                    }
                })
                .catch(() => {
                    document.getElementById('toStockDisplay').innerHTML =
                        '<span class="text-muted">Current: 0</span>';
                });
        } else {
            document.getElementById('toStockDisplay').innerHTML = '';
        }
    }

    // Adjust stock form submission - FIXED VERSION
    document.getElementById('adjustStockForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        console.log('Adjust stock form submitted');

        const btn = document.getElementById('adjustStockBtn');
        const spinner = document.getElementById('adjustSpinner');
        if (btn) btn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');

        const formData = new FormData(this);

        // Convert form data to object for logging
        const formDataObj = {};
        for (let [key, value] of formData.entries()) {
            formDataObj[key] = value;
        }
        console.log('Form data:', formDataObj);

        // DEBUG: Check route
        const route = '{{ route("inventory.adjust") }}';
        console.log('Sending to route:', route);

        // Send request
        axios.post(route, formData)
            .then(response => {
                console.log('Server response:', response.data);

                if (response.data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.data.message,
                        confirmButtonText: 'OK',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(() => {
                        // Close modal
                        const modalEl = document.getElementById('adjustStockModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();

                        // Reset form
                        document.getElementById('adjustStockForm').reset();
                        document.getElementById('currentStockDisplay').innerHTML = '';

                        // Reload page to show updated data
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.data.message || 'Failed to adjust stock',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('AJAX error:', error);
                console.error('Error response:', error.response);

                let errorMessage = 'Failed to adjust stock. Please try again.';

                if (error.response?.status === 422) {
                    // Validation errors
                    const errors = error.response.data.errors;
                    errorMessage = Object.values(errors).flat().join('<br>');
                } else if (error.response?.data?.message) {
                    errorMessage = error.response.data.message;
                } else if (error.message) {
                    errorMessage = error.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    html: errorMessage,
                    confirmButtonText: 'OK'
                });
            })
            .finally(() => {
                if (btn) btn.disabled = false;
                if (spinner) spinner.classList.add('d-none');
            });
    });

    // Transfer stock form submission - FIXED VERSION
    document.getElementById('transferStockForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        console.log('Transfer stock form submitted');

        const btn = document.getElementById('transferStockBtn');
        const spinner = document.getElementById('transferSpinner');
        if (btn) btn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');

        const formData = new FormData(this);

        // Validate from and to locations are different
        const fromLocation = document.getElementById('from_location_id').value;
        const toLocation = document.getElementById('to_location_id').value;

        if (fromLocation === toLocation) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Source and destination locations cannot be the same'
            });
            btn.disabled = false;
            spinner.classList.add('d-none');
            return;
        }

        // Convert form data to object for logging
        const formDataObj = {};
        for (let [key, value] of formData.entries()) {
            formDataObj[key] = value;
        }
        console.log('Transfer form data:', formDataObj);

        // Send request
        axios.post('{{ route("inventory.transfer") }}', formData)
            .then(response => {
                console.log('Transfer response:', response.data);

                if (response.data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.data.message,
                        confirmButtonText: 'OK',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(() => {
                        // Close modal
                        const modalEl = document.getElementById('transferStockModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();

                        // Reset form
                        document.getElementById('transferStockForm').reset();
                        document.getElementById('fromStockDisplay').innerHTML = '';
                        document.getElementById('toStockDisplay').innerHTML = '';

                        // Reload page to show updated data
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.data.message || 'Failed to transfer stock',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Transfer AJAX error:', error);
                console.error('Error response:', error.response);

                let errorMessage = 'Failed to transfer stock. Please try again.';

                if (error.response?.status === 422) {
                    // Validation errors
                    const errors = error.response.data.errors;
                    errorMessage = Object.values(errors).flat().join('<br>');
                } else if (error.response?.data?.message) {
                    errorMessage = error.response.data.message;
                } else if (error.message) {
                    errorMessage = error.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    html: errorMessage,
                    confirmButtonText: 'OK'
                });
            })
            .finally(() => {
                if (btn) btn.disabled = false;
                if (spinner) spinner.classList.add('d-none');
            });
    });

    // View transaction details
    document.addEventListener('click', function(e) {
        if (e.target.closest('.view-transaction-btn')) {
            const transactionId = e.target.closest('.view-transaction-btn').dataset.id;
            console.log('Viewing transaction:', transactionId);

            axios.get(`/inventory/${transactionId}`)
                .then(response => {
                    console.log('Transaction details:', response.data);
                    if (response.data.success) {
                        const transaction = response.data.stock;

                        let html = `
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Date:</label>
                                    <p>${new Date(transaction.created_at).toLocaleString()}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Type:</label>
                                    <p><span class="badge bg-${getTypeColor(transaction.type)}">
                                        ${getTypeLabel(transaction.type)}
                                    </span></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Product:</label>
                                    <p>${transaction.product.title} (${transaction.product.sku})</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Location:</label>
                                    <p>${transaction.stock_location.name}</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Quantity:</label>
                                    <p class="fw-bold ${transaction.type === 'in' || transaction.type === 'adjustment' || transaction.type === 'transfer_in' ? 'text-success' : 'text-danger'}">
                                        ${transaction.type === 'in' || transaction.type === 'adjustment' || transaction.type === 'transfer_in' ? '+' : '-'}${transaction.quantity}
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Reference:</label>
                                    <p>${transaction.reference_number}</p>
                                </div>
                            </div>
                        `;

                        if (transaction.type === 'transfer' && transaction.destination_location) {
                            html += `
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-semibold">Transfer To:</label>
                                        <p>${transaction.destination_location.name}</p>
                                    </div>
                                </div>
                            `;
                        }

                        if (transaction.adjustment_reason) {
                            html += `
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-semibold">Reason:</label>
                                        <p>${transaction.adjustment_reason}</p>
                                    </div>
                                </div>
                            `;
                        }

                        if (transaction.notes) {
                            html += `
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-semibold">Notes:</label>
                                        <p>${transaction.notes}</p>
                                    </div>
                                </div>
                            `;
                        }

                        if (transaction.unit_cost) {
                            html += `
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Unit Cost:</label>
                                        <p>₦${parseFloat(transaction.unit_cost).toFixed(2)}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Total Cost:</label>
                                        <p>₦${parseFloat(transaction.total_cost || 0).toFixed(2)}</p>
                                    </div>
                                </div>
                            `;
                        }

                        html += `
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-semibold">Performed By:</label>
                                    <p>${transaction.user?.name || 'System'}</p>
                                </div>
                            </div>
                        `;

                        document.getElementById('transactionDetails').innerHTML = html;
                        new bootstrap.Modal(document.getElementById('viewTransactionModal')).show();
                    }
                })
                .catch(error => {
                    console.error('View Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load transaction details'
                    });
                });
        }

        // Delete transaction
        if (e.target.closest('.delete-transaction-btn')) {
            const transactionId = e.target.closest('.delete-transaction-btn').dataset.id;

            Swal.fire({
                title: 'Delete Transaction?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`/inventory/${transactionId}`)
                        .then(response => {
                            if (response.data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.data.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.data.message
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Delete Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: error.response?.data?.message || 'Failed to delete transaction'
                            });
                        });
                }
            });
        }
    });

    // Reset forms when modals are closed
    document.getElementById('adjustStockModal')?.addEventListener('hidden.bs.modal', function () {
        console.log('Adjust modal closed');
        document.getElementById('adjustStockForm')?.reset();
        document.getElementById('currentStockDisplay').innerHTML = '';
    });

    document.getElementById('transferStockModal')?.addEventListener('hidden.bs.modal', function () {
        console.log('Transfer modal closed');
        document.getElementById('transferStockForm')?.reset();
        document.getElementById('fromStockDisplay').innerHTML = '';
        document.getElementById('toStockDisplay').innerHTML = '';
    });

    // Helper functions
    function getTypeColor(type) {
        const typeColors = {
            'in': 'success',
            'out': 'danger',
            'adjustment': 'warning',
            'transfer': 'info',
            'transfer_in': 'info',
            'return': 'primary',
            'damage': 'dark'
        };
        return typeColors[type] || 'secondary';
    }

    function getTypeLabel(type) {
        const typeLabels = {
            'in': 'Stock In',
            'out': 'Stock Out',
            'adjustment': 'Adjustment',
            'transfer': 'Transfer',
            'transfer_in': 'Transfer In',
            'return': 'Return',
            'damage': 'Damage'
        };
        return typeLabels[type] || type.charAt(0).toUpperCase() + type.slice(1);
    }

    // Initialize adjustment type label
    document.getElementById('adjustment_type')?.dispatchEvent(new Event('change'));
});
</script>
@endsection
