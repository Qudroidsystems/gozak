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
                                        <button class="btn btn-danger d-none" id="remove-actions" onclick="deleteMultiple()">
                                            Delete Selected (<span id="selectedCount">0</span>)
                                        </button>
                                        @can('Create category')
                                            <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#showModal">
                                                Add Category
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
                                        {{-- NEW: the controller already supports filtering by nsfw,
                                             but nothing in the UI exposed it. Added here. --}}
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
                                                        <a href="{{ route('categories.index') }}" class="btn btn-sm btn-outline-primary mt-2">Clear filters</a>
                                                    @else
                                                        No categories found. <a href="javascript:void(0)" class="text-primary" data-bs-toggle="modal" data-bs-target="#showModal">Add your first category</a>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row mt-3 align-items-center">
                                    <div class="col-sm">
                                        <div class="text-muted text-center text-sm-start">
                                            Showing {{ $categories->firstItem() ?? 0 }} to {{ $categories->lastItem() ?? 0 }} of {{ $categories->total() }} Results
                                        </div>
                                    </div>
                                    <div class="col-sm-auto mt-3 mt-sm-0">
                                        {!! $categories->appends(request()->query())->links('pagination::bootstrap-5') !!}
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
            <form id="categoryForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" id="category_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" class="form-control" name="name" id="name" required>
                        <div class="invalid-feedback" id="name_error"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent Category</label>
                        <select class="form-select" name="parent_id" id="parent_id">
                            <option value="">No Parent (Top Level)</option>
                            @foreach($allCategories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">When editing, this category and its own sub-categories are hidden here to prevent circular relationships.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image</label>
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
    /* SweetAlert2's default z-index can end up underneath the Bootstrap
       modal backdrop in this layout (same issue seen elsewhere with the
       spotlight overlay) — force it above everything so success/error
       popups triggered while the Add/Edit modal is open are actually visible. */
    .swal2-container {
        z-index: 20000 !important;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Generated server-side with route() so this works no matter what
    // prefix/group these routes actually live under (e.g. /admin/categories
    // vs /categories). Hardcoding "/categories/..." in JS breaks the moment
    // the routes file changes; this doesn't.
    const categoryRoutes = {
        store:   @json(route('web.categories.store')),
        edit:    @json(route('web.categories.edit', ['category' => '__ID__'])),
        update:  @json(route('web.categories.update', ['category' => '__ID__'])),
        destroy: @json(route('web.categories.destroy', ['category' => '__ID__'])),
    };

    function categoryUrl(action, id) {
        return categoryRoutes[action].replace('__ID__', id);
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
    }

    // Pulls a readable message out of any failed axios response, falling
    // back gracefully. Used everywhere instead of hardcoded error strings,
    // so real server errors are never hidden from you again.
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
        if (countEl) countEl.textContent = selectedCategories.length;
        if (btn) btn.classList.toggle('d-none', selectedCategories.length === 0);
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

            Promise.allSettled(selectedCategories.map(id => axios.delete(categoryUrl('destroy', id))))
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
    const parentOptionsHtml = parentSelect.innerHTML; // pristine copy, restored on reset/add

    function resetForm() {
        form.reset();
        document.getElementById('category_id').value = '';
        document.getElementById('modalTitle').textContent = 'Add Category';
        document.getElementById('submitBtn').textContent = 'Save Category';
        parentSelect.innerHTML = parentOptionsHtml;
        imgPreview.style.display = 'none';
        imgPreview.src = '';
        document.getElementById('name').classList.remove('is-invalid');
    }

    document.querySelector('.add-btn')?.addEventListener('click', resetForm);

    // Edit
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.edit-item-btn');
        if (!btn) return;

        const id = btn.dataset.id;
        Swal.fire({ title: 'Loading...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        axios.get(categoryUrl('edit', id))
            .then(res => {
                const c = res.data;
                resetForm();

                document.getElementById('category_id').value = c.id;
                document.getElementById('name').value = c.name;
                document.getElementById('is_featured').checked = c.is_featured;
                document.getElementById('is_nsfw').checked = c.is_nsfw;

                // Remove this category and its own descendants from the
                // parent dropdown so it can't become its own ancestor.
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

                document.getElementById('modalTitle').textContent = 'Edit Category';
                document.getElementById('submitBtn').textContent = 'Update Category';
                Swal.close();
                modal.show();
            })
            .catch(err => {
                // FIX: this used to be a hardcoded "Failed to load category"
                // no matter what the actual problem was. Now it shows the
                // real reason (permission denied, category deleted, server
                // error, etc.) so the actual cause is visible instead of
                // guessing.
                Swal.fire('Error', extractErrorMessage(err, 'Failed to load category.'), 'error');
            });
    });

    // Submit
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const id = document.getElementById('category_id').value;
        const url = id ? categoryUrl('update', id) : categoryRoutes.store;
        const data = new FormData(form);
        if (id) data.append('_method', 'PUT');

        const btn = document.getElementById('submitBtn');
        const spinner = document.getElementById('submitSpinner');
        btn.disabled = true;
        spinner.classList.remove('d-none');
        document.getElementById('name').classList.remove('is-invalid');

        axios.post(url, data)
            .then(() => {
                Swal.fire({ icon: 'success', title: 'Saved!', showConfirmButton: false, timer: 1200 })
                    .then(() => location.reload());
            })
            .catch(err => {
                if (err.response?.status === 422) {
                    const errors = err.response.data.errors || {};
                    if (errors.name) {
                        document.getElementById('name').classList.add('is-invalid');
                        document.getElementById('name_error').textContent = errors.name[0];
                    }
                    const msg = Object.values(errors).flat().join('<br>');
                    Swal.fire('Validation Error', msg, 'error');
                } else {
                    Swal.fire('Error', extractErrorMessage(err, 'Something went wrong.'), 'error');
                }
            })
            .finally(() => {
                btn.disabled = false;
                spinner.classList.add('d-none');
            });
    });

    document.getElementById('image_input')?.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            imgPreview.src = URL.createObjectURL(file);
            imgPreview.style.display = 'block';
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
        axios.delete(categoryUrl('destroy', deleteId))
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
