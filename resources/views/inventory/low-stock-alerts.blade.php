@extends('layouts.master')

@section('title', 'Low Stock Alerts')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- PAGE TITLE -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle ?? 'Low Stock Alerts' }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
                                <li class="breadcrumb-item active">Low Stock Alerts</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SUMMARY CARD -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-danger-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-danger mb-0">Low Stock Items</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ $products->total() }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-danger rounded-circle fs-3">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-warning-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-warning mb-0">Critical Items</p>
                                    <h4 class="fs-22 fw-semibold mb-0">
                                        {{ $products->where('current_stock', '<=', 3)->count() }}
                                    </h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-warning rounded-circle fs-3">
                                        <i class="bi bi-exclamation-octagon"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-info-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-info mb-0">Locations</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ $locations->count() }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-info rounded-circle fs-3">
                                        <i class="bi bi-geo-alt"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-success-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-success mb-0">Action Required</p>
                                    <h4 class="fs-22 fw-semibold mb-0">
                                        {{ $products->where('current_stock', '<=', 5)->count() }}
                                    </h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-success rounded-circle fs-3">
                                        <i class="bi bi-lightning-charge"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" id="filterForm">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-3">
                                <label class="form-label">Category</label>
                                <select class="form-control" name="category_id">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Brand</label>
                                <select class="form-control" name="brand_id">
                                    <option value="">All Brands</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Stock Threshold</label>
                                <select class="form-control" name="threshold">
                                    <option value="10" {{ request('threshold', 10) == 10 ? 'selected' : '' }}>≤ 10 units</option>
                                    <option value="5" {{ request('threshold') == 5 ? 'selected' : '' }}>≤ 5 units</option>
                                    <option value="3" {{ request('threshold') == 3 ? 'selected' : '' }}>≤ 3 units (Critical)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Product name, SKU...">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12 d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-funnel me-1"></i> Apply Filters
                                </button>
                                <div>
                                    <a href="{{ route('inventory.index') }}" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left me-1"></i> Back to Inventory
                                    </a>
                                    <a href="{{ route('inventory.stock-levels') }}" class="btn btn-info ms-2">
                                        <i class="bi bi-box-seam me-1"></i> View All Stock
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- LOW STOCK PRODUCTS -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Low Stock Products ({{ $products->total() }})</h5>
                    <div>
                        <span class="badge bg-danger">Threshold: ≤ 10 units</span>
                    </div>
                </div>
                <div class="card-body">
                    @if($products->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-centered align-middle table-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Brand</th>
                                        <th>Current Stock</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Last Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $product)
                                        @php
                                            $stock = $product->current_stock ?? 0;
                                            if($stock <= 3) {
                                                $statusClass = 'danger';
                                                $statusText = 'Critical';
                                                $stockClass = 'text-danger fw-bold';
                                            } elseif($stock <= 5) {
                                                $statusClass = 'warning';
                                                $statusText = 'Low';
                                                $stockClass = 'text-warning fw-bold';
                                            } else {
                                                $statusClass = 'info';
                                                $statusText = 'Warning';
                                                $stockClass = 'text-info fw-semibold';
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ $loop->iteration + (($products->currentPage() - 1) * $products->perPage()) }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($product->thumbnail)
                                                        <img src="{{ asset('storage/' . $product->thumbnail) }}" 
                                                             class="rounded me-2" width="40" height="40" 
                                                             alt="{{ $product->title }}">
                                                    @else
                                                        <div class="rounded bg-light d-flex align-items-center justify-content-center me-2" 
                                                             style="width: 40px; height: 40px;">
                                                            <i class="bi bi-box text-muted"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <div class="fw-semibold">{{ $product->title }}</div>
                                                        <small class="text-muted">{{ $product->sku }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $product->category->name ?? 'N/A' }}</td>
                                            <td>{{ $product->brand->name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="{{ $stockClass }}">
                                                    {{ $stock }}
                                                </span>
                                            </td>
                                            <td>${{ number_format($product->price, 2) }}</td>
                                            <td>
                                                <span class="badge bg-{{ $statusClass }}">
                                                    {{ $statusText }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($product->updated_at)
                                                    {{ $product->updated_at->format('M d, Y') }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('inventory.history', $product->id) }}" 
                                                       class="btn btn-sm btn-info" 
                                                       title="View Stock History">
                                                        <i class="bi bi-clock-history"></i>
                                                    </a>
                                                    <a href="{{ route('products.edit', $product->id) }}" 
                                                       class="btn btn-sm btn-warning" 
                                                       title="Edit Product">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    @can('Adjust stock')
                                                    <button type="button" 
                                                            class="btn btn-sm btn-success adjust-stock-btn"
                                                            data-product-id="{{ $product->id }}"
                                                            data-product-name="{{ $product->title }}"
                                                            title="Adjust Stock">
                                                        <i class="bi bi-plus-slash-minus"></i>
                                                    </button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="row mt-3 align-items-center">
                            <div class="col-sm">
                                <div class="text-muted text-center text-sm-start">
                                    Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} Products
                                </div>
                            </div>
                            <div class="col-sm-auto mt-3 mt-sm-0">
                                {!! $products->appends(request()->query())->links('pagination::bootstrap-5') !!}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-check-circle fs-1 text-success"></i>
                            <p class="mt-2">No low stock products found</p>
                            <p class="text-muted">All products are sufficiently stocked</p>
                            <a href="{{ route('inventory.stock-levels') }}" class="btn btn-primary mt-2">
                                <i class="bi bi-box-seam me-1"></i> View All Stock Levels
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- STOCK DISTRIBUTION BY LOCATION (Optional Section) -->
            @if($products->count() > 0 && $locations->count() > 0)
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Stock Distribution by Location</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered align-middle table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    @foreach($locations as $location)
                                        <th class="text-center">{{ $location->name }}</th>
                                    @endforeach
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products->take(10) as $product)
                                    @php
                                        $totalStock = 0;
                                    @endphp
                                    <tr>
                                        <td>{{ $product->title }}</td>
                                        <td>{{ $product->sku }}</td>
                                        @foreach($locations as $location)
                                            @php
                                                $locationStock = $locationStockData[$product->id][$location->id] ?? 0;
                                                $totalStock += $locationStock;
                                            @endphp
                                            <td class="text-center">
                                                @if($locationStock > 0)
                                                    <span class="badge bg-info">{{ $locationStock }}</span>
                                                @else
                                                    <span class="text-muted">0</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="text-center fw-bold">
                                            <span class="badge bg-primary">{{ $totalStock }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($products->count() > 10)
                        <div class="text-center mt-3">
                            <small class="text-muted">Showing top 10 products. 
                                <a href="{{ route('inventory.stock-levels') }}">View all products with location breakdown</a>
                            </small>
                        </div>
                    @endif
                </div>
            </div>
            @endif

        </div>
    </div>
</div>

<!-- QUICK ADJUST STOCK MODAL -->
<div class="modal fade" id="quickAdjustModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="quickAdjustForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Quick Stock Adjustment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <input type="text" class="form-control" id="adjustProductName" readonly>
                        <input type="hidden" name="product_id" id="adjustProductId">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adjustment Type</label>
                        <select name="adjustment_type" class="form-control" required>
                            <option value="add">Add Stock</option>
                            <option value="remove">Remove Stock</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" class="form-control" required min="1" value="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <select name="location_id" class="form-control" required>
                            <option value="">Select Location</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ $location->is_default ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Reason</label>
                        <input type="text" name="reason" class="form-control" required 
                               placeholder="e.g., Restock, Found extra stock, etc.">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="quickAdjustBtn">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="quickAdjustSpinner"></span>
                        Apply Adjustment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    
    // Quick adjust stock button
    document.addEventListener('click', function(e) {
        const adjustBtn = e.target.closest('.adjust-stock-btn');
        if (adjustBtn) {
            e.preventDefault();
            
            const productId = adjustBtn.dataset.productId;
            const productName = adjustBtn.dataset.productName;
            
            document.getElementById('adjustProductId').value = productId;
            document.getElementById('adjustProductName').value = productName;
            
            new bootstrap.Modal(document.getElementById('quickAdjustModal')).show();
        }
    });
    
    // Quick adjust form submission
    document.getElementById('quickAdjustForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('quickAdjustBtn');
        const spinner = document.getElementById('quickAdjustSpinner');
        if (btn) btn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');
        
        const formData = new FormData(this);
        
        axios.post('{{ route("inventory.adjust") }}', formData)
            .then(response => {
                if (response.data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.data.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('quickAdjustModal'));
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
                let errorMessage = 'Failed to adjust stock';
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
});
</script>
@endsection