{{-- resources/views/products/show.blade.php --}}
@extends('layouts.master')

@section('title', $product->title . ' - Product Details')

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
<link rel="stylesheet" href="https://unpkg.com/dropzone@6/dist/dropzone.css">
<style>
    /* Stock color improvements */
    .bg-success { background-color: #0a3622 !important; }
    .bg-warning { background-color: #664d03 !important; }
    .bg-danger { background-color: #58151c !important; }
    
    /* Swiper custom styles */
    .swiper-slide img {
        max-height: 450px;
        object-fit: contain;
    }
    .product-nav-slider .swiper-slide {
        opacity: 0.5;
        transition: opacity 0.3s;
    }
    .product-nav-slider .swiper-slide-thumb-active {
        opacity: 1;
        border-color: #0d6efd !important;
    }
    
    /* Sticky sidebar */
    .sticky-side-div {
        position: sticky;
        top: 100px;
    }
    
    /* Custom badge colors for better visibility */
    .badge.bg-warning-subtle.text-warning {
        color: #664d03 !important;
        background-color: #fff3cd !important;
        border: 1px solid #ffecb5;
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
                        <h4 class="mb-sm-0">{{ $product->title }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                                <li class="breadcrumb-item active">Product Details</li>
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
                                            <img src="{{ asset('storage/' . $product->thumbnail) }}" alt="{{ $product->title }}" class="img-fluid mx-auto d-block">
                                        </div>
                                    @endif
                                    @foreach($product->images as $img)
                                        <div class="swiper-slide">
                                            <img src="{{ asset('storage/' . $img->image_path) }}" alt="Gallery" class="img-fluid mx-auto d-block">
                                        </div>
                                    @endforeach
                                    @if(!$product->thumbnail && $product->images->isEmpty())
                                        <div class="swiper-slide d-flex align-items-center justify-content-center bg-white" style="height: 450px;">
                                            <i class="bi bi-image fs-1 text-muted"></i>
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
                                            <div class="nav-slide-item border rounded cursor-pointer">
                                                <img src="{{ asset('storage/' . $product->thumbnail) }}" class="img-fluid" alt="">
                                            </div>
                                        </div>
                                    @endif
                                    @foreach($product->images as $img)
                                        <div class="swiper-slide">
                                            <div class="nav-slide-item border rounded cursor-pointer">
                                                <img src="{{ asset('storage/' . $img->image_path) }}" class="img-fluid" alt="">
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
                                        <h4 class="mb-0 text-capitalize">{{ $product->title }}</h4>
                                        @if($product->is_featured)
                                            <span class="badge bg-warning-subtle text-warning fs-6">
                                                <i class="bi bi-star-fill me-1"></i> Featured
                                            </span>
                                        @endif
                                    </div>

                                    <div class="hstack gap-3 flex-wrap mb-4 text-muted">
                                        <div><strong>SKU:</strong> {{ $product->sku }}</div>
                                        <div class="vr"></div>
                                        <div><strong>Brand:</strong> {{ $product->brand?->name ?? 'N/A' }}</div>
                                        <div class="vr"></div>
                                        <div><strong>Category:</strong> {{ $product->category?->name ?? 'Uncategorized' }}</div>
                                    </div>

                                    <!-- Price -->
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

                                    <!-- Stock with clearer colors -->
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

                                    <!-- Description -->
                                    @if($product->description)
                                        <div class="mt-4">
                                            <h5 class="fs-18 mb-3">Description</h5>
                                            <p class="text-muted">{!! nl2br(e($product->description)) !!}</p>
                                        </div>
                                    @endif

                                    <!-- Additional Info -->
                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <h6 class="text-muted mb-2">Product Type</h6>
                                                <p class="fw-semibold">{{ ucfirst($product->product_type ?? 'simple') }}</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <h6 class="text-muted mb-2">Published Date</h6>
                                                <p class="fw-semibold">{{ $product->created_at->format('F d, Y') }}</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <h6 class="text-muted mb-2">Last Updated</h6>
                                                <p class="fw-semibold">{{ $product->updated_at->format('F d, Y') }}</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <h6 class="text-muted mb-2">Product ID</h6>
                                                <p class="fw-semibold">#{{ $product->id }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stats Sidebar -->
                        <div class="col-xxl-4">
                            <div class="card card-height-100 border-start rounded-start-0">
                                <div class="card-body p-4">
                                    <div class="row g-3 text-center">
                                        <div class="col-6">
                                            <div class="card border shadow-none">
                                                <div class="card-body p-3">
                                                    <p class="text-muted mb-1">Total Sold</p>
                                                    <h5 class="mb-0">{{ $product->sold_quantity ?? 0 }}</h5>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="card border shadow-none">
                                                <div class="card-body p-3">
                                                    <p class="text-muted mb-1">Revenue</p>
                                                    <h5>${{ number_format(($product->sold_quantity ?? 0) * ($product->sale_price ?? $product->price), 2) }}</h5>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="card border shadow-none">
                                                <div class="card-body p-3">
                                                    <p class="text-muted mb-1">Stock</p>
                                                    <h5>{{ $product->stock }}</h5>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="card border shadow-none">
                                                <div class="card-body p-3">
                                                    <p class="text-muted mb-1">Reviews</p>
                                                    <h5>{{ $product->reviews_count ?? 0 }}</h5>
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

                    <!-- Reviews Section -->
                    @if($product->reviews->count() > 0)
                    <div class="card mt-4">
                        <div class="card-header d-flex flex-wrap align-items-center gap-3 mb-2">
                            <h6 class="card-title flex-grow-1 mb-0">
                                Ratings & Reviews ({{ $product->reviews->count() }})
                            </h6>
                            <div class="text-warning hstack gap-1">
                                @php
                                    $avgRating = $product->reviews->avg('rating');
                                    $fullStars = floor($avgRating);
                                    $hasHalfStar = ($avgRating - $fullStars) >= 0.5;
                                @endphp
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= $fullStars)
                                        <i class="bi bi-star-fill"></i>
                                    @elseif($hasHalfStar && $i == $fullStars + 1)
                                        <i class="bi bi-star-half"></i>
                                    @else
                                        <i class="bi bi-star"></i>
                                    @endif
                                @endfor
                                <span class="ms-2 text-muted">{{ number_format($avgRating, 1) }}/5.0</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="me-lg-n3 pe-lg-4" data-simplebar style="max-height: 500px;">
                                <ul class="list-unstyled mb-0" id="review-list">
                                    @forelse($product->reviews as $review)
                                        <li class="review-list py-2" id="review-{{ $review->id }}">
                                            <div class="border border-dashed rounded p-3">
                                                <div class="hstack flex-wrap gap-3 mb-4">
                                                    <div class="badge rounded-pill bg-danger-subtle text-danger mb-0">
                                                        <i class="bi bi-star-fill"></i> <span class="rate-num">{{ number_format($review->rating, 1) }}</span>
                                                    </div>
                                                    <div class="vr"></div>
                                                    <div class="flex-grow-1">
                                                        <p class="mb-0"><strong>{{ $review->user->name ?? $review->user_name ?? 'Anonymous' }}</strong></p>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <span class="text-muted fs-13 mb-0">{{ $review->created_at->format('d M, Y') }}</span>
                                                    </div>
                                                </div>
                                                <h6 class="review-title fs-md">{{ $review->comment_title ?? 'Great Product!' }}</h6>
                                                <p class="review-desc mb-0">{{ $review->comment }}</p>
                                            </div>
                                        </li>
                                    @empty
                                        <div class="text-center py-5 text-muted">
                                            <i class="bi bi-chat-left-text fs-1"></i>
                                            <p class="mt-3">No reviews yet. Be the first!</p>
                                        </div>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Swiper: Product Image Gallery
    const productNavSlider = new Swiper(".product-nav-slider", {
        loop: true,
        spaceBetween: 10,
        slidesPerView: 4,
        freeMode: true,
        watchSlidesProgress: true,
    });

    const productThumbnailSlider = new Swiper(".product-thumbnail-slider", {
        loop: true,
        spaceBetween: 24,
        autoplay: {
            delay: 3500,
            disableOnInteraction: false,
        },
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
        thumbs: {
            swiper: productNavSlider,
        },
    });
    
    // Add click event to thumbnail slider to change main image
    document.querySelectorAll('.nav-slide-item').forEach((item, index) => {
        item.addEventListener('click', () => {
            productThumbnailSlider.slideTo(index);
        });
    });
});

function editProductFromShow(id) {
    // This will trigger when user clicks "Edit Product" from show page
    // The modal should open on the index page
    localStorage.setItem('editProductId', id);
}

function deleteProduct(id) {
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
            axios.delete(`/products/${id}`)
                .then(response => {
                    if (response.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.data.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = "{{ route('products.index') }}";
                        });
                    }
                })
                .catch(error => {
                    Swal.fire('Error!', 'Failed to delete product', 'error');
                });
        }
    });
}
</script>
@endpush