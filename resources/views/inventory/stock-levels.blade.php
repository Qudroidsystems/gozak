@extends('layouts.master')

@section('title', 'Stock Levels')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- PAGE TITLE -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle ?? 'Stock Levels' }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
                                <li class="breadcrumb-item active">Stock Levels</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" id="stockLevelsForm">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Stock Status</label>
                                <select name="stock_status" class="form-control" onchange="this.form.submit()">
                                    <option value="">All Status</option>
                                    <option value="in_stock" {{ request('stock_status') == 'in_stock' ? 'selected' : '' }}>In Stock (>10)</option>
                                    <option value="low_stock" {{ request('stock_status') == 'low_stock' ? 'selected' : '' }}>Low Stock (1-10)</option>
                                    <option value="out_of_stock" {{ request('stock_status') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                                    <option value="negative_stock" {{ request('stock_status') == 'negative_stock' ? 'selected' : '' }}>Negative Stock</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-control" onchange="this.form.submit()">
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
                                <select name="brand_id" class="form-control" onchange="this.form.submit()">
                                    <option value="">All Brands</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search products...">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i>
                                    </button>
                                    <a href="{{ route('inventory.stock-levels') }}" class="btn btn-secondary">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="form-label">Sort By</label>
                                <div class="input-group">
                                    <select name="sort_by" class="form-control" onchange="this.form.submit()">
                                        <option value="total_stock" {{ request('sort_by') == 'total_stock' ? 'selected' : '' }}>Stock Quantity</option>
                                        <option value="title" {{ request('sort_by') == 'title' ? 'selected' : '' }}>Product Name</option>
                                        <option value="sku" {{ request('sort_by') == 'sku' ? 'selected' : '' }}>SKU</option>
                                        <option value="price" {{ request('sort_by') == 'price' ? 'selected' : '' }}>Price</option>
                                    </select>
                                    <select name="sort_order" class="form-control" onchange="this.form.submit()">
                                        <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>Ascending</option>
                                        <option value="desc" {{ request('sort_order') == 'desc' ? 'selected' : '' }}>Descending</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- STOCK LEVELS TABLE -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Stock Levels by Location</h5>
                    <div>
                        <a href="{{ route('inventory.export.stock-levels') }}?{{ http_build_query(request()->query()) }}" class="btn btn-success">
                            <i class="bi bi-download me-1"></i> Export CSV
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered align-middle table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Category</th>
                                    <th>Brand</th>
                                    <th>Price</th>
                                    @foreach($locations as $location)
                                        <th class="text-center">{{ $location->name }}</th>
                                    @endforeach
                                    <th>Total Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    @php
                                        $totalStock = 0;
                                        foreach($locations as $location) {
                                            // FIXED: Use the locationStockData array instead of trying to access a non-existent property
                                            $stock = $locationStockData[$product->id][$location->id] ?? 0;
                                            $totalStock += $stock;
                                        }
                                        
                                        if ($totalStock > 10) {
                                            $statusClass = 'success';
                                            $statusText = 'In Stock';
                                        } elseif ($totalStock > 0) {
                                            $statusClass = 'warning';
                                            $statusText = 'Low Stock';
                                        } elseif ($totalStock == 0) {
                                            $statusClass = 'secondary';
                                            $statusText = 'Out of Stock';
                                        } else {
                                            $statusClass = 'danger';
                                            $statusText = 'Negative';
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($product->thumbnail)
                                                    <img src="{{ asset('storage/' . $product->thumbnail) }}" class="rounded me-2" width="40" height="40" alt="{{ $product->title }}">
                                                @endif
                                                <div>
                                                    <div class="fw-semibold">{{ $product->title }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $product->sku }}</td>
                                        <td>{{ $product->category?->name ?? '-' }}</td>
                                        <td>{{ $product->brand?->name ?? '-' }}</td>
                                        <td>${{ number_format($product->price, 2) }}</td>
                                        
                                        @foreach($locations as $location)
                                            @php
                                                // FIXED: Use the locationStockData array
                                                $stock = $locationStockData[$product->id][$location->id] ?? 0;
                                                $stockClass = $stock > 10 ? 'success' : ($stock > 0 ? 'warning' : ($stock == 0 ? 'secondary' : 'danger'));
                                            @endphp
                                            <td class="text-center">
                                                <span class="badge bg-{{ $stockClass }}-subtle text-{{ $stockClass }} border border-{{ $stockClass }}-subtle">
                                                    {{ $stock }}
                                                </span>
                                            </td>
                                        @endforeach
                                        
                                        <td>
                                            <span class="fw-bold {{ $totalStock > 10 ? 'text-success' : ($totalStock > 0 ? 'text-warning' : ($totalStock == 0 ? 'text-secondary' : 'text-danger')) }}">
                                                {{ $totalStock }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }} border border-{{ $statusClass }}-subtle">
                                                {{ $statusText }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-subtle-secondary btn-sm btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('products.show', $product->id) }}">
                                                            <i class="bi bi-eye me-2"></i> View Product
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#" onclick="showStockHistory({{ $product->id }})">
                                                            <i class="bi bi-clock-history me-2"></i> View History
                                                        </a>
                                                    </li>
                                                    @can('Manage inventory')
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="quickAdjust({{ $product->id }}, '{{ $product->title }}')">
                                                                <i class="bi bi-plus-slash-minus me-2"></i> Adjust Stock
                                                            </a>
                                                        </li>
                                                    @endcan
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 8 + count($locations) }}" class="text-center py-5 text-muted">
                                            <i class="bi bi-box-seam fs-1"></i>
                                            <p class="mt-2">No products found</p>
                                        </td>
                                    </tr>
                                @endforelse
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
                </div>
            </div>

        </div>
    </div>
</div>

<!-- STOCK HISTORY MODAL -->
<div class="modal fade" id="stockHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Stock History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="stockHistoryContent">
                    <!-- Content loaded via JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- QUICK ADJUST MODAL -->
<div class="modal fade" id="quickAdjustModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="quickAdjustForm">
                @csrf
                <input type="hidden" name="product_id" id="quickAdjustProductId">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickAdjustModalTitle">Quick Stock Adjustment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="currentStockInfo" class="alert alert-info mb-3">
                        <!-- Current stock info will be loaded here -->
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location <span class="text-danger">*</span></label>
                        <select name="location_id" id="quickAdjustLocation" class="form-control" required>
                            <option value="">Select Location</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ $location->is_default ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adjustment Type <span class="text-danger">*</span></label>
                        <select name="adjustment_type" id="adjustmentType" class="form-control" required>
                            <option value="add">Add Stock</option>
                            <option value="remove">Remove Stock</option>
                            <option value="set">Set Stock to</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="quantityLabel">Quantity to Add <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="adjustmentQuantity" class="form-control" required min="1" value="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control" required placeholder="e.g., Restock, Damage, etc.">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Additional information..."></textarea>
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


<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function showStockHistory(productId) {
    axios.get(`/inventory/history/${productId}`)
        .then(response => {
            const product = response.data.product;
            const history = response.data.history;
            
            let html = `
                <h5>${product.title} (${product.sku})</h5>
                <p class="text-muted">Stock History</p>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Quantity</th>
                                <th>Reference</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            if (history.data && history.data.length > 0) {
                history.data.forEach(transaction => {
                    const typeColors = {
                        'in': 'success',
                        'out': 'danger',
                        'adjustment': 'warning',
                        'transfer': 'info',
                        'return': 'primary',
                        'damage': 'dark'
                    };
                    
                    html += `
                        <tr>
                            <td>${new Date(transaction.created_at).toLocaleString()}</td>
                            <td>
                                <span class="badge bg-${typeColors[transaction.type] || 'secondary'}">
                                    ${transaction.type}
                                </span>
                            </td>
                            <td>${transaction.stock_location?.name || '-'}</td>
                            <td class="${transaction.type === 'in' || transaction.type === 'adjustment' ? 'text-success' : 'text-danger'}">
                                ${transaction.type === 'in' || transaction.type === 'adjustment' ? '+' : '-'}${transaction.quantity}
                            </td>
                            <td>${transaction.reference_number}</td>
                            <td>${transaction.user?.name || 'System'}</td>
                        </tr>
                    `;
                });
            } else {
                html += `
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No transactions found</td>
                    </tr>
                `;
            }
            
            html += `
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    ${history.links ? history.links : ''}
                </div>
            `;
            
            document.getElementById('stockHistoryContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('stockHistoryModal')).show();
        })
        .catch(error => {
            console.error('Error loading stock history:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load stock history'
            });
        });
}

function quickAdjust(productId, productTitle = '') {
    document.getElementById('quickAdjustProductId').value = productId;
    document.getElementById('quickAdjustModalTitle').textContent = 'Quick Stock Adjustment - ' + productTitle;
    
    // Reset form
    document.getElementById('quickAdjustForm').reset();
    document.getElementById('adjustmentQuantity').value = 1;
    document.getElementById('adjustmentType').value = 'add';
    updateQuantityLabel();
    
    // Clear current stock info
    document.getElementById('currentStockInfo').innerHTML = '<i class="bi bi-info-circle me-1"></i> Select a location to see current stock';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('quickAdjustModal'));
    modal.show();
    
    // Add event listener for location change
    const locationSelect = document.getElementById('quickAdjustLocation');
    locationSelect.addEventListener('change', function() {
        if (this.value) {
            loadCurrentStock(productId, this.value);
        } else {
            document.getElementById('currentStockInfo').innerHTML = '<i class="bi bi-info-circle me-1"></i> Select a location to see current stock';
        }
    });
    
    // Add event listener for adjustment type change
    const adjustmentTypeSelect = document.getElementById('adjustmentType');
    adjustmentTypeSelect.addEventListener('change', updateQuantityLabel);
    
    // Trigger initial load if default location is selected
    if (locationSelect.value) {
        loadCurrentStock(productId, locationSelect.value);
    }
}

function loadCurrentStock(productId, locationId) {
    axios.get(`/inventory/stock-level/${productId}/${locationId}`)
        .then(response => {
            if (response.data.success) {
                const stock = response.data.stock || 0;
                document.getElementById('currentStockInfo').innerHTML = 
                    `<i class="bi bi-info-circle me-1"></i> Current stock at this location: <strong>${stock}</strong>`;
            }
        })
        .catch(error => {
            console.error('Error loading current stock:', error);
            document.getElementById('currentStockInfo').innerHTML = 
                '<i class="bi bi-exclamation-triangle me-1"></i> Unable to load current stock';
        });
}

function updateQuantityLabel() {
    const type = document.getElementById('adjustmentType').value;
    const label = document.getElementById('quantityLabel');
    
    switch(type) {
        case 'add':
            label.textContent = 'Quantity to Add *';
            break;
        case 'remove':
            label.textContent = 'Quantity to Remove *';
            break;
        case 'set':
            label.textContent = 'Set Stock To *';
            break;
    }
}

document.getElementById('quickAdjustForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('quickAdjustBtn');
    const spinner = document.getElementById('quickAdjustSpinner');
    
    if (btn) btn.disabled = true;
    if (spinner) spinner.classList.remove('d-none');
    
    const formData = new FormData(this);
    
    axios.post('{{ route("inventory.adjust") }}', formData)
        .then(response => {
            console.log('Adjust response:', response.data);
            if (response.data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.data.message,
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Close modal
                    const modalElement = document.getElementById('quickAdjustModal');
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
            console.error('Error response:', error.response);
            
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
            if (btn) btn.disabled = false;
            if (spinner) spinner.classList.add('d-none');
        });
});

// Auto-submit form when filter select changes
document.querySelectorAll('select[onchange="this.form.submit()"]').forEach(select => {
    select.addEventListener('change', function() {
        this.form.submit();
    });
});
</script>
@endsection