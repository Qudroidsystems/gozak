{{-- resources/views/promo_banners/index.blade.php --}}
@extends('layouts.master')

@section('title', 'Promo Banners Management')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            {{-- ─── Page Title ─────────────────────────────────────────────────────── --}}
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle ?? 'Promo Banners' }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0)">Marketing</a></li>
                                <li class="breadcrumb-item active">Promo Banners</li>
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
                                    <p class="text-uppercase fw-medium text-primary mb-0">Total Banners</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ number_format($analytics['total'] ?? 0) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-primary rounded-circle fs-3">
                                        <i class="bi bi-images"></i>
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
                                    <p class="text-uppercase fw-medium text-success mb-0">Active</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ number_format($analytics['active'] ?? 0) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-success rounded-circle fs-3">
                                        <i class="bi bi-check-circle"></i>
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
                                    <p class="text-uppercase fw-medium text-warning mb-0">Scheduled</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ number_format($analytics['scheduled'] ?? 0) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-warning rounded-circle fs-3">
                                        <i class="bi bi-calendar-event"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-danger-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-danger mb-0">Expired</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ number_format($analytics['expired'] ?? 0) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-danger rounded-circle fs-3">
                                        <i class="bi bi-clock-history"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── Banner Table ──────────────────────────────────────────────────── --}}
            <div id="bannerList" class="mt-4">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">
                                        Promo Banners <span class="badge bg-dark-subtle text-dark ms-1" id="totalBanners">{{ $banners->total() }}</span>
                                    </h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        <!-- Bulk Actions Dropdown -->
                                        <div class="dropdown me-2" id="bulkActionsDropdown" style="display: none;">
                                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                Bulk Actions (<span id="selectedCount">0</span>)
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item bulk-action" href="#" data-action="activate">Activate</a></li>
                                                <li><a class="dropdown-item bulk-action" href="#" data-action="deactivate">Deactivate</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item bulk-action text-danger" href="#" data-action="delete">Delete Selected</a></li>
                                            </ul>
                                        </div>

                                        <div class="input-group input-group-sm me-2" style="width: 250px;">
                                            <input type="text" class="form-control" id="searchInput" placeholder="Search banners..." value="{{ request('search', '') }}">
                                            <button class="btn btn-outline-secondary" type="button" id="searchButton"><i class="bi bi-search"></i></button>
                                            <button class="btn btn-outline-secondary" type="button" id="clearSearch" style="display: {{ request('search') ? 'inline-block' : 'none' }};"><i class="bi bi-x"></i></button>
                                        </div>
                                        @can('Create promo_banner')
                                            <button type="button" class="btn btn-primary add-btn" onclick="resetForm()">
                                                <i class="bi bi-plus-lg me-1"></i> Add Banner
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            </div>

                            {{-- Advanced Filters --}}
                            <div class="card-body border-bottom">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Search</label>
                                        <input type="text" class="form-control" id="searchInput2" placeholder="Title, Badge..." value="{{ request('search', '') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Screen</label>
                                        <select class="form-control" id="screenFilter">
                                            <option value="">All Screens</option>
                                            <option value="all" {{ request('screen') == 'all' ? 'selected' : '' }}>All Pages</option>
                                            <option value="home" {{ request('screen') == 'home' ? 'selected' : '' }}>Home</option>
                                            <option value="category" {{ request('screen') == 'category' ? 'selected' : '' }}>Category</option>
                                            <option value="product" {{ request('screen') == 'product' ? 'selected' : '' }}>Product</option>
                                            <option value="offers" {{ request('screen') == 'offers' ? 'selected' : '' }}>Offers</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Status</label>
                                        <select class="form-control" id="statusFilter">
                                            <option value="">All Status</option>
                                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                            <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Sort By</label>
                                        <select class="form-control" id="sortFilter">
                                            <option value="sort_order" {{ request('sort') == 'sort_order' ? 'selected' : '' }}>Sort Order</option>
                                            <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Created Date</option>
                                            <option value="starts_at" {{ request('sort') == 'starts_at' ? 'selected' : '' }}>Start Date</option>
                                            <option value="title" {{ request('sort') == 'title' ? 'selected' : '' }}>Title</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Order</label>
                                        <select class="form-control" id="orderFilter">
                                            <option value="asc" {{ request('order') == 'asc' ? 'selected' : '' }}>Ascending</option>
                                            <option value="desc" {{ request('order') == 'desc' ? 'selected' : '' }}>Descending</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end gap-2">
                                        <button type="button" class="btn btn-primary w-100" id="applyFilter">
                                            <i class="bi bi-funnel"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="clearFilters"
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
                                        @if(request('screen'))
                                            <span class="badge bg-primary-subtle text-primary">Screen: {{ ucfirst(request('screen')) }} <button type="button" class="btn-close btn-close-sm ms-1" onclick="removeFilter('screen')"></button></span>
                                        @endif
                                        @if(request('status'))
                                            <span class="badge bg-primary-subtle text-primary">Status: {{ ucfirst(request('status')) }} <button type="button" class="btn-close btn-close-sm ms-1" onclick="removeFilter('status')"></button></span>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0">
                                        <thead class="table-active">
                                            <tr>
                                                <th style="width: 50px;">
                                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                                </th>
                                                <th style="width: 60px;">#</th>
                                                <th>Preview</th>
                                                <th>Badge / Title</th>
                                                <th>Screen</th>
                                                <th>Schedule</th>
                                                <th>Status</th>
                                                <th>Sort Order</th>
                                                <th style="width: 120px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="sortable-body">
                                            @forelse($banners as $banner)
                                            <tr class="sortable-row" data-id="{{ $banner->id }}">
                                                <td class="text-center">
                                                    <input type="checkbox" class="row-select form-check-input" value="{{ $banner->id }}">
                                                </td>
                                                <td class="fw-medium text-muted">{{ $loop->iteration }}</td>
                                                <td>
                                                    @if($banner->image_url)
                                                        <img src="{{ $banner->full_image_url }}" alt="{{ $banner->title }}"
                                                             class="img-fluid rounded" style="width: 120px; height: 67px; object-fit: cover;">
                                                    @else
                                                        <div class="banner-card-preview"
                                                             style="background: linear-gradient(135deg, {{ $banner->gradient_start }}, {{ $banner->gradient_end }});
                                                                    width: 120px; height: 67px; border-radius: 8px; display: flex;
                                                                    align-items: center; justify-content: center; font-size: 10px;
                                                                    padding: 4px; text-align: center; color: #fff;">
                                                            <span>{{ Str::limit($banner->badge_text, 15) }}</span>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="badge bg-dark-subtle text-dark d-inline-block mb-1" style="font-size: 10px;">
                                                            {{ $banner->badge_text }}
                                                        </span>
                                                        <span class="fw-semibold">{{ Str::limit($banner->title, 40) }}</span>
                                                        <small class="text-muted">{{ Str::limit($banner->subtitle, 50) }}</small>
                                                        <small class="text-primary">
                                                            <i class="bi bi-arrow-right-circle me-1"></i>{{ $banner->cta_text }}
                                                            @if($banner->cta_route)
                                                                <span class="badge bg-info-subtle text-info ms-1">{{ $banner->cta_route }}</span>
                                                            @endif
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info-subtle text-info">
                                                        <i class="bi bi-device-desktop me-1"></i>
                                                        {{ ucfirst($banner->target_screen) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($banner->starts_at || $banner->ends_at)
                                                        <div class="d-flex flex-column">
                                                            <small class="text-muted">
                                                                <i class="bi bi-calendar3 me-1"></i>
                                                                From: {{ $banner->starts_at?->format('d M Y H:i') ?? '—' }}
                                                            </small>
                                                            <small class="text-muted">
                                                                <i class="bi bi-calendar3 me-1"></i>
                                                                To: {{ $banner->ends_at?->format('d M Y H:i') ?? '—' }}
                                                            </small>
                                                            @if($banner->show_once_daily)
                                                                <span class="badge bg-secondary-subtle text-secondary mt-1" style="font-size: 9px;">
                                                                    <i class="bi bi-repeat me-1"></i> Once daily
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-muted small">
                                                            <i class="bi bi-infinity me-1"></i> Always
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $statusClass = 'bg-success-subtle text-success';
                                                        $statusIcon = 'bi-check-circle';
                                                        $statusText = 'Active';

                                                        if (!$banner->active) {
                                                            $statusClass = 'bg-secondary-subtle text-secondary';
                                                            $statusIcon = 'bi-slash-circle';
                                                            $statusText = 'Inactive';
                                                        } elseif ($banner->starts_at && $banner->starts_at > now()) {
                                                            $statusClass = 'bg-warning-subtle text-warning';
                                                            $statusIcon = 'bi-clock';
                                                            $statusText = 'Scheduled';
                                                        } elseif ($banner->ends_at && $banner->ends_at < now()) {
                                                            $statusClass = 'bg-danger-subtle text-danger';
                                                            $statusIcon = 'bi-clock-history';
                                                            $statusText = 'Expired';
                                                        }
                                                    @endphp
                                                    <span class="badge {{ $statusClass }} d-inline-flex align-items-center gap-1">
                                                        <i class="bi {{ $statusIcon }}"></i>
                                                        {{ $statusText }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary-subtle text-secondary">
                                                        {{ $banner->sort_order }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="btn btn-subtle-secondary btn-sm btn-icon" data-bs-toggle="dropdown">
                                                            <i class="bi bi-three-dots-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item view-btn" href="javascript:void(0);"
                                                                   data-id="{{ $banner->id }}"
                                                                   data-badge="{{ $banner->badge_text }}"
                                                                   data-title="{{ $banner->title }}"
                                                                   data-subtitle="{{ $banner->subtitle }}"
                                                                   data-cta-text="{{ $banner->cta_text }}"
                                                                   data-cta-route="{{ $banner->cta_route }}"
                                                                   data-gradient-start="{{ $banner->gradient_start }}"
                                                                   data-gradient-end="{{ $banner->gradient_end }}"
                                                                   data-accent="{{ $banner->accent_color }}"
                                                                   data-screen="{{ $banner->target_screen }}"
                                                                   data-image="{{ $banner->full_image_url }}">
                                                                    <i class="bi bi-eye me-1"></i> View
                                                                </a>
                                                            </li>
                                                            @can('Update promo_banner')
                                                                <li>
                                                                    <a class="dropdown-item edit-btn" href="javascript:void(0);"
                                                                       data-id="{{ $banner->id }}"
                                                                       data-badge="{{ $banner->badge_text }}"
                                                                       data-title="{{ $banner->title }}"
                                                                       data-subtitle="{{ $banner->subtitle }}"
                                                                       data-cta-text="{{ $banner->cta_text }}"
                                                                       data-cta-route="{{ $banner->cta_route }}"
                                                                       data-gradient-start="{{ $banner->gradient_start }}"
                                                                       data-gradient-end="{{ $banner->gradient_end }}"
                                                                       data-accent="{{ $banner->accent_color }}"
                                                                       data-screen="{{ $banner->target_screen }}"
                                                                       data-active="{{ $banner->active ? '1' : '0' }}"
                                                                       data-starts="{{ $banner->starts_at?->format('Y-m-d\TH:i') }}"
                                                                       data-ends="{{ $banner->ends_at?->format('Y-m-d\TH:i') }}"
                                                                       data-image="{{ $banner->full_image_url }}"
                                                                       data-lottie="{{ $banner->lottie_asset }}"
                                                                       data-show-once="{{ $banner->show_once_daily ? '1' : '0' }}"
                                                                       data-sort="{{ $banner->sort_order }}">
                                                                        <i class="bi bi-pencil me-1"></i> Edit
                                                                    </a>
                                                                </li>
                                                            @endcan
                                                            <li>
                                                                <a class="dropdown-item toggle-status-btn" href="javascript:void(0);"
                                                                   data-id="{{ $banner->id }}"
                                                                   data-status="{{ $banner->active ? 'active' : 'inactive' }}">
                                                                    <i class="bi bi-arrow-repeat me-1"></i>
                                                                    {{ $banner->active ? 'Deactivate' : 'Activate' }}
                                                                </a>
                                                            </li>
                                                            @can('Delete promo_banner')
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <a class="dropdown-item text-danger remove-item-btn" href="javascript:void(0);"
                                                                       data-id="{{ $banner->id }}"
                                                                       data-title="{{ $banner->title }}">
                                                                        <i class="bi bi-trash me-1"></i> Delete
                                                                    </a>
                                                                </li>
                                                            @endcan
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr id="noResultsRow">
                                                <td colspan="9" class="text-center py-5 text-muted">
                                                    @if(request()->except('page'))
                                                        No promo banners found matching your filters.<br>
                                                        <a href="{{ route('web.promo-banners.index') }}" class="btn btn-sm btn-outline-primary mt-2">Clear filters</a>
                                                    @else
                                                        <i class="bi bi-images display-1 d-block mb-3 text-muted"></i>
                                                        No promo banners found.
                                                        @can('Create promo_banner')
                                                            <a href="javascript:void(0)" class="text-primary add-btn" onclick="resetForm()">Add your first banner</a>
                                                        @endcan
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
                                            Showing {{ $banners->firstItem() }} to {{ $banners->lastItem() }} of {{ $banners->total() }} Results
                                        </div>
                                    </div>
                                    <div class="col-sm-auto mt-3 mt-sm-0">
                                        {!! $banners->appends(request()->query())->links('pagination::bootstrap-5') !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ─── Add / Edit Banner Modal ───────────────────────────────────────── --}}
<div class="modal fade" id="showModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="bannerForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" id="banner_id">
                <input type="hidden" name="_method" id="form_method" value="POST">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Promo Banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                    <div class="row g-4">

                        {{-- ── Left column: content ── --}}
                        <div class="col-lg-7">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">Banner Content</h6>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Badge Text <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="badge_text" id="f_badge"
                                                   placeholder="⚡ TODAY ONLY" maxlength="80" required>
                                            <small class="text-muted">Short label shown in the top-left pill (emoji + text)</small>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Title <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="title" id="f_title"
                                                   placeholder="Flash Sale — Up to 70% Off" maxlength="120" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Subtitle <span class="text-danger">*</span></label>
                                            <textarea class="form-control" name="subtitle" id="f_subtitle"
                                                      rows="2" maxlength="300" required
                                                      placeholder="Grab the best deals before they're gone."></textarea>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label">CTA Button Text <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="cta_text" id="f_cta_text"
                                                   placeholder="Shop Now" maxlength="60" required>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label">CTA Route <small class="text-muted">(optional)</small></label>
                                            <input type="text" class="form-control" name="cta_route" id="f_cta_route"
                                                   placeholder="all_products">
                                            <small class="text-muted">Named Flutter route the button opens</small>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Banner Image <small class="text-muted">(optional)</small></label>
                                            <input type="file" class="form-control" name="image" id="f_image"
                                                   accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                                            <small class="text-muted">Max 3 MB. If omitted a fallback gradient is shown.</small>
                                            <div class="mt-2 text-center">
                                                <img id="img_preview" class="img-fluid rounded shadow"
                                                     style="max-width:100%;max-height:160px;display:none;" alt="Preview">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Lottie Animation Asset <small class="text-muted">(optional)</small></label>
                                            <input type="text" class="form-control" name="lottie_asset" id="f_lottie"
                                                   placeholder="assets/animations/sale.json">
                                            <small class="text-muted">Path to Lottie animation in Flutter assets</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ── Right column: visual & scheduling ── --}}
                        <div class="col-lg-5">
                            {{-- Live card preview --}}
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">Live Preview</h6>
                                    <div id="card_preview" class="banner-card-preview p-3"
                                         style="background: linear-gradient(135deg, #FF4E50, #F9A720); border-radius: 16px; min-height: 150px;">
                                        <span id="prev_badge" class="badge bg-white bg-opacity-25 text-white"
                                              style="font-size:11px; padding: 4px 12px;">⚡ TODAY ONLY</span>
                                        <div class="mt-3">
                                            <div id="prev_title" style="font-size:18px;font-weight:800;color:#fff;">
                                                Banner Title
                                            </div>
                                            <div id="prev_subtitle" style="font-size:13px;opacity:.85;color:#fff;margin-top:4px;">
                                                Subtitle goes here
                                            </div>
                                            <div id="prev_cta"
                                                 style="margin-top:12px;background:rgba(255,255,255,.25);display:inline-block;
                                                        padding:6px 16px;border-radius:20px;font-size:13px;font-weight:700;color:#fff;">
                                                Shop Now
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Colors --}}
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">Colors</h6>
                                    <div class="row g-3">
                                        <div class="col-sm-4">
                                            <label class="form-label">Gradient Start</label>
                                            <input type="color" class="form-control form-control-color w-100"
                                                   name="gradient_start" id="f_grad_start" value="#FF4E50" required>
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label">Gradient End</label>
                                            <input type="color" class="form-control form-control-color w-100"
                                                   name="gradient_end" id="f_grad_end" value="#F9A720" required>
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label">Accent</label>
                                            <input type="color" class="form-control form-control-color w-100"
                                                   name="accent_color" id="f_accent" value="#FFD700" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Settings --}}
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">Settings</h6>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Target Screen</label>
                                            <select class="form-select" name="target_screen" id="f_screen" required>
                                                <option value="all">All Pages</option>
                                                <option value="home">Home Screen</option>
                                                <option value="category">Category Page</option>
                                                <option value="product">Product Detail</option>
                                                <option value="offers">Offers Page</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label">Start Date</label>
                                            <input type="datetime-local" class="form-control" name="starts_at" id="f_starts">
                                            <small class="text-muted">Leave blank = always start</small>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label">End Date</label>
                                            <input type="datetime-local" class="form-control" name="ends_at" id="f_ends">
                                            <small class="text-muted">Leave blank = never expire</small>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label">Sort Order</label>
                                            <input type="number" class="form-control" name="sort_order" id="f_sort" value="0" min="0">
                                        </div>
                                        <div class="col-sm-6 d-flex flex-column justify-content-end pb-1">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="active"
                                                       value="1" id="f_active" checked>
                                                <label class="form-check-label" for="f_active">Active</label>
                                            </div>
                                            <div class="form-check form-switch mt-1">
                                                <input class="form-check-input" type="checkbox" name="show_once_daily"
                                                       value="1" id="f_show_once" checked>
                                                <label class="form-check-label" for="f_show_once">Show once daily</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="submitSpinner"></span>
                        Save Banner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ─── View Banner Modal ──────────────────────────────────────────────── --}}
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Banner Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" id="viewModalBody">
                <!-- Dynamic content -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
    }

    // ==================== BULK ACTIONS ====================
    let selectedBanners = [];

    function updateSelectedCount() {
        selectedBanners = Array.from(document.querySelectorAll('.row-select:checked'))
                               .map(cb => cb.value);
        const bulkActionsDropdown = document.getElementById('bulkActionsDropdown');
        const selectedCountEl = document.getElementById('selectedCount');

        if (selectedCountEl) selectedCountEl.textContent = selectedBanners.length;
        if (bulkActionsDropdown) {
            bulkActionsDropdown.style.display = selectedBanners.length > 0 ? 'block' : 'none';
        }
    }

    // Select All functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            document.querySelectorAll('.row-select').forEach(cb => cb.checked = this.checked);
            updateSelectedCount();
        });
    }

    // Individual row selection
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('row-select')) {
            updateSelectedCount();
            if (selectAllCheckbox) {
                const allChecked = document.querySelectorAll('.row-select:checked').length ===
                                   document.querySelectorAll('.row-select').length;
                selectAllCheckbox.checked = allChecked;
            }
        }
    });

    // Bulk Actions
    document.querySelectorAll('.bulk-action').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const action = this.dataset.action;

            if (selectedBanners.length === 0) {
                Swal.fire('No Selection', 'Please select at least one banner.', 'warning');
                return;
            }

            let title, text, confirmText, actionFn;

            switch(action) {
                case 'activate':
                    title = 'Activate Banners?';
                    text = `This will activate ${selectedBanners.length} banner(s).`;
                    confirmText = 'Yes, Activate';
                    actionFn = () => bulkAction('active', 1);
                    break;
                case 'deactivate':
                    title = 'Deactivate Banners?';
                    text = `This will deactivate ${selectedBanners.length} banner(s).`;
                    confirmText = 'Yes, Deactivate';
                    actionFn = () => bulkAction('active', 0);
                    break;
                case 'delete':
                    title = 'Delete Banners?';
                    text = `This will permanently delete ${selectedBanners.length} banner(s). This cannot be undone!`;
                    confirmText = 'Yes, Delete';
                    actionFn = () => bulkAction('delete');
                    break;
                default:
                    return;
            }

            Swal.fire({
                title: title,
                text: text,
                icon: action === 'delete' ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: confirmText,
                confirmButtonColor: action === 'delete' ? '#d33' : undefined
            }).then(result => {
                if (result.isConfirmed) {
                    actionFn();
                }
            });
        });
    });

    async function bulkAction(action, value = null) {
        try {
            const response = await axios.post('/web/promo-banners/bulk', {
                ids: selectedBanners,
                action: action,
                value: value
            });

            Swal.fire('Success', response.data.message, 'success')
                .then(() => location.reload());
        } catch (error) {
            Swal.fire('Error', error.response?.data?.message || 'Failed to perform action', 'error');
        }
    }

    // ==================== SEARCH / FILTER ====================
    function initializeSearch() {
        const searchInput = document.getElementById('searchInput');
        const searchButton = document.getElementById('searchButton');
        const clearSearch = document.getElementById('clearSearch');
        const applyFilter = document.getElementById('applyFilter');
        const clearFilters = document.getElementById('clearFilters');
        const screenFilter = document.getElementById('screenFilter');
        const statusFilter = document.getElementById('statusFilter');
        const sortFilter = document.getElementById('sortFilter');
        const orderFilter = document.getElementById('orderFilter');

        function performServerSearch() {
            const params = new URLSearchParams(window.location.search);
            const s = searchInput?.value.trim();
            s ? params.set('search', s) : params.delete('search');
            screenFilter?.value ? params.set('screen', screenFilter.value) : params.delete('screen');
            statusFilter?.value ? params.set('status', statusFilter.value) : params.delete('status');
            sortFilter?.value ? params.set('sort', sortFilter.value) : params.delete('sort');
            orderFilter?.value ? params.set('order', orderFilter.value) : params.delete('order');
            params.delete('page');
            window.location.href = `${window.location.pathname}?${params.toString()}`;
        }

        if (searchButton) searchButton.addEventListener('click', performServerSearch);
        if (applyFilter) applyFilter.addEventListener('click', performServerSearch);

        // Enter key support
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') performServerSearch();
            });
        }

        if (clearFilters) {
            clearFilters.addEventListener('click', () => window.location.href = window.location.pathname);
        }

        if (clearSearch) {
            clearSearch.addEventListener('click', () => {
                if (searchInput) searchInput.value = '';
                performServerSearch();
            });
        }
    }
    initializeSearch();

    // ==================== REMOVE FILTER ====================
    window.removeFilter = function(filterName) {
        const params = new URLSearchParams(window.location.search);
        params.delete(filterName);
        params.delete('page');
        window.location.href = `${window.location.pathname}?${params.toString()}`;
    };

    // ==================== LIVE PREVIEW ====================
    function updatePreview() {
        const gs = document.getElementById('f_grad_start').value;
        const ge = document.getElementById('f_grad_end').value;
        document.getElementById('card_preview').style.background =
            `linear-gradient(135deg, ${gs}, ${ge})`;
        document.getElementById('prev_badge').textContent = document.getElementById('f_badge').value || '⚡ BADGE';
        document.getElementById('prev_title').textContent = document.getElementById('f_title').value || 'Banner Title';
        document.getElementById('prev_subtitle').textContent = document.getElementById('f_subtitle').value || 'Subtitle';
        document.getElementById('prev_cta').textContent = document.getElementById('f_cta_text').value || 'CTA';
    }

    ['f_badge','f_title','f_subtitle','f_cta_text','f_grad_start','f_grad_end'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', updatePreview);
    });

    // ==================== IMAGE PREVIEW ====================
    document.getElementById('f_image')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        const prev = document.getElementById('img_preview');
        if (file) {
            const reader = new FileReader();
            reader.onload = ev => {
                prev.src = ev.target.result;
                prev.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            prev.style.display = 'none';
        }
    });

    // ==================== FORM HANDLING ====================
    window.resetForm = function() {
        document.getElementById('bannerForm').reset();
        document.getElementById('banner_id').value = '';
        document.getElementById('form_method').value = 'POST';
        document.getElementById('modalTitle').textContent = 'Add Promo Banner';
        document.getElementById('img_preview').style.display = 'none';
        document.getElementById('f_grad_start').value = '#FF4E50';
        document.getElementById('f_grad_end').value = '#F9A720';
        document.getElementById('f_accent').value = '#FFD700';
        document.getElementById('f_active').checked = true;
        document.getElementById('f_show_once').checked = true;
        document.getElementById('f_sort').value = '0';
        updatePreview();

        const modalEl = document.getElementById('showModal');
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    };

    // Add button
    document.querySelector('.add-btn')?.addEventListener('click', resetForm);

    // Edit button
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const d = this.dataset;
            document.getElementById('banner_id').value = d.id;
            document.getElementById('form_method').value = 'PUT';
            document.getElementById('modalTitle').textContent = 'Edit Promo Banner';
            document.getElementById('f_badge').value = d.badge || '';
            document.getElementById('f_title').value = d.title || '';
            document.getElementById('f_subtitle').value = d.subtitle || '';
            document.getElementById('f_cta_text').value = d.ctaText || '';
            document.getElementById('f_cta_route').value = d.ctaRoute || '';
            document.getElementById('f_grad_start').value = d.gradientStart || '#FF4E50';
            document.getElementById('f_grad_end').value = d.gradientEnd || '#F9A720';
            document.getElementById('f_accent').value = d.accent || '#FFD700';
            document.getElementById('f_screen').value = d.screen || 'all';
            document.getElementById('f_active').checked = d.active === '1';
            document.getElementById('f_show_once').checked = d.showOnce === '1';
            document.getElementById('f_starts').value = d.starts || '';
            document.getElementById('f_ends').value = d.ends || '';
            document.getElementById('f_lottie').value = d.lottie || '';
            document.getElementById('f_sort').value = d.sort || '0';

            const prev = document.getElementById('img_preview');
            if (d.image && d.image !== 'null' && d.image !== '') {
                prev.src = d.image;
                prev.style.display = 'block';
            } else {
                prev.style.display = 'none';
            }

            updatePreview();

            const modalEl = document.getElementById('showModal');
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        });
    });

    // View button
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const d = this.dataset;
            const body = document.getElementById('viewModalBody');

            let html = `
                <div class="banner-card-preview p-4 mx-auto"
                     style="background: linear-gradient(135deg, ${d.gradientStart}, ${d.gradientEnd});
                            max-width: 400px; border-radius: 16px; min-height: 200px;">
                    <span class="badge bg-white bg-opacity-25 text-white" style="font-size:12px; padding: 4px 16px;">
                        ${d.badge}
                    </span>
                    <div class="mt-3">
                        <div style="font-size:24px;font-weight:800;color:#fff;">${d.title}</div>
                        <div style="font-size:14px;opacity:.85;color:#fff;margin-top:6px;">${d.subtitle}</div>
                        <div style="margin-top:16px;background:rgba(255,255,255,.25);display:inline-block;
                                   padding:6px 20px;border-radius:20px;font-size:14px;font-weight:700;color:#fff;">
                            ${d.ctaText}
                        </div>
                        ${d.image ? `<img src="${d.image}" class="img-fluid mt-3 rounded" style="max-height:150px;">` : ''}
                    </div>
                </div>
                <div class="mt-3 text-start">
                    <p><strong>Screen:</strong> ${d.screen}</p>
                    <p><strong>CTA Route:</strong> ${d.ctaRoute || 'None'}</p>
                </div>
            `;

            body.innerHTML = html;

            const modalEl = document.getElementById('viewModal');
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        });
    });

    // Toggle status
    document.querySelectorAll('.toggle-status-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const currentStatus = this.dataset.status;
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const action = newStatus === 'active' ? 'activate' : 'deactivate';

            Swal.fire({
                title: `${action} Banner?`,
                text: `This will ${action} this banner.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: `Yes, ${action}`
            }).then(result => {
                if (result.isConfirmed) {
                    axios.patch(`/web/promo-banners/${id}/toggle-status`)
                        .then(() => location.reload())
                        .catch(() => Swal.fire('Error', 'Failed to update status', 'error'));
                }
            });
        });
    });

    // Delete single
    document.querySelectorAll('.remove-item-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const title = this.dataset.title || 'this banner';

            Swal.fire({
                title: 'Delete Promo Banner?',
                text: `Are you sure you want to delete "${title}"? This cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete!'
            }).then(result => {
                if (result.isConfirmed) {
                    axios.delete(`/web/promo-banners/${id}`)
                        .then(() => location.reload())
                        .catch(() => Swal.fire('Error', 'Failed to delete', 'error'));
                }
            });
        });
    });

    // ==================== FORM SUBMIT ====================
    const bannerForm = document.getElementById('bannerForm');
    if (bannerForm) {
        bannerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const id = document.getElementById('banner_id').value;
            const method = document.getElementById('form_method').value;

            if (method === 'PUT') {
                formData.append('_method', 'PUT');
            }

            // Fix checkbox values
            formData.set('active', document.getElementById('f_active').checked ? '1' : '0');
            formData.set('show_once_daily', document.getElementById('f_show_once').checked ? '1' : '0');

            const btn = document.getElementById('submitBtn');
            const spinner = document.getElementById('submitSpinner');
            if (btn) btn.disabled = true;
            if (spinner) spinner.classList.remove('d-none');

            const url = id ? `/web/promo-banners/${id}` : '/web/promo-banners';

            axios.post(url, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
            .then(res => {
                Swal.fire({ icon: 'success', title: 'Success!', text: res.data.message || 'Banner saved', showConfirmButton: false, timer: 1500 })
                    .then(() => location.reload());
            })
            .catch(err => {
                let msg = 'An error occurred';
                if (err.response?.data?.errors) {
                    msg = Object.entries(err.response.data.errors).map(([k, v]) => `${k}: ${v.join(', ')}`).join('<br>');
                } else if (err.response?.data?.message) {
                    msg = err.response.data.message;
                }
                Swal.fire({ icon: 'error', title: 'Error', html: msg });
            })
            .finally(() => {
                if (btn) btn.disabled = false;
                if (spinner) spinner.classList.add('d-none');
            });
        });
    }

    // ==================== DRAG-AND-DROP REORDER ====================
    const tbody = document.getElementById('sortable-body');
    if (tbody) {
        Sortable.create(tbody, {
            animation: 150,
            handle: '.sortable-row',
            onEnd() {
                const ids = Array.from(tbody.querySelectorAll('tr[data-id]'))
                                 .map(tr => tr.dataset.id);
                axios.post('/web/promo-banners/reorder', { ids })
                     .then(() => {
                         // Show a subtle success toast
                         Swal.fire({
                             icon: 'success',
                             title: 'Reordered!',
                             text: 'Banners reordered successfully',
                             timer: 1500,
                             showConfirmButton: false,
                             toast: true,
                             position: 'bottom-end'
                         });
                     })
                     .catch(() => {
                         Swal.fire('Error', 'Failed to save reorder', 'error');
                         location.reload();
                     });
            },
        });
    }

    // ==================== MODAL BACKDROP CLEANUP ====================
    const showModal = document.getElementById('showModal');
    if (showModal) {
        showModal.addEventListener('hidden.bs.modal', function() {
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });
    }

    // Initial preview render
    updatePreview();

    // ==================== KEYBOARD SHORTCUTS ====================
    document.addEventListener('keydown', function(e) {
        // Ctrl + Shift + A = Add new banner
        if (e.ctrlKey && e.shiftKey && e.key === 'A') {
            e.preventDefault();
            resetForm();
        }
        // Escape to close modals
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.show').forEach(modal => {
                const instance = bootstrap.Modal.getInstance(modal);
                if (instance) instance.hide();
            });
        }
    });
});

// ==================== UTILITY FUNCTIONS ====================
window.copyText = function(text) {
    navigator.clipboard.writeText(text).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'Copied!',
            timer: 1500,
            showConfirmButton: false,
            toast: true,
            position: 'bottom-end'
        });
    }).catch(() => {
        Swal.fire('Error', 'Could not copy text', 'error');
    });
};
</script>

<style>
.banner-card-preview {
    transition: background 0.3s ease;
}
.sortable-row {
    cursor: grab;
}
.sortable-row:active {
    cursor: grabbing;
    opacity: 0.7;
}
.sortable-row.sortable-chosen {
    background-color: #f0f0f0 !important;
}
.sortable-ghost {
    opacity: 0.4;
    background-color: #e9ecef;
}
.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
</style>
@endsection
