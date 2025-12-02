{{-- resources/views/brands/index.blade.php --}}
@extends('layouts.master')

@section('title', 'Brands Management')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Page Title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Brands</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">E-commerce</a></li>
                                <li class="breadcrumb-item active">Brands</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Products per Brand</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="brandChart" height="100px"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Brands Table -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card" id="brandList">
                        <div class="card-header d-flex align-items-center">
                            <h5 class="card-title mb-0 flex-grow-1">All Brands</h5>
                            <div class="flex-shrink-0">
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-danger d-none" id="remove-actions" onclick="deleteMultiple()">
                                        <i class="ri-delete-bin-2-line"></i>
                                    </button>
                                    @can('Create brand')
                                        <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#showModal">
                                            <i class="bi bi-plus-circle me-1"></i> Add Brand
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive table-card">
                                <table class="table table-borderless table-centered align-middle table-nowrap mb-0">
                                    <thead class="text-muted table-light">
                                        <tr>
                                            <th>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="checkAll">
                                                    <label class="form-check-label" for="checkAll"></label>
                                                </div>
                                            </th>
                                            <th class="sort" data-sort="id">#</th>
                                            <th class="sort" data-sort="name">Brand Name</th>
                                            <th class="sort" data-sort="logo">Logo</th>
                                            <th class="sort" data-sort="categories">Categories</th>
                                            <th class="sort" data-sort="products">Products</th>
                                            <th class="sort" data-sort="featured">Featured</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="list form-check-all">
                                        @forelse($data as $brand)
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="chk_child" value="{{ $brand->id }}">
                                                </div>
                                            </td>
                                            <td class="id">{{ $brand->id }}</td>
                                            <td class="name"><strong>{{ $brand->name }}</strong></td>
                                            <td class="logo">
                                                <img src="{{ $brand->logo ? asset('storage/'.$brand->logo) : 'https://via.placeholder.com/60' }}"
                                                     class="avatar-sm rounded-circle" alt="{{ $brand->name }}">
                                            </td>
                                            <td class="categories">
                                                @if($brand->categories->count())
                                                    @foreach($brand->categories->take(3) as $c)
                                                        <span class="badge bg-info-subtle text-info me-1">{{ $c->name }}</span>
                                                    @endforeach
                                                    @if($brand->categories->count() > 3)
                                                        <small class="text-muted">+{{ $brand->categories->count()-3 }}</small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="products">{{ $brand->products_count }}</td>
                                            <td class="featured">
                                                <span class="badge {{ $brand->is_featured ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                                    {{ $brand->is_featured ? 'Yes' : 'No' }}
                                                </span>
                                            </td>
                                            <td>
                                                <ul class="list-inline hstack gap-2 mb-0">
                                                    @can('Update brand')
                                                        <li><button class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn" data-id="{{ $brand->id }}"><i class="ph-pencil"></i></button></li>
                                                    @endcan
                                                    @can('Delete brand')
                                                        <li><button class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" data-id="{{ $brand->id }}"><i class="ph-trash"></i></button></li>
                                                    @endcan
                                                </ul>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-5 noresult" style="display:none">No brands found</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>

                                <div class="noresult" style="display:none">
                                    <div class="text-center py-4">
                                        <i class="ph-magnifying-glass fs-1 text-primary"></i>
                                        <h5 class="mt-2">Sorry! No Result Found</h5>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-3">
                                <div class="pagination-wrap hstack gap-2">
                                    <a class="page-item pagination-prev disabled" href="javascript:void(0);"><i class="mdi mdi-chevron-left"></i></a>
                                    <ul class="pagination listjs-pagination mb-0"></ul>
                                    <a class="page-item pagination-next" href="javascript:void(0);"><i class="mdi mdi-chevron-right"></i></a>
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
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured">
                        <label class="form-check-label">Featured Brand</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Save Brand</button>
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
                <h4 class="mt-4">Delete Brand?</h4>
                <p class="text-muted">This action cannot be undone.</p>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="delete-record">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- ALL JAVASCRIPT (inline - no external files) -->


<!-- Replace the entire script section with this -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/list.js@2.3.1/dist/list.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Setup axios defaults
    axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    // Pass PHP data to JS
    const chartLabels = @json($chart_labels);
    const chartData   = @json($chart_data);

    // Chart
    new Chart(document.getElementById('brandChart'), {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Products',
                data: chartData,
                backgroundColor: '#405189'
            }]
        },
        options: {
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { display: false } }
        }
    });

    // List.js - Fixed pagination plugin
    var options = {
        valueNames: ['id', 'name', 'categories', 'products', 'featured'],
        page: 10,
        pagination: true
    };
    var brandList = new List('brandList', options);

    // Choices.js
    let choices = new Choices('#categories_select', {
        removeItemButton: true,
        searchEnabled: true,
        placeholder: true,
        placeholderValue: 'Select categories'
    });

    // Modal
    const modalElement = document.getElementById('showModal');
    const modal = new bootstrap.Modal(modalElement);
    const form = document.getElementById('brandForm');
    const logoPreview = document.getElementById('logo_preview');
    let deleteId = null;

    // Checkbox Select All
    const checkAllBtn = document.getElementById('checkAll');
    if (checkAllBtn) {
        checkAllBtn.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="chk_child"]');
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
                const row = cb.closest('tr');
                if (row) {
                    row.classList.toggle('table-active', this.checked);
                }
            });
            updateRemoveButton();
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
                
                if (checkAllBtn) {
                    checkAllBtn.checked = checkedCount === allCheckboxes.length;
                }
                updateRemoveButton();
            });
        });
    }

    function updateRemoveButton() {
        const checkedCount = document.querySelectorAll('input[name="chk_child"]:checked').length;
        const removeBtn = document.getElementById('remove-actions');
        if (removeBtn) {
            removeBtn.classList.toggle('d-none', checkedCount === 0);
        }
    }

    attachCheckboxListeners();

    // Reset form when Add button clicked
    const addBtn = document.querySelector('.add-btn');
    if (addBtn) {
        addBtn.addEventListener('click', () => {
            form.reset();
            document.getElementById('brand_id').value = '';
            document.getElementById('modalTitle').textContent = 'Add Brand';
            document.getElementById('submitBtn').textContent = 'Save Brand';
            logoPreview.style.display = 'none';
            logoPreview.src = '';
            choices.removeActiveItems();
            choices.setChoiceByValue([]);
        });
    }

    // Edit buttons
    function attachEditListeners() {
        document.querySelectorAll('.edit-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                axios.get(`/brands/${id}/edit`)
                    .then(res => {
                        const b = res.data;
                        document.getElementById('brand_id').value = b.id;
                        form.querySelector('[name="name"]').value = b.name;
                        document.getElementById('is_featured').checked = b.is_featured == 1;
                        
                        if (b.logo) {
                            logoPreview.src = b.logo;
                            logoPreview.style.display = 'block';
                        } else {
                            logoPreview.style.display = 'none';
                        }
                        
                        // Set categories in Choices.js
                        choices.removeActiveItems();
                        if (b.categories && b.categories.length > 0) {
                            const categoryIds = b.categories.map(c => String(c.id));
                            choices.setChoiceByValue(categoryIds);
                        }
                        
                        document.getElementById('modalTitle').textContent = 'Edit Brand';
                        document.getElementById('submitBtn').textContent = 'Update Brand';
                        modal.show();
                    })
                    .catch(err => {
                        console.error('Edit error:', err);
                        Swal.fire('Error!', 'Failed to load brand data', 'error');
                    });
            });
        });
    }

    attachEditListeners();

    // Submit form (Add & Update)
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';
        
        const id = document.getElementById('brand_id').value;
        const url = id ? `/brands/${id}` : '/brands';
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
                text: id ? 'Brand updated successfully' : 'Brand added successfully',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        })
        .catch(err => {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Update Brand' : 'Save Brand';
            
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
            
            axios.delete(`/brands/${deleteId}`)
                .then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Brand has been deleted',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                })
                .catch(err => {
                    this.disabled = false;
                    this.textContent = 'Yes, Delete';
                    Swal.fire('Error!', 'Failed to delete brand', 'error');
                    console.error('Delete error:', err);
                });
        });
    }

    // Multiple Delete
    window.deleteMultiple = function() {
        const ids = Array.from(document.querySelectorAll('input[name="chk_child"]:checked'))
            .map(cb => cb.value);
        
        if (ids.length === 0) {
            Swal.fire('Warning', 'Please select brands to delete', 'warning');
            return;
        }
        
        Swal.fire({
            title: `Delete ${ids.length} brand(s)?`,
            text: 'This action cannot be undone',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete them',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33'
        }).then(result => {
            if (result.isConfirmed) {
                Promise.all(ids.map(id => axios.delete(`/brands/${id}`)))
                    .then(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Selected brands have been deleted',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    })
                    .catch(err => {
                        Swal.fire('Error!', 'Failed to delete some brands', 'error');
                        console.error('Multiple delete error:', err);
                    });
            }
        });
    };

    // Logo preview on file select
    const logoInput = form.querySelector('[name="logo"]');
    if (logoInput) {
        logoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    logoPreview.src = e.target.result;
                    logoPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>
@endsection