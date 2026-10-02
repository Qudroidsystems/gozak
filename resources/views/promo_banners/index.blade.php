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

            {{-- ─── Banner Table (server-side DataTable) ─────────────────────────── --}}
            <x-cb.card title="Promo Banners" icon="ri-megaphone-line" :flush="true" class="mt-4">
                <x-slot:tools>
                    <div class="dropdown" id="bulkActionsDropdown" style="display: none;">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Bulk Actions (<span id="selectedCount">0</span>)
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item bulk-action" href="#" data-action="activate">Activate</a></li>
                            <li><a class="dropdown-item bulk-action" href="#" data-action="deactivate">Deactivate</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item bulk-action text-danger" href="#" data-action="delete">Delete Selected</a></li>
                        </ul>
                    </div>
                    @can('Create promo_banner')
                        <button type="button" class="btn btn-sm btn-primary add-btn" onclick="resetForm()"><i class="bi bi-plus-lg me-1"></i> Add Banner</button>
                    @endcan
                </x-slot:tools>

                <div class="gz-filter-bar px-3 pt-3">
                    <select class="form-select form-select-sm" id="screenFilter" data-dt-filter="#promoBannersTable">
                        <option value="">All Screens</option>
                        <option value="all">All Pages</option>
                        <option value="home">Home</option>
                        <option value="category">Category</option>
                        <option value="product">Product</option>
                        <option value="offers">Offers</option>
                    </select>
                    <select class="form-select form-select-sm" id="styleFilter" data-dt-filter="#promoBannersTable">
                        <option value="">All Styles</option>
                        <option value="coupon">Coupon Ticket</option>
                        <option value="voucher">Gift Voucher</option>
                        <option value="gradient">Gradient</option>
                    </select>
                    <select class="form-select form-select-sm" id="statusFilter" data-dt-filter="#promoBannersTable">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="expired">Expired</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clearFilters"><i class="bi bi-x-circle me-1"></i> Clear</button>
                    <small class="text-muted ms-auto"><i class="bi bi-arrows-move me-1"></i>Drag the <i class="bi bi-grip-vertical"></i> handle to reorder (when sorted by Sort Order)</small>
                </div>

                <div class="px-3 pb-3 gz-dt-wrap">
                    <table id="promoBannersTable" class="table gz-dt align-middle w-100 mb-0">
                        <thead>
                            <tr>
                                <th style="width: 36px;"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                                <th style="width: 30px;"></th>
                                <th>Preview</th>
                                <th>Badge / Title</th>
                                <th>Style</th>
                                <th>Screen</th>
                                <th>Schedule</th>
                                <th>Status</th>
                                <th>Sort Order</th>
                                <th style="width: 70px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="sortable-body"></tbody>
                    </table>
                </div>
            </x-cb.card>

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
                                            <label class="form-label">Display Style</label>
                                            <select class="form-select" name="display_style" id="f_style">
                                                <option value="">Auto-cycle (coupon → voucher → gradient)</option>
                                                <option value="coupon">Coupon Ticket (Temu "Coupons Available")</option>
                                                <option value="voucher">Gift Voucher (Temu certificate style)</option>
                                                <option value="gradient">Gradient Promo (flash-sale style)</option>
                                            </select>
                                            <small class="text-muted">Leave on Auto-cycle if you want batches of banners to rotate through all three looks.</small>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Badge Text <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="badge_text" id="f_badge"
                                                   placeholder="⚡ TODAY ONLY" maxlength="80" required>
                                            <small class="text-muted">Short label shown in the top-left pill (emoji + text) — used by the Gradient style</small>
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
                                            <label class="form-label">Banner Image <small class="text-muted">(optional, Gradient style only)</small></label>
                                            <input type="file" class="form-control" name="image" id="f_image"
                                                   accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                                            <small class="text-muted">Max 3 MB. If omitted a fallback gradient/icon is shown.</small>
                                            <div class="mt-2 text-center">
                                                <img id="img_preview" class="img-fluid rounded shadow"
                                                     style="max-width:100%;max-height:160px;display:none;" alt="Preview">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Lottie Animation Asset <small class="text-muted">(optional, Gradient style only)</small></label>
                                            <input type="text" class="form-control" name="lottie_asset" id="f_lottie"
                                                   placeholder="assets/animations/sale.json">
                                            <small class="text-muted">Path to Lottie animation in Flutter assets</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Temu-style coupon / voucher fields ── shown for coupon & voucher styles --}}
                            <div class="card" id="temuFieldsCard">
                                <div class="card-body">
                                    <h6 class="card-title mb-1">Coupon / Voucher Details</h6>
                                    <small class="text-muted d-block mb-3">Used by the Coupon and Voucher display styles (and shown when Auto-cycle rotates into them).</small>
                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <label class="form-label">Amount Text</label>
                                            <input type="text" class="form-control" name="amount_text" id="f_amount"
                                                   placeholder="₦300,000" maxlength="50">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label">Masked User</label>
                                            <input type="text" class="form-control" name="masked_user" id="f_masked_user"
                                                   placeholder="IL***JI" maxlength="50">
                                            <small class="text-muted">Recipient shown on the card ("TO" / verified user)</small>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label">From Label</label>
                                            <input type="text" class="form-control" name="from_label" id="f_from_label"
                                                   placeholder="Event" maxlength="60">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label">Type Label</label>
                                            <input type="text" class="form-control" name="type_label" id="f_type_label"
                                                   placeholder="Coupons / Coupon bundle" maxlength="60">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label">Date Label</label>
                                            <input type="text" class="form-control" name="date_label" id="f_date_label"
                                                   placeholder="06/27/2026" maxlength="30">
                                            <small class="text-muted">Leave blank to auto-derive from End Date</small>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label">Conditions Text</label>
                                            <input type="text" class="form-control" name="conditions_text" id="f_conditions"
                                                   placeholder="With qualifying orders" maxlength="150">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Announcement Text <small class="text-muted">(Voucher style — floats above the card)</small></label>
                                            <input type="text" class="form-control" name="announcement_text" id="f_announcement"
                                                   placeholder="Gift Voucher Awaits You" maxlength="150">
                                            <small class="text-muted">Leave blank to use the default "Gift Voucher Awaits You"</small>
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

                                    {{-- Gradient preview — CTA now floats OUTSIDE the gradient box,
                                         matching the app's separate-button layout --}}
                                    <div id="preview_gradient">
                                        <div class="banner-card-preview p-3"
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
                                            </div>
                                        </div>
                                        <div class="text-center mt-2">
                                            <span id="prev_cta"
                                                  class="d-inline-block"
                                                  style="background:#FF7A1A;padding:8px 24px;border-radius:24px;font-size:13px;font-weight:800;color:#fff;">
                                                Shop Now
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Coupon ticket preview --}}
                                    <div id="preview_coupon" class="d-none text-center p-3"
                                         style="background:#fff;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.12);">
                                        <div class="d-flex align-items-center justify-content-center gap-1 mb-2">
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                            <span id="prev_masked_user" class="fw-bold text-success" style="font-size:13px;">IL***JI</span>
                                        </div>
                                        <hr class="my-2">
                                        <div id="prev_amount" style="font-size:28px;font-weight:900;color:#1a1a1a;">₦300,000</div>
                                        <div class="text-success fw-bold" style="font-size:12px;">Coupons Available</div>
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between text-start" style="font-size:12px;">
                                            <div><small class="text-muted d-block">From</small><span id="prev_from" class="fw-semibold">Event</span></div>
                                            <div><small class="text-muted d-block">Type</small><span id="prev_type" class="fw-semibold">Coupons</span></div>
                                        </div>
                                    </div>

                                    {{-- Voucher preview --}}
                                    <div id="preview_voucher" class="d-none text-center p-3"
                                         style="background:#FFFDF8;border:1.2px solid #E8D9B5;border-radius:16px;">
                                        <div id="prev_announcement" class="fw-bold text-success mb-2" style="font-size:12px;">
                                            <i class="bi bi-check-circle-fill"></i> Gift Voucher Awaits You
                                        </div>
                                        <div style="font-family:serif;font-size:20px;font-weight:700;letter-spacing:1px;">GIFT VOUCHER</div>
                                        <hr class="my-2" style="border-color:#E8D9B5;">
                                        <div class="d-flex justify-content-between text-start" style="font-size:11px;">
                                            <div><small class="text-muted d-block">TO</small><span id="prev_voucher_user" class="fw-semibold">IL***JI</span></div>
                                            <div><small class="text-muted d-block">FROM</small><span id="prev_voucher_from" class="fw-semibold">event</span></div>
                                        </div>
                                        <div id="prev_voucher_amount" class="mt-2" style="font-family:serif;font-size:22px;font-weight:800;color:#C9881A;">₦300,000</div>
                                        <small id="prev_voucher_type" class="text-muted d-block mt-1">coupon bundle</small>
                                    </div>
                                </div>
                            </div>

                            {{-- Colors --}}
                            <div class="card" id="colorsCard">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">Colors <small class="text-muted">(Gradient style only)</small></h6>
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
var PB_URLS = {
    data:   @json(route('web.promo-banners.data')),
    bulk:   @json(route('web.promo-banners.bulk')),
    toggle: @json(route('web.promo-banners.toggle-status', '__ID__'))
};
function reloadBanners() { if (window.promoTable) { window.promoTable.ajax.reload(null, false); } }
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
            const response = await axios.post(PB_URLS.bulk, {
                ids: selectedBanners,
                action: action,
                value: value
            });

            Swal.fire('Success', response.data.message, 'success');
            reloadBanners();
        } catch (error) {
            Swal.fire('Error', error.response?.data?.message || 'Failed to perform action', 'error');
        }
    }

    // ==================== SERVER-SIDE TABLE ====================
    window.promoTable = GZ.dt('#promoBannersTable', {
        url: PB_URLS.data,
        order: [[8, 'asc']],
        filters: function () {
            return { screen: $('#screenFilter').val(), display_style: $('#styleFilter').val(), status: $('#statusFilter').val() };
        },
        columns: [
            { data: 'checkbox',   name: 'checkbox', orderable: false, searchable: false },
            { data: 'handle',     name: 'handle',   orderable: false, searchable: false },
            { data: 'preview',    name: 'preview',  orderable: false, searchable: false },
            { data: 'content',    name: 'content' },
            { data: 'style',      name: 'display_style', searchable: false },
            { data: 'screen',     name: 'target_screen', searchable: false },
            { data: 'schedule',   name: 'starts_at', searchable: false },
            { data: 'status',     name: 'active', searchable: false },
            { data: 'sort_order', name: 'sort_order', searchable: false },
            { data: 'action',     name: 'action', orderable: false, searchable: false }
        ],
        dt: { createdRow: function (row, data) { row.classList.add('sortable-row'); row.dataset.id = data.id; } },
        onDraw: function () {
            var all = document.getElementById('selectAll'); if (all) all.checked = false;
            updateSelectedCount();
            // drag-and-drop only makes sense when the table is in sort-order order
            var ord = window.promoTable ? window.promoTable.order() : [[8, 'asc']];
            $('#promoBannersTable').toggleClass('pb-drag-enabled', ord.length && ord[0][0] === 8 && ord[0][1] === 'asc');
        }
    });
    var initialSearch = @json(request('search', ''));
    if (initialSearch) { window.promoTable.search(initialSearch).draw(); }
    document.getElementById('clearFilters')?.addEventListener('click', function () {
        ['screenFilter', 'styleFilter', 'statusFilter'].forEach(function (id) { document.getElementById(id).value = ''; });
        window.promoTable.search('').ajax.reload();
    });

    // ==================== STYLE-DEPENDENT FIELD VISIBILITY ====================
    function updateStyleVisibility() {
        const style = document.getElementById('f_style').value; // '', 'coupon', 'voucher', 'gradient'

        // Colors card is only really meaningful for the gradient style
        document.getElementById('colorsCard').style.display = (style === 'voucher') ? 'none' : '';

        // Swap preview panels — toggling the outer #preview_gradient wrapper,
        // which now contains both the gradient box AND its floating CTA pill
        document.getElementById('preview_gradient').classList.toggle('d-none', style === 'coupon' || style === 'voucher');
        document.getElementById('preview_coupon').classList.toggle('d-none', style !== 'coupon');
        document.getElementById('preview_voucher').classList.toggle('d-none', style !== 'voucher');

        updatePreview();
    }

    // ==================== LIVE PREVIEW ====================
    function updatePreview() {
        const style = document.getElementById('f_style').value;

        const badge = document.getElementById('f_badge').value || '⚡ BADGE';
        const title = document.getElementById('f_title').value || 'Banner Title';
        const subtitle = document.getElementById('f_subtitle').value || 'Subtitle';
        const ctaText = document.getElementById('f_cta_text').value || 'CTA';
        const amount = document.getElementById('f_amount').value || title || '₦0';
        const maskedUser = document.getElementById('f_masked_user').value || 'You';
        const fromLabel = document.getElementById('f_from_label').value || 'Event';
        const typeLabel = document.getElementById('f_type_label').value || 'Coupons';
        const announcement = document.getElementById('f_announcement').value || 'Gift Voucher Awaits You';

        // Gradient preview
        const gs = document.getElementById('f_grad_start').value;
        const ge = document.getElementById('f_grad_end').value;
        document.querySelector('#preview_gradient .banner-card-preview').style.background = `linear-gradient(135deg, ${gs}, ${ge})`;
        document.getElementById('prev_badge').textContent = badge;
        document.getElementById('prev_title').textContent = title;
        document.getElementById('prev_subtitle').textContent = subtitle;
        document.getElementById('prev_cta').textContent = ctaText;
        document.getElementById('prev_cta').style.background = gs; // CTA pill echoes the gradient start color

        // Coupon preview
        document.getElementById('prev_masked_user').textContent = maskedUser;
        document.getElementById('prev_amount').textContent = amount;
        document.getElementById('prev_from').textContent = fromLabel;
        document.getElementById('prev_type').textContent = typeLabel;

        // Voucher preview
        document.getElementById('prev_announcement').innerHTML = `<i class="bi bi-check-circle-fill"></i> ${announcement}`;
        document.getElementById('prev_voucher_user').textContent = maskedUser;
        document.getElementById('prev_voucher_from').textContent = fromLabel;
        document.getElementById('prev_voucher_amount').textContent = amount;
        document.getElementById('prev_voucher_type').textContent = typeLabel;
    }

    document.getElementById('f_style')?.addEventListener('change', updateStyleVisibility);

    [
        'f_badge','f_title','f_subtitle','f_cta_text','f_grad_start','f_grad_end',
        'f_amount','f_masked_user','f_from_label','f_type_label','f_announcement'
    ].forEach(id => {
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
        document.getElementById('f_style').value = '';
        document.getElementById('f_grad_start').value = '#FF4E50';
        document.getElementById('f_grad_end').value = '#F9A720';
        document.getElementById('f_accent').value = '#FFD700';
        document.getElementById('f_active').checked = true;
        document.getElementById('f_show_once').checked = true;
        document.getElementById('f_sort').value = '0';
        updateStyleVisibility();

        const modalEl = document.getElementById('showModal');
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    };

    // Add button
    document.querySelector('.add-btn')?.addEventListener('click', resetForm);

    // Edit button
    // delegated: rows are drawn by the DataTable
    $(document).on('click', '.edit-btn', function() {
            const d = this.dataset;
            document.getElementById('banner_id').value = d.id;
            document.getElementById('form_method').value = 'PUT';
            document.getElementById('modalTitle').textContent = 'Edit Promo Banner';
            document.getElementById('f_style').value = d.style || '';
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

            // Temu-style fields
            document.getElementById('f_amount').value = d.amount || '';
            document.getElementById('f_masked_user').value = d.maskedUser || '';
            document.getElementById('f_from_label').value = d.fromLabel || '';
            document.getElementById('f_type_label').value = d.typeLabel || '';
            document.getElementById('f_date_label').value = d.dateLabel || '';
            document.getElementById('f_conditions').value = d.conditions || '';
            document.getElementById('f_announcement').value = d.announcement || '';

            const prev = document.getElementById('img_preview');
            if (d.image && d.image !== 'null' && d.image !== '') {
                prev.src = d.image;
                prev.style.display = 'block';
            } else {
                prev.style.display = 'none';
            }

            updateStyleVisibility();

            const modalEl = document.getElementById('showModal');
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        });

    // View button
    // delegated: rows are drawn by the DataTable
    $(document).on('click', '.view-btn', function() {
            const d = this.dataset;
            const body = document.getElementById('viewModalBody');
            const style = d.style || '';

            let html = '';

            if (style === 'coupon') {
                html = `
                    <div class="p-4 mx-auto text-center" style="max-width:360px;background:#fff;border-radius:16px;box-shadow:0 8px 30px rgba(0,0,0,.15);">
                        <div class="d-flex align-items-center justify-content-center gap-1 mb-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span class="fw-bold text-success" style="font-size:13px;">${d.maskedUser || 'You'}</span>
                        </div>
                        <hr class="my-2">
                        <div style="font-size:30px;font-weight:900;color:#1a1a1a;">${d.amount || d.title}</div>
                        <div class="text-success fw-bold" style="font-size:12px;">Coupons Available</div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between text-start" style="font-size:13px;">
                            <div><small class="text-muted d-block">From</small><span class="fw-semibold">${d.fromLabel || 'Event'}</span></div>
                            <div><small class="text-muted d-block">Type</small><span class="fw-semibold">${d.typeLabel || 'Coupons'}</span></div>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <span class="d-inline-block"
                              style="background:#FF7A1A;padding:8px 28px;border-radius:24px;font-size:14px;font-weight:800;color:#fff;">
                            ${d.ctaText}
                        </span>
                    </div>`;
            } else if (style === 'voucher') {
                html = `
                    <div class="fw-bold text-success mb-2" style="font-size:13px;">
                        <i class="bi bi-check-circle-fill"></i> ${d.announcement || 'Gift Voucher Awaits You'}
                    </div>
                    <div class="p-4 mx-auto text-center" style="max-width:360px;background:#FFFDF8;border:1.2px solid #E8D9B5;border-radius:16px;">
                        <div style="font-family:serif;font-size:24px;font-weight:700;letter-spacing:1.5px;">GIFT VOUCHER</div>
                        <hr class="my-2" style="border-color:#E8D9B5;">
                        <div class="d-flex justify-content-between text-start" style="font-size:12px;">
                            <div><small class="text-muted d-block">TO</small><span class="fw-semibold">${d.maskedUser || 'You'}</span></div>
                            <div><small class="text-muted d-block">FROM</small><span class="fw-semibold">${d.fromLabel || 'event'}</span></div>
                        </div>
                        <div class="mt-2" style="font-family:serif;font-size:26px;font-weight:800;color:#C9881A;">${d.amount || d.title}</div>
                        <small class="text-muted d-block mt-1">${d.typeLabel || 'coupon bundle'}</small>
                    </div>
                    <div class="text-center mt-3">
                        <span class="d-inline-block"
                              style="background:#FF7A1A;padding:8px 28px;border-radius:24px;font-size:14px;font-weight:800;color:#fff;">
                            ${d.ctaText}
                        </span>
                    </div>`;
            } else {
                html = `
                    <div class="banner-card-preview p-4 mx-auto"
                         style="background: linear-gradient(135deg, ${d.gradientStart}, ${d.gradientEnd});
                                max-width: 400px; border-radius: 16px; min-height: 200px;">
                        <span class="badge bg-white bg-opacity-25 text-white" style="font-size:12px; padding: 4px 16px;">
                            ${d.badge}
                        </span>
                        <div class="mt-3">
                            <div style="font-size:24px;font-weight:800;color:#fff;">${d.title}</div>
                            <div style="font-size:14px;opacity:.85;color:#fff;margin-top:6px;">${d.subtitle}</div>
                            ${d.image ? `<img src="${d.image}" class="img-fluid mt-3 rounded" style="max-height:150px;">` : ''}
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <span class="d-inline-block"
                              style="background:#fff;padding:8px 28px;border-radius:24px;font-size:14px;font-weight:700;color:${d.gradientStart};">
                            ${d.ctaText}
                        </span>
                    </div>`;
            }

            html += `
                <div class="mt-3 text-start">
                    <p><strong>Style:</strong> ${style ? style.charAt(0).toUpperCase() + style.slice(1) : 'Auto-cycle'}</p>
                    <p><strong>Screen:</strong> ${d.screen}</p>
                    <p><strong>CTA Route:</strong> ${d.ctaRoute || 'None'}</p>
                    ${d.conditions ? `<p><strong>Conditions:</strong> ${d.conditions}</p>` : ''}
                </div>
            `;

            body.innerHTML = html;

            const modalEl = document.getElementById('viewModal');
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        });

    // Toggle status
    // delegated: rows are drawn by the DataTable
    $(document).on('click', '.toggle-status-btn', function() {
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
                    axios.patch(PB_URLS.toggle.replace('__ID__', id))
                        .then(() => { GZ.toast('Status updated'); reloadBanners(); })
                        .catch(() => Swal.fire('Error', 'Failed to update status', 'error'));
                }
            });
        });

    // Delete single
    // delegated: rows are drawn by the DataTable
    $(document).on('click', '.remove-item-btn', function() {
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
                        .then(() => { GZ.toast('Banner deleted'); reloadBanners(); })
                        .catch(() => Swal.fire('Error', 'Failed to delete', 'error'));
                }
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
                Swal.fire({ icon: 'success', title: 'Success!', text: res.data.message || 'Banner saved', showConfirmButton: false, timer: 1500 });
                bootstrap.Modal.getInstance(document.getElementById('showModal'))?.hide();
                reloadBanners();
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
            handle: '.pb-drag-handle',
            filter: '.dataTables_empty',
            onStart(evt) {
                if (!document.getElementById('promoBannersTable').classList.contains('pb-drag-enabled')) {
                    GZ.toast('Sort by "Sort Order" (ascending) to drag-reorder', 'info');
                }
            },
            onEnd() {
                if (!document.getElementById('promoBannersTable').classList.contains('pb-drag-enabled')) { reloadBanners(); return; }
                const ids = Array.from(tbody.querySelectorAll('tr[data-id]'))
                                 .map(tr => tr.dataset.id);
                axios.post('/web/promo-banners/reorder', { ids, offset: window.promoTable ? window.promoTable.page.info().start : 0 })
                     .then(() => {
                         reloadBanners();
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
                         reloadBanners();
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
    updateStyleVisibility();

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
.pb-drag-handle { cursor: grab; color: #94a3b8; }
#promoBannersTable:not(.pb-drag-enabled) .pb-drag-handle { opacity: .3; cursor: not-allowed; }
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
