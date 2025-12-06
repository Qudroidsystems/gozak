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

            <!-- ACTIONS -->
            <div class="card mt-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Stock Locations</h5>
                        @can('Manage stock locations')
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createLocationModal">
                                <i class="bi bi-plus-circle me-1"></i> Add Location
                            </button>
                        @endcan
                    </div>
                </div>
            </div>

            <!-- LOCATIONS TABLE -->
            <div class="card mt-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered align-middle table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Default</th>
                                    <th>Total Products</th>
                                    <th>Total Value</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($locations as $location)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $location->name }}</div>
                                            @if($location->address)
                                                <small class="text-muted">{{ Str::limit($location->address, 50) }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $location->code ?? '-' }}</td>
                                        <td>
                                            @if($location->contact_person)
                                                <div>{{ $location->contact_person }}</div>
                                            @endif
                                            @if($location->phone)
                                                <small class="text-muted">{{ $location->phone }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $location->is_active ? 'success' : 'danger' }}-subtle text-{{ $location->is_active ? 'success' : 'danger' }} border border-{{ $location->is_active ? 'success' : 'danger' }}-subtle">
                                                {{ $location->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($location->is_default)
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                    Default
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $location->total_products ?? 0 }}</td>
                                        <td>${{ number_format($location->total_value, 2) }}</td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-subtle-secondary btn-sm btn-icon" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
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
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('inventory.index') }}?location_id={{ $location->id }}">
                                                            <i class="bi bi-list-check me-2"></i> View Transactions
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('inventory.stock-levels') }}?location_id={{ $location->id }}">
                                                            <i class="bi bi-box-seam me-2"></i> View Stock
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="bi bi-shop fs-1"></i>
                                            <p class="mt-2">No stock locations found</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- CREATE LOCATION MODAL -->
@can('Manage stock locations')
<div class="modal fade" id="createLocationModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="createLocationForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Stock Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" class="form-control" placeholder="e.g., WAREHOUSE-01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="is_default" class="form-check-input" id="is_default">
                                <label class="form-check-label" for="is_default">Set as Default Location</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="is_active" checked>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Location</button>
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
                <input type="hidden" name="location_id" id="edit_location_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Stock Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="editLocationContent">
                    <!-- Content loaded via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Location</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
    
    @can('Manage stock locations')
    // Create location form
    document.getElementById('createLocationForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        axios.post('{{ route("stock-locations.store") }}', new FormData(this))
            .then(response => {
                if (response.data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.data.message
                    }).then(() => {
                        location.reload();
                    });
                }
            })
            .catch(error => {
                let errorMessage = 'Failed to create location';
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
                            <div class="mb-3">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="${location.name}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Code</label>
                                <input type="text" name="code" class="form-control" value="${location.code || ''}" placeholder="e.g., WAREHOUSE-01">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2">${location.address || ''}</textarea>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Contact Person</label>
                                    <input type="text" name="contact_person" class="form-control" value="${location.contact_person || ''}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone" class="form-control" value="${location.phone || ''}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="${location.email || ''}">
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_default" class="form-check-input" id="edit_is_default" ${location.is_default ? 'checked' : ''}>
                                        <label class="form-check-label" for="edit_is_default">Set as Default Location</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_active" class="form-check-input" id="edit_is_active" ${location.is_active ? 'checked' : ''}>
                                        <label class="form-check-label" for="edit_is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3">${location.notes || ''}</textarea>
                            </div>
                        `;
                        
                        document.getElementById('editLocationContent').innerHTML = html;
                        document.getElementById('edit_location_id').value = locationId;
                        new bootstrap.Modal(document.getElementById('editLocationModal')).show();
                    }
                })
                .catch(error => {
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
                                    text: response.data.message
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
        
        const locationId = document.getElementById('edit_location_id').value;
        
        axios.post(`/stock-locations/${locationId}`, new FormData(this))
            .then(response => {
                if (response.data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.data.message
                    }).then(() => {
                        location.reload();
                    });
                }
            })
            .catch(error => {
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
            });
    });
    @endcan
});
</script>
@endsection