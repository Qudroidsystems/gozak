{{-- resources/views/products/show.blade.php --}}
@extends('layouts.master')

@section('title', $product->title . ' - Product Details')

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
<link rel="stylesheet" href="https://unpkg.com/dropzone@6/dist/dropzone.css">
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

                                    <!-- Stock -->
                                    <div class="mb-4">
                                        <span class="badge {{ $product->stock > 10 ? 'bg-success' : ($product->stock > 0 ? 'bg-warning' : 'bg-danger') }}">
                                            @if($product->stock > 10)
                                                In Stock ({{ $product->stock }} left)
                                            @elseif($product->stock > 0)
                                                Low Stock (Only {{ $product->stock }} left!)
                                            @else
                                                Out of Stock
                                            @endif
                                        </span>
                                    </div>

                                    <!-- Description -->
                                    @if($product->description)
                                        <div class="mt-4">
                                            <h5 class="fs-18 mb-3">Description</h5>
                                            <p class="text-muted">{!! nl2br(e($product->description)) !!}</p>
                                        </div>
                                    @endif
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
                                                    <h5>{{ $product->reviews->count() }}</h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 d-flex gap-2">
                                        @can('Update product')
                                            <button type="button" class="btn btn-primary w-100 edit-item-btn" data-id="{{ $product->id }}" data-bs-toggle="modal" data-bs-target="#showModal">
                                                <i class="ph-pencil me-1"></i> Edit Product
                                            </button>
                                        @endcan
                                        @can('Delete product')
                                            <button type="button" class="btn btn-danger w-100" onclick="deleteProduct({{ $product->id }})">
                                                <i class="ph-trash me-1"></i> Delete
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reviews Section -->
                    <div class="card mt-4">
                        <div class="card-header d-flex flex-wrap align-items-center gap-3 mb-2">
                            <h6 class="card-title flex-grow-1 mb-0">
                                Ratings & Reviews ({{ $product->reviews->count() }})
                            </h6>
                            @if($product->reviews->count() > 0)
                                <div class="text-warning hstack gap-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="bi {{ $i <= round($product->reviews->avg('rating')) ? 'bi-star-fill' : 'bi-star' }}"></i>
                                    @endfor
                                    <span class="ms-2 text-muted">{{ number_format($product->reviews->avg('rating'), 1) }}/5.0</span>
                                </div>
                            @endif
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReview">
                                <i class="ph-plus-circle align-middle me-1"></i> Add Review
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="me-lg-n3 pe-lg-4" data-simplebar style="max-height: 500px;">
                                <ul class="list-unstyled mb-0" id="review-list">
                                    @forelse($product->reviews as $review)
                                        <li class="review-list py-2" id="review-{{ $review->id }}">
                                            <div class="border border-dashed rounded p-3">
                                                <div class="hstack flex-wrap gap-3 mb-4">
                                                    <div class="badge rounded-pill bg-danger-subtle text-danger mb-0">
                                                        <i class="mdi mdi-star"></i> <span class="rate-num">{{ number_format($review->rating, 1) }}</span>
                                                    </div>
                                                    <div class="vr"></div>
                                                    <div class="flex-grow-1">
                                                        <p class="mb-0"><a href="#!">{{ $review->user_name ?? 'Anonymous' }}</a></p>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <span class="text-muted fs-13 mb-0">{{ $review->created_at->format('d M, Y') }}</span>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <a href="#addReview" class="badge bg-secondary-subtle text-secondary edit-item-list" data-bs-toggle="modal">
                                                            <i class="ph-pencil align-baseline me-1"></i> Edit
                                                        </a>
                                                        <a href="#removeItemModal" class="badge bg-danger-subtle text-danger" data-bs-toggle="modal">
                                                            <i class="ph-trash align-baseline"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                                <h6 class="review-title fs-md">{{ $review->comment_title ?? 'Great Product!' }}</h6>
                                                <p class="review-desc mb-0">{{ $review->comment }}</p>
                                                @if($review->images?->count())
                                                    <div class="d-flex flex-grow-1 gap-2 review-gallery-img mt-3">
                                                        @foreach($review->images as $img)
                                                            <a href="{{ asset('storage/' . $img->path) }}" class="avatar-md">
                                                                <div class="avatar-title bg-light rounded">
                                                                    <img src="{{ asset('storage/' . $img->path) }}" class="product-img avatar-sm" alt="">
                                                                </div>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @endif
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
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Review Modal -->
<div class="modal fade" id="addReview" tabindex="-1" aria-labelledby="addReviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form class="review-form">
                @csrf
                <input type="hidden" id="id-field">
                <div class="modal-header">
                    <h5 class="modal-title">Write a Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" id="review-close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="alert-error-msg"></div>

                    <div class="mb-3">
                        <label class="form-label">Rating <span class="text-danger">*</span></label>
                        <div id="basic-rater"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Review Title</label>
                        <input type="text" class="form-control" id="reviewTitle-input" placeholder="Enter title">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Your Review <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reviewDesc-input" rows="4" placeholder="Write your review..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Add Photos (Optional)</label>
                        <form action="#" class="dropzone border-dashed">
                            <div class="fallback">
                                <input name="file" type="file" multiple>
                            </div>
                            <div class="dz-message needsclick">
                                <div class="mb-3">
                                    <i class="display-4 text-muted ri-upload-cloud-2-fill"></i>
                                </div>
                                <h5>Drop images here or click to upload.</h5>
                            </div>
                        </form>
                        <ul class="list-unstyled mb-0" id="dropzone-preview"></ul>
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

<!-- Delete Review Modal -->
<div class="modal fade" id="removeItemModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <i class="bi bi-trash text-danger display-4"></i>
                <h4 class="mt-4">Delete Review?</h4>
                <p class="text-muted">This action cannot be undone.</p>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="remove-product">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="https://unpkg.com/dropzone@6/dist/dropzone-min.js"></script>
<script src="https://unpkg.com/rater-js@1.0.1/lib/rater-js.min.js"></script>

<script>

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

        // Dropzone: Review Image Upload
        let previewTemplate, dropzone;
        const dropzonePreviewNode = document.querySelector("#dropzone-preview-list");
        let editList = false;

        if (dropzonePreviewNode) {
            previewTemplate = dropzonePreviewNode.parentNode.innerHTML;
            dropzonePreviewNode.parentNode.removeChild(dropzonePreviewNode);

            dropzone = new Dropzone(".dropzone", {
                url: "https://httpbin.org/post", // Change to your actual endpoint later
                method: "post",
                previewTemplate: previewTemplate,
                previewsContainer: "#dropzone-preview",
                clickable: ".file-upload",
                maxFiles: 5,
                maxFilesize: 5, // MB
                acceptedFiles: "image/*",
            });
        }

        // Rater.js: Star Rating
        let basicRating;
        const ratingElement = document.querySelector("#basic-rater");
        if (ratingElement) {
            basicRating = raterJs({
                starSize: 22,
                rating: 0,
                step: 0.5,
                element: ratingElement,
                rateCallback: function (rating, done) {
                    this.setRating(rating);
                    done();
                }
            });
        }

        // Review Form Elements
        const reviewTitleInput = document.getElementById("reviewTitle-input");
        const reviewDescInput = document.getElementById("reviewDesc-input");
        const userNameVal = document.querySelector(".user-name-text")?.innerHTML || "You";
        const currentDate = new Date().toUTCString().slice(5, 16);

        // Clear form fields
        function clearFields() {
            if (basicRating) {
                basicRating.setRating(0);
                ratingElement.removeAttribute("data-rating");
            }
            if (reviewTitleInput) reviewTitleInput.value = "";
            if (reviewDescInput) reviewDescInput.value = "";
            if (dropzone) dropzone.removeAllFiles();
            document.getElementById("dropzone-preview").innerHTML = "";
            editList = false;
        }

        // Submit Review (Add or Edit)
        document.querySelectorAll(".review-form").forEach(form => {
            form.addEventListener("submit", function (e) {
                e.preventDefault();

                const rating = ratingElement?.getAttribute("data-rating") || 0;
                const errorMsg = document.getElementById("alert-error-msg");

                if (!rating || rating == 0) {
                    errorMsg.innerHTML = "Please select a rating";
                    errorMsg.classList.remove("d-none");
                    setTimeout(() => errorMsg.classList.add("d-none"), 3000);
                    return false;
                }

                if (!reviewTitleInput.value.trim()) {
                    errorMsg.innerHTML = "Please enter a review title";
                    errorMsg.classList.remove("d-none");
                    setTimeout(() => errorMsg.classList.add("d-none"), 3000);
                    return false;
                }

                if (!reviewDescInput.value.trim()) {
                    errorMsg.innerHTML = "Please enter a review description";
                    errorMsg.classList.remove("d-none");
                    setTimeout(() => errorMsg.classList.add("d-none"), 3000);
                    return false;
                }

                // Build gallery HTML from uploaded images
                let galleryHTML = '<div class="d-flex flex-grow-1 gap-2 review-gallery-img mt-3">';
                document.querySelectorAll("#dropzone-preview .dz-image-preview").forEach(preview => {
                    const img = preview.querySelector("[data-dz-thumbnail]");
                    const name = preview.querySelector("[data-dz-name]")?.innerHTML || "image.jpg";
                    galleryHTML += `
                        <a href="${img.src}" class="avatar-md">
                            <div class="avatar-title bg-light rounded">
                                <img src="${img.src}" alt="${name}" class="product-img avatar-sm">
                            </div>
                        </a>`;
                });
                galleryHTML += '</div>';

                const reviewId = editList ? document.getElementById("id-field").value : "review-" + Math.floor(Math.random() * 10000);

                const reviewHTML = `
                    <li class="review-list py-2" id="${reviewId}">
                        <div class="border border-dashed rounded p-3">
                            <div class="hstack flex-wrap gap-3 mb-4">
                                <div class="badge rounded-pill bg-danger-subtle text-danger mb-0">
                                    <i class="mdi mdi-star"></i> <span class="rate-num">${parseFloat(rating).toFixed(1)}</span>
                                </div>
                                <div class="vr"></div>
                                <div class="flex-grow-1">
                                    <p class="mb-0"><a href="#!">${userNameVal}</a></p>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="text-muted fs-13 mb-0">${currentDate}</span>
                                </div>
                                <div class="flex-shrink-0">
                                    <a href="#addReview" class="badge bg-secondary-subtle text-secondary edit-item-list" data-bs-toggle="modal">
                                        <i class="ph-pencil align-baseline me-1"></i> Edit
                                    </a>
                                    <a href="#removeItemModal" class="badge bg-danger-subtle text-danger" data-bs-toggle="modal">
                                        <i class="ph-trash align-baseline"></i>
                                    </a>
                                </div>
                            </div>
                            <h6 class="review-title fs-md">${reviewTitleInput.value}</h6>
                            <p class="review-desc mb-0">${reviewDescInput.value}</p>
                            ${galleryHTML}
                        </div>
                    </li>`;

                if (!editList) {
                    // Add new review
                    document.getElementById("review-list").insertAdjacentHTML("afterbegin", reviewHTML);
                } else {
                    // Update existing review
                    const reviewEl = document.getElementById(reviewId);
                    reviewEl.querySelector(".rate-num").innerHTML = parseFloat(rating).toFixed(1);
                    reviewEl.querySelector(".review-title").innerHTML = reviewTitleInput.value;
                    reviewEl.querySelector(".review-desc").innerHTML = reviewDescInput.value;
                    reviewEl.querySelector(".review-gallery-img") ? 
                        reviewEl.querySelector(".review-gallery-img").outerHTML = galleryHTML :
                        reviewEl.insertAdjacentHTML("beforeend", galleryHTML);
                }

                // Close modal & reset
                document.getElementById("review-close")?.click();
                clearFields();
            });
        });

        // Edit Review
        function setupReviewEditButtons() {
            document.querySelectorAll(".edit-item-list").forEach(btn => {
                btn.addEventListener("click", function () {
                    editList = true;
                    const reviewItem = this.closest(".review-list");
                    const id = reviewItem.id;

                    document.getElementById("id-field").value = id;
                    reviewTitleInput.value = reviewItem.querySelector(".review-title").innerHTML;
                    reviewDescInput.value = reviewItem.querySelector(".review-desc").innerHTML;

                    // Load images into Dropzone
                    if (dropzone) dropzone.removeAllFiles();
                    reviewItem.querySelectorAll(".review-gallery-img img").forEach(img => {
                        const mockFile = { name: "review-image.jpg", size: 12345, accepted: true };
                        dropzone.emit("addedfile", mockFile);
                        dropzone.emit("thumbnail", mockFile, img.src);
                        dropzone.emit("complete", mockFile);
                    });

                    // Set rating
                    const rateNum = reviewItem.querySelector(".rate-num").innerHTML;
                    basicRating.setRating(parseFloat(rateNum));
                });
            });
        }

        // Delete Review
        document.getElementById("removeItemModal")?.addEventListener("show.bs.modal", function (e) {
            document.getElementById("remove-product").onclick = function () {
                e.relatedTarget.closest(".review-list")?.remove();
                document.getElementById("close-modal-review")?.click();
            };
        });

        // Reset form when modal is closed
        document.getElementById("addReview")?.addEventListener("hidden.bs.modal", clearFields);

        // Re-bind edit buttons after adding new review
        document.getElementById("review-list").addEventListener("DOMNodeInserted", setupReviewEditButtons);

        // Initial setup
        setupReviewEditButtons();


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
        autoplay: { delay: 3500, disableOnInteraction: false },
        navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
        thumbs: { swiper: productNavSlider },
    });

    function deleteProduct(id) {
        Swal.fire({
            title: 'Delete Product?',
            text: "This will delete the product and all its data!",
            icon: 'warning',
            showCancelButton: true,
        }).then((result) => {
            if (result.isConfirmed) {
                axios.delete(`/products/${id}`).then(() => {
                    window.location = "{{ route('products.index') }}";
                });
            }
        });
    }
</script>
@endpush
@endsection