@extends('layouts.master')

@section('title', 'Stock Locations')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- PAGE TITLE -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle ?? 'Stock Locations' }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
                                <li class="breadcrumb-item active">Locations</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LOCATIONS MANAGEMENT -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Stock Locations ({{ $locations->count() }})</h5>
                            @can('Manage stock locations')
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLocationModal">
                                <i class="bi bi-plus-circle me-1"></i> Add Location
                            </button>
                            @endcan
                        </div>
                        <div class="card-body">
                            @if($locations->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="5%">#</th>
                                                <th>Name</th>
                                                <th>Code</th>
                                                <th>Address</th>
                                                <th>Contact</th>
                                                <th>Status</th>
                                                <th>Default</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($locations as $location)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>
                                                        <div class="fw-semibold">{{ $location->name }}</div>
                                                        @if($location->notes)
                                                            <small class="text-muted">{{ Str::limit($location->notes, 50) }}</small>
                                                        @endif
                                                    </td>
                                                    <td>{{ $location->code ?? 'N/A' }}</td>
                                                    <td>{{ Str::limit($location->address, 50) ?? 'N/A' }}</td>
                                                    <td>
                                                        @if($location->contact_person)
                                                            <div>{{ $location->contact_person }}</div>
                                                            <small class="text-muted">{{ $location->phone }}</small>
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-{{ $location->is_active ? 'success' : 'danger' }}">
                                                            {{ $location->is_active ? 'Active' : 'Inactive' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($location->is_default)
                                                            <span class="badge bg-primary">Default</span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button class="btn btn-subtle-secondary btn-sm btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="bi bi-three-dots-vertical"></i>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end">
                                                                <li>
                                                                    <a class="dropdown-item view-location-btn" href="javascript:void(0);" data-id="{{ $location->id }}">
                                                                        <i class="bi bi-eye me-2"></i> View Details
                                                                    </a>
                                                                </li>
                                                                @can('Manage stock locations')
                                                                <li>
                                                                    <a class="dropdown-item edit-location-btn" href="javascript:void(0);" data-id="{{ $location->id }}">
                                                                        <i class="bi bi-pencil me-2"></i> Edit
                                                                    </a>
                                                                </li>
                                                                @if(!$location->is_default)
                                                                <li>
                                                                    <a class="dropdown-item text-danger delete-location-btn" href="javascript:void(0);" data-id="{{ $location->id }}">
                                                                        <i class="bi bi-trash me-2"></i> Delete
                                                                    </a>
                                                                </li>
                                                                @endif
                                                                @endcan
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1"></i>
                                    <p class="mt-2">No stock locations found</p>
                                    @can('Manage stock locations')
                                    <button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addLocationModal">
                                        <i class="bi bi-plus-circle me-1"></i> Add Your First Location
                                    </button>
                                    @endcan
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ADD LOCATION MODAL -->
<div class="modal fade" id="addLocationModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="addLocationForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Stock Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g., Main Warehouse, Store Front">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" placeholder="e.g., WH1, STORE1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="e.g., (123) 456-7890">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Full address..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" class="form-control" placeholder="e.g., John Doe">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="e.g., contact@example.com">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_default" id="is_default">
                                <label class="form-check-label" for="is_default">Set as Default Location</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Additional information..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="addLocationBtn">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="addLocationSpinner"></span>
                        Add Location
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT LOCATION MODAL -->
<div class="modal fade" id="editLocationModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editLocationForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Stock Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="editLocationBody">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="editLocationBtn">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="editLocationSpinner"></span>
                        Update Location
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- VIEW LOCATION MODAL -->
<div class="modal fade" id="viewLocationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Location Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewLocationBody">
                <!-- Content will be loaded here -->
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
    console.log('DOM loaded - Initializing stock locations script');
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
        console.log('CSRF token found:', csrfToken.getAttribute('content'));
    } else {
        console.error('CSRF token not found!');
    }
    
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    
    // Function to escape HTML to prevent XSS
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Function to format date
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        } catch (e) {
            return 'Invalid date';
        }
    }
    
    // Function to format currency
    function formatCurrency(amount) {
        if (amount === null || amount === undefined) return '$0.00';
        return '$' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
    
    // Add location form
    const addLocationForm = document.getElementById('addLocationForm');
    if (addLocationForm) {
        console.log('Add location form found');
        
        addLocationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Add location form submitted');
            
            const btn = document.getElementById('addLocationBtn');
            const spinner = document.getElementById('addLocationSpinner');
            
            if (btn) {
                btn.disabled = true;
                console.log('Submit button disabled');
            }
            
            if (spinner) {
                spinner.classList.remove('d-none');
                console.log('Spinner shown');
            }
            
            // Collect form data
            const formData = new FormData(this);
            const data = {};
            
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            // Convert checkboxes to boolean
            data.is_default = this.querySelector('[name="is_default"]').checked ? 1 : 0;
            data.is_active = this.querySelector('[name="is_active"]').checked ? 1 : 0;
            
            console.log('Form data to submit:', data);
            console.log('Route:', '{{ route("stock-locations.store") }}');
            
            // Make the AJAX request
            axios.post('{{ route("stock-locations.store") }}', data)
                .then(response => {
                    console.log('Response received:', response.data);
                    
                    if (response.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.data.message,
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Close modal
                                const modalElement = document.getElementById('addLocationModal');
                                if (modalElement) {
                                    const modal = bootstrap.Modal.getInstance(modalElement);
                                    if (modal) {
                                        modal.hide();
                                        console.log('Modal closed');
                                    }
                                }
                                
                                // Clear form
                                this.reset();
                                
                                // Reload page to show updated data
                                console.log('Reloading page...');
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.data.message || 'Unknown error occurred'
                        });
                    }
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                    console.error('Error response:', error.response);
                    
                    let errorMessage = 'Failed to add location. Please try again.';
                    
                    if (error.response) {
                        // Server responded with error
                        if (error.response.status === 422 && error.response.data.errors) {
                            // Validation errors
                            errorMessage = Object.values(error.response.data.errors)
                                .flat()
                                .map(err => `<li>${escapeHtml(err)}</li>`)
                                .join('');
                            errorMessage = `<ul style="text-align: left; padding-left: 20px;">${errorMessage}</ul>`;
                        } else if (error.response.data.message) {
                            errorMessage = escapeHtml(error.response.data.message);
                        } else if (error.response.status === 500) {
                            errorMessage = 'Server error. Please try again later.';
                        }
                    } else if (error.request) {
                        // Request made but no response
                        errorMessage = 'No response from server. Please check your internet connection.';
                    } else {
                        // Something else happened
                        errorMessage = error.message || 'Unknown error occurred';
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        html: errorMessage
                    });
                })
                .finally(() => {
                    if (btn) {
                        btn.disabled = false;
                        console.log('Submit button re-enabled');
                    }
                    
                    if (spinner) {
                        spinner.classList.add('d-none');
                        console.log('Spinner hidden');
                    }
                });
        });
    } else {
        console.error('Add location form not found!');
    }
    
    // Edit location button click
    document.addEventListener('click', function(e) {
        const editBtn = e.target.closest('.edit-location-btn');
        if (editBtn) {
            e.preventDefault();
            const locationId = editBtn.dataset.id;
            console.log('Edit clicked for location ID:', locationId);
            
            // Use the route helper for edit
            const editUrl = `{{ route('stock-locations.edit', ':id') }}`.replace(':id', locationId);
            console.log('Fetching edit data from:', editUrl);
            
            axios.get(editUrl)
                .then(response => {
                    console.log('Edit response:', response.data);
                    if (response.data.success) {
                        const location = response.data.location;
                        
                        // Build the edit form HTML
                        let html = `
                            <div class="mb-3">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required value="${escapeHtml(location.name)}">
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Code</label>
                                    <input type="text" name="code" class="form-control" value="${escapeHtml(location.code || '')}" placeholder="e.g., WH1, STORE1">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone" class="form-control" value="${escapeHtml(location.phone || '')}" placeholder="e.g., (123) 456-7890">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2" placeholder="Full address...">${escapeHtml(location.address || '')}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="contact_person" class="form-control" value="${escapeHtml(location.contact_person || '')}" placeholder="e.g., John Doe">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="${escapeHtml(location.email || '')}" placeholder="e.g., contact@example.com">
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_default" id="edit_is_default" ${location.is_default ? 'checked' : ''} ${location.is_default ? 'disabled' : ''}>
                                        <label class="form-check-label" for="edit_is_default">Set as Default Location</label>
                                        ${location.is_default ? '<small class="text-muted d-block">This is the current default location</small>' : ''}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" ${location.is_active ? 'checked' : ''}>
                                        <label class="form-check-label" for="edit_is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Additional information...">${escapeHtml(location.notes || '')}</textarea>
                            </div>
                            <input type="hidden" name="id" value="${location.id}">
                        `;
                        
                        document.getElementById('editLocationBody').innerHTML = html;
                        
                        // Show the modal
                        const editModal = new bootstrap.Modal(document.getElementById('editLocationModal'));
                        editModal.show();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.data.message || 'Failed to load location data'
                        });
                    }
                })
                .catch(error => {
                    console.error('Edit error:', error);
                    console.error('Error response:', error.response);
                    
                    let errorMessage = 'Failed to load location data for editing';
                    
                    if (error.response?.status === 404) {
                        errorMessage = 'Location not found';
                    } else if (error.response?.data?.message) {
                        errorMessage = error.response.data.message;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage
                    });
                });
        }
        
        // View location button click
        const viewBtn = e.target.closest('.view-location-btn');
        if (viewBtn) {
            e.preventDefault();
            const locationId = viewBtn.dataset.id;
            console.log('View clicked for location ID:', locationId);
            
            // Use the route helper for show - make sure the route exists
            const viewUrl = `{{ route('stock-locations.show', ':id') }}`.replace(':id', locationId);
            console.log('Fetching view data from:', viewUrl);
            
            axios.get(viewUrl)
                .then(response => {
                    console.log('View response:', response.data);
                    if (response.data.success) {
                        const location = response.data.location;
                        
                        let html = `
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Name:</label>
                                    <p>${escapeHtml(location.name)}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Code:</label>
                                    <p>${escapeHtml(location.code || 'N/A')}</p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Status:</label>
                                    <p><span class="badge bg-${location.is_active ? 'success' : 'danger'}">${location.is_active ? 'Active' : 'Inactive'}</span></p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Default:</label>
                                    <p>${location.is_default ? '<span class="badge bg-primary">Default Location</span>' : 'No'}</p>
                                </div>
                            </div>
                        `;
                        
                        // Add address if exists
                        if (location.address) {
                            html += `
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Address:</label>
                                        <p>${escapeHtml(location.address).replace(/\n/g, '<br>')}</p>
                                    </div>
                                </div>
                            `;
                        }
                        
                        // Add contact info if exists
                        if (location.contact_person || location.phone || location.email) {
                            html += `
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Contact Information:</label>
                                        <p>
                                            ${location.contact_person ? `<strong>${escapeHtml(location.contact_person)}</strong><br>` : ''}
                                            ${location.phone ? `<i class="bi bi-telephone me-1"></i> ${escapeHtml(location.phone)}<br>` : ''}
                                            ${location.email ? `<i class="bi bi-envelope me-1"></i> ${escapeHtml(location.email)}` : ''}
                                        </p>
                                    </div>
                                </div>
                            `;
                        }
                        
                        // Add notes if exists
                        if (location.notes) {
                            html += `
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Notes:</label>
                                        <p>${escapeHtml(location.notes).replace(/\n/g, '<br>')}</p>
                                    </div>
                                </div>
                            `;
                        }
                        
                        // Add timestamps
                        html += `
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Created:</label>
                                    <p>${formatDate(location.created_at)}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Last Updated:</label>
                                    <p>${formatDate(location.updated_at)}</p>
                                </div>
                            </div>
                        `;
                        
                        // Add stock summary if available
                        if (response.data.stock_summary) {
                            const summary = response.data.stock_summary;
                            html += `
                                <hr>
                                <h6 class="mb-3">Stock Summary</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <small class="text-muted">Total Stock In:</small>
                                        <div class="fw-semibold">${summary.total_in || 0}</div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <small class="text-muted">Total Stock Out:</small>
                                        <div class="fw-semibold">${summary.total_out || 0}</div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <small class="text-muted">Adjustments:</small>
                                        <div class="fw-semibold">${summary.total_adjustments || 0}</div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <small class="text-muted">Transfers:</small>
                                        <div class="fw-semibold">${summary.total_transfers || 0}</div>
                                    </div>
                                    <div class="col-12 mb-2">
                                        <small class="text-muted">Total Stock Value:</small>
                                        <div class="fw-semibold">${formatCurrency(summary.total_value || 0)}</div>
                                    </div>
                                </div>
                            `;
                        }
                        
                        // Add recent transactions if available
                        if (response.data.location && response.data.location.stocks && response.data.location.stocks.length > 0) {
                            html += `
                                <hr>
                                <h6 class="mb-3">Recent Transactions</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Product</th>
                                                <th>Type</th>
                                                <th>Qty</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                            `;
                            
                            response.data.location.stocks.forEach(transaction => {
                                const typeColors = {
                                    'in': 'success',
                                    'out': 'danger',
                                    'adjustment': 'warning',
                                    'transfer': 'info'
                                };
                                
                                html += `
                                    <tr>
                                        <td><small>${formatDate(transaction.created_at)}</small></td>
                                        <td><small>${escapeHtml(transaction.product?.title || 'N/A')}</small></td>
                                        <td><span class="badge bg-${typeColors[transaction.type] || 'secondary'}">${transaction.type}</span></td>
                                        <td><small>${transaction.quantity}</small></td>
                                    </tr>
                                `;
                            });
                            
                            html += `
                                        </tbody>
                                    </table>
                                </div>
                            `;
                        }
                        
                        document.getElementById('viewLocationBody').innerHTML = html;
                        
                        // Show the modal
                        const viewModal = new bootstrap.Modal(document.getElementById('viewLocationModal'));
                        viewModal.show();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.data.message || 'Failed to load location details'
                        });
                    }
                })
                .catch(error => {
                    console.error('View error:', error);
                    console.error('Error response:', error.response);
                    
                    let errorMessage = 'Failed to load location details';
                    
                    if (error.response?.status === 404) {
                        errorMessage = 'Location not found';
                    } else if (error.response?.status === 405) {
                        errorMessage = 'GET method not allowed for this route. Please check your routes configuration.';
                    } else if (error.response?.data?.message) {
                        errorMessage = error.response.data.message;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage
                    });
                });
        }
        
        // Delete location button click
        const deleteBtn = e.target.closest('.delete-location-btn');
        if (deleteBtn) {
            e.preventDefault();
            const locationId = deleteBtn.dataset.id;
            console.log('Delete clicked for location ID:', locationId);
            
            Swal.fire({
                title: 'Delete Location?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Use the route helper for destroy
                    const deleteUrl = `{{ route('stock-locations.destroy', ':id') }}`.replace(':id', locationId);
                    console.log('Deleting from:', deleteUrl);
                    
                    axios.delete(deleteUrl)
                        .then(response => {
                            console.log('Delete response:', response.data);
                            if (response.data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.data.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    console.log('Reloading page after delete...');
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
                            console.error('Delete error:', error);
                            let errorMessage = 'Failed to delete location';
                            
                            if (error.response?.data?.message) {
                                errorMessage = error.response.data.message;
                            }
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: errorMessage
                            });
                        });
                }
            });
        }
    });
    
    // Edit location form submission
    const editLocationForm = document.getElementById('editLocationForm');
    if (editLocationForm) {
        editLocationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Edit location form submitted');
            
            const btn = document.getElementById('editLocationBtn');
            const spinner = document.getElementById('editLocationSpinner');
            
            if (btn) btn.disabled = true;
            if (spinner) spinner.classList.remove('d-none');
            
            // Collect form data
            const formData = new FormData(this);
            const data = {};
            
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            // Convert checkboxes to boolean
            const isDefaultCheckbox = this.querySelector('[name="is_default"]');
            const isActiveCheckbox = this.querySelector('[name="is_active"]');
            data.is_default = isDefaultCheckbox && !isDefaultCheckbox.disabled && isDefaultCheckbox.checked ? 1 : 0;
            data.is_active = isActiveCheckbox && isActiveCheckbox.checked ? 1 : 0;
            
            const locationId = data.id;
            console.log('Updating location ID:', locationId);
            console.log('Form data:', data);
            
            // Use the route helper for update
            const updateUrl = `{{ route('stock-locations.update', ':id') }}`.replace(':id', locationId);
            console.log('Updating via:', updateUrl);
            
            // Use POST with _method=PUT for Laravel
            data._method = 'PUT';
            
            axios.post(updateUrl, data)
                .then(response => {
                    console.log('Update response:', response.data);
                    if (response.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.data.message,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Close modal
                            const modalElement = document.getElementById('editLocationModal');
                            if (modalElement) {
                                const modal = bootstrap.Modal.getInstance(modalElement);
                                if (modal) modal.hide();
                            }
                            
                            // Reload page to show updated data
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.data.message || 'Failed to update location'
                        });
                    }
                })
                .catch(error => {
                    console.error('Update error:', error);
                    console.error('Error response:', error.response);
                    
                    let errorMessage = 'Failed to update location. Please try again.';
                    
                    if (error.response?.status === 422 && error.response.data.errors) {
                        // Validation errors
                        errorMessage = Object.values(error.response.data.errors)
                            .flat()
                            .map(err => `<li>${escapeHtml(err)}</li>`)
                            .join('');
                        errorMessage = `<ul style="text-align: left; padding-left: 20px;">${errorMessage}</ul>`;
                    } else if (error.response?.data?.message) {
                        errorMessage = error.response.data.message;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        html: errorMessage
                    });
                })
                .finally(() => {
                    if (btn) btn.disabled = false;
                    if (spinner) spinner.classList.add('d-none');
                });
        });
    }
    
    // Reset add form when modal closes
    const addModal = document.getElementById('addLocationModal');
    if (addModal) {
        addModal.addEventListener('hidden.bs.modal', function () {
            console.log('Add modal closed, resetting form');
            const form = document.getElementById('addLocationForm');
            if (form) {
                form.reset();
            }
        });
    }
    
    // Reset edit form when modal closes
    const editModal = document.getElementById('editLocationModal');
    if (editModal) {
        editModal.addEventListener('hidden.bs.modal', function () {
            console.log('Edit modal closed, clearing form');
            document.getElementById('editLocationBody').innerHTML = '';
        });
    }
    
    // Reset view modal when it closes
    const viewModal = document.getElementById('viewLocationModal');
    if (viewModal) {
        viewModal.addEventListener('hidden.bs.modal', function () {
            console.log('View modal closed, clearing content');
            document.getElementById('viewLocationBody').innerHTML = '';
        });
    }
    
    console.log('Stock locations script initialized successfully');
});
</script>

@endsection