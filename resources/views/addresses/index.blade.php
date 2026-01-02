@extends('layouts.master')

@section('title', 'Address Management')

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .card { border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .table th { background-color: #f8f9fa; font-weight: 600; }
    .badge-default {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 0.5em 1em;
        border-radius: 50px;
    }
    .modal-header {
        background: linear-gradient(135deg, #6f42c1, #e83e8c);
        color: white;
        border-radius: 12px 12px 0 0;
    }
    .modal-content { border-radius: 12px; overflow: hidden; border: none; }
    .btn-primary {
        background: linear-gradient(135deg, #6f42c1, #e83e8c);
        border: none;
    }
    .btn-primary:hover { opacity: 0.9; }
    .form-control, .form-select { border-radius: 8px; }
    .loading-spinner { display: none; }
</style>
@endsection

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0 fw-bold">Address Management</h4>
                        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#createModal">
                            <i class="bi bi-plus-circle me-2"></i> Add New Address
                        </button>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5 position-relative">
                                    <input type="text" id="searchInput" class="form-control ps-5" placeholder="🔍 Live search: customer, street, city, phone...">
                                    <i class="bi bi-search position-absolute top-50 start-3 translate-middle-y text-muted"></i>
                                    <div class="loading-spinner position-absolute top-50 end-3 translate-middle-y">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <select id="customerFilter" class="form-select">
                                        <option value="">All Customers</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">
                                                {{ $customer->first_name }} {{ $customer->last_name }} ({{ $customer->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button id="clearFilters" class="btn btn-outline-secondary w-100">
                                        <i class="bi bi-arrow-repeat"></i> Clear Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div id="addressesTableContainer">
                                @include('addresses.partials.table')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CREATE MODAL -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="createForm" action="{{ route('adminaddresses.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add New Address</h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label>Customer <span class="text-danger">*</span></label>
                            <select name="user_id" class="form-select" required>
                                <option value="">Select Customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->first_name }} {{ $customer->last_name }} ({{ $customer->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6"><label>Recipient Name</label><input type="text" name="name" class="form-control"></div>
                        <div class="col-md-6"><label>Phone Number <span class="text-danger">*</span></label><input type="text" name="phone_number" class="form-control" required></div>
                        <div class="col-md-12"><label>Street Address <span class="text-danger">*</span></label><input type="text" name="street" class="form-control" required></div>
                        <div class="col-md-5"><label>City <span class="text-danger">*</span></label><input type="text" name="city" class="form-control" required></div>
                        <div class="col-md-4"><label>State <span class="text-danger">*</span></label><input type="text" name="state" class="form-control" required></div>
                        <div class="col-md-3"><label>Postal Code <span class="text-danger">*</span></label><input type="text" name="postal_code" class="form-control" required></div>
                        <div class="col-md-8"><label>Country <span class="text-danger">*</span></label><input type="text" name="country" class="form-control" value="United States" required></div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1">
                                <label class="form-check-label">Set as default</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Address</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Address</h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label>Customer <span class="text-danger">*</span></label>
                            <select name="user_id" id="edit-user_id" class="form-select" required></select>
                        </div>
                        <div class="col-md-6"><label>Recipient Name</label><input type="text" name="name" id="edit-name" class="form-control"></div>
                        <div class="col-md-6"><label>Phone Number <span class="text-danger">*</span></label><input type="text" name="phone_number" id="edit-phone_number" class="form-control" required></div>
                        <div class="col-md-12"><label>Street Address <span class="text-danger">*</span></label><input type="text" name="street" id="edit-street" class="form-control" required></div>
                        <div class="col-md-5"><label>City <span class="text-danger">*</span></label><input type="text" name="city" id="edit-city" class="form-control" required></div>
                        <div class="col-md-4"><label>State <span class="text-danger">*</span></label><input type="text" name="state" id="edit-state" class="form-control" required></div>
                        <div class="col-md-3"><label>Postal Code <span class="text-danger">*</span></label><input type="text" name="postal_code" id="edit-postal_code" class="form-control" required></div>
                        <div class="col-md-8"><label>Country <span class="text-danger">*</span></label><input type="text" name="country" id="edit-country" class="form-control" required></div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_default" id="edit-is_default" value="1">
                                <label class="form-check-label">Set as default</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Address</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- VIEW MODAL -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Address Details</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4 fw-bold">Recipient Name:</div><div class="col-md-8" id="view-name">-</div>
                    <div class="col-md-4 fw-bold">Customer:</div><div class="col-md-8" id="view-customer">-</div>
                    <div class="col-md-4 fw-bold">Street:</div><div class="col-md-8" id="view-street">-</div>
                    <div class="col-md-4 fw-bold">City, State, ZIP:</div><div class="col-md-8" id="view-city">-</div>
                    <div class="col-md-4 fw-bold">Country:</div><div class="col-md-8" id="view-country">-</div>
                    <div class="col-md-4 fw-bold">Phone:</div><div class="col-md-8" id="view-phone">-</div>
                    <div class="col-md-4 fw-bold">Default:</div><div class="col-md-8" id="view-default">-</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
// Axios Laravel setup
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'text/html';

const csrfToken = document.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
}

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

let searchTimeout;

function loadAddresses() {
    const search = document.getElementById('searchInput').value.trim();
    const customerId = document.getElementById('customerFilter').value;

    document.querySelector('.loading-spinner').style.display = 'block';

    axios.get('{{ route("adminaddresses.index") }}', {
        params: { search, customer_id: customerId },
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => {
        document.getElementById('addressesTableContainer').innerHTML = response.data;
    })
    .catch(err => {
        console.error(err);
        Toast.fire({ icon: 'error', title: 'Failed to load addresses' });
    })
    .finally(() => {
        document.querySelector('.loading-spinner').style.display = 'none';
    });
}

document.getElementById('searchInput').addEventListener('input', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(loadAddresses, 400);
});

document.getElementById('customerFilter').addEventListener('change', loadAddresses);
document.getElementById('clearFilters').addEventListener('click', () => {
    document.getElementById('searchInput').value = '';
    document.getElementById('customerFilter').value = '';
    loadAddresses();
});

function viewAddress(id) {
    axios.get(`/adminaddresses/${id}`).then(res => {
        const a = res.data.address;
        document.getElementById('view-name').textContent = a.name || '—';
        document.getElementById('view-customer').textContent = `${a.user.first_name} ${a.user.last_name} (${a.user.email})`;
        document.getElementById('view-street').textContent = a.street;
        document.getElementById('view-city').textContent = `${a.city}, ${a.state} ${a.postal_code}`;
        document.getElementById('view-country').textContent = a.country;
        document.getElementById('view-phone').textContent = a.phone_number;
        document.getElementById('view-default').innerHTML = a.is_default ? '<span class="badge badge-default">Default</span>' : '<span class="text-muted">No</span>';
        new bootstrap.Modal(document.getElementById('viewModal')).show();
    });
}

function editAddress(id) {
    axios.get(`/adminaddresses/${id}/edit`).then(res => {
        const a = res.data.address;
        const customers = res.data.customers;
        document.getElementById('editForm').action = `/adminaddresses/${id}`;
        document.getElementById('edit-user_id').value = a.user_id;
        document.getElementById('edit-name').value = a.name || '';
        document.getElementById('edit-street').value = a.street;
        document.getElementById('edit-city').value = a.city;
        document.getElementById('edit-state').value = a.state;
        document.getElementById('edit-postal_code').value = a.postal_code;
        document.getElementById('edit-country').value = a.country;
        document.getElementById('edit-phone_number').value = a.phone_number;
        document.getElementById('edit-is_default').checked = a.is_default;

        const select = document.getElementById('edit-user_id');
        select.innerHTML = '<option value="">Select Customer</option>' + customers.map(c =>
            `<option value="${c.id}" ${c.id == a.user_id ? 'selected' : ''}>${c.first_name} ${c.last_name} (${c.email})</option>`
        ).join('');

        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
}

function deleteAddress(id) {
    Swal.fire({
        title: 'Delete address?',
        text: "This cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!'
    }).then(result => {
        if (result.isConfirmed) {
            axios.delete(`/adminaddresses/${id}`).then(() => {
                Toast.fire({ icon: 'success', title: 'Address deleted!' });
                loadAddresses();
            });
        }
    });
}

document.querySelectorAll('#createForm, #editForm').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const method = this.querySelector('input[name="_method"]') ? 'put' : 'post';
        axios({ method, url: this.action, data: new FormData(this) })
            .then(res => {
                Toast.fire({ icon: 'success', title: res.data.message });
                bootstrap.Modal.getInstance(this.closest('.modal')).hide();
                loadAddresses();
            })
            .catch(err => {
                let msg = 'Validation error.<br>';
                if (err.response?.data?.errors) {
                    Object.values(err.response.data.errors).forEach(e => msg += e.join('<br>') + '<br>');
                }
                Swal.fire('Error', msg, 'error');
            });
    });
});
</script>
@endsection
