@extends('layouts.master')

@section('title', 'Products Management')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- PAGE TITLE -->
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

            <!-- ANALYTICS DASHBOARD -->
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
                    <div class="card card-animate bg-warning-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-warning mb-0">Featured Products</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ $analytics['featured_count'] ?? 0 }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-warning rounded-circle fs-3">
                                        <i class="bi bi-star-fill text-white"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate {{ ($analytics['low_stock_count'] ?? 0) > 0 ? 'bg-danger-subtle' : 'bg-info-subtle' }} border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium {{ ($analytics['low_stock_count'] ?? 0) > 0 ? 'text-danger' : 'text-info' }} mb-0">Low Stock Alert</p>
                                    <h4 class="fs-22 fw-semibold mb-0 {{ ($analytics['low_stock_count'] ?? 0) > 0 ? 'text-danger' : 'text-info' }}">{{ $analytics['low_stock_count'] ?? 0 }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title {{ ($analytics['low_stock_count'] ?? 0) > 0 ? 'bg-danger' : 'bg-info' }} rounded-circle fs-3">
                                        <i class="bi bi-exclamation-triangle-fill text-white"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CHARTS & TOP PRODUCTS -->
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

            <!-- PRODUCT LIST -->
            <div id="productList" class="mt-4">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">
                                        Products <span class="badge bg-dark-subtle text-dark ms-1" id="totalProducts">{{ $products->total() }}</span>
                                        <small class="text-muted ms-2">Drag to reorder</small>
                                    </h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        <button class="btn btn-subtle-danger d-none" id="remove-actions" onclick="deleteMultiple()">Delete Selected</button>
                                        <button type="button" class="btn btn-warning d-none" id="bulkEditBtn" data-bs-toggle="modal" data-bs-target="#bulkEditModal">Bulk Edit</button>
                                        @can('Create product')
                                            <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#showModal" onclick="resetForm()">Add Product</button>
                                        @endcan
                                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">Import CSV</button>
                                        <a href="{{ route('products.export') }}" class="btn btn-info">Export CSV</a>
                                        <!-- Sync Stock Button -->
                                        <button type="button" class="btn btn-secondary" onclick="syncProductStocks()" title="Sync stock from all locations">
                                            <i class="bi bi-arrow-clockwise"></i> Sync Stock
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0">
                                        <thead class="table-active">
                                            <tr>
                                                <th></th>
                                                <th><div class="form-check"><input class="form-check-input" type="checkbox" id="checkAll"><label class="form-check-label" for="checkAll"></label></div></th>
                                                <th>Product</th>
                                                <th>Category</th>
                                                <th>Stock</th>
                                                <th>Price</th>
                                                <th>Sold</th>
                                                <th>Featured</th>
                                                <th>Published</th>
                                                <th>Inventory</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="list form-check-all" id="sortableTableBody">
                                            @forelse($products as $product)
                                                <tr data-product-id="{{ $product->id }}" class="draggable-row">
                                                    <td class="text-center cursor-move"><i class="bi bi-grip-vertical text-muted fs-4"></i></td>
                                                    <td><div class="form-check"><input class="form-check-input bulk-checkbox" type="checkbox" value="{{ $product->id }}"></div></td>
                                                    <td class="title">
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-sm bg-light rounded p-1 me-3">
                                                                @if($product->thumbnail)
                                                                    <img src="{{ asset('storage/' . $product->thumbnail) }}" alt="" class="img-fluid rounded" style="max-height:40px;">
                                                                @else
                                                                    <div class="bg-secondary-subtle rounded d-flex align-items-center justify-content-center" style="width:40px;height:40px;"><i class="bi bi-image text-muted fs-5"></i></div>
                                                                @endif
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-1"><a href="{{ route('products.show', $product->id) }}" class="text-reset">{{ Str::limit($product->title, 50) }}</a></h6>
                                                                <p class="mb-0 text-muted small">SKU: {{ $product->sku }}</p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="category">{{ $product->category?->name ?? 'Uncategorized' }}</td>
                                                    <td class="stock">
                                                        @php
                                                            // Use the product's total stock (from all locations)
                                                            $totalStock = $product->stock;
                                                        @endphp
                                                        @if($totalStock > 10)
                                                            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle" title="Total stock across all locations">
                                                                <i class="bi bi-box me-1"></i>{{ $totalStock }} in stock
                                                            </span>
                                                        @elseif($totalStock > 0)
                                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" title="Total stock across all locations">
                                                                <i class="bi bi-exclamation-triangle me-1"></i>{{ $totalStock }} low stock
                                                            </span>
                                                        @else
                                                            <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle" title="Total stock across all locations">
                                                                <i class="bi bi-x-circle me-1"></i>Out of stock
                                                            </span>
                                                        @endif
                                                        <!-- Show stock per location on hover -->
                                                        <div class="stock-locations-popover d-none">
                                                            <small class="d-block text-muted mt-1">
                                                                <i class="bi bi-info-circle me-1"></i>Stock by location:
                                                            </small>
                                                            @php
                                                                $locations = \App\Models\StockLocation::all();
                                                            @endphp
                                                            @foreach($locations as $location)
                                                                @php
                                                                    $locationStock = $location->getProductStock($product->id);
                                                                @endphp
                                                                @if($locationStock > 0)
                                                                    <small class="d-block">
                                                                        • {{ $location->name }}: <strong>{{ $locationStock }}</strong>
                                                                    </small>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                    <td class="price">
                                                        @if($product->sale_price && $product->sale_price < $product->price)
                                                            @php $discount = round((($product->price - $product->sale_price) / $product->price) * 100); @endphp
                                                            <div class="position-relative d-inline-block">
                                                                <del class="text-muted small">${{ number_format($product->price, 2) }}</del><br>
                                                                <span class="text-danger fw-bold">${{ number_format($product->sale_price, 2) }}</span>
                                                                <span class="badge bg-danger position-absolute" style="top:-8px;right:-32px;font-size:0.65rem;">-{{ $discount }}%</span>
                                                            </div>
                                                        @else
                                                            <span class="fw-bold">${{ number_format($product->price, 2) }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="sold text-center"><span class="fw-semibold">{{ $product->sold_quantity ?? 0 }}</span></td>
                                                    <td class="featured">
                                                        @if($product->is_featured)
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                                <i class="bi bi-star-fill text-warning me-1"></i> Featured
                                                            </span>
                                                        @else
                                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Regular</span>
                                                        @endif
                                                    </td>
                                                    <td class="created_at"><small class="text-muted">{{ $product->created_at->format('d M, Y') }}</small></td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-outline-info inventory-btn" data-id="{{ $product->id }}" data-title="{{ $product->title }}">
                                                            <i class="bi bi-box-arrow-up-right"></i> View Log
                                                        </button>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button class="btn btn-subtle-secondary btn-sm btn-icon" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                                            <ul class="dropdown-menu dropdown-menu-end">
                                                                <li><a class="dropdown-item" href="{{ route('products.show', $product->id) }}">View</a></li>
                                                                @can('Update product')
                                                                    <li><a class="dropdown-item edit-item-btn" href="javascript:void(0);" data-id="{{ $product->id }}">Edit</a></li>
                                                                @endcan
                                                                @can('Adjust stock')
                                                                    <li>
                                                                        <a class="dropdown-item adjust-stock-btn" href="javascript:void(0);" 
                                                                           data-id="{{ $product->id }}"
                                                                           data-title="{{ $product->title }}">
                                                                            <i class="bi bi-plus-slash-minus me-2"></i> Adjust Stock
                                                                        </a>
                                                                    </li>
                                                                @endcan
                                                                @can('Delete product')
                                                                    <li><a class="dropdown-item remove-item-btn text-danger" href="javascript:void(0);" data-id="{{ $product->id }}">Delete</a></li>
                                                                @endcan
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="11" class="text-center py-5 text-muted">No products found</td></tr>
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

            <!-- ADD/EDIT MODAL -->
            <!-- ... keep existing modal code ... -->

            <!-- IMPORT MODAL -->
            <!-- ... keep existing modal code ... -->

            <!-- BULK EDIT MODAL -->
            <!-- ... keep existing modal code ... -->

            <!-- INVENTORY LOG MODAL -->
            <!-- ... keep existing modal code ... -->

            <!-- ADJUST STOCK MODAL -->
            <div class="modal fade" id="adjustStockModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form id="adjustStockForm">
                            @csrf
                            <input type="hidden" name="product_id" id="adjustProductId">
                            <div class="modal-header">
                                <h5 class="modal-title" id="adjustStockModalTitle">Adjust Stock</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div id="adjustStockInfo" class="alert alert-info mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <div>
                                            <strong id="adjustProductTitle"></strong><br>
                                            <small>SKU: <span id="adjustProductSku"></span></small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Location <span class="text-danger">*</span></label>
                                    <select name="location_id" id="adjustLocation" class="form-control" required>
                                        <option value="">Select Location</option>
                                        @php
                                            $locations = \App\Models\StockLocation::orderBy('name')->get();
                                        @endphp
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}">
                                                {{ $location->name }}
                                                @if($location->is_default)
                                                    (Default)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Adjustment Type <span class="text-danger">*</span></label>
                                    <select name="adjustment_type" id="adjustmentType" class="form-control" required>
                                        <option value="add">Add Stock</option>
                                        <option value="remove">Remove Stock</option>
                                        <option value="set">Set Stock to Specific Quantity</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label" id="quantityLabel">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" name="quantity" id="adjustQuantity" class="form-control" required min="1" value="1">
                                    <small class="text-muted" id="currentLocationStock"></small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Unit Cost</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="0.01" name="unit_cost" id="adjustUnitCost" class="form-control" placeholder="Optional">
                                    </div>
                                    <small class="text-muted">Leave blank to use product price</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                                    <input type="text" name="reason" class="form-control" required placeholder="e.g., Restock, Damage, Physical Count, etc.">
                                </div>
                                
                                <div class="mb-0">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Additional information..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="adjustStockBtn">
                                    <span class="spinner-border spinner-border-sm d-none me-1" id="adjustStockSpinner"></span>
                                    Apply Adjustment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- COMPLETE JAVASCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/list.js@2.3.1/dist/list.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Set CSRF token for all axios requests
    axios.defaults.headers.common['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    // Initialize Sales Chart
    const salesChartCtx = document.getElementById('salesChart');
    if (salesChartCtx) {
        new Chart(salesChartCtx, {
            type: 'line',
            data: {
                labels: @json($analytics['sales_chart']['labels'] ?? []),
                datasets: [{
                    label: 'Daily Sales ($)',
                    data: @json($analytics['sales_chart']['data'] ?? []),
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13,110,253,0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { color: '#495057' }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#6c757d' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: {
                            color: '#6c757d',
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                }
            }
        });
    }

    // Initialize List.js for product filtering
    const productListOptions = {
        valueNames: ['title', 'category', 'stock', 'price', 'sold', 'created_at', 'featured'],
        page: 15,
        pagination: true
    };
    
    if (document.getElementById('productList')) {
        new List('productList', productListOptions);
    }

    // Initialize drag and drop for product reordering
    const tbody = document.getElementById('sortableTableBody');
    if (tbody) {
        new Sortable(tbody, {
            animation: 150,
            ghostClass: 'bg-light',
            handle: '.cursor-move',
            filter: '.ignore-drag',
            preventOnFilter: false,
            onEnd: function () {
                const items = Array.from(tbody.querySelectorAll('.draggable-row'));
                const newOrder = items.map((row, i) => ({
                    id: row.dataset.productId,
                    position: i + 1
                }));
                
                axios.post('/products/reorder', { order: newOrder })
                    .then(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Order Saved!',
                            text: 'Product order has been updated successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    })
                    .catch((error) => {
                        console.error('Reorder error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to save product order. Please try again.',
                            timer: 2000
                        });
                        location.reload();
                    });
            }
        });
    }

    // Bulk selection functionality
    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
        checkAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.bulk-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateBulkActions();
        });
    }

    function updateBulkActions() {
        const checkedBoxes = document.querySelectorAll('.bulk-checkbox:checked');
        const count = checkedBoxes.length;
        
        const removeActionsBtn = document.getElementById('remove-actions');
        const bulkEditBtn = document.getElementById('bulkEditBtn');
        const bulkCountSpan = document.getElementById('bulkCount');
        const bulkProductIdsInput = document.getElementById('bulk_product_ids');
        
        if (removeActionsBtn) removeActionsBtn.classList.toggle('d-none', count === 0);
        if (bulkEditBtn) bulkEditBtn.classList.toggle('d-none', count === 0);
        if (bulkCountSpan) bulkCountSpan.textContent = count;
        if (bulkProductIdsInput) {
            bulkProductIdsInput.value = Array.from(checkedBoxes).map(cb => cb.value).join(',');
        }
    }

    // Attach change event to all bulk checkboxes
    document.querySelectorAll('.bulk-checkbox').forEach(cb => {
        cb.addEventListener('change', updateBulkActions);
    });

    // Stock location popover hover effect
    document.querySelectorAll('.stock').forEach(stockCell => {
        stockCell.addEventListener('mouseenter', function() {
            const popover = this.querySelector('.stock-locations-popover');
            if (popover) {
                popover.classList.remove('d-none');
            }
        });
        
        stockCell.addEventListener('mouseleave', function() {
            const popover = this.querySelector('.stock-locations-popover');
            if (popover) {
                popover.classList.add('d-none');
            }
        });
    });

    // Event delegation for dynamic elements
    document.addEventListener('click', function(e) {
        // Adjust Stock button click
        if (e.target.closest('.adjust-stock-btn')) {
            const btn = e.target.closest('.adjust-stock-btn');
            const productId = btn.dataset.id;
            const productTitle = btn.dataset.title;
            
            showAdjustStockModal(productId, productTitle);
        }

        // Inventory button click
        if (e.target.closest('.inventory-btn')) {
            const id = e.target.closest('.inventory-btn').dataset.id;
            const title = e.target.closest('.inventory-btn').dataset.title;
            document.getElementById('inventoryTitle').textContent = title;
            
            axios.get(`/products/${id}/inventory`)
                .then(response => {
                    const tbody = document.getElementById('inventoryLogBody');
                    if (response.data && response.data.length > 0) {
                        tbody.innerHTML = response.data.map(log => `
                            <tr>
                                <td>${new Date(log.created_at).toLocaleString()}</td>
                                <td>
                                    <span class="badge bg-${log.type === 'in' ? 'success' : 'danger'}">
                                        ${log.type === 'in' ? 'Added' : 'Removed'}
                                    </span>
                                </td>
                                <td><strong>${log.quantity}</strong></td>
                                <td>${log.reference || '-'}</td>
                                <td>${log.previous_stock}</td>
                                <td><strong>${log.new_stock}</strong></td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No inventory records found</td></tr>';
                    }
                    
                    // Show the inventory modal
                    const inventoryModal = new bootstrap.Modal(document.getElementById('inventoryModal'));
                    inventoryModal.show();
                })
                .catch(error => {
                    console.error('Inventory load error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load inventory data. Please try again.'
                    });
                });
        }

        // Edit button click
        if (e.target.closest('.edit-item-btn')) {
            const btn = e.target.closest('.edit-item-btn');
            const id = btn.dataset.id;
            
            // Show loading state
            Swal.fire({
                title: 'Loading product data...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            axios.get(`/products/${id}/edit`)
                .then(response => {
                    Swal.close();
                    const product = response.data;

                    // Reset form first
                    resetForm();
                    
                    // Fill form with product data
                    document.getElementById('product_id').value = product.id;
                    document.getElementById('title').value = product.title || '';
                    document.getElementById('sku').value = product.sku || '';
                    document.getElementById('price').value = product.price || '';
                    document.getElementById('sale_price').value = product.sale_price || '';
                    document.getElementById('stock').value = product.stock || '';
                    document.getElementById('description').value = product.description || '';
                    document.getElementById('brand_id').value = product.brand_id || '';
                    document.getElementById('category_id').value = product.category_id || '';
                    document.getElementById('product_type').value = product.product_type || 'simple';
                    document.getElementById('is_featured').checked = !!product.is_featured;

                    // Handle thumbnail image
                    const thumbPreview = document.getElementById('thumbnail_preview');
                    const thumbPlaceholder = document.getElementById('thumbnail_placeholder');
                    if (product.thumbnail) {
                        const thumbnailUrl = product.thumbnail.includes('http') ? product.thumbnail : `/storage/${product.thumbnail}`;
                        thumbPreview.src = thumbnailUrl;
                        thumbPreview.style.display = 'block';
                        thumbPlaceholder.style.display = 'none';
                    } else {
                        thumbPreview.style.display = 'none';
                        thumbPlaceholder.style.display = 'block';
                    }

                    // Handle gallery images
                    const gallery = document.getElementById('imageGallery');
                    gallery.innerHTML = '';
                    if (product.gallery && product.gallery.length > 0) {
                        product.gallery.forEach((img, index) => {
                            const div = document.createElement('div');
                            div.className = 'col-6 col-md-4 position-relative';
                            const imgUrl = img.url || img;
                            div.innerHTML = `
                                <img src="${imgUrl}" class="img-fluid rounded" style="height:100px;object-fit:cover;">
                                <input type="hidden" name="existing_images[]" value="${imgUrl}">
                                <button type="button" class="btn-close position-absolute top-0 end-0 bg-white" onclick="this.parentElement.remove()"></button>
                            `;
                            gallery.appendChild(div);
                        });
                    }

                    // Handle sale price calculation
                    calculateFromSalePrice();

                    // Handle variations for variable products
                    const variationsSection = document.getElementById('variationsSection');
                    if (product.product_type === 'variable' && product.variations && product.variations.length > 0) {
                        variationsSection.style.display = 'block';
                        
                        // Clear existing attributes
                        const container = document.getElementById('attributesContainer');
                        container.innerHTML = '';
                        
                        // Add attributes if they exist
                        if (product.attributes && product.attributes.length > 0) {
                            product.attributes.forEach((attr, index) => {
                                const attrHtml = `
                                    <div class="row g-3 align-items-end attribute-row ${index > 0 ? 'mt-3' : ''}">
                                        <div class="col-md-5">
                                            <input type="text" class="form-control" placeholder="e.g. Color" 
                                                   name="attributes[${index}][name]" value="${attr.name}">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" class="form-control" placeholder="Red, Blue, Green" 
                                                   name="attributes[${index}][values]" value="${attr.values ? attr.values.join(', ') : ''}">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger btn-sm remove-attribute">Remove</button>
                                        </div>
                                    </div>
                                `;
                                container.insertAdjacentHTML('beforeend', attrHtml);
                            });
                            attrIndex = product.attributes.length;
                        } else {
                            // Add default attribute row
                            const defaultRow = `
                                <div class="row g-3 align-items-end attribute-row">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" placeholder="e.g. Color" name="attributes[0][name]">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" placeholder="Red, Blue, Green" name="attributes[0][values]">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-danger btn-sm remove-attribute">Remove</button>
                                    </div>
                                </div>
                            `;
                            container.innerHTML = defaultRow;
                            attrIndex = 1;
                        }

                        // Generate variations table
                        let variationsHtml = `
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Variant</th>
                                        <th>SKU</th>
                                        <th>Price</th>
                                        <th>Sale Price</th>
                                        <th>Stock</th>
                                        <th>Image</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>`;
                        
                        product.variations.forEach((variation, index) => {
                            const name = Object.entries(variation.attributes || {}).map(([key, value]) => `${key}: ${value}`).join(' | ') || 'Default';
                            const hiddenInputs = Object.entries(variation.attributes || {}).map(([key, value]) => 
                                `<input type="hidden" name="variations[${index}][attributes][${key}]" value="${value}">`
                            ).join('');
                            
                            variationsHtml += `
                                <tr>
                                    <td>
                                        <small>${name}</small>
                                        ${hiddenInputs}
                                        <input type="hidden" name="variations[${index}][id]" value="${variation.id || ''}">
                                    </td>
                                    <td>
                                        <input type="text" name="variations[${index}][sku]" value="${variation.sku || ''}" 
                                               class="form-control form-control-sm" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="variations[${index}][price]" value="${variation.price}" 
                                               class="form-control form-control-sm" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="variations[${index}][sale_price]" value="${variation.sale_price || ''}" 
                                               class="form-control form-control-sm">
                                    </td>
                                    <td>
                                        <input type="number" name="variations[${index}][stock]" value="${variation.stock}" 
                                               class="form-control form-control-sm" required>
                                    </td>
                                    <td>
                                        <input type="file" name="variations[${index}][image]" class="form-control form-control-sm variation-image" accept="image/*">
                                        ${variation.image ? `<img src="${variation.image}" class="variation-preview mt-2 img-fluid rounded" style="max-height:80px;">` : ''}
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm remove-variation">Remove</button>
                                    </td>
                                </tr>
                            `;
                        });
                        
                        variationsHtml += `</tbody></table>`;
                        document.getElementById('variationsTable').innerHTML = variationsHtml;
                    } else {
                        variationsSection.style.display = 'none';
                    }

                    // Update modal title and button
                    document.getElementById('modalTitle').textContent = 'Edit Product';
                    document.getElementById('submitBtn').innerHTML = '<span class="spinner-border spinner-border-sm d-none me-1" id="submitSpinner"></span> Update Product';
                    
                    // Show the modal
                    const modalElement = document.getElementById('showModal');
                    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                    modal.show();
                    
                })
                .catch(error => {
                    Swal.close();
                    console.error('Error loading product:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load product data. Please try again.'
                    });
                });
        }

        // Delete button click
        if (e.target.closest('.remove-item-btn')) {
            const id = e.target.closest('.remove-item-btn').dataset.id;
            Swal.fire({
                title: 'Are you sure?',
                text: "This product will be permanently deleted!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`/products/${id}`)
                        .then(() => {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'Product has been deleted successfully.',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        })
                        .catch(error => {
                            console.error('Delete error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to delete product. Please try again.'
                            });
                        });
                }
            });
        }
    });

    // Variables for product form
    let attrIndex = 1;
    let priceChanging = false;
    let discountChanging = false;
    let salePriceChanging = false;

    // Product type change handler
    const productTypeSelect = document.getElementById('product_type');
    if (productTypeSelect) {
        productTypeSelect.addEventListener('change', function() {
            const variationsSection = document.getElementById('variationsSection');
            variationsSection.style.display = this.value === 'variable' ? 'block' : 'none';
        });
    }

    // Add attribute button
    const addAttributeBtn = document.getElementById('addAttribute');
    if (addAttributeBtn) {
        addAttributeBtn.addEventListener('click', () => {
            const html = `
                <div class="row g-3 align-items-end attribute-row mt-3">
                    <div class="col-md-5">
                        <input type="text" class="form-control" placeholder="e.g. Size" name="attributes[${attrIndex}][name]">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" placeholder="S, M, L" name="attributes[${attrIndex}][values]">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm remove-attribute">Remove</button>
                    </div>
                </div>
            `;
            document.getElementById('attributesContainer').insertAdjacentHTML('beforeend', html);
            attrIndex++;
        });
    }

    // Price calculation handlers
    const priceInput = document.getElementById('price');
    const discountInput = document.getElementById('discount_percent');
    const salePriceInput = document.getElementById('sale_price');

    if (priceInput) {
        priceInput.addEventListener('input', () => {
            if (!priceChanging) {
                priceChanging = true;
                calculateFromPrice();
                priceChanging = false;
            }
        });
    }

    if (discountInput) {
        discountInput.addEventListener('input', () => {
            if (!discountChanging) {
                discountChanging = true;
                calculateFromDiscount();
                discountChanging = false;
            }
        });
    }

    if (salePriceInput) {
        salePriceInput.addEventListener('input', () => {
            if (!salePriceChanging) {
                salePriceChanging = true;
                calculateFromSalePrice();
                salePriceChanging = false;
            }
        });
    }

    function calculateFromPrice() {
        const price = parseFloat(priceInput.value) || 0;
        const discount = parseFloat(discountInput.value) || 0;
        if (discount > 0 && discount <= 100) {
            salePriceInput.value = (price * (1 - discount / 100)).toFixed(2);
            document.getElementById('sale_price_note').style.display = 'block';
        }
    }

    function calculateFromDiscount() {
        const price = parseFloat(priceInput.value) || 0;
        const discount = parseFloat(discountInput.value) || 0;
        if (price && discount) {
            salePriceInput.value = (price * (1 - discount / 100)).toFixed(2);
            document.getElementById('sale_price_note').style.display = 'block';
        }
    }

    function calculateFromSalePrice() {
        const price = parseFloat(priceInput.value) || 0;
        const sale = parseFloat(salePriceInput.value) || 0;
        if (price && sale && sale < price) {
            const discount = ((price - sale) / price) * 100;
            discountInput.value = discount.toFixed(2);
            document.getElementById('sale_price_note').style.display = 'block';
        }
    }

    // Apply bulk discount to variations
    const applyBulkDiscountBtn = document.getElementById('applyBulkDiscount');
    if (applyBulkDiscountBtn) {
        applyBulkDiscountBtn.addEventListener('click', () => {
            const bulkDiscount = parseFloat(document.getElementById('bulk_discount').value) || 0;
            if (bulkDiscount < 0 || bulkDiscount > 100) {
                Swal.fire('Warning', 'Please enter a valid discount percentage (0-100).', 'warning');
                return;
            }
            
            document.querySelectorAll('#variationsTable tbody tr').forEach(row => {
                const priceInput = row.querySelector('input[name*="price"]');
                const saleInput = row.querySelector('input[name*="sale_price"]');
                const price = parseFloat(priceInput.value) || 0;
                if (price > 0) {
                    saleInput.value = (price * (1 - bulkDiscount / 100)).toFixed(2);
                }
            });
            
            Swal.fire('Success', `Applied ${bulkDiscount}% discount to all variations.`, 'success');
        });
    }

    // Generate variations button
    const generateVariationsBtn = document.getElementById('generateVariations');
    if (generateVariationsBtn) {
        generateVariationsBtn.addEventListener('click', () => {
            const attrs = [];
            document.querySelectorAll('.attribute-row').forEach(row => {
                const name = row.querySelector('input[name$="[name]"]').value.trim();
                const values = row.querySelector('input[name$="[values]"]').value.split(',').map(v => v.trim()).filter(v => v);
                if (name && values.length) attrs.push({name, values});
            });
            
            if (!attrs.length) {
                document.getElementById('variationsTable').innerHTML = '<p class="text-muted">Please add attributes first</p>';
                return;
            }
            
            // Generate all combinations
            const combos = attrs.reduce((accumulator, currentAttr) => 
                accumulator.flatMap(combo => 
                    currentAttr.values.map(value => ({...combo, [currentAttr.name]: value}))
                ), 
                [{}]
            ).filter(combo => Object.keys(combo).length > 0);
            
            let html = `
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Variant</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Sale Price</th>
                            <th>Stock</th>
                            <th>Image</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>`;
            
            combos.forEach((combo, i) => {
                const name = Object.entries(combo).map(([key, value]) => `${key}: ${value}`).join(' | ');
                html += `
                    <tr>
                        <td>
                            <small>${name}</small>
                            ${Object.entries(combo).map(([key, value]) => 
                                `<input type="hidden" name="variations[${i}][attributes][${key}]" value="${value}">`
                            ).join('')}
                        </td>
                        <td>
                            <input type="text" name="variations[${i}][sku]" class="form-control form-control-sm" required>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="variations[${i}][price]" class="form-control form-control-sm" required>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="variations[${i}][sale_price]" class="form-control form-control-sm">
                        </td>
                        <td>
                            <input type="number" name="variations[${i}][stock]" class="form-control form-control-sm" required>
                        </td>
                        <td>
                            <input type="file" name="variations[${i}][image]" class="form-control form-control-sm variation-image" accept="image/*">
                            <img class="variation-preview mt-2 img-fluid rounded" style="max-height:80px;display:none;">
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm remove-variation">Remove</button>
                        </td>
                    </tr>
                `;
            });
            
            html += `</tbody></table>`;
            document.getElementById('variationsTable').innerHTML = html;
        });
    }

    // Thumbnail image preview
    const thumbnailInput = document.getElementById('thumbnail_input');
    if (thumbnailInput) {
        thumbnailInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('thumbnail_preview').src = event.target.result;
                    document.getElementById('thumbnail_preview').style.display = 'block';
                    document.getElementById('thumbnail_placeholder').style.display = 'none';
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    }

    // Gallery images preview
    const galleryInput = document.getElementById('gallery_input');
    if (galleryInput) {
        galleryInput.addEventListener('change', function(e) {
            const container = document.getElementById('imageGallery');
            container.innerHTML = '';
            
            Array.from(e.target.files).forEach((file, index) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const div = document.createElement('div');
                    div.className = 'col-6 col-md-4 position-relative';
                    div.innerHTML = `
                        <img src="${event.target.result}" class="img-fluid rounded" style="height:100px;object-fit:cover;">
                        <button type="button" class="btn-close position-absolute top-0 end-0 bg-white" onclick="this.parentElement.remove()"></button>
                    `;
                    container.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // Make gallery sortable
    const imageGallery = document.getElementById('imageGallery');
    if (imageGallery) {
        new Sortable(imageGallery, {
            animation: 150,
            ghostClass: 'bg-light'
        });
    }

    // Variation image preview
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('variation-image') && e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const previewImg = e.target.closest('td').querySelector('.variation-preview');
                if (previewImg) {
                    previewImg.src = event.target.result;
                    previewImg.style.display = 'block';
                }
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    });

    // Remove attribute and variation buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-attribute')) {
            e.target.closest('.attribute-row').remove();
        }
        
        if (e.target.classList.contains('remove-variation')) {
            e.target.closest('tr').remove();
        }
    });

    // Import form submission
    const importForm = document.getElementById('importForm');
    if (importForm) {
        importForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const progress = document.getElementById('importProgress');
            const bar = progress.querySelector('.progress-bar');
            
            progress.style.display = 'block';
            bar.style.width = '0%';
            bar.textContent = '0%';
            
            axios.post('{{ route("products.import") }}', formData, {
                headers: {'Content-Type': 'multipart/form-data'},
                onUploadProgress: function(progressEvent) {
                    const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    bar.style.width = percent + '%';
                    bar.textContent = percent + '%';
                }
            })
            .then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Import completed successfully.',
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            })
            .catch(error => {
                console.error('Import error:', error);
                let errorMessage = 'Import failed. Please try again.';
                if (error.response && error.response.data && error.response.data.message) {
                    errorMessage = error.response.data.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage
                });
                progress.style.display = 'none';
            });
        });
    }

    // Bulk edit form submission
    const bulkEditForm = document.getElementById('bulkEditForm');
    if (bulkEditForm) {
        bulkEditForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Applying changes...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            axios.post('{{ route("products.bulkUpdate") }}', new FormData(this))
                .then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Bulk update completed successfully.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                })
                .catch(error => {
                    console.error('Bulk update error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Bulk update failed. Please try again.'
                    });
                });
        });
    }

    // Product form submission
    const productForm = document.getElementById('productForm');
    if (productForm) {
        productForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const productId = document.getElementById('product_id').value;
            const url = productId ? `/products/${productId}` : '/products';
            const formData = new FormData(this);
            
            if (productId) {
                formData.append('_method', 'PUT');
            }
            
            const btn = document.getElementById('submitBtn');
            const spinner = document.getElementById('submitSpinner');
            btn.disabled = true;
            spinner.classList.remove('d-none');
            
            axios.post(url, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
            .then(response => {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.data.message || 'Product saved successfully.',
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            })
            .catch(error => {
                console.error('Save error:', error);
                let errorMessage = 'Error saving product. Please try again.';
                
                if (error.response && error.response.data) {
                    if (error.response.data.message) {
                        errorMessage = error.response.data.message;
                    } else if (error.response.data.errors) {
                        const errors = Object.values(error.response.data.errors).flat();
                        errorMessage = errors.join('<br>');
                    }
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    html: errorMessage
                });
            })
            .finally(() => {
                btn.disabled = false;
                spinner.classList.add('d-none');
            });
        });
    }

    // Adjust stock form submission
    const adjustStockForm = document.getElementById('adjustStockForm');
    if (adjustStockForm) {
        adjustStockForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('adjustStockBtn');
            const spinner = document.getElementById('adjustStockSpinner');
            btn.disabled = true;
            spinner.classList.remove('d-none');
            
            const formData = new FormData(this);
            
            axios.post('/inventory/adjust', formData)
                .then(response => {
                    if (response.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.data.message,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Close modal
                            const modalElement = document.getElementById('adjustStockModal');
                            if (modalElement) {
                                const modal = bootstrap.Modal.getInstance(modalElement);
                                if (modal) modal.hide();
                            }
                            
                            // Reload page to show updated stock levels
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
                    console.error('Adjust error:', error);
                    let errorMessage = 'Failed to adjust stock. Please try again.';
                    
                    if (error.response?.status === 422 && error.response.data.errors) {
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
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                });
        });
    }

    // Location change handler for adjust stock
    const adjustLocation = document.getElementById('adjustLocation');
    if (adjustLocation) {
        adjustLocation.addEventListener('change', function() {
            const productId = document.getElementById('adjustProductId').value;
            const locationId = this.value;
            
            if (productId && locationId) {
                loadLocationStock(productId, locationId);
            }
        });
    }

    // Adjustment type change handler
    const adjustmentType = document.getElementById('adjustmentType');
    if (adjustmentType) {
        adjustmentType.addEventListener('change', function() {
            updateQuantityLabel();
        });
    }
});

// Show adjust stock modal
function showAdjustStockModal(productId, productTitle) {
    // Reset form
    document.getElementById('adjustStockForm').reset();
    document.getElementById('adjustProductId').value = productId;
    document.getElementById('adjustProductTitle').textContent = productTitle;
    document.getElementById('adjustQuantity').value = 1;
    
    // Get product info
    axios.get(`/products/${productId}`)
        .then(response => {
            const product = response.data;
            document.getElementById('adjustProductSku').textContent = product.sku;
            document.getElementById('adjustUnitCost').placeholder = 'Default: $' + product.price;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('adjustStockModal'));
            modal.show();
            
            // Update modal title
            document.getElementById('adjustStockModalTitle').textContent = `Adjust Stock - ${productTitle}`;
            
            // Trigger location change to load initial stock
            const locationSelect = document.getElementById('adjustLocation');
            if (locationSelect.value) {
                loadLocationStock(productId, locationSelect.value);
            }
        })
        .catch(error => {
            console.error('Error loading product:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load product information'
            });
        });
}

// Load location stock
function loadLocationStock(productId, locationId) {
    axios.get(`/inventory/stock-level/${productId}/${locationId}`)
        .then(response => {
            if (response.data.success) {
                const locationStock = response.data.stock || 0;
                const totalStock = response.data.product.total_stock || 0;
                
                document.getElementById('currentLocationStock').innerHTML = 
                    `Current stock at this location: <strong>${locationStock}</strong> | Total stock: <strong>${totalStock}</strong>`;
                
                updateQuantityLabel();
            }
        })
        .catch(error => {
            console.error('Error loading location stock:', error);
            document.getElementById('currentLocationStock').innerHTML = 
                '<span class="text-danger">Unable to load stock information</span>';
        });
}

// Update quantity label based on adjustment type
function updateQuantityLabel() {
    const type = document.getElementById('adjustmentType').value;
    const label = document.getElementById('quantityLabel');
    const input = document.getElementById('adjustQuantity');
    
    switch(type) {
        case 'add':
            label.textContent = 'Quantity to Add *';
            input.min = 1;
            break;
        case 'remove':
            label.textContent = 'Quantity to Remove *';
            input.min = 1;
            break;
        case 'set':
            label.textContent = 'Set Stock To *';
            input.min = 0;
            break;
    }
}

// Sync product stocks
function syncProductStocks() {
    Swal.fire({
        title: 'Syncing stocks...',
        text: 'Updating product stock from all locations',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    axios.post('/inventory/sync-all-stocks')
        .then(response => {
            if (response.data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.data.message,
                    confirmButtonText: 'OK'
                }).then(() => {
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
            console.error('Sync error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Failed to sync stocks. Please try again.'
            });
        });
}

// Reset form function
window.resetForm = function() {
    // Reset form inputs
    const productForm = document.getElementById('productForm');
    if (productForm) {
        productForm.reset();
    }
    
    // Clear hidden fields
    document.getElementById('product_id').value = '';
    
    // Reset modal title and button
    document.getElementById('modalTitle').textContent = 'Add Product';
    document.getElementById('submitBtn').innerHTML = '<span class="spinner-border spinner-border-sm d-none me-1" id="submitSpinner"></span> Save Product';
    
    // Hide variations section
    document.getElementById('variationsSection').style.display = 'none';
    
    // Reset attributes container
    const container = document.getElementById('attributesContainer');
    if (container) {
        container.innerHTML = `
            <div class="row g-3 align-items-end attribute-row">
                <div class="col-md-5">
                    <input type="text" class="form-control" placeholder="e.g. Color" name="attributes[0][name]">
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control" placeholder="Red, Blue, Green" name="attributes[0][values]">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm remove-attribute">Remove</button>
                </div>
            </div>`;
    }
    
    // Clear variations table
    document.getElementById('variationsTable').innerHTML = '';
    
    // Clear image gallery
    document.getElementById('imageGallery').innerHTML = '';
    
    // Reset thumbnail preview
    const thumbPreview = document.getElementById('thumbnail_preview');
    const thumbPlaceholder = document.getElementById('thumbnail_placeholder');
    if (thumbPreview && thumbPlaceholder) {
        thumbPreview.style.display = 'none';
        thumbPreview.src = '';
        thumbPlaceholder.style.display = 'block';
    }
    
    // Reset sale price note
    const salePriceNote = document.getElementById('sale_price_note');
    if (salePriceNote) {
        salePriceNote.style.display = 'none';
    }
    
    // Close modal if open
    const modalElement = document.getElementById('showModal');
    if (modalElement) {
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }
    }
};

// Real-time stock updates (optional)
setInterval(() => {
    axios.get('/products/realtime-stock')
        .then(response => {
            if (response.data && Array.isArray(response.data)) {
                response.data.forEach(item => {
                    const row = document.querySelector(`tr[data-product-id="${item.id}"]`);
                    if (row) {
                        const cell = row.querySelector('.stock .badge');
                        if (cell) {
                            const oldStock = parseInt(cell.textContent.match(/\d+/)?.[0] || 0);
                            if (oldStock !== item.stock) {
                                let badgeClass, text, icon;
                                if (item.stock > 10) {
                                    badgeClass = 'bg-success-subtle text-success-emphasis border border-success-subtle';
                                    text = `${item.stock} in stock`;
                                    icon = 'bi-box';
                                } else if (item.stock > 0) {
                                    badgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                    text = `${item.stock} low stock`;
                                    icon = 'bi-exclamation-triangle';
                                } else {
                                    badgeClass = 'bg-danger-subtle text-danger-emphasis border border-danger-subtle';
                                    text = 'Out of stock';
                                    icon = 'bi-x-circle';
                                }
                                
                                cell.className = `badge ${badgeClass}`;
                                cell.innerHTML = `<i class="bi ${icon} me-1"></i>${text}`;
                                
                                // Highlight change
                                cell.style.transition = 'background-color 0.3s';
                                cell.style.backgroundColor = item.stock > oldStock ? '#d4edda' : '#f8d7da';
                                setTimeout(() => {
                                    cell.style.backgroundColor = '';
                                }, 1000);
                            }
                        }
                    }
                });
            }
        })
        .catch(error => {
            console.error('Real-time stock update error:', error);
        });
}, 30000); // Update every 30 seconds
</script>
@endsection