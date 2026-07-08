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

            {{-- ─── Category Table ─────────────────────────────────────────────────── --}}
            <div id="categoryList" class="mt-2">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">
                                        Categories <span class="badge bg-dark-subtle text-dark ms-1">{{ $categories->total() }}</span>
                                    </h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        <!-- Bulk Actions -->
                                        <div class="btn-group d-none" id="bulkActions">
                                            <button type="button" class="btn btn-warning dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-gear me-1"></i> Bulk Actions (<span id="selectedCountBulk">0</span>)
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="bulkUpdate('featured', 1)">
                                                    <i class="bi bi-star-fill text-warning me-2"></i> Mark as Featured
                                                </a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="bulkUpdate('featured', 0)">
                                                    <i class="bi bi-star text-muted me-2"></i> Unmark Featured
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="bulkUpdate('nsfw', 1)">
                                                    <i class="bi bi-exclamation-triangle text-danger me-2"></i> Mark as NSFW
                                                </a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="bulkUpdate('nsfw', 0)">
                                                    <i class="bi bi-check-circle text-success me-2"></i> Mark as Safe
                                                </a></li>
                                            </ul>
                                        </div>

                                        <button class="btn btn-danger d-none" id="remove-actions" onclick="deleteMultiple()">
                                            <i class="bi bi-trash me-1"></i> Delete Selected (<span id="selectedCount">0</span>)
                                        </button>

                                        @can('Create category')
                                            <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#showModal">
                                                <i class="bi bi-plus-lg me-1"></i> Add Category
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            </div>

                            {{-- Filters --}}
                            <div class="card-body border-bottom">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Search</label>
                                        <input type="text" class="form-control" id="searchInput" placeholder="Category or parent name..." value="{{ request('search', '') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Level</label>
                                        <select class="form-control" id="parentFilter">
                                            <option value="">All</option>
                                            <option value="top" {{ request('parent_filter') == 'top' ? 'selected' : '' }}>Top-Level Only</option>
                                            <option value="child" {{ request('parent_filter') == 'child' ? 'selected' : '' }}>Sub-Categories Only</option>
                                            @foreach($allCategories as $parentOpt)
                                                <option value="{{ $parentOpt->id }}" {{ request('parent_filter') == $parentOpt->id ? 'selected' : '' }}>
                                                    Under: {{ $parentOpt->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Featured</label>
                                        <select class="form-control" id="featuredFilter">
                                            <option value="">All</option>
                                            <option value="1" {{ request('featured') === '1' ? 'selected' : '' }}>Featured</option>
                                            <option value="0" {{ request('featured') === '0' ? 'selected' : '' }}>Regular</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Content</label>
                                        <select class="form-control" id="nsfwFilter">
                                            <option value="">All</option>
                                            <option value="0" {{ request('nsfw') === '0' ? 'selected' : '' }}>Safe Only</option>
                                            <option value="1" {{ request('nsfw') === '1' ? 'selected' : '' }}>NSFW Only</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Stock</label>
                                        <select class="form-control" id="stockFilter">
                                            <option value="">All</option>
                                            <option value="empty" {{ request('stock_filter') == 'empty' ? 'selected' : '' }}>Empty (0 products)</option>
                                            <option value="has_stock" {{ request('stock_filter') == 'has_stock' ? 'selected' : '' }}>Has Products</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">Sort By</label>
                                        <select class="form-control" id="sortFilter">
                                            <option value="name_asc" {{ request('sort', 'name_asc') == 'name_asc' ? 'selected' : '' }}>Name A-Z</option>
                                            <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Name Z-A</option>
                                            <option value="most_products" {{ request('sort') == 'most_products' ? 'selected' : '' }}>Most Products</option>
                                            <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest First</option>
                                            <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-outline-secondary" id="clearFilters" title="Clear all filters"
                                            style="{{ request()->except('page') ? '' : 'display: none;' }}">
                                            <i class="bi bi-x-circle me-1"></i> Clear
                                        </button>
                                        <button type="button" class="btn btn-primary" id="applyFilter" title="Apply">
                                            <i class="bi bi-funnel me-1"></i> Apply Filters
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                {{-- Active Filter Badges --}}
                                @if(request()->except('page'))
                                <div class="mb-3">
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="text-muted me-1">Active filters:</span>
                                        @if(request('search'))
                                            <span class="badge bg-primary-subtle text-primary">Search: {{ request('search') }} <button type="button" class="btn-close btn-close-sm ms-1" onclick="removeFilter('search')"></button></span>
                                        @endif
                                        @if(request('parent_filter'))
                                            <span class="badge bg-primary-subtle text-primary">Level: {{ request('parent_filter') == 'top' ? 'Top-Level' : (request('parent_filter') == 'child' ? 'Sub-Categories' : 'Under #' . request('parent_filter')) }} <button type="button" class="btn-close btn-close-sm ms-1" onclick="removeFilter('parent_filter')"></button></span>
                                        @endif
                                        @if(request('featured') !== null && request('featured') !== '')
                                            <span class="badge bg-primary-subtle text-primary">{{ request('featured') === '1' ? 'Featured' : 'Regular' }} <button type="button" class="btn-close btn-close-sm ms-1" onclick="removeFilter('featured')"></button></span>
                                        @endif
                                        @if(request('nsfw') !== null && request('nsfw') !== '')
                                            <span class="badge bg-danger-subtle text-danger">{{ request('nsfw') === '1' ? 'NSFW Only' : 'Safe Only' }} <button type="button" class="btn-close btn-close-sm ms-1" onclick="removeFilter('nsfw')"></button></span>
                                        @endif
                                        @if(request('stock_filter'))
                                            <span class="badge bg-info-subtle text-info">Stock: {{ request('stock_filter') == 'empty' ? 'Empty' : 'Has Products' }} <button type="button" class="btn-close btn-close-sm ms-1" onclick="removeFilter('stock_filter')"></button></span>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0">
                                        <thead class="table-active">
                                            <tr>
                                                <th style="width: 50px;">
                                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                                </th>
                                                <th>Category</th>
                                                <th>Parent</th>
                                                <th class="text-center">Children</th>
                                                <th class="text-center">Products</th>
                                                <th>Featured</th>
                                                <th>Visibility</th>
                                                <th style="width: 90px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($categories as $cat)
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="row-select form-check-input" value="{{ $cat->id }}">
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm bg-light rounded p-1 me-3">
                                                            @if($cat->image && \Storage::disk('public')->exists($cat->image))
                                                                <img src="{{ asset('storage/' . $cat->image) }}" alt="" class="img-fluid rounded" style="max-height:40px;object-fit:cover;">
                                                            @else
                                                                <div class="bg-secondary-subtle rounded d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                                                    <i class="bi bi-image text-muted fs-5"></i>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-1">
                                                                @if(\Illuminate\Support\Facades\Route::has('web.products.index'))
                                                                    <a href="{{ route('web.products.index', ['category_id' => $cat->id]) }}" class="text-reset">{{ $cat->name }}</a>
                                                                @else
                                                                    {{ $cat->name }}
                                                                @endif
                                                            </h6>
                                                            <small class="text-muted">ID: {{ $cat->id }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($cat->parent)
                                                        <span class="badge bg-primary-subtle text-primary">{{ $cat->parent->name }}</span>
                                                    @else
                                                        <span class="text-muted">— Top Level</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ $cat->children_count ?? 0 }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @if(($cat->products_count ?? 0) > 0)
                                                        <span class="badge bg-success-subtle text-success">{{ $cat->products_count }}</span>
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger">Empty</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge {{ $cat->is_featured ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary' }}">
                                                        {{ $cat->is_featured ? 'Featured' : 'Regular' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($cat->is_nsfw)
                                                        <span class="badge bg-danger-subtle text-danger">NSFW</span>
                                                    @else
                                                        <span class="badge bg-success-subtle text-success">Safe</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="btn btn-subtle-secondary btn-sm btn-icon" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            @can('Update category')
                                                                <li><a class="dropdown-item edit-item-btn" href="javascript:void(0);" data-id="{{ $cat->id }}">Edit</a></li>
                                                            @endcan
                                                            @can('Delete category')
                                                                <li>
                                                                    <a class="dropdown-item remove-item-btn text-danger" href="javascript:void(0);"
                                                                       data-id="{{ $cat->id }}"
                                                                       data-name="{{ $cat->name }}"
                                                                       data-products="{{ $cat->products_count ?? 0 }}"
                                                                       data-children="{{ $cat->children_count ?? 0 }}">Delete</a>
                                                                </li>
                                                            @endcan
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="8" class="text-center py-5 text-muted">
                                                    @if(request()->except('page'))
                                                        No categories found matching your filters.<br>
                                                        <a href="{{ route('web.categories.index') }}" class="btn btn-sm btn-outline-primary mt-2">Clear filters</a>
                                                    @else
                                                        No categories found. <a href="javascript:void(0)" class="text-primary" data-bs-toggle="modal" data-bs-target="#showModal">Add your first category</a>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                {{-- ─── PAGINATION ─────────────────────────────────── --}}
                                <div class="row mt-3 align-items-center">
                                    <div class="col-sm">
                                        <div class="text-muted text-center text-sm-start">
                                            Showing {{ $categories->firstItem() ?? 0 }} to {{ $categories->lastItem() ?? 0 }} of {{ $categories->total() }} Results
                                        </div>
                                    </div>
                                    <div class="col-sm-auto mt-3 mt-sm-0">
                                        {!! $categories->appends(request()->query())->links('pagination::custom') !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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

    // ==================== SEARCH / FILTERS ====================
    function performServerSearch() {
        const params = new URLSearchParams(window.location.search);
        const s = document.getElementById('searchInput')?.value.trim();
        s ? params.set('search', s) : params.delete('search');

        const parentFilter = document.getElementById('parentFilter')?.value;
        parentFilter ? params.set('parent_filter', parentFilter) : params.delete('parent_filter');

        const featuredFilter = document.getElementById('featuredFilter')?.value;
        (featuredFilter !== '' && featuredFilter !== undefined) ? params.set('featured', featuredFilter) : params.delete('featured');

        const nsfwFilter = document.getElementById('nsfwFilter')?.value;
        (nsfwFilter !== '' && nsfwFilter !== undefined) ? params.set('nsfw', nsfwFilter) : params.delete('nsfw');

        const stockFilter = document.getElementById('stockFilter')?.value;
        stockFilter ? params.set('stock_filter', stockFilter) : params.delete('stock_filter');

        const sortFilter = document.getElementById('sortFilter')?.value;
        sortFilter ? params.set('sort', sortFilter) : params.delete('sort');

        params.delete('page');
        window.location.href = `${window.location.pathname}?${params.toString()}`;
    }

    document.getElementById('applyFilter')?.addEventListener('click', performServerSearch);
    document.getElementById('searchInput')?.addEventListener('keyup', e => { if (e.key === 'Enter') performServerSearch(); });
    document.getElementById('clearFilters')?.addEventListener('click', () => window.location.href = window.location.pathname);

    window.removeFilter = function (filterName) {
        const params = new URLSearchParams(window.location.search);
        params.delete(filterName);
        params.delete('page');
        window.location.href = `${window.location.pathname}?${params.toString()}`;
    };

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
                    }).then(() => {
                        location.reload();
                    });
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
                    Swal.fire('Deleted!', 'Categories removed.', 'success').then(() => location.reload());
                } else {
                    const firstError = extractErrorMessage(failed[0].reason, 'Unknown error');
                    Swal.fire(
                        'Partially completed',
                        `${results.length - failed.length} deleted, ${failed.length} failed. First error: ${firstError}`,
                        'warning'
                    ).then(() => location.reload());
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

    // Cache DOM elements
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

    // Edit
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

                // Remove this category and its own descendants from parent dropdown
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

    // ==================== FORM SUBMIT ====================
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const id = categoryIdInput.value;
        const isUpdate = id !== '';
        const url = isUpdate ? categoryRoutes.update(id) : categoryRoutes.store;
        const data = new FormData(form);

        // For update, add _method=PUT
        if (isUpdate) {
            data.append('_method', 'PUT');
        }

        // Disable button and show spinner
        submitBtn.disabled = true;
        submitSpinner.classList.remove('d-none');

        // Clear previous errors
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
                Swal.fire('Deleted!', 'Category has been deleted', 'success').then(() => location.reload());
            })
            .catch(err => {
                deleteModal.hide();
                Swal.fire('Error', extractErrorMessage(err, 'Cannot delete category.'), 'error');
            });
    });
});
</script>
@endsection
