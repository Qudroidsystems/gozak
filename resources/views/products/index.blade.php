@extends('layouts.master')

@section('title', 'Products Management')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- YOUR ORIGINAL PAGE TITLE - UNCHANGED -->
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
                <!-- YOUR ORIGINAL FILTERS - 100% UNCHANGED -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="filterForm" method="GET" action="{{ route('products.index') }}">
                                    <div class="row g-3">
                                        <div class="col-xxl-3">
                                            <div class="search-box">
                                                <input type="text" class="form-control search" name="search" placeholder="Search products, SKU, price..." value="{{ request('search') }}" autocomplete="off">
                                                <i class="ri-search-line search-icon"></i>
                                            </div>
                                        </div>
                                        <div class="col-xxl-2 col-sm-6">
                                            <select class="form-control" name="brands[]" id="brandFilter" data-choices data-choices-search-false multiple>
                                                <option value="">All Brands</option>
                                                @foreach($brands as $brand)
                                                    <option value="{{ $brand->id }}" {{ in_array($brand->id, (array)request('brands', [])) ? 'selected' : '' }}>{{ $brand->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-xxl-2 col-sm-6">
                                            <select class="form-control" name="category" id="categoryFilter" data-choices data-choices-search-false>
                                                <option value="">All Categories</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                                    @if($category->children->count())
                                                        @foreach($category->children as $child)
                                                            <option value="{{ $child->id }}" {{ request('category') == $child->id ? 'selected' : '' }}>— {{ $child->name }}</option>
                                                        @endforeach
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-xxl-2 col-sm-4">
                                            <select class="form-control" name="stock" id="stockFilter">
                                                <option value="">All Stock</option>
                                                <option value="in_stock" {{ request('stock') == 'in_stock' ? 'selected' : '' }}>In Stock</option>
                                                <option value="low_stock" {{ request('stock') == 'low_stock' ? 'selected' : '' }}>Low Stock (less than 10)</option>
                                                <option value="out_of_stock" {{ request('stock') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                                            </select>
                                        </div>
                                        <div class="col-xxl-2 col-sm-4">
                                            <select class="form-control" name="featured" id="featuredFilter">
                                                <option value="">All Products</option>
                                                <option value="yes" {{ request('featured') == 'yes' ? 'selected' : '' }}>Featured Only</option>
                                                <option value="no" {{ request('featured') == 'no' ? 'selected' : '' }}>Non-Featured</option>
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

                <!-- YOUR ORIGINAL PRODUCT LIST - 100% UNCHANGED -->
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

            <!-- YOUR ORIGINAL MODAL - ONLY FUNCTIONALITY ADDED -->
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
                                                        <label class="form-label">Discount %</label>
                                                        <input type="number" step="0.01" min="0" max="100" id="discount_percent" class="form-control" placeholder="e.g. 25">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Sale Price</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">$</span>
                                                            <input type="number" step="0.01" name="sale_price" id="sale_price" class="form-control">
                                                        </div>
                                                        <small class="text-success" id="sale_price_note" style="display:none;">Auto-calculated</small>
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

                                        <!-- Variations - YOUR ORIGINAL STRUCTURE -->
                                        <div id="variationsSection" style="display: none;">
                                            <div class="card mt-4">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0">Product Variations</h6>
                                                    <div>
                                                        <input type="number" step="0.01" min="0" max="100" id="bulk_discount" class="form-control form-control-sm d-inline-block w-auto me-2" placeholder="Bulk %">
                                                        <button type="button" class="btn btn-success btn-sm" id="applyBulkDiscount">Apply to All</button>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div id="attributesContainer" class="mb-4">
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
                                                    </div>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="addAttribute">+ Add Attribute</button>
                                                    <button type="button" class="btn btn-primary btn-sm mb-3" id="generateVariations">Generate Variations</button>
                                                    <hr>
                                                    <div id="variationsTable"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- YOUR ORIGINAL RIGHT SIDEBAR - 100% UNCHANGED -->
                                    <div class="col-lg-4">
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <label class="form-label">Thumbnail Image</label>
                                                <input type="file" name="thumbnail" id="thumbnail_input" class="form-control mb-2" accept="image/*">
                                                <div class="text-center">
                                                    <img id="thumbnail_preview" src="" class="img-fluid rounded" style="max-height:200px; display:none;">
                                                    <div id="thumbnail_placeholder" class="text-muted">
                                                        <i class="bi bi-image display-4"></i>
                                                        <p class="mt-2">No image selected</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <label class="form-label">Gallery Images</label>
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

<!-- YOUR ORIGINAL SCRIPTS + ONLY FUNCTIONALITY FIXES -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/list.js@2.3.1/dist/list.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    // YOUR ORIGINAL LIST.JS + BULK + LIVE SEARCH + CHOICES
    const list = new List('productList', {
        valueNames: ['title', 'category', 'stock', 'price', 'sold', 'created_at', 'featured'],
        page: 12,
        pagination: true
    });

    document.getElementById('checkAll')?.addEventListener('change', function () {
        document.querySelectorAll('input[name="chk_child"]').forEach(cb => cb.checked = this.checked);
        toggleBulkActions();
    });

    function toggleBulkActions() {
        const checked = document.querySelectorAll('input[name="chk_child"]:checked').length;
        document.getElementById('remove-actions').classList.toggle('d-none', checked === 0);
    }

    window.deleteMultiple = function () {
        const ids = Array.from(document.querySelectorAll('input[name="chk_child"]:checked')).map(cb => cb.value);
        if (!ids.length) return;
        Swal.fire({
            title: `Delete ${ids.length} product(s)?`,
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete them!'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Deleting...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                Promise.all(ids.map(id => axios.delete(`/products/${id}`)))
                    .then(() => location.reload())
                    .catch(() => Swal.fire('Error!', 'Failed to delete some products', 'error'));
            }
        });
    };

    document.querySelectorAll('input[name="chk_child"]').forEach(cb => cb.addEventListener('change', toggleBulkActions));

    let searchTimeout;
    document.querySelector('.search')?.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => document.getElementById('filterForm').submit(), 500);
    });

    if (typeof Choices !== 'undefined') {
        new Choices('#brandFilter', { removeItemButton: true, searchEnabled: true, shouldSort: false });
        new Choices('#categoryFilter', { searchEnabled: true, shouldSort: false });
    }
});

// GLOBAL
let attrIndex = 1;
let productData = null;

// TOGGLE VARIATIONS
document.getElementById('product_type').addEventListener('change', function() {
    document.getElementById('variationsSection').style.display = this.value === 'variable' ? 'block' : 'none';
});

// ADD ATTRIBUTE
document.getElementById('addAttribute')?.addEventListener('click', () => {
    const html = `<div class="row g-3 align-items-end attribute-row mt-3">
        <div class="col-md-5"><input type="text" class="form-control" placeholder="e.g. Size" name="attributes[${attrIndex}][name]"></div>
        <div class="col-md-6"><input type="text" class="form-control" placeholder="S, M, L" name="attributes[${attrIndex}][values]"></div>
        <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm remove-attribute">Remove</button></div>
    </div>`;
    document.getElementById('attributesContainer').insertAdjacentHTML('beforeend', html);
    attrIndex++;
});

// BIDIRECTIONAL PRICING + BULK + VISUAL BADGE
document.getElementById('price')?.addEventListener('input', calculateFromPrice);
document.getElementById('discount_percent')?.addEventListener('input', calculateFromDiscount);
document.getElementById('sale_price')?.addEventListener('input', calculateFromSalePrice);

function calculateFromPrice() {
    const price = parseFloat(document.getElementById('price').value) || 0;
    const discount = parseFloat(document.getElementById('discount_percent').value) || 0;
    if (discount > 0 && discount <= 100) {
        document.getElementById('sale_price').value = (price - (price * discount / 100)).toFixed(2);
        document.getElementById('sale_price_note').style.display = 'block';
    }
}

function calculateFromDiscount() {
    const price = parseFloat(document.getElementById('price').value) || 0;
    const discount = parseFloat(document.getElementById('discount_percent').value) || 0;
    if (price > 0 && discount > 0 && discount <= 100) {
        document.getElementById('sale_price').value = (price - (price * discount / 100)).toFixed(2);
        document.getElementById('sale_price_note').style.display = 'block';
    }
}

function calculateFromSalePrice() {
    const price = parseFloat(document.getElementById('price').value) || 0;
    const salePrice = parseFloat(document.getElementById('sale_price').value) || 0;
    if (price > 0 && salePrice > 0 && salePrice < price) {
        const discount = ((price - salePrice) / price) * 100;
        document.getElementById('discount_percent').value = discount.toFixed(2);
        document.getElementById('sale_price_note').style.display = 'block';
        document.getElementById('sale_price_note').textContent = 'Auto-calculated from Sale Price';
    }
}

// BULK DISCOUNT
document.getElementById('applyBulkDiscount')?.addEventListener('click', () => {
    const bulk = parseFloat(document.getElementById('bulk_discount').value) || 0;
    if (bulk < 0 || bulk > 100) return Swal.fire('Error', '0-100%', 'error');
    document.querySelectorAll('#variationsTable tbody tr').forEach(row => {
        const price = parseFloat(row.querySelector('input[name*="price"]').value) || 0;
        if (price > 0) {
            row.querySelector('input[name*="sale_price"]').value = (price - (price * bulk / 100)).toFixed(2);
            let badge = row.querySelector('.discount-badge');
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'badge bg-danger discount-badge position-absolute';
                badge.style.top = '5px'; badge.style.right = '5px';
                row.querySelector('td').style.position = 'relative';
                row.querySelector('td').appendChild(badge);
            }
            badge.textContent = `-${bulk}%`;
        }
    });
});

// GENERATE + EDIT FIX
document.getElementById('generateVariations')?.addEventListener('click', () => {
    const attrs = [];
    document.querySelectorAll('.attribute-row').forEach(row => {
        const name = row.querySelector('input[name$="[name]"]').value.trim();
        const values = row.querySelector('input[name$="[values]"]').value.split(',').map(v => v.trim()).filter(v => v);
        if (name && values.length) attrs.push({ name, values });
    });

    if (attrs.length === 0 && productData?.variations?.length > 0) {
        renderExistingVariations();
        return;
    }

    if (attrs.length === 0) {
        document.getElementById('variationsTable').innerHTML = '<p class="text-muted">Add attributes and click Generate</p>';
        return;
    }

    const combos = attrs.reduce((a, b) => a.flatMap(x => b.values.map(y => ({...x, [b.name]: y}))), [{}]);
    renderVariationsTable(combos);
});

function renderVariationsTable(combos) {
    let html = `<table class="table table-bordered"><thead><tr>
        <th>Variant</th><th>SKU</th><th>Price</th><th>Sale Price</th><th>Stock</th><th>Image</th><th></th>
    </tr></thead><tbody>`;
    combos.forEach((c, i) => {
        const name = Object.entries(c).map(([k,v]) => `${k}: ${v}`).join(' | ');
        html += `<tr>
            <td style="position:relative"><small>${name}</small>
                ${Object.entries(c).map(([k,v]) => `<input type="hidden" name="variations[${i}][attributes][${k}]" value="${v}">`).join('')}
            </td>
            <td><input type="text" name="variations[${i}][sku]" class="form-control form-control-sm" required></td>
            <td><input type="number" step="0.01" name="variations[${i}][price]" class="form-control form-control-sm variation-price" required></td>
            <td><input type="number" step="0.01" name="variations[${i}][sale_price]" class="form-control form-control-sm variation-sale"></td>
            <td><input type="number" name="variations[${i}][stock]" class="form-control form-control-sm" required></td>
            <td>
                <input type="file" name="variations[${i}][image]" class="form-control form-control-sm variation-image" accept="image/*">
                <img class="variation-preview mt-2 img-fluid rounded" style="max-height:80px; display:none;">
            </td>
            <td><button type="button" class="btn btn-danger btn-sm remove-variation">Remove</button></td>
        </tr>`;
    });
    html += `</tbody></table>`;
    document.getElementById('variationsTable').innerHTML = html;
}

function renderExistingVariations() {
    let html = `<table class="table table-bordered"><thead><tr><th>Variant</th><th>SKU</th><th>Price</th><th>Sale</th><th>Stock</th><th>Image</th><th></th></tr></thead><tbody>`;
    productData.variations.forEach((v, i) => {
        const attrs = v.attributes || {};
        const name = Object.entries(attrs).map(([k,val]) => `${k}: ${val}`).join(' | ') || 'Default';
        const discount = v.sale_price && v.price ? Math.round(((v.price - v.sale_price) / v.price) * 100) : 0;

        html += `<tr>
            <td style="position:relative"><small>${name}</small>
                ${Object.entries(attrs).map(([k,val]) => `<input type="hidden" name="variations[${i}][attributes][${k}]" value="${val}">`).join('')}
                ${discount > 0 ? `<span class="badge bg-danger position-absolute" style="top:5px;right:5px;">-${discount}%</span>` : ''}
            </td>
            <td><input type="text" name="variations[${i}][sku]" value="${v.sku || ''}" class="form-control form-control-sm" required></td>
            <td><input type="number" step="0.01" name="variations[${i}][price]" value="${v.price}" class="form-control form-control-sm variation-price" required></td>
            <td><input type="number" step="0.01" name="variations[${i}][sale_price]" value="${v.sale_price || ''}" class="form-control form-control-sm variation-sale"></td>
            <td><input type="number" name="variations[${i}][stock]" value="${v.stock}" class="form-control form-control-sm" required></td>
            <td>
                <input type="file" name="variations[${i}][image]" class="form-control form-control-sm variation-image" accept="image/*">
                ${v.image ? `<img src="${v.image}" class="variation-preview mt-2 img-fluid rounded" style="max-height:80px;">` : ''}
            </td>
            <td><button type="button" class="btn btn-danger btn-sm remove-variation">Remove</button></td>
        </tr>`;
    });
    html += `</tbody></table>`;
    document.getElementById('variationsTable').innerHTML = html;
}

// IMAGE PREVIEWS + DRAG & DROP
document.getElementById('thumbnail_input')?.addEventListener('change', e => {
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

document.getElementById('gallery_input')?.addEventListener('change', e => {
    const container = document.getElementById('imageGallery');
    container.innerHTML = '';
    Array.from(e.target.files).forEach(file => {
        const reader = new FileReader();
        reader.onload = ev => {
            const div = document.createElement('div');
            div.className = 'col-6 col-md-4';
            div.innerHTML = `<div class="gallery-image">
                <img src="${ev.target.result}" class="img-fluid rounded" alt="Gallery image">
                <div class="delete-btn" onclick="this.parentElement.parentElement.remove()">X</div>
            </div>`;
            container.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
});

new Sortable(document.getElementById('imageGallery'), { animation: 150 });

// Variation image preview
document.addEventListener('change', e => {
    if (e.target.classList.contains('variation-image') && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = ev => {
            e.target.closest('td').querySelector('.variation-preview').src = ev.target.result;
            e.target.closest('td').querySelector('.variation-preview').style.display = 'block';
        };
        reader.readAsDataURL(e.target.files[0]);
    }
});

// REMOVE BUTTONS
document.addEventListener('click', e => {
    if (e.target.classList.contains('remove-attribute')) e.target.closest('.attribute-row').remove();
    if (e.target.classList.contains('remove-variation')) e.target.closest('tr').remove();
});

// SAVE BUTTON
document.getElementById('productForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('product_id').value;
    const url = id ? `/products/${id}` : '/products';
    const formData = new FormData(this);
    if (id) formData.append('_method', 'PUT');

    const btn = document.getElementById('submitBtn');
    const spinner = document.getElementById('submitSpinner');
    btn.disabled = true;
    spinner.classList.remove('d-none');

    axios.post(url, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
        .then(r => { if (r.data.success) { Swal.fire('Success!', r.data.message, 'success').then(() => location.reload()); } })
        .catch(err => {
            const msg = err.response?.data?.errors ? Object.values(err.response.data.errors).flat().join('<br>') : err.response?.data?.message || 'Error';
            Swal.fire('Error!', msg, 'error');
        })
        .finally(() => {
            btn.disabled = false;
            spinner.classList.add('d-none');
        });
});

// EDIT + DELETE + RESET
document.querySelectorAll('.edit-item-btn').forEach(b => b.addEventListener('click', function() {
    axios.get(`/products/${this.dataset.id}/edit`).then(r => {
        productData = r.data;
        document.getElementById('product_id').value = productData.id;
        document.getElementById('title').value = productData.title;
        document.getElementById('sku').value = productData.sku;
        document.getElementById('price').value = productData.price;
        document.getElementById('sale_price').value = productData.sale_price || '';
        document.getElementById('stock').value = productData.stock;
        document.getElementById('description').value = productData.description || '';
        document.getElementById('brand_id').value = productData.brand_id || '';
        document.getElementById('category_id').value = productData.category_id || '';
        document.getElementById('product_type').value = productData.product_type;
        document.getElementById('is_featured').checked = productData.is_featured;

        if (productData.thumbnail) {
            document.getElementById('thumbnail_preview').src = productData.thumbnail;
            document.getElementById('thumbnail_preview').style.display = 'block';
            document.getElementById('thumbnail_placeholder').style.display = 'none';
        }

        calculateFromSalePrice();

        if (productData.product_type === 'variable') {
            document.getElementById('variationsSection').style.display = 'block';
            document.getElementById('attributesContainer').innerHTML = '';
            attrIndex = 0;
            productData.attributes.forEach(a => {
                if (attrIndex > 0) document.getElementById('addAttribute').click();
                const row = document.querySelectorAll('.attribute-row')[attrIndex];
                row.querySelector('input[name$="[name]"]').value = a.name;
                row.querySelector('input[name$="[values]"]').value = a.values.join(', ');
                attrIndex++;
            });
            setTimeout(() => renderExistingVariations(), 300);
        }

        document.getElementById('modalTitle').textContent = 'Edit Product';
        document.getElementById('submitBtn').innerHTML = '<span class="spinner-border spinner-border-sm d-none me-1" id="submitSpinner"></span> Update Product';
    });
}));

document.querySelectorAll('.remove-item-btn').forEach(b => b.addEventListener('click', function() {
    Swal.fire({ title: 'Are you sure?', text: "You won't be able to revert this!", icon: 'warning', showCancelButton: true })
        .then(res => { if (res.isConfirmed) axios.delete(`/products/${this.dataset.id}`).then(() => location.reload()); });
}));

function resetForm() {
    document.getElementById('productForm').reset();
    document.getElementById('product_id').value = '';
    document.getElementById('modalTitle').textContent = 'Add Product';
    document.getElementById('submitBtn').innerHTML = '<span class="spinner-border spinner-border-sm d-none me-1" id="submitSpinner"></span> Save Product';
    document.getElementById('variationsSection').style.display = 'none';
    document.getElementById('attributesContainer').innerHTML = `<div class="row g-3 align-items-end attribute-row">
        <div class="col-md-5"><input type="text" class="form-control" placeholder="e.g. Color" name="attributes[0][name]"></div>
        <div class="col-md-6"><input type="text" class="form-control" placeholder="Red, Blue, Green" name="attributes[0][values]"></div>
        <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm remove-attribute">Remove</button></div>
    </div>`;
    document.getElementById('variationsTable').innerHTML = '';
    document.getElementById('imageGallery').innerHTML = '';
    document.getElementById('thumbnail_preview').style.display = 'none';
    document.getElementById('thumbnail_placeholder').style.display = 'block';
    document.getElementById('sale_price').value = '';
    document.getElementById('discount_percent').value = '';
    document.getElementById('sale_price_note').style.display = 'none';
    document.getElementById('bulk_discount').value = '';
    attrIndex = 1;
}
</script>
@endsection