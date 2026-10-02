{{-- resources/views/categories/index.blade.php --}}
@extends('layouts.master')

@section('title', 'Categories Management')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            {{-- ─── Page Title ─────────────────────────────────────────────────────── --}}
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle ?? 'Category Management' }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0)">Ecommerce</a></li>
                                <li class="breadcrumb-item active">Categories</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── Analytics Cards ────────────────────────────────────────────────── --}}
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-primary-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-primary mb-0">Total Categories</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ number_format($analytics['total_categories'] ?? 0) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-primary rounded-circle fs-3">
                                        <i class="bi bi-diagram-3"></i>
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
                                    <p class="text-uppercase fw-medium text-info mb-0">Top-Level Categories</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ number_format($analytics['top_level_count'] ?? 0) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-info rounded-circle fs-3">
                                        <i class="bi bi-diagram-2"></i>
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
                                    <p class="text-uppercase fw-medium text-warning mb-0">Featured</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ number_format($analytics['featured_count'] ?? 0) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-warning rounded-circle fs-3">
                                        <i class="bi bi-star-fill"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate {{ ($analytics['empty_count'] ?? 0) > 0 ? 'bg-danger-subtle' : 'bg-success-subtle' }} border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium {{ ($analytics['empty_count'] ?? 0) > 0 ? 'text-danger' : 'text-success' }} mb-0">Empty Categories</p>
                                    <h4 class="fs-22 fw-semibold mb-0 {{ ($analytics['empty_count'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($analytics['empty_count'] ?? 0) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title {{ ($analytics['empty_count'] ?? 0) > 0 ? 'bg-danger' : 'bg-success' }} rounded-circle fs-3">
                                        <i class="bi bi-inbox"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── Chart ───────────────────────────────────────────────────────────── --}}
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Top Categories by Products</h5>
                        </div>
                        <div class="card-body">
                            @if(count($chart_labels ?? []) > 0)
                                <div style="max-width: 320px; height: 260px; margin: 0 auto;">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                            @else
                                <p class="text-muted text-center mb-0 py-4">No product data yet to chart.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── Category Table (server-side DataTable) ─────────────────────────── --}}
            <x-cb.card title="Categories" icon="ri-folders-line" :flush="true" class="mt-2">
                <x-slot:tools>
                    <div class="btn-group d-none" id="bulkActions">
                        <button type="button" class="btn btn-sm btn-warning dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear me-1"></i> Bulk Actions (<span id="selectedCountBulk">0</span>)
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="bulkUpdate('featured', 1)"><i class="bi bi-star-fill text-warning me-2"></i> Mark as Featured</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="bulkUpdate('featured', 0)"><i class="bi bi-star text-muted me-2"></i> Unmark Featured</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="bulkUpdate('nsfw', 1)"><i class="bi bi-exclamation-triangle text-danger me-2"></i> Mark as NSFW</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="bulkUpdate('nsfw', 0)"><i class="bi bi-check-circle text-success me-2"></i> Mark as Safe</a></li>
                        </ul>
                    </div>
                    <button class="btn btn-sm btn-danger d-none" id="remove-actions" onclick="deleteMultiple()">
                        <i class="bi bi-trash me-1"></i> Delete Selected (<span id="selectedCount">0</span>)
                    </button>
                    @can('Create category')
                        <button type="button" class="btn btn-sm btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#showModal">
                            <i class="bi bi-plus-lg me-1"></i> Add Category
                        </button>
                    @endcan
                </x-slot:tools>

                {{-- Filters (reload the table, no page refresh) --}}
                <div class="gz-filter-bar px-3 pt-3">
                    <select class="form-select form-select-sm" id="parentFilter" data-dt-filter="#categoriesTable">
                        <option value="">All levels</option>
                        <option value="top" @selected(request('parent_filter') == 'top')>Top-Level Only</option>
                        <option value="child" @selected(request('parent_filter') == 'child')>Sub-Categories Only</option>
                        @foreach($allCategories as $parentOpt)
                            <option value="{{ $parentOpt->id }}" @selected(request('parent_filter') == $parentOpt->id)>Under: {{ $parentOpt->name }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm" id="featuredFilter" data-dt-filter="#categoriesTable">
                        <option value="">Featured: any</option>
                        <option value="1">Featured</option>
                        <option value="0">Regular</option>
                    </select>
                    <select class="form-select form-select-sm" id="nsfwFilter" data-dt-filter="#categoriesTable">
                        <option value="">Content: any</option>
                        <option value="0">Safe Only</option>
                        <option value="1">NSFW Only</option>
                    </select>
                    <select class="form-select form-select-sm" id="stockFilter" data-dt-filter="#categoriesTable">
                        <option value="">Products: any</option>
                        <option value="empty">Empty (0 products)</option>
                        <option value="has_stock">Has Products</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clearFilters"><i class="bi bi-x-circle me-1"></i> Clear</button>
                </div>

                <div class="px-3 pb-3 gz-dt-wrap">
                    <table id="categoriesTable" class="table gz-dt align-middle w-100 mb-0">
                        <thead>
                            <tr>
                                <th style="width: 36px;"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                                <th>Category</th>
                                <th>Parent</th>
                                <th class="text-center">Children</th>
                                <th class="text-center">Products</th>
                                <th>Featured</th>
                                <th>Visibility</th>
                                <th style="width: 70px;">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </x-cb.card>

        </div>
    </div>
</div>

{{-- ─── Add / Edit Modal ───────────────────────────────────────────────────── --}}
<div class="modal fade" id="showModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="categoryForm" enctype="multipart/form-data" action="" method="POST">
                @csrf
                <input type="hidden" name="id" id="category_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="category_name">Category Name *</label>
                        <input type="text" class="form-control" name="name" id="category_name" required>
                        <div class="invalid-feedback" id="name_error"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="parent_id">Parent Category</label>
                        <select class="form-select" name="parent_id" id="parent_id">
                            <option value="">No Parent (Top Level)</option>
                            @foreach($allCategories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">When editing, this category and its own sub-categories are hidden here to prevent circular relationships.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="image_input">Image</label>
                        <input type="file" class="form-control" name="image" id="image_input" accept="image/*">
                        <div class="mt-2">
                            <img id="image_preview" class="rounded shadow-sm" style="max-height:120px; display:none;">
                        </div>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured">
                        <label class="form-check-label fw-semibold" for="is_featured"><i class="bi bi-star-fill text-warning me-1"></i> Featured Category</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_nsfw" value="1" id="is_nsfw">
                        <label class="form-check-label fw-semibold text-danger" for="is_nsfw">NSFW / Adult Content</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="submitSpinner"></span>
                        Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ─── Delete Modal ───────────────────────────────────────────────────────── --}}
<div class="modal fade" id="deleteRecordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <i class="bi bi-trash text-danger display-4"></i>
                <h4 class="mt-4">Delete "<span id="deleteCategoryName"></span>"?</h4>
                <p class="text-muted mb-1">This action cannot be undone.</p>
                <div id="deleteWarnings" class="text-start alert alert-warning d-none mt-3"></div>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="delete-record">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

<style>
    .swal2-container {
        z-index: 20000 !important;
    }

    /* Custom Pagination Styles */
    .pagination {
        gap: 4px;
    }

    .pagination .page-item .page-link {
        border-radius: 4px;
        border: 1px solid #e0e0e0;
        padding: 0.5rem 0.75rem;
        color: #405189;
        font-weight: 500;
        transition: all 0.2s;
    }

    .pagination .page-item.active .page-link {
        background-color: #405189;
        border-color: #405189;
        color: #ffffff;
    }

    .pagination .page-item:not(.active):not(.disabled) .page-link:hover {
        background-color: #f8f9fa;
        border-color: #405189;
        color: #405189;
    }

    .pagination .page-item.disabled .page-link {
        color: #9ca3af;
        cursor: not-allowed;
    }

    .pagination .page-item .page-link:focus {
        box-shadow: 0 0 0 0.2rem rgba(64, 81, 137, 0.25);
    }

    /* Per Page Selector Styles */
    #perPageSelect {
        border-color: #e0e0e0;
        background-color: #fff;
        color: #333;
        cursor: pointer;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    #perPageSelect:hover {
        border-color: #405189;
    }

    #perPageSelect:focus {
        border-color: #405189;
        box-shadow: 0 0 0 0.2rem rgba(64, 81, 137, 0.25);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .row .col-sm-6 {
            text-align: center !important;
        }
        .d-flex.align-items-center.justify-content-sm-end {
            justify-content: center !important;
            margin-top: 10px;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Build URLs with the correct web prefix
    const baseUrl = '{{ url("/web/categories") }}';

    const categoryRoutes = {
        store: baseUrl,
        edit: function(id) { return baseUrl + '/' + id + '/edit'; },
        update: function(id) { return baseUrl + '/' + id; },
        destroy: function(id) { return baseUrl + '/' + id; },
        bulkUpdate: baseUrl + '/bulk-update',
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
    }

    function extractErrorMessage(err, fallback) {
        if (err?.response?.data?.message) return err.response.data.message;
        if (err?.response?.status === 419) return 'Your session expired — please refresh the page and try again.';
        if (err?.request && !err.response) return 'Could not reach the server. Check your connection and try again.';
        return fallback;
    }

    // ==================== CHART ====================
    const chartLabels = @json($chart_labels ?? []);
    const chartData = @json($chart_data ?? []);
    const chartCanvas = document.getElementById('categoryChart');
    if (chartCanvas && chartLabels.length > 0) {
        new Chart(chartCanvas, {
            type: 'doughnut',
            data: {
                labels: chartLabels,
                datasets: [{
                    data: chartData,
                    backgroundColor: ['#405189', '#f1b44c', '#34c38f', '#556ee6', '#f46a6a', '#50a5f1', '#0ab39c', '#6f42c1']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // ==================== SERVER-SIDE TABLE ====================
    window.categoriesTable = GZ.dt('#categoriesTable', {
        url: @json(route('web.categories.data')),
        order: [[1, 'asc']],
        filters: function () {
            return {
                parent_filter: $('#parentFilter').val(), featured: $('#featuredFilter').val(),
                nsfw: $('#nsfwFilter').val(), stock_filter: $('#stockFilter').val()
            };
        },
        columns: [
            { data: 'checkbox',       name: 'checkbox', orderable: false, searchable: false },
            { data: 'category',       name: 'category' },
            { data: 'parent',         name: 'parent' },
            { data: 'children_count', name: 'children_count', searchable: false, className: 'text-center' },
            { data: 'products_count', name: 'products_count', searchable: false, className: 'text-center' },
            { data: 'is_featured',    name: 'categories.is_featured', searchable: false },
            { data: 'is_nsfw',        name: 'categories.is_nsfw', searchable: false },
            { data: 'action',         name: 'action', orderable: false, searchable: false }
        ],
        onDraw: function () {
            var all = document.getElementById('selectAll'); if (all) all.checked = false;
            updateSelectedCount();
        }
    });
    var reloadTable = function () { window.categoriesTable.ajax.reload(null, false); };
    var initialSearch = @json(request('search', ''));
    if (initialSearch) { window.categoriesTable.search(initialSearch).draw(); }

    document.getElementById('clearFilters')?.addEventListener('click', function () {
        ['parentFilter', 'featuredFilter', 'nsfwFilter', 'stockFilter'].forEach(function (id) { document.getElementById(id).value = ''; });
        window.categoriesTable.search('').ajax.reload();
    });

    // ==================== BULK SELECT ====================
    let selectedCategories = [];

    function updateSelectedCount() {
        selectedCategories = Array.from(document.querySelectorAll('.row-select:checked')).map(cb => cb.value);
        const btn = document.getElementById('remove-actions');
        const countEl = document.getElementById('selectedCount');
        const bulkActions = document.getElementById('bulkActions');
        const countElBulk = document.getElementById('selectedCountBulk');

        if (countEl) countEl.textContent = selectedCategories.length;
        if (countElBulk) countElBulk.textContent = selectedCategories.length;

        if (btn) btn.classList.toggle('d-none', selectedCategories.length === 0);
        if (bulkActions) bulkActions.classList.toggle('d-none', selectedCategories.length === 0);
    }

    document.getElementById('selectAll')?.addEventListener('change', function () {
        document.querySelectorAll('.row-select').forEach(cb => cb.checked = this.checked);
        updateSelectedCount();
    });

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('row-select')) {
            updateSelectedCount();
            const allChecked = document.querySelectorAll('.row-select:checked').length === document.querySelectorAll('.row-select').length;
            if (document.getElementById('selectAll')) document.getElementById('selectAll').checked = allChecked;
        }
    });

    // ==================== BULK UPDATE ====================
    window.bulkUpdate = function(field, value) {
        const ids = selectedCategories;
        if (!ids.length) {
            Swal.fire('Warning', 'Please select at least one category.', 'warning');
            return;
        }

        const fieldLabels = {
            'featured': value === 1 ? 'Featured' : 'Regular',
            'nsfw': value === 1 ? 'NSFW' : 'Safe'
        };

        Swal.fire({
            title: `Bulk Update`,
            html: `
                <p>You are about to update <strong>${ids.length}</strong> categories.</p>
                <p>Set <strong>${fieldLabels[field]}</strong> status for all selected categories.</p>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, update',
            cancelButtonText: 'Cancel'
        }).then(result => {
            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Updating...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            axios.post(categoryRoutes.bulkUpdate, {
                ids: ids,
                field: field,
                value: value
            })
            .then(response => {
                if (response.data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: `${response.data.updated} categories updated successfully.`,
                        timer: 2000,
                        showConfirmButton: true
                    });
                    reloadTable();
                }
            })
            .catch(err => {
                Swal.fire('Error', extractErrorMessage(err, 'Failed to update categories.'), 'error');
            });
        });
    };

    // ==================== DELETE MULTIPLE ====================
    window.deleteMultiple = function () {
        if (!selectedCategories.length) return;
        Swal.fire({
            title: `Delete ${selectedCategories.length} categories?`,
            text: 'Products in these categories will become uncategorized. This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete all'
        }).then(r => {
            if (!r.isConfirmed) return;

            Promise.allSettled(selectedCategories.map(id =>
                axios.delete(categoryRoutes.destroy(id))
            ))
            .then(results => {
                const failed = results.filter(r => r.status === 'rejected');
                if (failed.length === 0) {
                    Swal.fire('Deleted!', 'Categories removed.', 'success'); reloadTable();
                } else {
                    const firstError = extractErrorMessage(failed[0].reason, 'Unknown error');
                    Swal.fire(
                        'Partially completed',
                        `${results.length - failed.length} deleted, ${failed.length} failed. First error: ${firstError}`,
                        'warning'
                    );
                    reloadTable();
                }
            });
        });
    };

    // ==================== MODAL / FORM ====================
    const modalEl = document.getElementById('showModal');
    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('categoryForm');
    const imgPreview = document.getElementById('image_preview');
    const parentSelect = document.getElementById('parent_id');
    const parentOptionsHtml = parentSelect.innerHTML;

    const nameInput = document.getElementById('category_name');
    const nameError = document.getElementById('name_error');
    const categoryIdInput = document.getElementById('category_id');
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');
    const submitSpinner = document.getElementById('submitSpinner');

    function resetForm() {
        form.reset();
        categoryIdInput.value = '';
        modalTitle.textContent = 'Add Category';
        submitBtn.textContent = 'Save Category';
        parentSelect.innerHTML = parentOptionsHtml;
        imgPreview.style.display = 'none';
        imgPreview.src = '';
        if (nameInput) {
            nameInput.classList.remove('is-invalid');
        }
        if (nameError) {
            nameError.textContent = '';
        }
    }

    document.querySelector('.add-btn')?.addEventListener('click', resetForm);

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.edit-item-btn');
        if (!btn) return;

        const id = btn.dataset.id;
        Swal.fire({ title: 'Loading...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        axios.get(categoryRoutes.edit(id))
            .then(res => {
                const c = res.data;
                resetForm();

                categoryIdInput.value = c.id;
                if (nameInput) nameInput.value = c.name;
                document.getElementById('is_featured').checked = c.is_featured;
                document.getElementById('is_nsfw').checked = c.is_nsfw;

                (c.excluded_ids || []).forEach(excludedId => {
                    const opt = parentSelect.querySelector(`option[value="${excludedId}"]`);
                    if (opt) opt.remove();
                });
                parentSelect.value = c.parent_id || '';

                if (c.image) {
                    imgPreview.src = c.image;
                    imgPreview.style.display = 'block';
                } else {
                    imgPreview.style.display = 'none';
                }

                modalTitle.textContent = 'Edit Category';
                submitBtn.textContent = 'Update Category';
                Swal.close();
                modal.show();
            })
            .catch(err => {
                Swal.fire('Error', extractErrorMessage(err, 'Failed to load category.'), 'error');
            });
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const id = categoryIdInput.value;
        const isUpdate = id !== '';
        const url = isUpdate ? categoryRoutes.update(id) : categoryRoutes.store;
        const data = new FormData(form);

        if (isUpdate) {
            data.append('_method', 'PUT');
        }

        submitBtn.disabled = true;
        submitSpinner.classList.remove('d-none');

        if (nameInput) {
            nameInput.classList.remove('is-invalid');
        }
        if (nameError) {
            nameError.textContent = '';
        }

        axios({
            method: 'POST',
            url: url,
            data: data,
            headers: {
                'Content-Type': 'multipart/form-data',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (response.data.success) {
                Swal.fire({
                    icon: 'success',
                    title: response.data.message || (isUpdate ? 'Category updated!' : 'Category created!'),
                    showConfirmButton: false,
                    timer: 1200
                }).then(() => {
                    location.reload();
                });
            }
        })
        .catch(err => {
            if (err.response?.status === 422) {
                const errors = err.response.data.errors || {};
                let errorMessages = [];

                if (errors.name && nameInput) {
                    nameInput.classList.add('is-invalid');
                    if (nameError) nameError.textContent = errors.name[0];
                    errorMessages.push(errors.name[0]);
                }

                if (errors.parent_id) {
                    errorMessages.push(errors.parent_id[0]);
                }

                if (errors.image) {
                    errorMessages.push(errors.image[0]);
                }

                if (errorMessages.length > 0) {
                    Swal.fire('Validation Error', errorMessages.join('<br>'), 'error');
                } else {
                    Swal.fire('Validation Error', 'Please check the form for errors.', 'error');
                }
            } else {
                Swal.fire('Error', extractErrorMessage(err, 'Something went wrong. Please try again.'), 'error');
            }
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitSpinner.classList.add('d-none');
        });
    });

    document.getElementById('image_input')?.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                imgPreview.src = event.target.result;
                imgPreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    });

    // ==================== DELETE ====================
    let deleteId = null;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteRecordModal'));

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-item-btn');
        if (!btn) return;

        deleteId = btn.dataset.id;
        document.getElementById('deleteCategoryName').textContent = btn.dataset.name || '';

        const products = parseInt(btn.dataset.products || '0');
        const children = parseInt(btn.dataset.children || '0');
        const warnBox = document.getElementById('deleteWarnings');
        const notes = [];
        if (products > 0) notes.push(`${products} product(s) currently in this category will become uncategorized.`);
        if (children > 0) notes.push(`${children} sub-categor${children === 1 ? 'y' : 'ies'} will be promoted to top-level.`);

        if (notes.length) {
            warnBox.innerHTML = notes.map(n => `<div><i class="bi bi-exclamation-triangle me-1"></i>${n}</div>`).join('');
            warnBox.classList.remove('d-none');
        } else {
            warnBox.classList.add('d-none');
        }

        deleteModal.show();
    });

    document.getElementById('delete-record')?.addEventListener('click', function () {
        if (!deleteId) return;
        axios.delete(categoryRoutes.destroy(deleteId))
            .then(() => {
                deleteModal.hide();
                GZ.toast('Category deleted'); reloadTable();
            })
            .catch(err => {
                deleteModal.hide();
                Swal.fire('Error', extractErrorMessage(err, 'Cannot delete category.'), 'error');
            });
    });
});
</script>
@endsection
