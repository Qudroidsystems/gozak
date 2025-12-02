{{-- resources/views/products/index.blade.php --}}
@extends('layouts.master')

@section('title', 'Products Management')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Page Title -->
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

            <div id="productList">
                <!-- Filters -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="filterForm" method="GET" action="{{ route('products.index') }}">
                                    <div class="row g-3">
                                        <div class="col-xxl-3">
                                            <div class="search-box">
                                                <input type="text" class="form-control search" 
                                                       name="search" 
                                                       placeholder="Search products, SKU, price..."
                                                       value="{{ request('search') }}"
                                                       autocomplete="off">
                                                <i class="ri-search-line search-icon"></i>
                                            </div>
                                        </div>
                                        <div class="col-xxl-2 col-sm-6">
                                            <select class="form-control" name="brands[]" id="brandFilter" 
                                                    data-choices data-choices-search-false multiple>
                                                <option value="">All Brands</option>
                                                @foreach($brands as $brand)
                                                    <option value="{{ $brand->id }}" 
                                                            {{ in_array($brand->id, (array)request('brands', [])) ? 'selected' : '' }}>
                                                        {{ $brand->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-xxl-2 col-sm-6">
                                            <select class="form-control" name="category" id="categoryFilter" 
                                                    data-choices data-choices-search-false>
                                                <option value="">All Categories</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" 
                                                            {{ request('category') == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                    @if($category->children->count())
                                                        @foreach($category->children as $child)
                                                            <option value="{{ $child->id }}" 
                                                                    {{ request('category') == $child->id ? 'selected' : '' }}>
                                                                — {{ $child->name }}
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-xxl-2 col-sm-4">
                                            <select class="form-control" name="stock" id="stockFilter">
                                                <option value="">All Stock</option>
                                                <option value="in_stock" {{ request('stock') == 'in_stock' ? 'selected' : '' }}>
                                                    In Stock
                                                </option>
                                                <option value="low_stock" {{ request('stock') == 'low_stock' ? 'selected' : '' }}>
                                                    Low Stock (&lt;10)
                                                </option>
                                                <option value="out_of_stock" {{ request('stock') == 'out_of_stock' ? 'selected' : '' }}>
                                                    Out of Stock
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-xxl-2 col-sm-4">
                                            <select class="form-control" name="featured" id="featuredFilter">
                                                <option value="">All Products</option>
                                                <option value="yes" {{ request('featured') == 'yes' ? 'selected' : '' }}>
                                                    Featured Only
                                                </option>
                                                <option value="no" {{ request('featured') == 'no' ? 'selected' : '' }}>
                                                    Non-Featured
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-xxl-1 col-sm-4">
                                            <button type="submit" class="btn btn-secondary w-100">
                                                <i class="bi bi-funnel align-baseline me-1"></i> Filter
                                            </button>
                                        </div>
                                        @if(request()->hasAny(['search', 'brands', 'category', 'stock', 'featured']))
                                        <div class="col-xxl-1 col-sm-4">
                                            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary w-100">
                                                <i class="bi bi-x-circle align-baseline me-1"></i> Clear
                                            </a>
                                        </div>
                                        @endif
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product List -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">
                                        Products <span class="badge bg-dark-subtle text-dark ms-1">{{ $products->total() }}</span>
                                    </h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        <button class="btn btn-subtle-danger d-none" id="remove-actions" onclick="deleteMultiple()">
                                            <i class="ri-delete-bin-2-line"></i>
                                        </button>
                                        @can('Create product')
                                            <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#showModal" onclick="resetForm()">
                                                <i class="bi bi-plus-circle align-baseline me-1"></i> Add Product
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0">
                                        <thead class="table-active">
                                            <tr>
                                                <th>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkAll">
                                                        <label class="form-check-label" for="checkAll"></label>
                                                    </div>
                                                </th>
                                                <th class="sort cursor-pointer" data-sort="title">Product</th>
                                                <th class="sort cursor-pointer" data-sort="category">Category</th>
                                                <th class="sort cursor-pointer" data-sort="stock">Stock</th>
                                                <th class="sort cursor-pointer" data-sort="price">Price</th>
                                                <th class="sort cursor-pointer" data-sort="sold">Sold</th>
                                                <th class="sort cursor-pointer" data-sort="featured">Featured</th>
                                                <th class="sort cursor-pointer" data-sort="created_at">Published</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="list form-check-all">
                                            @forelse($products as $product)
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="chk_child" value="{{ $product->id }}">
                                                        </div>
                                                    </td>
                                                    <td class="title">
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-sm bg-light rounded p-1 me-3">
                                                                @if($product->thumbnail)
                                                                    <img src="{{ asset('storage/' . $product->thumbnail) }}" alt="" class="img-fluid rounded" style="max-height: 40px; width: auto;">
                                                                @else
                                                                    <div class="bg-secondary-subtle rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                        <i class="bi bi-image text-muted fs-5"></i>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-1">
                                                                    <a href="{{ route('products.show', $product->id) }}" class="text-reset">
                                                                        {{ Str::limit($product->title, 50) }}
                                                                    </a>
                                                                </h6>
                                                                <p class="mb-0 text-muted small">SKU: {{ $product->sku }}</p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="category">{{ $product->category?->name ?? 'Uncategorized' }}</td>
                                                    <td class="stock">
                                                        @if($product->stock > 10)
                                                            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">
                                                                <i class="bi bi-check-circle me-1"></i> {{ $product->stock }} in stock
                                                            </span>
                                                        @elseif($product->stock > 0)
                                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                                                <i class="bi bi-exclamation-triangle me-1"></i> {{ $product->stock }} low stock
                                                            </span>
                                                        @else
                                                            <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle">
                                                                <i class="bi bi-x-circle me-1"></i> Out of stock
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="price">
                                                        @if($product->sale_price)
                                                            <del class="text-muted small">${{ number_format($product->price, 2) }}</del>
                                                            <br>
                                                            <span class="text-danger fw-bold">${{ number_format($product->sale_price, 2) }}</span>
                                                        @else
                                                            <span class="fw-bold">${{ number_format($product->price, 2) }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="sold text-center">
                                                        <span class="fw-semibold">{{ $product->sold_quantity ?? 0 }}</span>
                                                    </td>
                                                    <td class="featured">
                                                        @if($product->is_featured)
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                                <i class="bi bi-star-fill me-1"></i> Featured
                                                            </span>
                                                        @else
                                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                                                <i class="bi bi-star me-1"></i> Regular
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="created_at">
                                                        <small class="text-muted">{{ $product->created_at->format('d M, Y') }}</small>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button class="btn btn-subtle-secondary btn-sm btn-icon" data-bs-toggle="dropdown">
                                                                <i class="bi bi-three-dots-vertical"></i>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end">
                                                                <li><a class="dropdown-item" href="{{ route('products.show', $product->id) }}"><i class="ph-eye me-1"></i> View</a></li>
                                                                @can('Update product')
                                                                    <li><a class="dropdown-item edit-item-btn" href="#showModal" data-bs-toggle="modal" data-id="{{ $product->id }}"><i class="ph-pencil me-1"></i> Edit</a></li>
                                                                @endcan
                                                                @can('Delete product')
                                                                    <li><a class="dropdown-item remove-item-btn" href="javascript:void(0);" data-id="{{ $product->id }}"><i class="ph-trash me-1"></i> Delete</a></li>
                                                                @endcan
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="9" class="text-center py-5 text-muted">
                                                        <div class="py-4">
                                                            <i class="bi bi-box-seam display-4 text-muted"></i>
                                                            <h5 class="mt-2">No products found</h5>
                                                            <p class="text-muted">Try adjusting your filters or add a new product.</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="noresult" style="display: none">
                                    <div class="text-center py-4">
                                        <i class="bi bi-search display-4 text-primary"></i>
                                        <h5 class="mt-2">Sorry! No Result Found</h5>
                                    </div>
                                </div>

                                <!-- Pagination -->
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

            <!-- Add/Edit Modal -->
            <div class="modal fade" id="showModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <form id="productForm" enctype="multipart/form-data" method="POST">
                            @csrf
                            <input type="hidden" name="id" id="product_id">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalTitle">Add Product</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="resetForm()"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-4">
                                    <div class="col-lg-8">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title mb-3">Basic Information</h6>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Title <span class="text-danger">*</span></label>
                                                        <input type="text" name="title" id="title" class="form-control" required>
                                                        <div class="invalid-feedback">Please enter product title</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">SKU <span class="text-danger">*</span></label>
                                                        <input type="text" name="sku" id="sku" class="form-control" required>
                                                        <div class="invalid-feedback">Please enter SKU</div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Price <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">$</span>
                                                            <input type="number" step="0.01" name="price" id="price" class="form-control" required min="0">
                                                        </div>
                                                        <div class="invalid-feedback">Please enter valid price</div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Sale Price</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">$</span>
                                                            <input type="number" step="0.01" name="sale_price" id="sale_price" class="form-control" min="0">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Stock <span class="text-danger">*</span></label>
                                                        <input type="number" name="stock" id="stock" class="form-control" required min="0">
                                                        <div class="invalid-feedback">Please enter stock quantity</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Product Type <span class="text-danger">*</span></label>
                                                        <select name="product_type" id="product_type" class="form-control" required>
                                                            <option value="simple">Simple Product</option>
                                                            <option value="variable">Variable Product</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Description</label>
                                                        <textarea name="description" id="description" rows="4" class="form-control"></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <!-- Thumbnail -->
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <label class="form-label">Thumbnail Image</label>
                                                <input type="file" name="thumbnail" class="form-control mb-2" accept="image/*" onchange="previewImage(event)">
                                                <div class="text-center">
                                                    <img id="thumbnail_preview" src="" class="img-fluid rounded" style="max-height:200px; display:none;">
                                                    <div id="thumbnail_placeholder" class="text-muted">
                                                        <i class="bi bi-image display-4"></i>
                                                        <p class="mt-2">No image selected</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Gallery Images -->
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <label class="form-label">Gallery Images</label>
                                                <input type="file" name="images[]" multiple class="form-control mb-3" accept="image/*">
                                                <div id="imageGallery" class="row g-2">
                                                    <!-- Existing images will be displayed here -->
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Brand & Category -->
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
                                                <div class="mb-0">
                                                    <label class="form-label">Category</label>
                                                    <select name="category_id" id="category_id" class="form-control">
                                                        <option value="">Select Category</option>
                                                        @foreach($categories as $cat)
                                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                            @foreach($cat->children as $child)
                                                                <option value="{{ $child->id }}">— {{ $child->name }}</option>
                                                            @endforeach
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Featured Switch -->
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1">
                                                    <label class="form-check-label" for="is_featured">Featured Product</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="resetForm()">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <span class="spinner-border spinner-border-sm d-none me-1" id="submitSpinner"></span>
                                    Save Product
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Stock column styling */
    .bg-success-subtle.text-success-emphasis { color: #0a3622 !important; }
    .bg-warning-subtle.text-warning-emphasis { color: #664d03 !important; }
    .bg-danger-subtle.text-danger-emphasis { color: #58151c !important; }
    .bg-primary-subtle.text-primary { color: #084298 !important; }
    .bg-secondary-subtle.text-secondary { color: #495057 !important; }
    
    /* Image gallery styling */
    .gallery-image {
        position: relative;
        margin-bottom: 10px;
    }
    .gallery-image img {
        border-radius: 6px;
        object-fit: cover;
    }
    .gallery-image .delete-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #dee2e6;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 12px;
    }
    .gallery-image .delete-btn:hover {
        background: #fff;
        border-color: #dc3545;
        color: #dc3545;
    }
    
    /* Table improvements */
    .table td {
        vertical-align: middle;
    }
    .avatar-sm {
        min-width: 40px;
    }
</style>



<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/list.js@2.3.1/dist/list.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Set CSRF token for all requests
    axios.defaults.headers.common['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    
    // Initialize List.js for search and sort
    const list = new List('productList', {
        valueNames: ['title', 'category', 'stock', 'price', 'sold', 'created_at', 'featured'],
        page: 12,
        pagination: true
    });
    
    // Bulk selection
    document.getElementById('checkAll')?.addEventListener('change', function () {
        document.querySelectorAll('input[name="chk_child"]').forEach(cb => cb.checked = this.checked);
        toggleBulkActions();
    });
    
    function toggleBulkActions() {
        const checked = document.querySelectorAll('input[name="chk_child"]:checked').length;
        document.getElementById('remove-actions').classList.toggle('d-none', checked === 0);
    }
    
    // Bulk delete function
    window.deleteMultiple = function () {
        const ids = Array.from(document.querySelectorAll('input[name="chk_child"]:checked')).map(cb => cb.value);
        if (!ids.length) return;
        
        Swal.fire({
            title: `Delete ${ids.length} product(s)?`,
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Send delete requests
                Promise.all(ids.map(id => axios.delete(`/products/${id}`)))
                    .then(() => {
                        Swal.fire('Deleted!', 'Products have been deleted.', 'success')
                            .then(() => location.reload());
                    })
                    .catch(error => {
                        Swal.fire('Error!', 'Failed to delete some products', 'error');
                    });
            }
        });
    };
    
    // Individual checkbox change
    document.querySelectorAll('input[name="chk_child"]').forEach(cb => {
        cb.addEventListener('change', toggleBulkActions);
    });
});

// Image preview function
window.previewImage = function(event) {
    const preview = document.getElementById('thumbnail_preview');
    const placeholder = document.getElementById('thumbnail_placeholder');
    
    if (event.target.files.length > 0) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        }
        reader.readAsDataURL(event.target.files[0]);
    } else {
        preview.style.display = 'none';
        placeholder.style.display = 'block';
    }
};

// Reset form function
window.resetForm = function() {
    const form = document.getElementById('productForm');
    form.reset();
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    
    document.getElementById('product_id').value = '';
    document.getElementById('modalTitle').textContent = 'Add Product';
    document.getElementById('submitBtn').innerHTML = '<span class="spinner-border spinner-border-sm d-none me-1" id="submitSpinner"></span>Save Product';
    
    const preview = document.getElementById('thumbnail_preview');
    const placeholder = document.getElementById('thumbnail_placeholder');
    preview.style.display = 'none';
    placeholder.style.display = 'block';
    
    // Clear gallery images
    document.getElementById('imageGallery').innerHTML = '';
    
    // Reset product type to default
    document.getElementById('product_type').value = 'simple';
    
    // Clear file inputs
    form.querySelectorAll('input[type="file"]').forEach(input => {
        input.value = '';
    });
};

// Edit product function
document.querySelectorAll('.edit-item-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const id = this.dataset.id;
        
        // Show loading in modal
        document.getElementById('modalTitle').textContent = 'Loading...';
        document.getElementById('submitBtn').disabled = true;
        
        axios.get(`/products/${id}/edit`)
            .then(response => {
                const p = response.data;
                
                // Populate form fields
                document.getElementById('product_id').value = p.id;
                document.getElementById('title').value = p.title;
                document.getElementById('sku').value = p.sku;
                document.getElementById('price').value = p.price;
                document.getElementById('sale_price').value = p.sale_price || '';
                document.getElementById('stock').value = p.stock;
                document.getElementById('description').value = p.description || '';
                document.getElementById('brand_id').value = p.brand_id || '';
                document.getElementById('category_id').value = p.category_id || '';
                document.getElementById('product_type').value = p.product_type || 'simple';
                document.getElementById('is_featured').checked = p.is_featured;
                
                // Handle thumbnail
                const preview = document.getElementById('thumbnail_preview');
                const placeholder = document.getElementById('thumbnail_placeholder');
                if (p.thumbnail) {
                    preview.src = p.thumbnail;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                } else {
                    preview.style.display = 'none';
                    placeholder.style.display = 'block';
                }
                
                // Handle gallery images
                const gallery = document.getElementById('imageGallery');
                gallery.innerHTML = '';
                if (p.images && p.images.length > 0) {
                    p.images.forEach(image => {
                        const col = document.createElement('div');
                        col.className = 'col-6 col-md-4';
                        col.innerHTML = `
                            <div class="gallery-image">
                                <img src="${image.url}" class="img-fluid rounded" alt="Gallery image">
                                <div class="delete-btn" onclick="deleteGalleryImage(${p.id}, ${image.id})">
                                    <i class="bi bi-x"></i>
                                </div>
                            </div>
                        `;
                        gallery.appendChild(col);
                    });
                }
                
                document.getElementById('modalTitle').textContent = 'Edit Product';
                document.getElementById('submitBtn').innerHTML = '<span class="spinner-border spinner-border-sm d-none me-1" id="submitSpinner"></span>Update Product';
                document.getElementById('submitBtn').disabled = false;
            })
            .catch(error => {
                console.error('Error fetching product:', error);
                Swal.fire('Error!', 'Failed to load product data', 'error');
                document.getElementById('submitBtn').disabled = false;
            });
    });
});

// Delete gallery image
window.deleteGalleryImage = function(productId, imageId) {
    Swal.fire({
        title: 'Delete this image?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(`/products/${productId}/images/${imageId}`)
                .then(response => {
                    if (response.data.success) {
                        // Remove image from DOM
                        document.querySelector(`.delete-btn[onclick="deleteGalleryImage(${productId}, ${imageId})"]`).parentElement.parentElement.remove();
                        Swal.fire('Deleted!', 'Image has been deleted.', 'success');
                    }
                })
                .catch(error => {
                    Swal.fire('Error!', 'Failed to delete image', 'error');
                });
        }
    });
};

// Form submission
document.getElementById('productForm').addEventListener('submit', function (e) {
    e.preventDefault();
    
    const form = this;
    const id = document.getElementById('product_id').value;
    const isEdit = !!id;
    const url = isEdit ? `/products/${id}` : '/products';
    const method = isEdit ? 'PUT' : 'POST';
    
    // Validate form
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    if (!isValid) {
        Swal.fire('Error!', 'Please fill in all required fields', 'error');
        return;
    }
    
    // Validate sale price
    const price = parseFloat(document.getElementById('price').value);
    const salePrice = document.getElementById('sale_price').value;
    if (salePrice && parseFloat(salePrice) >= price) {
        document.getElementById('sale_price').classList.add('is-invalid');
        Swal.fire('Error!', 'Sale price must be less than regular price', 'error');
        return;
    } else {
        document.getElementById('sale_price').classList.remove('is-invalid');
    }
    
    // Show loading
    const submitBtn = document.getElementById('submitBtn');
    const submitSpinner = document.getElementById('submitSpinner');
    const originalBtnText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitSpinner.classList.remove('d-none');
    submitBtn.innerHTML = submitBtn.innerHTML.replace('Save Product', 'Saving...').replace('Update Product', 'Updating...');
    
    // Prepare form data
    const formData = new FormData(form);
    if (isEdit) {
        formData.append('_method', 'PUT');
    }
    
    axios.post(url, formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
    .then(response => {
        if (response.data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: response.data.message,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                // Close modal and reload page
                const modal = bootstrap.Modal.getInstance(document.getElementById('showModal'));
                modal.hide();
                location.reload();
            });
        } else {
            throw new Error(response.data.message);
        }
    })
    .catch(error => {
        let errorMessage = 'Something went wrong';
        
        if (error.response) {
            if (error.response.status === 422) {
                // Validation errors
                const errors = error.response.data.errors;
                errorMessage = Object.values(errors).flat().join('<br>');
                
                // Mark invalid fields
                Object.keys(errors).forEach(field => {
                    const input = form.querySelector(`[name="${field}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                    }
                });
            } else if (error.response.data.message) {
                errorMessage = error.response.data.message;
            }
        } else if (error.message) {
            errorMessage = error.message;
        }
        
        Swal.fire('Error!', errorMessage, 'error');
    })
    .finally(() => {
        // Reset button state
        submitBtn.disabled = false;
        submitSpinner.classList.add('d-none');
        submitBtn.innerHTML = originalBtnText;
    });
});

// Delete single product
document.querySelectorAll('.remove-item-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const id = this.dataset.id;
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                axios.delete(`/products/${id}`)
                    .then(response => {
                        if (response.data.success) {
                            Swal.fire('Deleted!', 'Product has been deleted.', 'success')
                                .then(() => location.reload());
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error!', 'Failed to delete product', 'error');
                    });
            }
        });
    });
});

// Live search with debounce
let searchTimeout;
document.querySelector('.search')?.addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        document.getElementById('filterForm').submit();
    }, 500);
});

// Initialize Choices.js for select elements (if you have it)
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Choices !== 'undefined') {
        const brandFilter = document.getElementById('brandFilter');
        const categoryFilter = document.getElementById('categoryFilter');
        
        if (brandFilter) {
            new Choices(brandFilter, {
                removeItemButton: true,
                searchEnabled: true,
                shouldSort: false
            });
        }
        
        if (categoryFilter) {
            new Choices(categoryFilter, {
                searchEnabled: true,
                shouldSort: false
            });
        }
    }
});
</script>
@endsection