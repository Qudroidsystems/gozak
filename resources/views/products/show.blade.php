{{-- resources/views/products/show.blade.php --}}
@extends('layouts.master')

@section('title', $product->title . ' - Product Details')

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
<style>
    .sticky-side-div {
        position: sticky;
        top: 100px;
    }
    
    /* Swiper container styling */
    .product-thumbnail-slider {
        height: 400px;
        width: 100%;
    }
    
    .product-thumbnail-slider .swiper-wrapper {
        height: 100%;
    }
    
    .product-thumbnail-slider .swiper-slide {
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        height: 100%;
    }
    
    .product-thumbnail-slider .swiper-slide img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    
    .product-nav-slider {
        margin-top: 15px;
        height: 100px;
    }
    
    .product-nav-slider .swiper-slide {
        width: 80px;
        height: 80px;
        opacity: 0.5;
        transition: opacity 0.3s;
        cursor: pointer;
    }
    
    .product-nav-slider .swiper-slide-thumb-active {
        opacity: 1;
    }
    
    .nav-slide-item {
        border: 2px solid transparent;
        border-radius: 6px;
        padding: 2px;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        transition: border-color 0.3s;
    }
    
    .product-nav-slider .swiper-slide-thumb-active .nav-slide-item {
        border-color: #0d6efd;
    }
    
    .nav-slide-item img {
        border-radius: 4px;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .swiper-button-next,
    .swiper-button-prev {
        color: #fff;
        background: rgba(0,0,0,0.5);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        transition: background 0.3s;
    }
    
    .swiper-button-next:after,
    .swiper-button-prev:after {
        font-size: 20px;
    }
    
    .swiper-button-next:hover,
    .swiper-button-prev:hover {
        background: rgba(0,0,0,0.8);
    }
    
    /* Badge styles for stock */
    .badge.bg-success { background-color: #0a3622 !important; color: white !important; }
    .badge.bg-warning { background-color: #664d03 !important; color: white !important; }
    .badge.bg-danger { background-color: #58151c !important; color: white !important; }
    
    /* Custom styles */
    .card-height-100 {
        min-height: 100%;
    }
    
    .rounded-start-0 {
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
    }
    
    .rounded-end-0 {
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }
    
    .description-table th {
        width: 200px;
        font-weight: 600;
        background-color: #f8f9fa;
    }
    
    .rating-input .btn-outline-warning {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .rating-input .btn-outline-warning i {
        font-size: 1.5rem;
    }
</style>
@endpush

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Breadcrumb -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Product Overview</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                                <li class="breadcrumb-item active">Product Overview</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Image Gallery -->
                <div class="col-xxl-4">
                    <div class="card p-3 sticky-side-div">
                        <div class="product-img-slider">
                            <!-- Main Slider -->
                            <div class="swiper product-thumbnail-slider p-2 rounded bg-light">
                                <div class="swiper-wrapper">
                                    @if($product->thumbnail)
                                        <div class="swiper-slide">
                                            <img src="{{ asset('storage/' . $product->thumbnail) }}" alt="{{ $product->title }}" class="img-fluid">
                                        </div>
                                    @endif
                                    @foreach($product->images as $img)
                                        <div class="swiper-slide">
                                            <img src="{{ asset('storage/' . $img->image_path) }}" alt="Gallery" class="img-fluid">
                                        </div>
                                    @endforeach
                                    @if(!$product->thumbnail && $product->images->isEmpty())
                                        <div class="swiper-slide">
                                            <div class="d-flex align-items-center justify-content-center h-100">
                                                <i class="bi bi-image fs-1 text-muted"></i>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="swiper-button-next"></div>
                                <div class="swiper-button-prev"></div>
                            </div>

                            <!-- Thumbnail Slider -->
                            <div class="swiper product-nav-slider mt-3">
                                <div class="swiper-wrapper">
                                    @if($product->thumbnail)
                                        <div class="swiper-slide">
                                            <div class="nav-slide-item">
                                                <img src="{{ asset('storage/' . $product->thumbnail) }}" alt="" class="img-fluid">
                                            </div>
                                        </div>
                                    @endif
                                    @foreach($product->images as $img)
                                        <div class="swiper-slide">
                                            <div class="nav-slide-item">
                                                <img src="{{ asset('storage/' . $img->image_path) }}" alt="" class="img-fluid">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="col-xxl-8">
                    <div class="row g-0">
                        <div class="col-xxl-8">
                            <div class="card rounded-end-0">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h4 class="text-capitalize mb-3">{{ $product->title }}</h4>
                                        @if($product->is_featured)
                                            <span class="badge bg-warning-subtle text-warning fs-6">
                                                <i class="bi bi-star-fill me-1"></i> Featured
                                            </span>
                                        @endif
                                    </div>

                                    <div class="hstack gap-3 flex-wrap mb-4">
                                        <div class="text-muted"><b class="text-body fw-medium">{{ $totalSold ?? 0 }}</b> Sold</div>
                                        <div class="vr"></div>
                                        <div class="text-muted"><b class="text-body fw-medium">{{ $product->reviews->count() ?? 0 }}</b> Reviews</div>
                                        <div class="vr"></div>
                                        <div class="text-muted">Published : <span class="text-body fw-medium">{{ $product->created_at->format('d M, Y') }}</span></div>
                                    </div>

                                    <!-- Price Section -->
                                    <div class="mb-4">
                                        @if($product->sale_price)
                                            <h3 class="text-danger fw-bold mb-0">${{ number_format($product->sale_price, 2) }}</h3>
                                            <del class="text-muted fs-5">${{ number_format($product->price, 2) }}</del>
                                            <span class="badge bg-success ms-2">
                                                {{ round((($product->price - $product->sale_price) / $product->price) * 100) }}% OFF
                                            </span>
                                        @else
                                            <h3 class="fw-bold">${{ number_format($product->price, 2) }}</h3>
                                        @endif
                                    </div>

                                    <!-- Stock Status -->
                                    <div class="mb-4">
                                        @if($product->stock > 10)
                                            <span class="badge bg-success text-white">
                                                <i class="bi bi-check-circle me-1"></i> In Stock ({{ $product->stock }} left)
                                            </span>
                                        @elseif($product->stock > 0)
                                            <span class="badge bg-warning text-white">
                                                <i class="bi bi-exclamation-triangle me-1"></i> Low Stock (Only {{ $product->stock }} left!)
                                            </span>
                                        @else
                                            <span class="badge bg-danger text-white">
                                                <i class="bi bi-x-circle me-1"></i> Out of Stock
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Product Type -->
                                    <div class="mb-4">
                                        <h5 class="fs-md mb-2">Product Type:</h5>
                                        <div class="badge bg-primary-subtle text-primary fs-6">
                                            {{ ucfirst($product->product_type ?? 'simple') }}
                                        </div>
                                    </div>

                                    <!-- Brand & Category -->
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <h6 class="text-muted mb-2">Brand:</h6>
                                            <p class="fw-semibold">{{ $product->brand?->name ?? 'N/A' }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-muted mb-2">Category:</h6>
                                            <p class="fw-semibold">{{ $product->category?->name ?? 'Uncategorized' }}</p>
                                        </div>
                                    </div>

                                    <!-- SKU -->
                                    <div class="mb-4">
                                        <h6 class="text-muted mb-2">SKU:</h6>
                                        <p class="fw-semibold">{{ $product->sku }}</p>
                                    </div>

                                    <!-- Description -->
                                    @if($product->description)
                                    <div class="mt-4">
                                        <h5 class="fs-md mb-3">Description:</h5>
                                        <p class="text-muted">{{ $product->description }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Stats Sidebar -->
                        <div class="col-xxl-4">
                            <div class="card card-height-100 border-start rounded-start-0">
                                <div class="card-body p-4">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            @if($product->sale_price && $product->price > $product->sale_price)
                                            <div class="card bg-primary mb-4">
                                                <div class="card-body d-flex align-items-center">
                                                    <div class="flex-grow-1">
                                                        <h5 class="card-title text-white fs-xl">{{ round((($product->price - $product->sale_price) / $product->price) * 100) }}% Off</h5>
                                                        <p class="mb-0 text-white-50">Sale Active</p>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <button type="button" class="btn btn-light">Save ${{ number_format($product->price - $product->sale_price, 2) }}</button>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                            
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="card border shadow-none mb-0">
                                                        <div class="card-body p-2">
                                                            <div class="text-center">
                                                                <p class="text-muted text-truncate mb-2">PRICE</p>
                                                                <h6 class="fs-lg">${{ number_format($product->sale_price ?? $product->price, 2) }}</h6>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card border shadow-none mb-0">
                                                        <div class="card-body p-2">
                                                            <div class="text-center">
                                                                <p class="text-muted text-truncate mb-2">No. of Orders</p>
                                                                <h6 class="fs-lg">{{ $totalSold ?? 0 }}</h6>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card border shadow-none mb-0">
                                                        <div class="card-body p-2">
                                                            <div class="text-center">
                                                                <p class="text-muted text-truncate mb-2">Available Stocks</p>
                                                                <h6 class="fs-lg">{{ $product->stock }}</h6>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card border shadow-none mb-0">
                                                        <div class="card-body p-2">
                                                            <div class="text-center">
                                                                <p class="text-muted text-truncate mb-2">Total Revenue</p>
                                                                <h6 class="fs-lg">${{ number_format($revenue, 2) }}</h6>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 d-grid gap-2">
                                        @can('Update product')
                                            <a href="{{ route('products.index') }}#showModal" 
                                               class="btn btn-primary edit-item-btn" 
                                               data-bs-toggle="modal" 
                                               data-id="{{ $product->id }}"
                                               onclick="editProductFromShow({{ $product->id }})">
                                                <i class="ph-pencil me-1"></i> Edit Product
                                            </a>
                                        @endcan
                                        @can('Delete product')
                                            <button type="button" class="btn btn-danger" onclick="deleteProduct({{ $product->id }})">
                                                <i class="ph-trash me-1"></i> Delete
                                            </button>
                                        @endcan
                                        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-left me-1"></i> Back to Products
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Details Table -->
                    <div class="card mt-4">
                        <div class="card-body p-4">
                            <h5 class="fs-md mb-3">Product Details:</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless align-middle description-table mb-0">
                                    <tbody>
                                        <tr>
                                            <th>Type</th>
                                            <td>{{ ucfirst($product->product_type ?? 'simple') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Brand</th>
                                            <td>{{ $product->brand?->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Category</th>
                                            <td>{{ $product->category?->name ?? 'Uncategorized' }}</td>
                                        </tr>
                                        <tr>
                                            <th>SKU</th>
                                            <td>{{ $product->sku }}</td>
                                        </tr>
                                        <tr>
                                            <th>Stock</th>
                                            <td>{{ $product->stock }}</td>
                                        </tr>
                                        <tr>
                                            <th>Price</th>
                                            <td>${{ number_format($product->price, 2) }}</td>
                                        </tr>
                                        @if($product->sale_price)
                                        <tr>
                                            <th>Sale Price</th>
                                            <td class="text-danger fw-bold">${{ number_format($product->sale_price, 2) }}</td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <th>Created Date</th>
                                            <td>{{ $product->created_at->format('d M, Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Last Updated</th>
                                            <td>{{ $product->updated_at->format('d M, Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Featured</th>
                                            <td>
                                                @if($product->is_featured)
                                                    <span class="badge bg-success">Yes</span>
                                                @else
                                                    <span class="badge bg-secondary">No</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Reviews Section -->
                    <div class="card mt-4">
                        <div class="card-header d-flex flex-wrap align-items-center gap-3 mb-2">
                            <h6 class="card-title flex-grow-1 mb-0">Ratings & Reviews ({{ $product->reviews->count() ?? 0 }})</h6>
                            @if($product->reviews->count() > 0)
                                <div class="text-warning hstack gap-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= floor($averageRating))
                                            <i class="bi bi-star-fill"></i>
                                        @elseif($i == ceil($averageRating) && fmod($averageRating, 1) >= 0.5)
                                            <i class="bi bi-star-half"></i>
                                        @else
                                            <i class="bi bi-star"></i>
                                        @endif
                                    @endfor
                                    <span class="ms-2 text-muted">{{ number_format($averageRating, 1) }}/5.0</span>
                                </div>
                            @endif
                            <div class="flex-shrink-0">
                                <div class="dropdown card-header-dropdown">
                                    <a class="text-muted dropdown-btn" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        All Reviews <i class="mdi mdi-chevron-down ms-1"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="?rating=5">5 Stars</a>
                                        <a class="dropdown-item" href="?rating=4">4 Stars</a>
                                        <a class="dropdown-item" href="?rating=3">3 Stars</a>
                                        <a class="dropdown-item" href="?rating=2">2 Stars</a>
                                        <a class="dropdown-item" href="?rating=1">1 Star</a>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReviewModal">
                                    <i class="ph-plus-circle align-middle me-1"></i> Add Review
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row gy-3">
                                @if($product->reviews->count() > 0)
                                <div class="col-lg-2">
                                    <div class="text-center mt-3 mt-lg-5">
                                        <h1 class="mb-3">{{ number_format($averageRating, 1) }} <small class="fs-sm text-muted fw-normal">/ 5.0</small></h1>
                                        <div class="text-warning hstack gap-2 justify-content-center mb-2">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= floor($averageRating))
                                                    <i class="bi bi-star-fill"></i>
                                                @elseif($i == ceil($averageRating) && fmod($averageRating, 1) >= 0.5)
                                                    <i class="bi bi-star-half"></i>
                                                @else
                                                    <i class="bi bi-star"></i>
                                                @endif
                                            @endfor
                                        </div>
                                        <p class="mb-0"><b>{{ $product->reviews->count() }}</b> Reviews</p>
                                    </div>
                                </div>
                                <div class="col-lg-10">
                                    <div>
                                        <div class="me-lg-n3 pe-lg-4" data-simplebar style="max-height: 500px;">
                                            <ul class="list-unstyled mb-0" id="review-list">
                                                @foreach($product->reviews as $review)
                                                <li class="review-list py-2" id="review-{{ $review->id }}">
                                                    <div class="border border-dashed rounded p-3">
                                                        <div class="hstack flex-wrap gap-3 mb-4">
                                                            <div class="badge rounded-pill bg-danger-subtle text-danger mb-0">
                                                                <i class="mdi mdi-star"></i> <span class="rate-num">{{ number_format($review->rating, 1) }}</span>
                                                            </div>
                                                            <div class="vr"></div>
                                                            <div class="flex-grow-1">
                                                                <p class="mb-0"><strong>{{ $review->user_name ?? ($review->user ? $review->user->first_name . ' ' . $review->user->last_name : 'Anonymous') }}</strong></p>
                                                            </div>
                                                            <div class="flex-shrink-0">
                                                                <span class="text-muted fs-13 mb-0">{{ $review->created_at->format('d M, Y') }}</span>
                                                            </div>
                                                            <div class="flex-shrink-0">
                                                                <button type="button" class="badge bg-secondary-subtle text-secondary border-0 edit-review-btn" data-id="{{ $review->id }}" data-bs-toggle="modal" data-bs-target="#editReviewModal">
                                                                    <i class="ph-pencil align-baseline me-1"></i> Edit
                                                                </button>
                                                                <button type="button" class="badge bg-danger-subtle text-danger border-0 delete-review-btn" data-id="{{ $review->id }}">
                                                                    <i class="ph-trash align-baseline"></i>
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <p class="review-desc mb-0">{{ $review->comment }}</p>
                                                        
                                                        @if($review->company_comment)
                                                        <div class="mt-3 p-3 bg-light rounded">
                                                            <div class="d-flex align-items-center mb-2">
                                                                <strong class="text-primary">Company Response</strong>
                                                                <small class="text-muted ms-auto">{{ $review->company_timestamp?->format('d M, Y') }}</small>
                                                            </div>
                                                            <p class="mb-0">{{ $review->company_comment }}</p>
                                                        </div>
                                                        @endif
                                                    </div>
                                                </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                @else
                                <div class="col-12">
                                    <div class="text-center py-5 text-muted">
                                        <i class="bi bi-chat-left-text fs-1"></i>
                                        <p class="mt-3">No reviews yet. Be the first!</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Review Modal -->
<div class="modal fade" id="addReviewModal" tabindex="-1" aria-labelledby="addReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addReviewForm">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rating <span class="text-danger">*</span></label>
                        <div class="rating-input">
                            <div class="d-flex gap-1">
                                @for($i = 1; $i <= 5; $i++)
                                <button type="button" class="btn btn-outline-warning p-0 rating-star" data-rating="{{ $i }}">
                                    <i class="bi bi-star fs-2xl"></i>
                                </button>
                                @endfor
                            </div>
                            <input type="hidden" name="rating" id="rating" value="0">
                            <div class="text-muted mt-1" id="rating-text">Select a rating</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="comment" class="form-label">Review <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="comment" name="comment" rows="4" placeholder="Write your review..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="user_name" class="form-label">Your Name</label>
                        <input type="text" class="form-control" id="user_name" name="user_name" placeholder="Enter your name">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Review Modal -->
<div class="modal fade" id="editReviewModal" tabindex="-1" aria-labelledby="editReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editReviewForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="review_id" id="edit_review_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rating <span class="text-danger">*</span></label>
                        <div class="rating-input">
                            <div class="d-flex gap-1">
                                @for($i = 1; $i <= 5; $i++)
                                <button type="button" class="btn btn-outline-warning p-0 edit-rating-star" data-rating="{{ $i }}">
                                    <i class="bi bi-star fs-2xl"></i>
                                </button>
                                @endfor
                            </div>
                            <input type="hidden" name="rating" id="edit_rating" value="0">
                            <div class="text-muted mt-1" id="edit-rating-text">Select a rating</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_comment" class="form-label">Review <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="edit_comment" name="comment" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Company Comment Modal -->
<div class="modal fade" id="companyCommentModal" tabindex="-1" aria-labelledby="companyCommentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Company Response</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="companyCommentForm">
                @csrf
                <input type="hidden" name="review_id" id="company_review_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="company_comment" class="form-label">Company Comment <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="company_comment" name="company_comment" rows="4" placeholder="Enter company response..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Response</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Initialize Swiper Sliders
    let thumbnailSlider, navSlider;
    
    function initSwiper() {
        // Thumbnail navigation slider
        navSlider = new Swiper('.product-nav-slider', {
            slidesPerView: 4,
            spaceBetween: 10,
            freeMode: true,
            watchSlidesProgress: true,
            breakpoints: {
                320: {
                    slidesPerView: 3,
                },
                640: {
                    slidesPerView: 4,
                },
                1024: {
                    slidesPerView: 4,
                }
            }
        });

        // Main image slider
        thumbnailSlider = new Swiper('.product-thumbnail-slider', {
            spaceBetween: 10,
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            thumbs: {
                swiper: navSlider
            }
        });
        
        console.log('Swiper initialized successfully');
    }
    
    // Initialize swiper when DOM is loaded
    initSwiper();

    // Rating stars functionality
    function setupRatingStars(starClass, ratingInputId, textElementId) {
        const stars = document.querySelectorAll(`.${starClass}`);
        const ratingInput = document.getElementById(ratingInputId);
        const ratingText = document.getElementById(textElementId);
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                ratingInput.value = rating;
                
                // Update stars display
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.querySelector('i').className = 'bi bi-star-fill fs-2xl text-warning';
                        s.classList.remove('btn-outline-warning');
                        s.classList.add('btn-warning');
                    } else {
                        s.querySelector('i').className = 'bi bi-star fs-2xl';
                        s.classList.remove('btn-warning');
                        s.classList.add('btn-outline-warning');
                    }
                });
                
                // Update rating text
                const ratingTexts = ['Select a rating', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
                ratingText.textContent = ratingTexts[rating];
            });
        });
    }

    // Setup rating stars for add review
    setupRatingStars('rating-star', 'rating', 'rating-text');
    
    // Setup rating stars for edit review
    setupRatingStars('edit-rating-star', 'edit_rating', 'edit-rating-text');

    // Add review form submission
    document.getElementById('addReviewForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
        
        fetch(`/products/{{ $product->id }}/reviews`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                throw new Error(data.message || 'Failed to submit review');
            }
        })
        .catch(error => {
            Swal.fire('Error!', error.message, 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });

    // Edit review button click
    document.querySelectorAll('.edit-review-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const reviewId = this.dataset.id;
            
            fetch(`/reviews/${reviewId}/edit`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_review_id').value = data.id;
                    document.getElementById('edit_comment').value = data.comment;
                    document.getElementById('edit_rating').value = data.rating;
                    
                    // Update stars
                    const stars = document.querySelectorAll('.edit-rating-star');
                    stars.forEach((star, index) => {
                        if (index < data.rating) {
                            star.querySelector('i').className = 'bi bi-star-fill fs-2xl text-warning';
                            star.classList.remove('btn-outline-warning');
                            star.classList.add('btn-warning');
                        } else {
                            star.querySelector('i').className = 'bi bi-star fs-2xl';
                            star.classList.remove('btn-warning');
                            star.classList.add('btn-outline-warning');
                        }
                    });
                    
                    // Update rating text
                    const ratingTexts = ['Select a rating', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
                    document.getElementById('edit-rating-text').textContent = ratingTexts[data.rating];
                })
                .catch(error => {
                    console.error('Error loading review:', error);
                    Swal.fire('Error!', 'Failed to load review data', 'error');
                });
        });
    });

    // Edit review form submission
    document.getElementById('editReviewForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const reviewId = document.getElementById('edit_review_id').value;
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
        
        fetch(`/reviews/${reviewId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                throw new Error(data.message || 'Failed to update review');
            }
        })
        .catch(error => {
            Swal.fire('Error!', error.message, 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });

    // Delete review button click
    document.querySelectorAll('.delete-review-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const reviewId = this.dataset.id;
            
            Swal.fire({
                title: 'Delete Review?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/reviews/${reviewId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Deleted!', data.message, 'success')
                                .then(() => location.reload());
                        } else {
                            throw new Error(data.message || 'Failed to delete review');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error!', error.message, 'error');
                    });
                }
            });
        });
    });

    // Delete product function
    window.deleteProduct = function(id) {
        Swal.fire({
            title: 'Delete Product?',
            text: "This will delete the product and all its data!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/products/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Deleted!', data.message, 'success')
                            .then(() => window.location.href = "{{ route('products.index') }}");
                    } else {
                        throw new Error(data.message || 'Failed to delete product');
                    }
                })
                .catch(error => {
                    Swal.fire('Error!', error.message, 'error');
                });
            }
        });
    };

    // Edit product from show page
    window.editProductFromShow = function(id) {
        localStorage.setItem('editProductId', id);
    };

    // Reset add review form when modal is closed
    document.getElementById('addReviewModal')?.addEventListener('hidden.bs.modal', function() {
        document.getElementById('addReviewForm').reset();
        document.getElementById('rating').value = 0;
        document.getElementById('rating-text').textContent = 'Select a rating';
        
        // Reset stars
        document.querySelectorAll('.rating-star').forEach(star => {
            star.querySelector('i').className = 'bi bi-star fs-2xl';
            star.classList.remove('btn-warning');
            star.classList.add('btn-outline-warning');
        });
    });

    // Reset edit review form when modal is closed
    document.getElementById('editReviewModal')?.addEventListener('hidden.bs.modal', function() {
        document.getElementById('editReviewForm').reset();
        document.getElementById('edit_rating').value = 0;
        document.getElementById('edit-rating-text').textContent = 'Select a rating';
        
        // Reset stars
        document.querySelectorAll('.edit-rating-star').forEach(star => {
            star.querySelector('i').className = 'bi bi-star fs-2xl';
            star.classList.remove('btn-warning');
            star.classList.add('btn-outline-warning');
        });
    });

    // Update swiper when images load (if any images are loaded async)
    window.addEventListener('load', function() {
        if (thumbnailSlider) {
            thumbnailSlider.update();
        }
        if (navSlider) {
            navSlider.update();
        }
    });
});
</script>

@endsection