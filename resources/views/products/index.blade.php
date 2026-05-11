@extends('layouts.master')

@section('title', 'Products Management')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            {{-- ─── Page Title ─────────────────────────────────────────────────────── --}}
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle ?? 'Product Management' }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0)">Ecommerce</a></li>
                                <li class="breadcrumb-item active">Products</li>
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
                                    <p class="text-uppercase fw-medium text-primary mb-0">Total Products</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ number_format($analytics['total_products'] ?? 0) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-primary rounded-circle fs-3">
                                        <i class="bi bi-box-seam"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-success-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-success mb-0">Total Revenue</p>
                                    <h4 class="fs-22 fw-semibold mb-0">${{ number_format($analytics['total_revenue'] ?? 0, 2) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-success rounded-circle fs-3">
                                        <i class="bi bi-currency-dollar"></i>
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
                                    <p class="text-uppercase fw-medium text-info mb-0">Total Cost Value</p>
                                    <h4 class="fs-22 fw-semibold mb-0">${{ number_format($analytics['total_cost_value'] ?? 0, 2) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-info rounded-circle fs-3">
                                        <i class="bi bi-cash-stack"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate {{ ($analytics['low_stock_count'] ?? 0) > 0 ? 'bg-danger-subtle' : 'bg-warning-subtle' }} border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium {{ ($analytics['low_stock_count'] ?? 0) > 0 ? 'text-danger' : 'text-warning' }} mb-0">Low Stock Alert</p>
                                    <h4 class="fs-22 fw-semibold mb-0 {{ ($analytics['low_stock_count'] ?? 0) > 0 ? 'text-danger' : 'text-warning' }}">{{ $analytics['low_stock_count'] ?? 0 }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title {{ ($analytics['low_stock_count'] ?? 0) > 0 ? 'bg-danger' : 'bg-warning' }} rounded-circle fs-3">
                                        <i class="bi bi-exclamation-triangle-fill text-white"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── Filter Flag Summary Cards (NEW) ───────────────────────────────── --}}
            <div class="row mt-3">
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-success-subtle border-0">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="avatar-title bg-success rounded-circle fs-4" style="width:40px;height:40px;">
                                    <i class="bi bi-stars"></i>
                                </span>
                                <div>
                                    <p class="text-uppercase fw-medium text-success mb-0" style="font-size:11px;">New Products</p>
                                    <h5 class="fw-semibold mb-0">{{ \App\Models\Product::where('is_new', true)->count() }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-danger-subtle border-0">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="avatar-title bg-danger rounded-circle fs-4" style="width:40px;height:40px;">
                                    <i class="bi bi-fire"></i>
                                </span>
                                <div>
                                    <p class="text-uppercase fw-medium text-danger mb-0" style="font-size:11px;">Trending</p>
                                    <h5 class="fw-semibold mb-0">{{ \App\Models\Product::where('is_trending', true)->count() }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-warning-subtle border-0">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="avatar-title bg-warning rounded-circle fs-4" style="width:40px;height:40px;">
                                    <i class="bi bi-star-fill"></i>
                                </span>
                                <div>
                                    <p class="text-uppercase fw-medium text-warning mb-0" style="font-size:11px;">Top Rated</p>
                                    <h5 class="fw-semibold mb-0">{{ \App\Models\Product::where('is_top_rated', true)->count() }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-primary-subtle border-0">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="avatar-title bg-primary rounded-circle fs-4" style="width:40px;height:40px;">
                                    <i class="bi bi-tag-fill"></i>
                                </span>
                                <div>
                                    <p class="text-uppercase fw-medium text-primary mb-0" style="font-size:11px;">On Sale</p>
                                    <h5 class="fw-semibold mb-0">{{ \App\Models\Product::whereNotNull('sale_price')->where('sale_price', '>', 0)->whereColumn('sale_price', '<', 'price')->count() }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── Charts ─────────────────────────────────────────────────────────── --}}
            <div class="row mt-4">
                <div class="col-xl-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Sales Overview (Last 30 Days)</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="salesChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Top Selling Products</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless align-middle mb-0">
                                    <tbody>
                                        @forelse($analytics['top_products'] ?? [] as $item)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-xs me-3">
                                                            <img src="{{ $item->thumbnail ? asset('storage/'.$item->thumbnail) : asset('img/no-image.png') }}" class="rounded-circle avatar-xs">
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0">{{ Str::limit($item->title, 30) }}</h6>
                                                            <small class="text-muted">{{ $item->sold_quantity }} sold</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <span class="badge bg-success-subtle text-success">${{ number_format($item->total_sales ?? 0, 2) }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="2" class="text-center text-muted py-4">No sales yet</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── Product Table ──────────────────────────────────────────────────── --}}
            <div id="productList" class="mt-4">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">
                                        Products <span class="badge bg-dark-subtle text-dark ms-1" id="totalProducts">{{ $products->total() }}</span>
                                    </h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        <div class="input-group input-group-sm me-2" style="width: 250px;">
                                            <input type="text" class="form-control" id="searchInput" placeholder="Search products..." value="{{ request('search', '') }}">
                                            <button class="btn btn-outline-secondary" type="button" id="searchButton"><i class="bi bi-search"></i></button>
                                            <button class="btn btn-outline-secondary" type="button" id="clearSearch" style="display: {{ request('search') ? 'inline-block' : 'none' }};"><i class="bi bi-x"></i></button>
                                        </div>
                                        @can('Create product')
                                            <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#showModal" onclick="resetForm()">Add Product</button>
                                        @endcan
                                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">Import CSV</button>
                                        <a href="{{ route('web.products.export') }}" class="btn btn-info">Export CSV</a>
                                        <a href="{{ route('lightning-deals.index') }}" class="btn btn-warning">
                                            <i class="bi bi-lightning-fill me-1"></i> Lightning Deals
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Advanced Filters --}}
                            <div class="card-body border-bottom">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Search</label>
                                        <input type="text" class="form-control" id="searchInput2" placeholder="Name, SKU, barcode..." value="{{ request('search', '') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Category</label>
                                        <select class="form-control" id="categoryFilter">
                                            <option value="">All Categories</option>
                                            @foreach($categories as $cat)
                                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Brand</label>
                                        <select class="form-control" id="brandFilter">
                                            <option value="">All Brands</option>
                                            @foreach($brands as $brand)
                                                <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Stock Status</label>
                                        <select class="form-control" id="stockFilter">
                                            <option value="">All</option>
                                            <option value="in_stock"      {{ request('stock') == 'in_stock' ? 'selected' : '' }}>In Stock</option>
                                            <option value="low_stock"     {{ request('stock') == 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                                            <option value="out_of_stock"  {{ request('stock') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                                        </select>
                                    </div>
                                    {{-- NEW: filter-flag quick-filter --}}
                                    <div class="col-md-2">
                                        <label class="form-label">App Filter</label>
                                        <select class="form-control" id="appFilterFilter">
                                            <option value="">All</option>
                                            <option value="new"       {{ request('app_filter') == 'new'       ? 'selected' : '' }}>New</option>
                                            <option value="trending"  {{ request('app_filter') == 'trending'  ? 'selected' : '' }}>Trending</option>
                                            <option value="top_rated" {{ request('app_filter') == 'top_rated' ? 'selected' : '' }}>Top Rated</option>
                                            <option value="on_sale"   {{ request('app_filter') == 'on_sale'   ? 'selected' : '' }}>On Sale</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end gap-2">
                                        <button type="button" class="btn btn-primary w-100" id="applyFilter">
                                            <i class="bi bi-funnel"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="clearFilters" title="Clear all filters"
                                            style="{{ request()->except('page') ? '' : 'display: none;' }}">
                                            <i class="bi bi-x-circle"></i>
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
                                        @if(request('category_id'))
                                            @php $category = $categories->firstWhere('id', request('category_id')); @endphp
                                            <span class="badge bg-primary-subtle text-primary">Category: {{ $category->name ?? 'Unknown' }} <button type="button" class="btn-close btn-close-sm ms-1" onclick="removeFilter('category_id')"></button></span>
                                        @endif
                                        @if(request('brand_id'))
                                            @php $brand = $brands->firstWhere('id', request('brand_id')); @endphp
                                            <span class="badge bg-primary-subtle text-primary">Brand: {{ $brand->name ?? 'Unknown' }} <button type="button" class="btn-close btn-close-sm ms-1" onclick="removeFilter('brand_id')"></button></span>
                                        @endif
                                        @if(request('stock'))
                                            <span class="badge bg-primary-subtle text-primary">Stock: {{ ucfirst(str_replace('_', ' ', request('stock'))) }} <button type="button" class="btn-close btn-close-sm ms-1" onclick="removeFilter('stock')"></button></span>
                                        @endif
                                        @if(request('app_filter'))
                                            <span class="badge bg-info-subtle text-info">App Filter: {{ ucfirst(str_replace('_', ' ', request('app_filter'))) }} <button type="button" class="btn-close btn-close-sm ms-1" onclick="removeFilter('app_filter')"></button></span>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0">
                                        <thead class="table-active">
                                            <tr>
                                                <th>Product</th>
                                                <th>Barcode</th>
                                                <th>Category</th>
                                                <th>Cost Price</th>
                                                <th>Price</th>
                                                <th>Margin</th>
                                                <th>Stock</th>
                                                <th>Sold</th>
                                                {{-- ── NEW: combined flag column ── --}}
                                                <th class="text-center" style="min-width:180px;">
                                                    App Flags
                                                    <i class="bi bi-phone ms-1 text-muted" title="Controls which filter chips this product appears under in the mobile app"></i>
                                                </th>
                                                <th>Featured</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="productTableBody">
                                            @forelse($products as $product)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm bg-light rounded p-1 me-3">
                                                            @if($product->thumbnail)
                                                                <img src="{{ asset('storage/' . $product->thumbnail) }}" alt="" class="img-fluid rounded" style="max-height:40px;">
                                                            @else
                                                                <div class="bg-secondary-subtle rounded d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                                                    <i class="bi bi-image text-muted fs-5"></i>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-1"><a href="{{ route('web.products.show', $product->id) }}" class="text-reset">{{ Str::limit($product->title, 50) }}</a></h6>
                                                            <small class="text-muted d-block">
                                                                SKU: <span class="fw-semibold">{{ $product->sku }}</span><br>
                                                                <i class="bi bi-box-seam me-1"></i>{{ $product->product_type === 'variable' ? 'Variable' : 'Simple' }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($product->barcode)
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge bg-info-subtle text-info">{{ $product->barcode }}</span>
                                                            <button class="btn btn-sm btn-outline-secondary ms-1" onclick="copyBarcode('{{ $product->barcode }}')" title="Copy barcode"><i class="bi bi-copy"></i></button>
                                                        </div>
                                                    @else
                                                        <span class="text-muted small">Auto-generated</span>
                                                    @endif
                                                </td>
                                                <td>{{ $product->category?->name ?? 'Uncategorized' }}</td>
                                                <td>
                                                    @if($product->cost_price)
                                                        <span class="fw-bold">${{ number_format($product->cost_price, 2) }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($product->sale_price && $product->sale_price < $product->price)
                                                        @php $discount = round((($product->price - $product->sale_price) / $product->price) * 100); @endphp
                                                        <del class="text-muted small">${{ number_format($product->price, 2) }}</del><br>
                                                        <span class="text-danger fw-bold">${{ number_format($product->sale_price, 2) }}</span>
                                                        <span class="badge bg-danger position-relative" style="top:-8px;right:-32px;font-size:0.65rem;">-{{ $discount }}%</span>
                                                    @else
                                                        <span class="fw-bold">${{ number_format($product->price, 2) }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($product->cost_price && $product->cost_price > 0)
                                                        @php
                                                            $sellingPrice   = $product->sale_price ?? $product->price;
                                                            $margin         = $sellingPrice - $product->cost_price;
                                                            $marginPercent  = ($margin / $product->cost_price) * 100;
                                                        @endphp
                                                        <span class="badge {{ $marginPercent >= 50 ? 'bg-success-subtle text-success' : ($marginPercent >= 20 ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger') }}">
                                                            {{ number_format($marginPercent, 1) }}%
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $currentStock = $product->current_stock;
                                                        $primaryUnit  = $product->units->first();
                                                        $primaryShort = $primaryUnit ? $primaryUnit->short_name : '';
                                                    @endphp
                                                    @if($currentStock > 10)
                                                        <span class="badge bg-success-subtle text-success">{{ $currentStock }} {{ $primaryShort }}</span>
                                                    @elseif($currentStock > 0)
                                                        <span class="badge bg-warning-subtle text-warning">{{ $currentStock }} {{ $primaryShort }} low</span>
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger">Out of stock</span>
                                                    @endif
                                                </td>
                                                <td class="text-center"><span class="fw-semibold">{{ $product->sold_quantity ?? 0 }}</span></td>

                                                {{-- ── App Flags column ───────────────────────────────────────── --}}
                                                <td>
                                                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-center">
                                                        {{-- NEW --}}
                                                        <div class="form-check form-switch mb-0" title="Show in 'New' filter">
                                                            <input class="form-check-input flag-toggle" type="checkbox"
                                                                   data-id="{{ $product->id }}"
                                                                   data-flag="is_new"
                                                                   id="isNew_{{ $product->id }}"
                                                                   {{ $product->is_new ? 'checked' : '' }}>
                                                            <label class="form-check-label small text-success fw-semibold" for="isNew_{{ $product->id }}">New</label>
                                                        </div>
                                                        {{-- TRENDING --}}
                                                        <div class="form-check form-switch mb-0" title="Show in 'Trending' filter">
                                                            <input class="form-check-input flag-toggle" type="checkbox"
                                                                   data-id="{{ $product->id }}"
                                                                   data-flag="is_trending"
                                                                   id="isTrending_{{ $product->id }}"
                                                                   {{ $product->is_trending ? 'checked' : '' }}>
                                                            <label class="form-check-label small text-danger fw-semibold" for="isTrending_{{ $product->id }}">🔥 Hot</label>
                                                        </div>
                                                        {{-- TOP RATED --}}
                                                        <div class="form-check form-switch mb-0" title="Show in 'Top Rated' filter">
                                                            <input class="form-check-input flag-toggle" type="checkbox"
                                                                   data-id="{{ $product->id }}"
                                                                   data-flag="is_top_rated"
                                                                   id="isTopRated_{{ $product->id }}"
                                                                   {{ $product->is_top_rated ? 'checked' : '' }}>
                                                            <label class="form-check-label small text-warning fw-semibold" for="isTopRated_{{ $product->id }}">⭐ Top</label>
                                                        </div>
                                                    </div>
                                                </td>
                                                {{-- ── End App Flags ─────────────────────────────────────────── --}}

                                                <td>
                                                    @if($product->is_featured)
                                                        <span class="badge bg-primary-subtle text-primary"><i class="bi bi-star-fill text-warning me-1"></i> Featured</span>
                                                    @else
                                                        <span class="badge bg-secondary-subtle text-secondary">Regular</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="btn btn-subtle-secondary btn-sm btn-icon" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="{{ route('web.products.show', $product->id) }}">View</a></li>
                                                            @can('Update product')
                                                                <li><a class="dropdown-item edit-item-btn" href="javascript:void(0);" data-id="{{ $product->id }}">Edit</a></li>
                                                            @endcan
                                                            @can('Delete product')
                                                                <li><a class="dropdown-item remove-item-btn text-danger" href="javascript:void(0);" data-id="{{ $product->id }}">Delete</a></li>
                                                            @endcan
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr id="noResultsRow" class="text-center py-5 text-muted">
                                                <td colspan="11">
                                                    @if(request()->except('page'))
                                                        No products found matching your filters.<br>
                                                        <a href="{{ route('web.products.index') }}" class="btn btn-sm btn-outline-primary mt-2">Clear filters</a>
                                                    @else
                                                        No products found. <a href="javascript:void(0)" class="text-primary" data-bs-toggle="modal" data-bs-target="#showModal" onclick="resetForm()">Add your first product</a>
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
                                            Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} Results
                                        </div>
                                    </div>
                                    <div class="col-sm-auto mt-3 mt-sm-0">
                                        {!! $products->appends(request()->query())->links('pagination::bootstrap-5') !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── Import Modal ───────────────────────────────────────────────────── --}}
            <div class="modal fade" id="importModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('web.products.import') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Import Products</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">CSV File</label>
                                    <input type="file" name="file" class="form-control" accept=".csv" required>
                                </div>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Download the <a href="{{ route('web.products.template') }}" class="alert-link">template file</a> for correct formatting.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Import</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ─── Add / Edit Product Modal ───────────────────────────────────────── --}}
            <div class="modal fade" id="showModal" tabindex="-1" data-bs-backdrop="static">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <form id="productForm" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="id" id="product_id">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalTitle">Add Product</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                                <div class="row g-4">

                                    {{-- Left column --}}
                                    <div class="col-lg-8">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title mb-3">Basic Information</h6>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Title *</label>
                                                        <input type="text" name="title" id="title" class="form-control" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">SKU *</label>
                                                        <input type="text" name="sku" id="sku" class="form-control" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Barcode</label>
                                                        <div class="input-group">
                                                            <input type="text" name="barcode" id="barcode" class="form-control" placeholder="Auto-generate">
                                                            <button type="button" class="btn btn-outline-secondary" id="generateBarcodeBtn"><i class="bi bi-upc-scan"></i></button>
                                                        </div>
                                                        <small class="text-muted">Leave empty to auto-generate</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Product Type *</label>
                                                        <select name="product_type" id="product_type" class="form-control" required>
                                                            <option value="simple">Simple Product</option>
                                                            <option value="variable">Variable Product</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Cost Price</label>
                                                        <div class="input-group"><span class="input-group-text">$</span>
                                                            <input type="number" step="0.01" name="cost_price" id="cost_price" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Price *</label>
                                                        <div class="input-group"><span class="input-group-text">$</span>
                                                            <input type="number" step="0.01" name="price" id="price" class="form-control" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Discount %</label>
                                                        <input type="number" min="0" max="100" id="discount_percent" class="form-control">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Sale Price</label>
                                                        <div class="input-group"><span class="input-group-text">$</span>
                                                            <input type="number" step="0.01" name="sale_price" id="sale_price" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Description</label>
                                                        <textarea name="description" id="description" rows="4" class="form-control"></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="variationsSection" style="display:none;">
                                            <div class="card mt-4">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0">Product Variations</h6>
                                                    <div>
                                                        <input type="number" min="0" max="100" id="bulk_discount" class="form-control form-control-sm d-inline w-auto me-2" placeholder="Bulk %">
                                                        <button type="button" class="btn btn-success btn-sm" id="applyBulkDiscount">Apply</button>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div id="attributesContainer" class="mb-4">
                                                        <div class="row g-3 align-items-end attribute-row">
                                                            <div class="col-md-5"><input type="text" class="form-control" placeholder="e.g. Color" name="attributes[0][name]"></div>
                                                            <div class="col-md-6"><input type="text" class="form-control" placeholder="Red, Blue, Green" name="attributes[0][values]"></div>
                                                            <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm remove-attribute">Remove</button></div>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="addAttribute">+ Add Attribute</button>
                                                    <button type="button" class="btn btn-primary btn-sm mb-3" id="generateVariations">Generate Variations</button>
                                                    <hr>
                                                    <div id="variationsTable"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Right column --}}
                                    <div class="col-lg-4">
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <label class="form-label">Thumbnail</label>
                                                <input type="file" name="thumbnail" id="thumbnail_input" class="form-control mb-2" accept="image/*">
                                                <div class="text-center">
                                                    <img id="thumbnail_preview" src="" class="img-fluid rounded" style="max-height:200px;display:none;">
                                                    <div id="thumbnail_placeholder" class="text-muted"><i class="bi bi-image display-4"></i><p>No image</p></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <label class="form-label">Gallery</label>
                                                <input type="file" name="images[]" id="gallery_input" multiple class="form-control mb-3" accept="image/*">
                                                <div id="imageGallery" class="row g-2"></div>
                                            </div>
                                        </div>
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Brand</label>
                                                    <select name="brand_id" id="brand_id" class="form-control">
                                                        <option value="">No Brand</option>
                                                        @foreach($brands as $brand)
                                                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Category</label>
                                                    <select name="category_id" id="category_id" class="form-control">
                                                        <option value="">Select Category</option>
                                                        @foreach($categories as $cat)
                                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Primary Unit *</label>
                                                    <select name="primary_unit_id" id="primary_unit_id" class="form-control" required>
                                                        <option value="">Select Unit</option>
                                                        @foreach($units as $unit)
                                                            <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->short_name }})</option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-muted">Default unit for selling this product</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <h6 class="mb-3">Additional Units</h6>
                                                <div id="unitsContainer"></div>
                                                <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="addUnit">+ Add Unit</button>
                                            </div>
                                        </div>

                                        {{-- ── Product Flags card (NEW) ───────────────── --}}
                                        <div class="card mb-3 border-primary border-opacity-25">
                                            <div class="card-header bg-primary bg-opacity-10">
                                                <h6 class="card-title mb-0 text-primary">
                                                    <i class="bi bi-phone me-1"></i> App Display Flags
                                                </h6>
                                                <small class="text-muted">Controls which filter chips this product appears under in the mobile app</small>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured">
                                                    <label class="form-check-label fw-semibold" for="is_featured">
                                                        <i class="bi bi-star-fill text-warning me-1"></i> Featured
                                                    </label>
                                                    <div class="text-muted small">Shows on the Store featured section</div>
                                                </div>
                                                <hr class="my-2">
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" name="is_new" id="is_new">
                                                    <label class="form-check-label fw-semibold text-success" for="is_new">
                                                        ✨ New
                                                    </label>
                                                    <div class="text-muted small">Shows under the "New" filter chip</div>
                                                </div>
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" name="is_trending" id="is_trending">
                                                    <label class="form-check-label fw-semibold text-danger" for="is_trending">
                                                        🔥 Trending
                                                    </label>
                                                    <div class="text-muted small">Shows under the "Trending" filter chip</div>
                                                </div>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" name="is_top_rated" id="is_top_rated">
                                                    <label class="form-check-label fw-semibold text-warning" for="is_top_rated">
                                                        ⭐ Top Rated
                                                    </label>
                                                    <div class="text-muted small">Shows under the "Top Rated" filter chip</div>
                                                </div>
                                                <div class="alert alert-info py-2 mt-3 mb-0" style="font-size:12px;">
                                                    <i class="bi bi-tag me-1"></i> <strong>On Sale</strong> is automatic — set a Sale Price to enable it.
                                                </div>
                                            </div>
                                        </div>
                                        {{-- ── End App Flags card ─────────────────────── --}}

                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <span class="spinner-border spinner-border-sm d-none me-1" id="submitSpinner"></span>
                                    Save Product
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            {{-- ─── End Modals ─────────────────────────────────────────────────────── --}}

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
    }

    // ── Sales Chart ────────────────────────────────────────────────────────
    const salesChartCtx = document.getElementById('salesChart');
    if (salesChartCtx) {
        new Chart(salesChartCtx, {
            type: 'line',
            data: { labels: [], datasets: [{ label: 'Daily Sales ($)', data: [], borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.1)', tension: 0.4, fill: true, borderWidth: 2 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true } } }
        });
    }

    // ── Copy Barcode ───────────────────────────────────────────────────────
    window.copyBarcode = function(barcode) {
        navigator.clipboard.writeText(barcode).then(() => {
            Swal.fire({ icon: 'success', title: 'Copied!', text: 'Barcode copied to clipboard', timer: 1500, showConfirmButton: false });
        }).catch(() => Swal.fire({ icon: 'error', title: 'Failed', text: 'Could not copy barcode' }));
    };

    // ── Remove filter badge ────────────────────────────────────────────────
    window.removeFilter = function(filterName) {
        const params = new URLSearchParams(window.location.search);
        params.delete(filterName);
        params.delete('page');
        window.location.href = `${window.location.pathname}?${params.toString()}`;
    };

    // ─────────────────────────────────────────────────────────────────────
    // INLINE FLAG TOGGLES  (New / Trending / Top Rated switches in the table)
    // ─────────────────────────────────────────────────────────────────────
    document.querySelectorAll('.flag-toggle').forEach(toggle => {
        toggle.addEventListener('change', function () {
            const productId = this.dataset.id;
            const flag      = this.dataset.flag;   // is_new | is_trending | is_top_rated
            const value     = this.checked;
            const original  = !value;              // for rollback on error

            axios.patch(`/web/products/${productId}/flags`, { flag, value })
                .then(res => {
                    // tiny toast — no full Swal to avoid interrupting workflow
                    const toast = Swal.mixin({
                        toast: true,
                        position: 'bottom-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                    });
                    toast.fire({ icon: 'success', title: res.data.message ?? 'Saved' });
                })
                .catch(err => {
                    // Roll back toggle visually
                    this.checked = original;
                    Swal.fire('Error', err.response?.data?.message ?? 'Failed to update flag', 'error');
                });
        });
    });

    // ── Search / Filter ────────────────────────────────────────────────────
    function initializeSearch() {
        const searchInput    = document.getElementById('searchInput');
        const searchInput2   = document.getElementById('searchInput2');
        const searchButton   = document.getElementById('searchButton');
        const clearSearch    = document.getElementById('clearSearch');
        const applyFilter    = document.getElementById('applyFilter');
        const clearFilters   = document.getElementById('clearFilters');
        const categoryFilter = document.getElementById('categoryFilter');
        const brandFilter    = document.getElementById('brandFilter');
        const stockFilter    = document.getElementById('stockFilter');
        const appFilterFilter= document.getElementById('appFilterFilter');
        const tbody          = document.querySelector('tbody');
        const rows           = tbody ? Array.from(tbody.querySelectorAll('tr')) : [];
        const totalProducts  = document.getElementById('totalProducts');

        function syncSearchInputs() {
            if (searchInput && searchInput2) {
                searchInput2.addEventListener('input', function() { searchInput.value = this.value; });
                searchInput.addEventListener('input',  function() { searchInput2.value = this.value; });
            }
        }

        function performClientSearch(searchTerm) {
            if (!searchTerm.trim()) {
                rows.forEach(r => { if (r.id !== 'noResultsRow') r.style.display = ''; });
                if (totalProducts) totalProducts.textContent = '{{ $products->total() }}';
                if (clearSearch) clearSearch.style.display = 'none';
                return;
            }
            const term = searchTerm.toLowerCase();
            let visible = 0;
            rows.forEach(row => {
                if (row.id === 'noResultsRow') { row.style.display = 'none'; return; }
                const show = row.textContent.toLowerCase().includes(term);
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            if (totalProducts) totalProducts.textContent = visible;
            if (clearSearch)   clearSearch.style.display = 'inline-block';
            const noResultsRow = document.getElementById('noResultsRow');
            if (noResultsRow && visible === 0) noResultsRow.style.display = '';
        }

        function performServerSearch() {
            const params = new URLSearchParams(window.location.search);
            const s = searchInput?.value.trim();
            s ? params.set('search', s) : params.delete('search');
            categoryFilter?.value    ? params.set('category_id', categoryFilter.value) : params.delete('category_id');
            brandFilter?.value       ? params.set('brand_id', brandFilter.value)       : params.delete('brand_id');
            stockFilter?.value       ? params.set('stock', stockFilter.value)           : params.delete('stock');
            appFilterFilter?.value   ? params.set('app_filter', appFilterFilter.value) : params.delete('app_filter');
            params.delete('page');
            window.location.href = `${window.location.pathname}?${params.toString()}`;
        }

        syncSearchInputs();

        let searchTimeout;
        const debounced = function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => performClientSearch(this.value), 300);
        };
        searchInput?.addEventListener('input', debounced);
        searchInput2?.addEventListener('input', debounced);
        [searchInput, searchInput2].forEach(el => el?.addEventListener('keypress', e => { if (e.key === 'Enter') performServerSearch(); }));
        searchButton?.addEventListener('click', performServerSearch);
        clearSearch?.addEventListener('click', () => {
            if (searchInput)  searchInput.value = '';
            if (searchInput2) searchInput2.value = '';
            performClientSearch('');
            performServerSearch();
        });
        applyFilter?.addEventListener('click', performServerSearch);
        clearFilters?.addEventListener('click', () => window.location.href = window.location.pathname);
        if (searchInput && clearSearch) searchInput.addEventListener('input', function() { clearSearch.style.display = this.value ? 'inline-block' : 'none'; });
    }
    initializeSearch();

    // ── Product Type Toggle ────────────────────────────────────────────────
    const productTypeSelect  = document.getElementById('product_type');
    const variationsSection  = document.getElementById('variationsSection');
    function toggleVariationsSection() { variationsSection.style.display = productTypeSelect.value === 'variable' ? 'block' : 'none'; }
    productTypeSelect?.addEventListener('change', toggleVariationsSection);
    toggleVariationsSection();

    // ── Price Calculations ─────────────────────────────────────────────────
    const priceInput    = document.getElementById('price');
    const discountInput = document.getElementById('discount_percent');
    const salePriceInput= document.getElementById('sale_price');
    function calculateSalePrice() {
        const price = parseFloat(priceInput?.value) || 0;
        const disc  = parseFloat(discountInput?.value) || 0;
        if (price > 0 && disc > 0 && disc <= 100) salePriceInput.value = (price * (1 - disc / 100)).toFixed(2);
    }
    priceInput?.addEventListener('input', calculateSalePrice);
    discountInput?.addEventListener('input', calculateSalePrice);

    // ── Bulk Discount ──────────────────────────────────────────────────────
    document.getElementById('applyBulkDiscount')?.addEventListener('click', function () {
        const bulk = parseFloat(document.getElementById('bulk_discount').value) || 0;
        if (bulk < 0 || bulk > 100) return Swal.fire('Invalid', 'Discount must be 0–100%', 'warning');
        document.querySelectorAll('#variationsTable tbody tr').forEach(row => {
            const p = row.querySelector('input[name*="price"]');
            const s = row.querySelector('input[name*="sale_price"]');
            const price = parseFloat(p?.value) || 0;
            if (price > 0) s.value = (price * (1 - bulk / 100)).toFixed(2);
        });
        Swal.fire('Applied', `${bulk}% discount applied to all variations`, 'success');
    });

    // ── Dynamic Attributes ─────────────────────────────────────────────────
    let attrIndex = 1;
    document.getElementById('addAttribute')?.addEventListener('click', () => {
        document.getElementById('attributesContainer').insertAdjacentHTML('beforeend', `
            <div class="row g-3 align-items-end attribute-row mt-3">
                <div class="col-md-5"><input type="text" class="form-control" placeholder="e.g. Size" name="attributes[${attrIndex}][name]"></div>
                <div class="col-md-6"><input type="text" class="form-control" placeholder="S, M, L" name="attributes[${attrIndex}][values]"></div>
                <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm remove-attribute">Remove</button></div>
            </div>`);
        attrIndex++;
    });

    // ── Dynamic Units ──────────────────────────────────────────────────────
    let unitIndex = 0;
    document.getElementById('addUnit')?.addEventListener('click', () => {
        document.getElementById('unitsContainer').insertAdjacentHTML('beforeend', `
            <div class="row g-3 align-items-end unit-row mt-3">
                <div class="col-md-5">
                    <select name="units[${unitIndex}][unit_id]" class="form-control">
                        <option value="">Select Unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->short_name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5"><input type="number" step="0.01" name="units[${unitIndex}][quantity_per_unit]" class="form-control" placeholder="Qty per primary unit"></div>
                <div class="col-md-2"><button type="button" class="btn btn-danger btn-sm remove-unit">Remove</button></div>
            </div>`);
        unitIndex++;
    });

    // ── Remove Rows ────────────────────────────────────────────────────────
    document.addEventListener('click', e => {
        if (e.target.classList.contains('remove-attribute')) e.target.closest('.attribute-row').remove();
        if (e.target.classList.contains('remove-unit'))      e.target.closest('.unit-row').remove();
        if (e.target.classList.contains('remove-variation')) e.target.closest('tr').remove();
    });

    // ── Generate Barcode ───────────────────────────────────────────────────
    function generateVariationBarcode(baseSku, attributes) {
        const s = Object.values(attributes || {}).join('-').toUpperCase().replace(/\s+/g, '');
        const r = Math.random().toString(36).substring(2, 8).toUpperCase();
        return `${baseSku}-${s}-${r}`.substring(0, 20);
    }
    document.getElementById('generateBarcodeBtn')?.addEventListener('click', () => {
        const sku = document.getElementById('sku').value || 'PROD';
        const r   = Math.random().toString(36).substring(2, 10).toUpperCase();
        document.getElementById('barcode').value = `${sku}-${r}`.substring(0, 20);
    });

    // ── Generate Variations ────────────────────────────────────────────────
    document.getElementById('generateVariations')?.addEventListener('click', () => {
        const attrs = [];
        document.querySelectorAll('.attribute-row').forEach(row => {
            const name   = row.querySelector('input[name$="[name]"]')?.value.trim();
            const values = row.querySelector('input[name$="[values]"]')?.value.split(',').map(v => v.trim()).filter(v => v);
            if (name && values.length) attrs.push({ name, values });
        });
        if (!attrs.length) { document.getElementById('variationsTable').innerHTML = '<p class="text-muted">Add at least one attribute</p>'; return; }

        const combos  = attrs.reduce((acc, attr) => acc.flatMap(obj => attr.values.map(val => ({ ...obj, [attr.name]: val }))), [{}]);
        const baseSku = document.getElementById('sku').value || 'PROD';

        let html = `<div class="table-responsive"><table class="table table-bordered table-hover"><thead class="table-light"><tr>
            <th>Variant</th><th>SKU</th><th>Barcode</th><th>Cost Price</th><th>Price</th><th>Sale Price</th><th>Image</th><th>Action</th>
        </tr></thead><tbody>`;

        combos.forEach((combo, i) => {
            const badges = Object.entries(combo).map(([k, v]) => `<span class="badge bg-primary me-1">${k}: ${v}</span>`).join(' ');
            const hiddens= Object.entries(combo).map(([k, v]) => `<input type="hidden" name="variations[${i}][attributes][${k}]" value="${v}">`).join('');
            const bc     = generateVariationBarcode(baseSku, combo);
            html += `<tr>
                <td><div class="d-flex flex-wrap gap-1">${badges}</div>${hiddens}</td>
                <td><input type="text" name="variations[${i}][sku]" class="form-control form-control-sm" value="${baseSku}-VAR${i+1}"></td>
                <td><input type="text" name="variations[${i}][barcode]" class="form-control form-control-sm" value="${bc}"><small class="text-muted">Auto-generated</small></td>
                <td><input type="number" step="0.01" name="variations[${i}][cost_price]" class="form-control form-control-sm" placeholder="0.00"></td>
                <td><input type="number" step="0.01" name="variations[${i}][price]"      class="form-control form-control-sm" required placeholder="0.00"></td>
                <td><input type="number" step="0.01" name="variations[${i}][sale_price]" class="form-control form-control-sm" placeholder="0.00"></td>
                <td>
                    <div class="d-flex flex-column align-items-center">
                        <input type="file" name="variations[${i}][image]" class="form-control form-control-sm variation-image" accept="image/*">
                        <img class="variation-preview mt-2 img-fluid rounded" style="max-height:60px;width:60px;object-fit:cover;display:none;">
                    </div>
                </td>
                <td><button type="button" class="btn btn-danger btn-sm remove-variation"><i class="bi bi-trash"></i></button></td>
            </tr>`;
        });

        html += '</tbody></table></div>';
        document.getElementById('variationsTable').innerHTML = html;
    });

    // ── Thumbnail Preview ──────────────────────────────────────────────────
    document.getElementById('thumbnail_input')?.addEventListener('change', function(e) {
        if (e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = ev => {
                document.getElementById('thumbnail_preview').src = ev.target.result;
                document.getElementById('thumbnail_preview').style.display = 'block';
                document.getElementById('thumbnail_placeholder').style.display = 'none';
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    });

    // ── Gallery Preview ────────────────────────────────────────────────────
    document.getElementById('gallery_input')?.addEventListener('change', function(e) {
        const container = document.getElementById('imageGallery');
        container.innerHTML = '';
        Array.from(e.target.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = ev => {
                container.innerHTML += `<div class="col-4 position-relative">
                    <img src="${ev.target.result}" class="img-fluid rounded" style="height:100px;object-fit:cover;">
                    <button type="button" class="btn-close position-absolute top-0 end-0 bg-white" onclick="this.parentElement.remove()"></button>
                </div>`;
            };
            reader.readAsDataURL(file);
        });
    });

    // ── Variation Image Preview ────────────────────────────────────────────
    document.addEventListener('change', e => {
        if (e.target.classList.contains('variation-image') && e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = ev => {
                const preview = e.target.closest('td').querySelector('.variation-preview');
                if (preview) { preview.src = ev.target.result; preview.style.display = 'block'; }
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    });

    // ── Form Reset ─────────────────────────────────────────────────────────
    window.resetForm = function () {
        document.getElementById('productForm').reset();
        document.getElementById('product_id').value = '';
        document.getElementById('modalTitle').textContent = 'Add Product';
        document.getElementById('thumbnail_preview').style.display = 'none';
        document.getElementById('thumbnail_placeholder').style.display = 'block';
        document.getElementById('imageGallery').innerHTML = '';
        document.getElementById('variationsTable').innerHTML = '';
        document.getElementById('unitsContainer').innerHTML = '';
        document.getElementById('attributesContainer').innerHTML = `
            <div class="row g-3 align-items-end attribute-row">
                <div class="col-md-5"><input type="text" class="form-control" placeholder="e.g. Color" name="attributes[0][name]"></div>
                <div class="col-md-6"><input type="text" class="form-control" placeholder="Red, Blue, Green" name="attributes[0][values]"></div>
                <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm remove-attribute">Remove</button></div>
            </div>`;
        toggleVariationsSection();
        attrIndex = 1;
        unitIndex = 0;
    };

    // ── Edit Product ───────────────────────────────────────────────────────
    document.addEventListener('click', function(e) {
        if (e.target.matches('.edit-item-btn') || e.target.closest('.edit-item-btn')) {
            const btn = e.target.matches('.edit-item-btn') ? e.target : e.target.closest('.edit-item-btn');
            const id  = btn.dataset.id;
            if (!id) return;

            Swal.fire({ title: 'Loading...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            axios.get(`/web/products/${id}/edit`)
                .then(response => {
                    const p = response.data;
                    resetForm();

                    document.getElementById('product_id').value   = p.id;
                    document.getElementById('title').value        = p.title || '';
                    document.getElementById('sku').value          = p.sku || '';
                    document.getElementById('barcode').value      = p.barcode || '';
                    document.getElementById('price').value        = p.price || '';
                    document.getElementById('cost_price').value   = p.cost_price || '';
                    document.getElementById('sale_price').value   = p.sale_price || '';
                    document.getElementById('description').value  = p.description || '';
                    document.getElementById('product_type').value = p.product_type || 'simple';
                    document.getElementById('brand_id').value     = p.brand_id || '';
                    document.getElementById('category_id').value  = p.category_id || '';
                    document.getElementById('primary_unit_id').value = p.primary_unit_id || '';

                    // Booleans / flags
                    document.getElementById('is_featured').checked  = !!p.is_featured;
                    document.getElementById('is_new').checked       = !!p.is_new;
                    document.getElementById('is_trending').checked  = !!p.is_trending;
                    document.getElementById('is_top_rated').checked = !!p.is_top_rated;

                    // Thumbnail
                    if (p.thumbnail) {
                        document.getElementById('thumbnail_preview').src          = p.thumbnail;
                        document.getElementById('thumbnail_preview').style.display = 'block';
                        document.getElementById('thumbnail_placeholder').style.display = 'none';
                    }

                    // Gallery
                    const gallery = document.getElementById('imageGallery');
                    gallery.innerHTML = '';
                    (p.gallery || []).forEach(img => {
                        gallery.innerHTML += `<div class="col-4 position-relative">
                            <img src="${img.url}" class="img-fluid rounded" style="height:100px;object-fit:cover;">
                            <button type="button" class="btn-close position-absolute top-0 end-0 bg-white" onclick="this.parentElement.remove()"></button>
                        </div>`;
                    });

                    // Additional Units
                    document.getElementById('unitsContainer').innerHTML = '';
                    (p.additional_units || []).forEach(() => {
                        document.getElementById('addUnit').click();
                    });
                    document.querySelectorAll('.unit-row').forEach((row, i) => {
                        const u = (p.additional_units || [])[i];
                        if (!u) return;
                        row.querySelector('select[name*="unit_id"]').value = u.unit_id;
                        row.querySelector('input[name*="quantity_per_unit"]').value = u.quantity_per_unit;
                    });

                    toggleVariationsSection();

                    if (p.product_type === 'variable') {
                        document.getElementById('attributesContainer').innerHTML = '';
                        (p.attributes || []).forEach((attr, i) => {
                            if (i > 0) document.getElementById('addAttribute').click();
                            const rows = document.querySelectorAll('.attribute-row');
                            const row  = rows[i];
                            if (row) {
                                row.querySelector('input[name$="[name]"]').value   = attr.name || '';
                                row.querySelector('input[name$="[values]"]').value = Array.isArray(attr.values) ? attr.values.join(', ') : (attr.values || '');
                            }
                        });

                        setTimeout(() => {
                            if (p.variations && p.variations.length > 0) {
                                let tbl = `<div class="table-responsive"><table class="table table-bordered table-hover">
                                <thead class="table-light"><tr>
                                    <th>Variant</th><th>SKU</th><th>Barcode</th><th>Cost Price</th><th>Price</th><th>Sale Price</th><th>Image</th><th>Action</th>
                                </tr></thead><tbody>`;
                                p.variations.forEach((v, i) => {
                                    const badges = Object.entries(v.attributes || {}).map(([k, v]) => `<span class="badge bg-primary me-1">${k}: ${v}</span>`).join(' ');
                                    const hiddens= Object.entries(v.attributes || {}).map(([k, v]) => `<input type="hidden" name="variations[${i}][attributes][${k}]" value="${v}">`).join('');
                                    tbl += `<tr>
                                        <td><div class="d-flex flex-wrap gap-1">${badges}</div>${hiddens}</td>
                                        <td><input type="text"   name="variations[${i}][sku]"        class="form-control form-control-sm" value="${v.sku || ''}"></td>
                                        <td><input type="text"   name="variations[${i}][barcode]"    class="form-control form-control-sm" value="${v.barcode || ''}"></td>
                                        <td><input type="number" name="variations[${i}][cost_price]" class="form-control form-control-sm" value="${v.cost_price || ''}" step="0.01"></td>
                                        <td><input type="number" name="variations[${i}][price]"      class="form-control form-control-sm" value="${v.price || ''}"      step="0.01" required></td>
                                        <td><input type="number" name="variations[${i}][sale_price]" class="form-control form-control-sm" value="${v.sale_price || ''}" step="0.01"></td>
                                        <td><div class="d-flex flex-column align-items-center">
                                            <input type="file" name="variations[${i}][image]" class="form-control form-control-sm variation-image" accept="image/*">
                                            ${v.image ? `<img src="${v.image}" class="variation-preview mt-2 img-fluid rounded" style="max-height:60px;width:60px;object-fit:cover;">` : '<img class="variation-preview mt-2 img-fluid rounded" style="max-height:60px;width:60px;object-fit:cover;display:none;">'}
                                        </div></td>
                                        <td><button type="button" class="btn btn-danger btn-sm remove-variation"><i class="bi bi-trash"></i></button></td>
                                    </tr>`;
                                });
                                tbl += '</tbody></table></div>';
                                document.getElementById('variationsTable').innerHTML = tbl;
                            } else {
                                document.getElementById('generateVariations').click();
                            }
                        }, 100);
                    }

                    document.getElementById('modalTitle').textContent = 'Edit Product';

                    // Clean up any stale backdrop
                    const modalEl = document.getElementById('showModal');
                    const modal   = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow     = '';
                    document.body.style.paddingRight = '';
                    modal.show();

                    Swal.close();
                })
                .catch(err => {
                    Swal.fire('Error', 'Failed to load product: ' + (err.response?.data?.message || err.message), 'error');
                });
        }
    });

    // ── Delete Product ─────────────────────────────────────────────────────
    document.addEventListener('click', function(e) {
        if (e.target.matches('.remove-item-btn') || e.target.closest('.remove-item-btn')) {
            const btn = e.target.matches('.remove-item-btn') ? e.target : e.target.closest('.remove-item-btn');
            const id  = btn.dataset.id;
            Swal.fire({ title: 'Delete Product?', text: 'This action cannot be undone!', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete!' })
                .then(result => {
                    if (result.isConfirmed) {
                        axios.delete(`/web/products/${id}`)
                            .then(() => Swal.fire('Deleted!', 'Product has been deleted', 'success').then(() => location.reload()))
                            .catch(() => Swal.fire('Error', 'Failed to delete', 'error'));
                    }
                });
        }
    });

    // ── Form Submission ────────────────────────────────────────────────────
    document.getElementById('productForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const id       = document.getElementById('product_id').value;
        if (id) formData.append('_method', 'PUT');

        const btn     = document.getElementById('submitBtn');
        const spinner = document.getElementById('submitSpinner');
        btn.disabled  = true;
        spinner.classList.remove('d-none');

        axios.post(id ? `/web/products/${id}` : '/web/products', formData, {
            headers: { 'Content-Type': 'multipart/form-data', 'X-CSRF-TOKEN': csrfToken?.getAttribute('content') ?? '' }
        })
            .then(res => Swal.fire({ icon: 'success', title: 'Success!', text: res.data.message || 'Product saved', showConfirmButton: false, timer: 1500 }).then(() => location.reload()))
            .catch(err => {
                let msg = 'An error occurred';
                if (err.response?.data?.errors) {
                    msg = Object.entries(err.response.data.errors).map(([k, v]) => `${k}: ${v.join(', ')}`).join('<br>');
                } else if (err.response?.data?.message) {
                    msg = err.response.data.message;
                }
                Swal.fire({ icon: 'error', title: 'Error', html: msg });
            })
            .finally(() => { btn.disabled = false; spinner.classList.add('d-none'); });
    });

    // ── Modal backdrop cleanup ─────────────────────────────────────────────
    document.getElementById('showModal')?.addEventListener('hidden.bs.modal', function () {
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow     = '';
        document.body.style.paddingRight = '';
    });
});
</script>
@endsection
