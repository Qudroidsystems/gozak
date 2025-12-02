{{-- resources/views/products/show.blade.php --}}
@extends('layouts.master')

@section('title', $product->title . ' - Product Details')


<style>
    .image-slider {
        position: relative;
        overflow: hidden;
    }
    
    .slider-track {
        display: flex;
        transition: transform 0.3s ease-in-out;
    }
    
    .slider-image {
        min-width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .slider-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0, 0, 0, 0.5);
        color: white;
        border: none;
        padding: 12px 16px;
        cursor: pointer;
        z-index: 10;
        transition: background 0.2s;
    }
    
    .slider-btn:hover {
        background: rgba(0, 0, 0, 0.7);
    }
    
    .slider-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }
    
    .slider-btn-prev {
        left: 10px;
    }
    
    .slider-btn-next {
        right: 10px;
    }
    
    .slider-indicators {
        position: absolute;
        bottom: 15px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 8px;
        z-index: 10;
    }
    
    .slider-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
        border: none;
        cursor: pointer;
        padding: 0;
        transition: background 0.2s;
    }
    
    .slider-dot.active {
        background: rgba(255, 255, 255, 1);
    }
    
    .thumbnail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        gap: 10px;
        margin-top: 15px;
    }
    
    .thumbnail-item {
        cursor: pointer;
        border: 2px solid transparent;
        border-radius: 4px;
        overflow: hidden;
        transition: border-color 0.2s;
    }
    
    .thumbnail-item:hover {
        border-color: #ddd;
    }
    
    .thumbnail-item.active {
        border-color: #007bff;
    }
    
    .thumbnail-item img {
        width: 100%;
        height: 80px;
        object-fit: cover;
        display: block;
    }
</style>


@section('content')
<div class="container-fluid">
    <div class="page-header">
        <h4 class="page-title">Product Overview</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Products</a></li>
            <li class="breadcrumb-item active">Product Overview</li>
        </ol>
    </div>

    <div class="row">
        <div class="col-lg-5 col-md-12">
            <div class="card">
                <div class="card-body">
                    {{-- Main Image Slider --}}
                    <div class="image-slider" style="height: 400px; position: relative;">
                        @php
                            $allImages = [];
                            if($product->thumbnail) {
                                $allImages[] = $product->thumbnail;
                            }
                            foreach($product->images as $img) {
                                $allImages[] = $img->image_path;
                            }
                        @endphp

                        @if(count($allImages) > 0)
                            <div class="slider-track" id="sliderTrack">
                                @foreach($allImages as $image)
                                    <img src="{{ asset('storage/' . $image) }}" 
                                         alt="{{ $product->title }}" 
                                         class="slider-image">
                                @endforeach
                            </div>

                            @if(count($allImages) > 1)
                                <button class="slider-btn slider-btn-prev" id="prevBtn">
                                    <i class="fa fa-chevron-left"></i>
                                </button>
                                <button class="slider-btn slider-btn-next" id="nextBtn">
                                    <i class="fa fa-chevron-right"></i>
                                </button>

                                <div class="slider-indicators" id="sliderIndicators">
                                    @foreach($allImages as $index => $image)
                                        <button class="slider-dot {{ $index === 0 ? 'active' : '' }}" 
                                                data-index="{{ $index }}"></button>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <div class="d-flex align-items-center justify-content-center" style="height: 100%; background: #f5f5f5;">
                                <div class="text-center">
                                    <i class="fa fa-image fa-5x text-muted mb-3"></i>
                                    <p class="text-muted">No image available</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Thumbnail Grid --}}
                    @if(count($allImages) > 1)
                        <div class="thumbnail-grid">
                            @foreach($allImages as $index => $image)
                                <div class="thumbnail-item {{ $index === 0 ? 'active' : '' }}" 
                                     data-index="{{ $index }}">
                                    <img src="{{ asset('storage/' . $image) }}" 
                                         alt="{{ $product->title }}">
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7 col-md-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="mb-3">{{ $product->title }}</h3>

                    <div class="d-flex align-items-center mb-3 flex-wrap">
                        @if($product->is_featured)
                            <span class="badge badge-success mr-2">Featured</span>
                        @endif
                        <span class="text-muted mr-3">
                            <i class="fa fa-shopping-cart"></i> {{ $totalSold ?? 0 }} Sold
                        </span>
                        <span class="text-muted mr-3">
                            <i class="fa fa-star text-warning"></i> {{ $product->reviews->count() ?? 0 }} Reviews
                        </span>
                        <span class="text-muted">
                            <i class="fa fa-calendar"></i> Published : {{ $product->created_at->format('d M, Y') }}
                        </span>
                    </div>

                    <div class="mb-4">
                        @if($product->sale_price)
                            <h2 class="text-primary mb-2">
                                ${{ number_format($product->sale_price, 2) }}
                                <small class="text-muted"><del>${{ number_format($product->price, 2) }}</del></small>
                            </h2>
                            <span class="badge badge-danger">
                                {{ round((($product->price - $product->sale_price) / $product->price) * 100) }}% OFF
                            </span>
                        @else
                            <h2 class="text-primary mb-2">${{ number_format($product->price, 2) }}</h2>
                        @endif
                    </div>

                    <div class="mb-4">
                        @if($product->stock > 10)
                            <span class="badge badge-success">In Stock ({{ $product->stock }} left)</span>
                        @elseif($product->stock > 0)
                            <span class="badge badge-warning">Low Stock (Only {{ $product->stock }} left!)</span>
                        @else
                            <span class="badge badge-danger">Out of Stock</span>
                        @endif
                    </div>

                    <table class="table table-borderless">
                        <tr>
                            <td width="150"><strong>Product Type:</strong></td>
                            <td>{{ ucfirst($product->product_type ?? 'simple') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Brand:</strong></td>
                            <td>{{ $product->brand?->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Category:</strong></td>
                            <td>{{ $product->category?->name ?? 'Uncategorized' }}</td>
                        </tr>
                        <tr>
                            <td><strong>SKU:</strong></td>
                            <td>{{ $product->sku }}</td>
                        </tr>
                    </table>

                    @if($product->description)
                        <div class="mt-4">
                            <h5>Description:</h5>
                            <p class="text-muted">{{ $product->description }}</p>
                        </div>
                    @endif

                    @if($product->sale_price && $product->price > $product->sale_price)
                        <div class="alert alert-info mt-4">
                            <strong>{{ round((($product->price - $product->sale_price) / $product->price) * 100) }}% Off Sale Active</strong>
                            <p class="mb-0">Save ${{ number_format($product->price - $product->sale_price, 2) }}</p>
                        </div>
                    @endif

                    <div class="row mt-4">
                        <div class="col-6 col-md-3 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-1">PRICE</h6>
                                    <h4>${{ number_format($product->sale_price ?? $product->price, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-1">No. of Orders</h6>
                                    <h4>{{ $totalSold ?? 0 }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-1">Available Stocks</h6>
                                    <h4>{{ $product->stock }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-1">Total Revenue</h6>
                                    <h4>${{ number_format($revenue, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        @can('Update product')
                            <a href="{{ route('products.edit', $product->id) }}" class="btn btn-primary">
                                <i class="fa fa-edit"></i> Edit Product
                            </a>
                        @endcan
                        @can('Delete product')
                            <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">
                                    <i class="fa fa-trash"></i> Delete
                                </button>
                            </form>
                        @endcan
                        <a href="{{ route('products.index') }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back to Products
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Product Details Table --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Product Details:</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th width="200">Type</th>
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
                                    <td>${{ number_format($product->sale_price, 2) }}</td>
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
                                            <span class="badge badge-success">Yes</span>
                                        @else
                                            <span class="badge badge-secondary">No</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Reviews Section --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Ratings & Reviews ({{ $product->reviews->count() ?? 0 }})</h4>
                </div>
                <div class="card-body">
                    @if($product->reviews->count() > 0)
                        <div class="mb-4">
                            @php
                                $averageRating = $product->reviews->avg('rating');
                            @endphp
                            <div class="d-flex align-items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= floor($averageRating))
                                        <i class="fa fa-star text-warning"></i>
                                    @elseif($i == ceil($averageRating) && fmod($averageRating, 1) >= 0.5)
                                        <i class="fa fa-star-half-alt text-warning"></i>
                                    @else
                                        <i class="fa fa-star text-muted"></i>
                                    @endif
                                @endfor
                                <span class="ml-2"><strong>{{ number_format($averageRating, 1) }}/5.0</strong></span>
                                <span class="ml-2 text-muted">({{ $product->reviews->count() }} Reviews)</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <button class="btn btn-sm btn-outline-primary active" data-filter="all">All Reviews</button>
                            <button class="btn btn-sm btn-outline-primary" data-filter="5">5 Stars</button>
                            <button class="btn btn-sm btn-outline-primary" data-filter="4">4 Stars</button>
                            <button class="btn btn-sm btn-outline-primary" data-filter="3">3 Stars</button>
                            <button class="btn btn-sm btn-outline-primary" data-filter="2">2 Stars</button>
                            <button class="btn btn-sm btn-outline-primary" data-filter="1">1 Star</button>
                        </div>

                        <button class="btn btn-primary mb-4" data-toggle="modal" data-target="#addReviewModal">
                            <i class="fa fa-plus"></i> Add Review
                        </button>

                        @foreach($product->reviews as $review)
                            <div class="review-item border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="mb-1">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= $review->rating)
                                                    <i class="fa fa-star text-warning"></i>
                                                @else
                                                    <i class="fa fa-star text-muted"></i>
                                                @endif
                                            @endfor
                                            <span class="ml-2">{{ number_format($review->rating, 1) }}</span>
                                        </div>
                                        <p class="mb-1"><strong>{{ $review->user_name ?? ($review->user ? $review->user->first_name . ' ' . $review->user->last_name : 'Anonymous') }}</strong></p>
                                        <p class="text-muted mb-2"><small>{{ $review->created_at->format('d M, Y') }}</small></p>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="editReview({{ $review->id }}, {{ $review->rating }}, '{{ addslashes($review->comment) }}')">
                                        <i class="fa fa-edit"></i> Edit
                                    </button>
                                </div>
                                <p>{{ $review->comment }}</p>

                                @if($review->company_comment)
                                    <div class="alert alert-light mt-2">
                                        <strong>Company Response</strong>
                                        <small class="text-muted d-block">{{ $review->company_timestamp?->format('d M, Y') }}</small>
                                        <p class="mb-0 mt-2">{{ $review->company_comment }}</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-5">
                            <i class="fa fa-star fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No reviews yet. Be the first!</p>
                            <button class="btn btn-primary" data-toggle="modal" data-target="#addReviewModal">
                                <i class="fa fa-plus"></i> Add Review
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Review Modal --}}
<div class="modal fade" id="addReviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Review</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('reviews.store', $product->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Rating *</label>
                        <div class="rating-input">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fa fa-star rating-star" data-rating="{{ $i }}"></i>
                            @endfor
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" required>
                        <small class="text-danger">Select a rating</small>
                    </div>
                    <div class="form-group">
                        <label>Review *</label>
                        <textarea name="comment" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Your Name</label>
                        <input type="text" name="user_name" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Review Modal --}}
<div class="modal fade" id="editReviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Review</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="editReviewForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label>Rating *</label>
                        <div class="rating-input" id="editRatingInput">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fa fa-star rating-star" data-rating="{{ $i }}"></i>
                            @endfor
                        </div>
                        <input type="hidden" name="rating" id="editRatingValue" required>
                        <small class="text-danger">Select a rating</small>
                    </div>
                    <div class="form-group">
                        <label>Review *</label>
                        <textarea name="comment" id="editComment" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add Company Response Modal --}}
<div class="modal fade" id="companyResponseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Company Response</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="#" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Company Comment *</label>
                        <textarea name="company_comment" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Response</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Image Slider Functionality
    const sliderTrack = document.getElementById('sliderTrack');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const indicators = document.querySelectorAll('.slider-dot');
    const thumbnails = document.querySelectorAll('.thumbnail-item');
    
    if (sliderTrack) {
        const slides = sliderTrack.querySelectorAll('.slider-image');
        let currentIndex = 0;
        const totalSlides = slides.length;

        function updateSlider() {
            const offset = -currentIndex * 100;
            sliderTrack.style.transform = `translateX(${offset}%)`;
            
            // Update indicators
            indicators.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentIndex);
            });
            
            // Update thumbnails
            thumbnails.forEach((thumb, index) => {
                thumb.classList.toggle('active', index === currentIndex);
            });
            
            // Update button states
            if (prevBtn) prevBtn.disabled = currentIndex === 0;
            if (nextBtn) nextBtn.disabled = currentIndex === totalSlides - 1;
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                if (currentIndex > 0) {
                    currentIndex--;
                    updateSlider();
                }
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                if (currentIndex < totalSlides - 1) {
                    currentIndex++;
                    updateSlider();
                }
            });
        }

        // Indicator click handlers
        indicators.forEach((dot, index) => {
            dot.addEventListener('click', function() {
                currentIndex = index;
                updateSlider();
            });
        });

        // Thumbnail click handlers
        thumbnails.forEach((thumb, index) => {
            thumb.addEventListener('click', function() {
                currentIndex = index;
                updateSlider();
            });
        });

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft' && currentIndex > 0) {
                currentIndex--;
                updateSlider();
            } else if (e.key === 'ArrowRight' && currentIndex < totalSlides - 1) {
                currentIndex++;
                updateSlider();
            }
        });

        // Touch/swipe support
        let touchStartX = 0;
        let touchEndX = 0;

        sliderTrack.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        });

        sliderTrack.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });

        function handleSwipe() {
            if (touchEndX < touchStartX - 50 && currentIndex < totalSlides - 1) {
                currentIndex++;
                updateSlider();
            }
            if (touchEndX > touchStartX + 50 && currentIndex > 0) {
                currentIndex--;
                updateSlider();
            }
        }

        // Initialize
        updateSlider();
    }

    // Rating Stars
    document.querySelectorAll('.rating-star').forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.getAttribute('data-rating');
            const container = this.parentElement;
            const input = container.parentElement.querySelector('input[type="hidden"]');
            
            if (input) {
                input.value = rating;
            }
            
            container.querySelectorAll('.rating-star').forEach((s, index) => {
                if (index < rating) {
                    s.classList.add('text-warning');
                    s.classList.remove('text-muted');
                } else {
                    s.classList.remove('text-warning');
                    s.classList.add('text-muted');
                }
            });
        });
    });
});

// Edit Review Function
function editReview(reviewId, rating, comment) {
    const form = document.getElementById('editReviewForm');
    form.action = `/reviews/${reviewId}`;
    
    document.getElementById('editRatingValue').value = rating;
    document.getElementById('editComment').value = comment;
    
    // Update rating stars
    const stars = document.querySelectorAll('#editRatingInput .rating-star');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('text-warning');
            star.classList.remove('text-muted');
        } else {
            star.classList.remove('text-warning');
            star.classList.add('text-muted');
        }
    });
    
    $('#editReviewModal').modal('show');
}
</script>

@endsection