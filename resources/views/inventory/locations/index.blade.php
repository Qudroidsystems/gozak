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