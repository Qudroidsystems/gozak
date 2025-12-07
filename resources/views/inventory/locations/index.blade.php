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

@endsection

@section('scripts')
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    
    // Add location form
    document.getElementById('addLocationForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('addLocationBtn');
        const spinner = document.getElementById('addLocationSpinner');
        if (btn) btn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');
        
        const formData = new FormData(this);
        
        console.log('Submitting form data:', Object.fromEntries(formData));
        
        axios.post('{{ route("stock-locations.store") }}', formData)
            .then(response => {
                console.log('Response:', response.data);
                if (response.data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.data.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('addLocationModal'));
                        if (modal) modal.hide();
                        // Reload page to show updated table
                        location.reload();
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
                console.error('Error:', error);
                console.error('Error response:', error.response);
                
                let errorMessage = 'Failed to add location';
                if (error.response?.data?.errors) {
                    errorMessage = Object.values(error.response.data.errors).flat().join('<br>');
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
    
    // Edit location
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-location-btn')) {
            const locationId = e.target.closest('.edit-location-btn').dataset.id;
            
            axios.get(`/stock-locations/${locationId}/edit`)
                .then(response => {
                    if (response.data.success) {
                        const location = response.data.location;
                        
                        let html = `
                            <input type="hidden" name="id" value="${location.id}">
                            <div class="mb-3">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="${location.name}" required>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Code</label>
                                    <input type="text" name="code" class="form-control" value="${location.code || ''}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone" class="form-control" value="${location.phone || ''}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2">${location.address || ''}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="contact_person" class="form-control" value="${location.contact_person || ''}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="${location.email || ''}">
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_default" id="edit_is_default" ${location.is_default ? 'checked' : ''}>
                                        <label class="form-check-label" for="edit_is_default">Set as Default Location</label>
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
                                <textarea name="notes" class="form-control" rows="3">${location.notes || ''}</textarea>
                            </div>
                        `;
                        
                        document.getElementById('editLocationBody').innerHTML = html;
                        
                        new bootstrap.Modal(document.getElementById('editLocationModal')).show();
                    }
                })
                .catch(error => {
                    console.error('Edit error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load location details'
                    });
                });
        }
        
        // View location
        if (e.target.closest('.view-location-btn')) {
            const locationId = e.target.closest('.view-location-btn').dataset.id;
            
            axios.get(`/stock-locations/${locationId}`)
                .then(response => {
                    if (response.data.success) {
                        const location = response.data.location;
                        const summary = response.data.stock_summary || {};
                        
                        let html = `
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Name:</label>
                                    <p>${location.name}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Status:</label>
                                    <p><span class="badge bg-${location.is_active ? 'success' : 'danger'}">
                                        ${location.is_active ? 'Active' : 'Inactive'}
                                    </span></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Code:</label>
                                    <p>${location.code || 'N/A'}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Default:</label>
                                    <p>${location.is_default ? '<span class="badge bg-primary">Yes</span>' : 'No'}</p>
                                </div>
                            </div>
                        `;
                        
                        if (location.address) {
                            html += `
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-semibold">Address:</label>
                                        <p>${location.address}</p>
                                    </div>
                                </div>
                            `;
                        }
                        
                        if (location.contact_person) {
                            html += `
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Contact Person:</label>
                                        <p>${location.contact_person}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Phone:</label>
                                        <p>${location.phone || 'N/A'}</p>
                                    </div>
                                </div>
                            `;
                        }
                        
                        if (location.email) {
                            html += `
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-semibold">Email:</label>
                                        <p>${location.email}</p>
                                    </div>
                                </div>
                            `;
                        }
                        
                        if (location.notes) {
                            html += `
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-semibold">Notes:</label>
                                        <p>${location.notes}</p>
                                    </div>
                                </div>
                            `;
                        }
                        
                        // Show stock summary if available
                        if (Object.keys(summary).length > 0) {
                            html += `
                                <hr>
                                <h6 class="mb-3">Stock Summary</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <small class="text-muted">Total Stock In:</small>
                                        <p class="fw-semibold">${summary.total_in || 0}</p>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <small class="text-muted">Total Stock Out:</small>
                                        <p class="fw-semibold">${summary.total_out || 0}</p>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <small class="text-muted">Adjustments:</small>
                                        <p class="fw-semibold">${summary.total_adjustments || 0}</p>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <small class="text-muted">Transfers:</small>
                                        <p class="fw-semibold">${summary.total_transfers || 0}</p>
                                    </div>
                                    <div class="col-md-12 mb-2">
                                        <small class="text-muted">Total Value:</small>
                                        <p class="fw-semibold">$${(summary.total_value || 0).toFixed(2)}</p>
                                    </div>
                                </div>
                            `;
                        }
                        
                        document.getElementById('viewLocationBody').innerHTML = html;
                        new bootstrap.Modal(document.getElementById('viewLocationModal')).show();
                    }
                })
                .catch(error => {
                    console.error('View error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load location details'
                    });
                });
        }
        
        // Delete location
        if (e.target.closest('.delete-location-btn')) {
            const locationId = e.target.closest('.delete-location-btn').dataset.id;
            
            Swal.fire({
                title: 'Delete Location?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`/stock-locations/${locationId}`)
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
                            console.error('Delete error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: error.response?.data?.message || 'Failed to delete location'
                            });
                        });
                }
            });
        }
    });
    
    // Edit location form submission
    document.getElementById('editLocationForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('editLocationBtn');
        const spinner = document.getElementById('editLocationSpinner');
        if (btn) btn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');
        
        const formData = new FormData(this);
        const locationId = formData.get('id');
        
        if (!locationId) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Location ID is missing'
            });
            return;
        }
        
        // Use PUT method for update
        formData.append('_method', 'PUT');
        
        axios.post(`/stock-locations/${locationId}`, formData)
            .then(response => {
                if (response.data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.data.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editLocationModal'));
                        if (modal) modal.hide();
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.data.message
                    });
                }
            })
            .catch(error => {
                console.error('Update error:', error);
                let errorMessage = 'Failed to update location';
                if (error.response?.data?.errors) {
                    errorMessage = Object.values(error.response.data.errors).flat().join('<br>');
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
    
    // Reset forms when modals are closed
    document.getElementById('addLocationModal')?.addEventListener('hidden.bs.modal', function () {
        document.getElementById('addLocationForm')?.reset();
    });
    
    document.getElementById('editLocationModal')?.addEventListener('hidden.bs.modal', function () {
        document.getElementById('editLocationForm')?.reset();
        document.getElementById('editLocationBody').innerHTML = '';
    });
    
    document.getElementById('viewLocationModal')?.addEventListener('hidden.bs.modal', function () {
        document.getElementById('viewLocationBody').innerHTML = '';
    });
});
</script>
@endsection
@endsection