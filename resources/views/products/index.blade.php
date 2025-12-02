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
                        <h4 class="mb-sm-0">{{ $pagetitle ?? 'Banners' }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="#">Marketing</a></li>
                                <li class="breadcrumb-item active">Banners</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Banners Table -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h5 class="card-title mb-0 flex-grow-1">All Banners</h5>
                            <div class="flex-shrink-0">
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-danger d-none" id="remove-actions" onclick="deleteMultiple()">
                                        <i class="bi bi-trash"></i> Delete Selected
                                    </button>
                                    @can('Create banner')
                                        <button type="button" class="btn btn-primary add-btn">
                                            <i class="bi bi-plus-circle"></i> Add Banner
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bi bi-check-circle"></i> {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            @if($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bi bi-exclamation-triangle"></i> Please fix the errors below
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="bannersTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="50">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="checkAll">
                                                </div>
                                            </th>
                                            <th>#</th>
                                            <th>Image</th>
                                            <th>Title</th>
                                            <th>Target Screen</th>
                                            <th>Product / Link</th>
                                            <th>Status</th>
                                            <th>Order</th>
                                            <th>Created</th>
                                            <th>Actions</th>
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
                                            <td class="fw-medium">{{ $loop->iteration + (($banners->currentPage() - 1) * $banners->perPage()) }}</td>
                                            <td>
                                                @if($banner->image_url && Storage::disk('public')->exists($banner->image_url))
                                                    <img src="{{ asset('storage/' . $banner->image_url) }}"
                                                         class="rounded shadow-sm"
                                                         style="width: 180px; height: 90px; object-fit: cover;"
                                                         alt="Banner">
                                                @else
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                                         style="width:180px;height:90px;">
                                                        <i class="bi bi-image text-muted fs-3"></i>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="fw-medium">{{ $banner->title ?? 'No Title' }}</div>
                                                @if($banner->subtitle)
                                                    <small class="text-muted">{{ $banner->subtitle }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-info-subtle text-info fs-6">
                                                    {{ ucwords(str_replace('_', ' ', $banner->target_screen)) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($banner->target_screen === 'product' && $banner->product)
                                                    <div class="d-flex align-items-center">
                                                        @if($banner->product->thumbnail)
                                                            <img src="{{ asset('storage/' . $banner->product->thumbnail) }}"
                                                                 class="rounded me-2" 
                                                                 style="width: 40px; height: 40px; object-fit: cover;">
                                                        @endif
                                                        <div>
                                                            <div class="fw-medium">{{ $banner->product->title }}</div>
                                                            <small class="text-muted">{{ $banner->product->sku }}</small>
                                                        </div>
                                                    </div>
                                                @elseif($banner->link)
                                                    <a href="{{ $banner->link }}" target="_blank" class="text-truncate d-block" style="max-width: 150px;">
                                                        <i class="bi bi-link-45deg"></i> {{ Str::limit($banner->link, 30) }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">No link</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input status-toggle" 
                                                               type="checkbox" 
                                                               data-id="{{ $banner->id }}"
                                                               {{ $banner->active ? 'checked' : '' }}>
                                                    </div>
                                                    <span class="badge ms-2 {{ $banner->active ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ $banner->active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">{{ $banner->order ?? 0 }}</span>
                                            </td>
                                            <td>{{ $banner->created_at->format('d M Y') }}</td>
                                            <td>
                                                <div class="hstack gap-2">
                                                    @can('Update banner')
                                                        <button class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn"
                                                            data-id="{{ $banner->id }}"
                                                            data-image="{{ $banner->image_url ? asset('storage/' . $banner->image_url) : '' }}"
                                                            data-screen="{{ $banner->target_screen }}"
                                                            data-product-id="{{ $banner->product_id }}"
                                                            data-title="{{ $banner->title ?? '' }}"
                                                            data-subtitle="{{ $banner->subtitle ?? '' }}"
                                                            data-link="{{ $banner->link ?? '' }}"
                                                            data-active="{{ $banner->active }}"
                                                            data-order="{{ $banner->order ?? 0 }}">
                                                            <i class="ph-pencil"></i>
                                                        </button>
                                                    @endcan
                                                    @can('Delete banner')
                                                        <button class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" 
                                                                data-id="{{ $banner->id }}">
                                                            <i class="ph-trash"></i>
                                                        </button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-5 text-muted">
                                                <i class="bi bi-inbox fs-1"></i>
                                                <p class="mt-2">No banners found</p>
                                                @can('Create banner')
                                                    <button class="btn btn-primary add-btn mt-2">
                                                        <i class="bi bi-plus-circle"></i> Create First Banner
                                                    </button>
                                                @endcan
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            @if($banners->hasPages())
                                <div class="d-flex justify-content-end mt-4">
                                    <div class="pagination-wrap hstack gap-2">
                                        <a class="page-item pagination-prev {{ $banners->onFirstPage() ? 'disabled' : '' }}"
                                           href="{{ $banners->previousPageUrl() }}">
                                            <i class="bi bi-chevron-left"></i> Previous
                                        </a>
                                        <span class="px-3 py-2 bg-light rounded">{{ $banners->currentPage() }} / {{ $banners->lastPage() }}</span>
                                        <a class="page-item pagination-next {{ $banners->hasMorePages() ? '' : 'disabled' }}"
                                           href="{{ $banners->nextPageUrl() }}">
                                            Next <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Modal -->
            <div class="modal fade" id="showModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
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
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Banner Image <span class="text-danger">*</span></label>
                                            <input type="file" class="form-control" name="image" id="image_input" accept="image/*">
                                            <small class="text-muted">Recommended: 1200×600px, Max: 5MB</small>
                                            <div class="invalid-feedback" id="image_error"></div>
                                        </div>
                                        <div class="text-center mb-3">
                                            <img id="image_preview" class="rounded shadow" 
                                                 style="max-width:100%; max-height:250px; display:none; object-fit: contain;" 
                                                 alt="Preview">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Title</label>
                                            <input type="text" class="form-control" name="title" id="title" 
                                                   placeholder="Banner title (optional)">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Subtitle</label>
                                            <input type="text" class="form-control" name="subtitle" id="subtitle" 
                                                   placeholder="Subtitle (optional)">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Target Screen <span class="text-danger">*</span></label>
                                            <select class="form-select" name="target_screen" id="target_screen" required>
                                                <option value="home">Home Screen</option>
                                                <option value="category">Category Page</option>
                                                <option value="product">Product Detail</option>
                                                <option value="offers">Offers Page</option>
                                                <option value="all">All Pages</option>
                                            </select>
                                        </div>
                                        
                                        <!-- Product Selection (shown only when target_screen is 'product') -->
                                        <div class="mb-3" id="product_select_container" style="display: none;">
                                            <label class="form-label">Select Product <span class="text-danger">*</span></label>
                                            <select class="form-select" name="product_id" id="product_id">
                                                <option value="">-- Select Product --</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}" 
                                                            data-thumbnail="{{ $product->thumbnail ? asset('storage/' . $product->thumbnail) : asset('images/default-product.png') }}">
                                                        {{ $product->title }} ({{ $product->sku }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="mt-2" id="product_preview" style="display: none;">
                                                <img src="" id="product_image" class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                                <div class="d-inline-block ms-2">
                                                    <div id="product_title" class="fw-medium"></div>
                                                    <small id="product_sku" class="text-muted"></small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Custom Link (shown when NOT 'product') -->
                                        <div class="mb-3" id="link_container">
                                            <label class="form-label">Custom Link</label>
                                            <input type="url" class="form-control" name="link" id="link" 
                                                   placeholder="https://example.com (optional)">
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Order</label>
                                                    <input type="number" class="form-control" name="order" id="order" 
                                                           min="0" value="0">
                                                    <small class="text-muted">Lower number shows first</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Status</label>
                                                    <div class="form-check form-switch mt-2">
                                                        <input class="form-check-input" type="checkbox" name="active" value="1" id="active" checked>
                                                        <label class="form-check-label">Active</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <span id="submitText">Save Banner</span>
                                    <span id="spinner" class="spinner-border spinner-border-sm d-none"></span>
                                </button>
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
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-danger" id="confirm-delete">Yes, Delete</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .banner-image {
        transition: transform 0.2s;
    }
    .banner-image:hover {
        transform: scale(1.05);
    }
    .product-option {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .product-option img {
        width: 30px;
        height: 30px;
        object-fit: cover;
        border-radius: 4px;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // CSRF Token setup
    axios.defaults.headers.common['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    // Modal instances
    const bannerModal = new bootstrap.Modal('#showModal');
    const deleteModal = new bootstrap.Modal('#deleteRecordModal');
    const form = document.getElementById('bannerForm');
    const imagePreview = document.getElementById('image_preview');
    const imageInput = document.getElementById('image_input');
    const productSelect = document.getElementById('product_id');
    const productPreview = document.getElementById('product_preview');
    const productImage = document.getElementById('product_image');
    const productTitle = document.getElementById('product_title');
    const productSku = document.getElementById('product_sku');
    
    let deleteId = null;

    // Initialize
    initCheckboxes();
    initFormValidation();
    initEventListeners();

    function initCheckboxes() {
        // Check all functionality
        const checkAll = document.getElementById('checkAll');
        if (checkAll) {
            checkAll.addEventListener('change', function () {
                document.querySelectorAll('input[name="chk_child"]').forEach(cb => {
                    cb.checked = this.checked;
                    cb.closest('tr')?.classList.toggle('table-active', this.checked);
                });
                toggleDeleteBtn();
            });
        }

        // Individual checkbox handling
        document.querySelectorAll('input[name="chk_child"]').forEach(cb => {
            cb.addEventListener('change', () => {
                cb.closest('tr')?.classList.toggle('table-active', cb.checked);
                toggleDeleteBtn();
            });
        });

        function toggleDeleteBtn() {
            const count = document.querySelectorAll('input[name="chk_child"]:checked').length;
            const deleteBtn = document.getElementById('remove-actions');
            if (deleteBtn) {
                deleteBtn.classList.toggle('d-none', count === 0);
            }
        }
    }

    function initFormValidation() {
        // Reset form validation styles
        form.addEventListener('reset', () => {
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        });

        // Image preview
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    showError('image', 'Image size must be less than 5MB');
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        // Target screen change handler
        document.getElementById('target_screen').addEventListener('change', function() {
            const isProductScreen = this.value === 'product';
            const productContainer = document.getElementById('product_select_container');
            const linkContainer = document.getElementById('link_container');
            
            productContainer.style.display = isProductScreen ? 'block' : 'none';
            
            if (isProductScreen) {
                productSelect.required = true;
                document.getElementById('link').value = '';
            } else {
                productSelect.required = false;
                productSelect.value = '';
                productPreview.style.display = 'none';
            }
        });

        // Product selection handler
        productSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (this.value) {
                productImage.src = selectedOption.getAttribute('data-thumbnail');
                productTitle.textContent = selectedOption.text.split(' (')[0];
                productSku.textContent = selectedOption.text.match(/\((.*?)\)/)[1];
                productPreview.style.display = 'flex';
            } else {
                productPreview.style.display = 'none';
            }
        });
    }

    function initEventListeners() {
        // Add button
        document.querySelectorAll('.add-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                resetForm();
                document.getElementById('modalTitle').textContent = 'Add Banner';
                document.getElementById('submitBtn').textContent = 'Save Banner';
                bannerModal.show();
            });
        });

        // Edit buttons
        document.querySelectorAll('.edit-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                resetForm();
                
                const id = this.dataset.id;
                const screen = this.dataset.screen;
                const productId = this.dataset.productId;
                
                document.getElementById('banner_id').value = id;
                document.getElementById('target_screen').value = screen;
                document.getElementById('product_id').value = productId || '';
                document.getElementById('title').value = this.dataset.title || '';
                document.getElementById('subtitle').value = this.dataset.subtitle || '';
                document.getElementById('link').value = this.dataset.link || '';
                document.getElementById('active').checked = this.dataset.active == '1';
                document.getElementById('order').value = this.dataset.order || '0';
                
                // Handle image preview
                if (this.dataset.image) {
                    imagePreview.src = this.dataset.image;
                    imagePreview.style.display = 'block';
                }
                
                // Handle product preview
                if (productId) {
                    const selectedOption = productSelect.querySelector(`option[value="${productId}"]`);
                    if (selectedOption) {
                        productSelect.value = productId;
                        productImage.src = selectedOption.getAttribute('data-thumbnail');
                        productTitle.textContent = selectedOption.text.split(' (')[0];
                        productSku.textContent = selectedOption.text.match(/\((.*?)\)/)[1];
                        productPreview.style.display = 'flex';
                    }
                }
                
                // Show/hide product select based on screen
                const productContainer = document.getElementById('product_select_container');
                productContainer.style.display = screen === 'product' ? 'block' : 'none';
                
                document.getElementById('modalTitle').textContent = 'Edit Banner';
                document.getElementById('submitBtn').textContent = 'Update Banner';
                bannerModal.show();
            });
        });

        // Status toggle
        document.querySelectorAll('.status-toggle').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const id = this.dataset.id;
                const isActive = this.checked;
                
                axios.post(`/banners/${id}/toggle-status`)
                    .then(response => {
                        if (response.data.success) {
                            showToast('success', 'Status updated successfully');
                        }
                    })
                    .catch(error => {
                        this.checked = !isActive;
                        showToast('error', 'Failed to update status');
                    });
            });
        });

        // Delete buttons
        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                deleteId = this.dataset.id;
                deleteModal.show();
            });
        });

        // Confirm delete
        document.getElementById('confirm-delete')?.addEventListener('click', () => {
            if (deleteId) {
                axios.delete(`/banners/${deleteId}`)
                    .then(response => {
                        if (response.data.success) {
                            location.reload();
                        }
                    })
                    .catch(error => {
                        deleteModal.hide();
                        showToast('error', 'Failed to delete banner');
                    });
            }
        });

        // Multiple delete
        window.deleteMultiple = function() {
            const ids = Array.from(document.querySelectorAll('input[name="chk_child"]:checked'))
                .map(cb => cb.value);
            
            if (!ids.length) {
                showToast('warning', 'Please select at least one banner');
                return;
            }

            Swal.fire({
                title: 'Delete Selected Banners?',
                text: `This will delete ${ids.length} banner(s). This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete!'
            }).then(result => {
                if (result.isConfirmed) {
                    axios.post('/banners/bulk-delete', { ids: ids })
                        .then(response => {
                            if (response.data.success) {
                                location.reload();
                            }
                        })
                        .catch(error => {
                            showToast('error', 'Failed to delete banners');
                        });
                }
            });
        };
    }

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }

        const id = document.getElementById('banner_id').value;
        const url = id ? `/banners/${id}` : '/banners';
        const method = id ? 'PUT' : 'POST';
        const data = new FormData(this);
        
        if (id) {
            data.append('_method', 'PUT');
        }

        // Show loading
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const spinner = document.getElementById('spinner');
        
        submitBtn.disabled = true;
        submitText.textContent = id ? 'Updating...' : 'Saving...';
        spinner.classList.remove('d-none');

        axios.post(url, data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
        .then(response => {
            if (response.data.success) {
                bannerModal.hide();
                showToast('success', response.data.message);
                setTimeout(() => location.reload(), 1500);
            }
        })
        .catch(error => {
            if (error.response?.status === 422) {
                // Validation errors
                const errors = error.response.data.errors;
                Object.keys(errors).forEach(field => {
                    showError(field, errors[field][0]);
                });
            } else {
                showToast('error', error.response?.data?.message || 'Something went wrong');
            }
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            submitText.textContent = id ? 'Update Banner' : 'Save Banner';
            spinner.classList.add('d-none');
        });
    });

    function validateForm() {
        let isValid = true;
        
        // Clear previous errors
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        
        // Check image for new banners
        const bannerId = document.getElementById('banner_id').value;
        if (!bannerId && !imageInput.files[0]) {
            showError('image', 'Please select an image');
            isValid = false;
        }
        
        // Check product selection for product screen
        const screen = document.getElementById('target_screen').value;
        if (screen === 'product' && !document.getElementById('product_id').value) {
            showError('product_id', 'Please select a product');
            isValid = false;
        }
        
        return isValid;
    }

    function showError(field, message) {
        const input = document.querySelector(`[name="${field}"]`) || document.getElementById(field);
        const errorDiv = document.getElementById(`${field}_error`);
        
        if (input) {
            input.classList.add('is-invalid');
            if (errorDiv) {
                errorDiv.textContent = message;
            } else {
                const div = document.createElement('div');
                div.className = 'invalid-feedback';
                div.textContent = message;
                input.parentNode.appendChild(div);
            }
        }
    }

    function resetForm() {
        form.reset();
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        imagePreview.style.display = 'none';
        productPreview.style.display = 'none';
        document.getElementById('product_select_container').style.display = 'none';
    }

    function showToast(icon, message) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
        
        Toast.fire({
            icon: icon,
            title: message
        });
    }
});
</script>
@endpush