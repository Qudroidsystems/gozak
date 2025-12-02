{{-- resources/views/banners/index.blade.php --}}
@extends('layouts.master')

@section('title', 'Banners Management')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Page Title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Banners</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Marketing</a></li>
                                <li class="breadcrumb-item active">Banners</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Banners Table -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card" id="bannerList">
                        <div class="card-header d-flex align-items-center">
                            <h5 class="card-title mb-0 flex-grow-1">All Banners</h5>
                            <div class="flex-shrink-0">
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-danger d-none" id="remove-actions" onclick="deleteMultiple()">
                                        Delete Selected
                                    </button>
                                    @can('Create banner')
                                        <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#showModal">
                                            Add Banner
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="50">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="checkAll">
                                                </div>
                                            </th>
                                            <th>#</th>
                                            <th>Image</th>
                                            <th>Target Screen</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="list form-check-all">
                                        @forelse($banners as $banner)
                                        <tr class="border-bottom border-light-subtle">
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="chk_child" value="{{ $banner->id }}">
                                                </div>
                                            </td>
                                            <td class="fw-medium">{{ $loop->iteration }}</td>
                                            <td>
                                                @if($banner->image_url)
                                                    <img src="{{ asset('storage/' . $banner->image_url) }}"
                                                         class="rounded shadow-sm"
                                                         style="width: 220px; height: 110px; object-fit: cover;"
                                                         alt="Banner">
                                                @else
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                                         style="width:220px;height:110px;">
                                                        <i class="bi bi-image text-muted fs-3"></i>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-info-subtle text-info fs-6">
                                                    {{ ucwords(str_replace('_', ' ', $banner->target_screen)) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $banner->active ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ $banner->active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>{{ $banner->created_at->format('d M Y') }}</td>
                                            <td>
                                                <ul class="list-inline hstack gap-2 mb-0">
                                                    @can('Update banner')
                                                        <li>
                                                            <button class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn" data-id="{{ $banner->id }}">
                                                                <i class="ph-pencil"></i>
                                                            </button>
                                                        </li>
                                                    @endcan
                                                    @can('Delete banner')
                                                        <li>
                                                            <button class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" data-id="{{ $banner->id }}">
                                                                <i class="ph-trash"></i>
                                                            </button>
                                                        </li>
                                                    @endcan
                                                </ul>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">No banners found</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <div class="pagination-wrap hstack gap-2">
                                    <a class="page-item pagination-prev {{ $banners->onFirstPage() ? 'disabled' : '' }}"
                                       href="{{ $banners->previousPageUrl() }}">Previous</a>
                                    <span class="px-3 py-2 bg-light rounded">{{ $banners->currentPage() }} / {{ $banners->lastPage() }}</span>
                                    <a class="page-item pagination-next {{ $banners->hasMorePages() ? '' : 'disabled' }}"
                                       href="{{ $banners->nextPageUrl() }}">Next</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
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
                                <label class="form-label">Banner Image <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" name="image" accept="image/*" required>
                                <small class="text-muted">Recommended size: 1200×600px or larger</small>
                            </div>
                            <div class="text-center">
                                <img id="image_preview" class="rounded shadow" style="max-width:100%; max-height:300px; display:none;" alt="Preview">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label class="form-label">Target Screen</label>
                                <select class="form-select" name="target_screen" required>
                                    <option value="home">Home Screen</option>
                                    <option value="category">Category Page</option>
                                    <option value="product">Product Detail</option>
                                    <option value="offers">Offers Page</option>
                                    <option value="all">All Pages</option>
                                </select>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="active" value="1" id="active" checked>
                                <label class="form-check-label">Active (Visible to users)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Save Banner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteRecordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <i class="bi bi-trash text-danger display-4"></i>
                <h4 class="mt-4">Delete Banner?</h4>
                <p class="text-muted">This action cannot be undone.</p>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="delete-record">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- ALL SCRIPTS - INLINE & CDN -->
<!-- Replace the entire script section with this -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/list.js@2.3.1/dist/list.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Setup axios defaults
    axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    // List.js
    new List('bannerList', {
        valueNames: ['target_screen', 'active'],
        page: 10,
        pagination: true
    });

    // Checkbox Select All
    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            document.querySelectorAll('input[name="chk_child"]').forEach(cb => {
                cb.checked = this.checked;
                const row = cb.closest('tr');
                if (row) {
                    row.classList.toggle('table-active', this.checked);
                }
            });
            toggleRemoveBtn();
        });
    }

    // Individual checkboxes
    function attachCheckboxListeners() {
        document.querySelectorAll('input[name="chk_child"]').forEach(cb => {
            cb.addEventListener('change', function() {
                const row = this.closest('tr');
                if (row) {
                    row.classList.toggle('table-active', this.checked);
                }
                
                const allCheckboxes = document.querySelectorAll('input[name="chk_child"]');
                const checkedCount = document.querySelectorAll('input[name="chk_child"]:checked').length;
                
                if (checkAll) {
                    checkAll.checked = checkedCount === allCheckboxes.length;
                }
                toggleRemoveBtn();
            });
        });
    }

    attachCheckboxListeners();

    function toggleRemoveBtn() {
        const count = document.querySelectorAll('input[name="chk_child"]:checked').length;
        const removeBtn = document.getElementById('remove-actions');
        if (removeBtn) {
            removeBtn.classList.toggle('d-none', count === 0);
        }
    }

    const modalElement = document.getElementById('showModal');
    const modal = new bootstrap.Modal(modalElement);
    const form = document.getElementById('bannerForm');
    const imgPreview = document.getElementById('image_preview');
    let deleteId = null;

    // Add button
    const addBtn = document.querySelector('.add-btn');
    if (addBtn) {
        addBtn.addEventListener('click', () => {
            form.reset();
            document.getElementById('banner_id').value = '';
            document.getElementById('modalTitle').textContent = 'Add Banner';
            document.getElementById('submitBtn').textContent = 'Save Banner';
            imgPreview.style.display = 'none';
            imgPreview.src = '';
            // Make image required for add
            form.querySelector('[name="image"]').required = true;
        });
    }

    // Edit buttons
    function attachEditListeners() {
        document.querySelectorAll('.edit-item-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                
                console.log('Editing banner ID:', id);
                
                axios.get(`/banners/${id}/edit`)
                    .then(res => {
                        console.log('Edit response:', res.data);
                        const b = res.data;
                        
                        document.getElementById('banner_id').value = b.id;
                        form.querySelector('[name="target_screen"]').value = b.target_screen;
                        document.getElementById('active').checked = b.active == 1;

                        if (b.image_url) {
                            const imageUrl = b.image_url.startsWith('http') ? b.image_url : `/storage/${b.image_url}`;
                            imgPreview.src = imageUrl;
                            imgPreview.style.display = 'block';
                        } else {
                            imgPreview.style.display = 'none';
                        }

                        // Make image optional for edit
                        form.querySelector('[name="image"]').required = false;

                        document.getElementById('modalTitle').textContent = 'Edit Banner';
                        document.getElementById('submitBtn').textContent = 'Update Banner';
                        modal.show();
                    })
                    .catch(err => {
                        console.error('Edit error:', err);
                        console.error('Error response:', err.response);
                        
                        let errorMsg = 'Failed to load banner data';
                        if (err.response?.status === 404) {
                            errorMsg = 'Banner not found. It may have been deleted.';
                        } else if (err.response?.status === 403) {
                            errorMsg = 'You do not have permission to edit this banner.';
                        } else if (err.response?.data?.message) {
                            errorMsg = err.response.data.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMsg,
                            footer: err.response?.status ? `Status: ${err.response.status}` : 'Check console for details'
                        });
                    });
            });
        });
    }

    attachEditListeners();

    // Image preview on file select
    const imageInput = form.querySelector('[name="image"]');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imgPreview.src = e.target.result;
                    imgPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Submit form
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';
        
        const id = document.getElementById('banner_id').value;
        const url = id ? `/banners/${id}` : '/banners';
        const formData = new FormData(form);
        
        if (id) {
            formData.append('_method', 'PUT');
        }

        // Debug: Log what we're sending
        console.log('Submitting to:', url);
        console.log('Form data:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        axios.post(url, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
        .then(response => {
            console.log('Success response:', response);
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: id ? 'Banner updated successfully' : 'Banner added successfully',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        })
        .catch(err => {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Update Banner' : 'Save Banner';
            
            console.error('Full error:', err);
            console.error('Error response:', err.response);
            
            let msg = 'An error occurred';
            if (err.response?.status === 422) {
                const errors = err.response.data.errors;
                msg = Object.values(errors).flat().join('<br>');
            } else if (err.response?.data?.message) {
                msg = err.response.data.message;
            } else if (err.response?.status === 404) {
                msg = 'Route not found. Please check your routes configuration.';
            } else if (err.response?.status === 500) {
                msg = 'Server error. Please check the browser console and server logs.';
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                html: msg,
                footer: err.response?.status ? `Status: ${err.response.status}` : ''
            });
        });
    });

    // Delete buttons
    function attachDeleteListeners() {
        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                deleteId = this.dataset.id;
                const deleteModal = new bootstrap.Modal('#deleteRecordModal');
                deleteModal.show();
            });
        });
    }

    attachDeleteListeners();

    // Confirm delete
    const deleteRecordBtn = document.getElementById('delete-record');
    if (deleteRecordBtn) {
        deleteRecordBtn.addEventListener('click', function() {
            if (!deleteId) return;
            
            this.disabled = true;
            this.textContent = 'Deleting...';
            
            axios.delete(`/banners/${deleteId}`)
                .then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Banner has been deleted',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                })
                .catch(err => {
                    this.disabled = false;
                    this.textContent = 'Yes, Delete';
                    
                    let errorMsg = 'Failed to delete banner';
                    if (err.response?.data?.message) {
                        errorMsg = err.response.data.message;
                    }
                    
                    Swal.fire('Error!', errorMsg, 'error');
                    console.error('Delete error:', err);
                });
        });
    }

    // Multiple delete
    window.deleteMultiple = function () {
        const ids = Array.from(document.querySelectorAll('input[name="chk_child"]:checked'))
            .map(cb => cb.value);
        
        if (ids.length === 0) {
            Swal.fire('Warning', 'Please select banners to delete', 'warning');
            return;
        }

        Swal.fire({
            title: `Delete ${ids.length} banner(s)?`,
            text: 'This action cannot be undone',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete them',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33'
        }).then(result => {
            if (result.isConfirmed) {
                Promise.all(ids.map(id => axios.delete(`/banners/${id}`)))
                    .then(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Selected banners have been deleted',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    })
                    .catch(err => {
                        Swal.fire('Error!', 'Failed to delete some banners', 'error');
                        console.error('Multiple delete error:', err);
                    });
            }
        });
    };
});
</script>
@endsection