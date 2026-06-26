{{-- resources/views/promo_banners/index.blade.php --}}
@extends('layouts.master')

@section('title', 'Promo Banners')

<style>
    .gradient-preview {
        width: 100%;
        height: 48px;
        border-radius: 8px;
        border: 1px solid rgba(0,0,0,.1);
        transition: background .3s;
    }
    .banner-card-preview {
        border-radius: 16px;
        padding: 18px;
        color: #fff;
        min-height: 90px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: background .3s;
    }
    .sortable-row { cursor: grab; }
    .sortable-row:active { cursor: grabbing; opacity: .7; }
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-active { background: #d4edda; color: #155724; }
    .status-inactive { background: #f8d7da; color: #721c24; }
    .status-scheduled { background: #cce5ff; color: #004085; }
    .status-expired { background: #e2e3e5; color: #383d41; }
    .banner-thumb {
        width: 160px;
        height: 90px;
        object-fit: cover;
        border-radius: 8px;
    }
</style>

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            {{-- Page title --}}
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Promo Banners</h4>
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="#">Marketing</a></li>
                            <li class="breadcrumb-item active">Promo Banners</li>
                        </ol>
                    </div>
                </div>
            </div>

            {{-- Success/Error Messages --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="ri-check-line me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- Table card --}}
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h5 class="card-title mb-0 flex-grow-1">All Promo Banners</h5>
                            <div class="flex-shrink-0 d-flex gap-2">
                                <button class="btn btn-danger d-none" id="remove-actions" onclick="deleteMultiple()">
                                    <i class="ri-delete-bin-line me-1"></i> Delete Selected
                                </button>
                                @can('Create promo_banner')
                                    <button type="button" class="btn btn-primary add-btn">
                                        <i class="ri-add-line me-1"></i> Add Banner
                                    </button>
                                @endcan
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="promo-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="40">
                                                <input class="form-check-input" type="checkbox" id="checkAll">
                                            </th>
                                            <th width="30">#</th>
                                            <th>Preview</th>
                                            <th>Badge / Title</th>
                                            <th>Screen</th>
                                            <th>Schedule</th>
                                            <th>Status</th>
                                            <th width="120">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sortable-body">
                                        @forelse($banners as $banner)
                                        <tr class="sortable-row" data-id="{{ $banner->id }}">
                                            <td>
                                                <input class="form-check-input" type="checkbox" name="chk_child" value="{{ $banner->id }}">
                                            </td>
                                            <td class="fw-medium text-muted">{{ $loop->iteration }}</td>
                                            <td>
                                                @if($banner->image_url)
                                                    <img src="{{ $banner->full_image_url }}" alt="{{ $banner->title }}" class="banner-thumb">
                                                @else
                                                    <div class="banner-card-preview"
                                                         style="background: linear-gradient(135deg, {{ $banner->gradient_start }}, {{ $banner->gradient_end }}); width: 160px; height: 90px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 10px; padding: 8px; text-align: center;">
                                                        <span>{{ Str::limit($banner->badge_text, 15) }}</span>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $banner->badge_text }}</div>
                                                <small class="text-muted">{{ Str::limit($banner->title, 45) }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info-subtle text-info">
                                                    {{ ucfirst($banner->target_screen) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($banner->starts_at || $banner->ends_at)
                                                    <small class="text-muted d-block">
                                                        From: {{ $banner->starts_at?->format('d M Y') ?? '—' }}
                                                    </small>
                                                    <small class="text-muted d-block">
                                                        To: {{ $banner->ends_at?->format('d M Y') ?? '—' }}
                                                    </small>
                                                @else
                                                    <span class="text-muted small">Always</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $statusClass = 'status-active';
                                                    $statusText = 'Active';
                                                    if (!$banner->active) {
                                                        $statusClass = 'status-inactive';
                                                        $statusText = 'Inactive';
                                                    } elseif ($banner->starts_at && $banner->starts_at > now()) {
                                                        $statusClass = 'status-scheduled';
                                                        $statusText = 'Scheduled';
                                                    } elseif ($banner->ends_at && $banner->ends_at < now()) {
                                                        $statusClass = 'status-expired';
                                                        $statusText = 'Expired';
                                                    }
                                                @endphp
                                                <span class="status-badge {{ $statusClass }}">
                                                    {{ $statusText }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    @can('Update promo_banner')
                                                        <button class="btn btn-subtle-secondary btn-icon btn-sm edit-btn"
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
                                                            <i class="ri-pencil-line"></i>
                                                        </button>
                                                    @endcan
                                                    @can('Delete promo_banner')
                                                        <button class="btn btn-subtle-danger btn-icon btn-sm delete-btn"
                                                                data-id="{{ $banner->id }}">
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-5 text-muted">
                                                <i class="ri-image-line fs-1 d-block mb-2"></i>
                                                No promo banners yet. Click "Add Banner" to create one.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- Pagination --}}
                            <div class="d-flex justify-content-end mt-4">
                                {{ $banners->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- end container -->
    </div><!-- end page-content -->
</div><!-- end main-content -->

{{-- ─── Add / Edit Modal ─────────────────────────────────────────────────── --}}
<div class="modal fade" id="showModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <form id="bannerForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" id="banner_id">
                <input type="hidden" name="_method" id="form_method" value="POST">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Promo Banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-4">
                        {{-- ── Left column: content ── --}}
                        <div class="col-lg-7">
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
                                    <small class="text-muted">Max 3 MB. If omitted a fallback icon is shown.</small>
                                    <div class="mt-2 text-center">
                                        <img id="img_preview" class="rounded shadow" style="max-width:100%;max-height:160px;display:none;" alt="Preview">
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

                        {{-- ── Right column: visual & scheduling ── --}}
                        <div class="col-lg-5">
                            <div class="row g-3">
                                {{-- Live card preview --}}
                                <div class="col-12">
                                    <label class="form-label">Live Preview</label>
                                    <div id="card_preview" class="banner-card-preview p-3"
                                         style="background: linear-gradient(135deg, #FF4E50, #F9A720);">
                                        <span id="prev_badge" class="badge bg-white bg-opacity-25 text-white"
                                              style="font-size:11px;">⚡ TODAY ONLY</span>
                                        <div>
                                            <div id="prev_title" style="font-size:15px;font-weight:800;margin-top:10px;">
                                                Banner Title
                                            </div>
                                            <div id="prev_subtitle" style="font-size:11px;opacity:.85;margin-top:4px;">
                                                Subtitle goes here
                                            </div>
                                            <div id="prev_cta"
                                                 style="margin-top:12px;background:rgba(255,255,255,.25);display:inline-block;
                                                        padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;">
                                                Shop Now
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Colors --}}
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

                                {{-- Target screen --}}
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

                                {{-- Schedule --}}
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

                                {{-- Sort order --}}
                                <div class="col-sm-6">
                                    <label class="form-label">Sort Order</label>
                                    <input type="number" class="form-control" name="sort_order" id="f_sort" value="0" min="0">
                                </div>

                                {{-- Options --}}
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

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span id="submitSpinner" class="spinner-border spinner-border-sm d-none me-1"></span>
                        Save Banner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>




<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = '{{ csrf_token() }}';

    const modal = new bootstrap.Modal('#showModal');
    const form = document.getElementById('bannerForm');
    const spinner = document.getElementById('submitSpinner');

    // ── Checkbox select-all ──────────────────────────────────────────────────
    document.getElementById('checkAll')?.addEventListener('change', function () {
        document.querySelectorAll('input[name="chk_child"]').forEach(cb => {
            cb.checked = this.checked;
            cb.closest('tr')?.classList.toggle('table-active', this.checked);
        });
        toggleRemoveBtn();
    });
    document.querySelectorAll('input[name="chk_child"]').forEach(cb => {
        cb.addEventListener('change', () => {
            cb.closest('tr')?.classList.toggle('table-active', cb.checked);
            toggleRemoveBtn();
        });
    });
    function toggleRemoveBtn() {
        const count = document.querySelectorAll('input[name="chk_child"]:checked').length;
        document.getElementById('remove-actions').classList.toggle('d-none', count === 0);
    }

    // ── Live preview helpers ─────────────────────────────────────────────────
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

    // ── Image preview ────────────────────────────────────────────────────────
    document.getElementById('f_image')?.addEventListener('change', e => {
        const file = e.target.files[0];
        const prev = document.getElementById('img_preview');
        if (file) { prev.src = URL.createObjectURL(file); prev.style.display = 'block'; }
        else { prev.style.display = 'none'; }
    });

    // ── Add button ───────────────────────────────────────────────────────────
    document.querySelector('.add-btn')?.addEventListener('click', () => {
        form.reset();
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
        modal.show();
    });

    // ── Edit buttons ─────────────────────────────────────────────────────────
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function () {
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
            if (d.image && d.image !== 'null') {
                prev.src = d.image;
                prev.style.display = 'block';
            } else {
                prev.style.display = 'none';
            }

            updatePreview();
            modal.show();
        });
    });

    // ── Form submit ──────────────────────────────────────────────────────────
    form.addEventListener('submit', async e => {
        e.preventDefault();
        spinner.classList.remove('d-none');

        const id = document.getElementById('banner_id').value;
        const method = document.getElementById('form_method').value;
        const url = id ? `/web/promo-banners/${id}` : '/web/promo-banners';
        const data = new FormData(form);

        if (method === 'PUT') {
            data.append('_method', 'PUT');
        }

        // Fix checkbox values
        data.set('active', document.getElementById('f_active').checked ? '1' : '0');
        data.set('show_once_daily', document.getElementById('f_show_once').checked ? '1' : '0');

        try {
            const response = await axios.post(url, data, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            if (response.data.success) {
                Swal.fire('Success', response.data.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                throw new Error(response.data.message || 'Something went wrong');
            }
        } catch (err) {
            spinner.classList.add('d-none');
            const msg = err.response?.data?.message || err.message || 'Something went wrong';
            Swal.fire('Error', msg, 'error');
        }
    });

    // ── Delete single ────────────────────────────────────────────────────────
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            Swal.fire({
                title: 'Delete this promo banner?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then(r => {
                if (r.isConfirmed) {
                    axios.delete(`/web/promo-banners/${btn.dataset.id}`)
                         .then(() => location.reload())
                         .catch(() => Swal.fire('Error', 'Failed to delete', 'error'));
                }
            });
        });
    });

    // ── Bulk delete ──────────────────────────────────────────────────────────
    window.deleteMultiple = function () {
        const ids = Array.from(document.querySelectorAll('input[name="chk_child"]:checked'))
                        .map(cb => cb.value);
        if (!ids.length) return;
        Swal.fire({
            title: `Delete ${ids.length} banner(s)?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete all!'
        }).then(r => {
            if (r.isConfirmed) {
                Promise.all(ids.map(id => axios.delete(`/web/promo-banners/${id}`)))
                       .then(() => location.reload())
                       .catch(() => Swal.fire('Error', 'Failed to delete some banners', 'error'));
            }
        });
    };

    // ── Drag-and-drop reorder ────────────────────────────────────────────────
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
                         // Show a subtle success indicator
                         const toast = document.createElement('div');
                         toast.className = 'position-fixed bottom-0 end-0 p-3';
                         toast.style.zIndex = '9999';
                         toast.innerHTML = `
                             <div class="toast show" role="alert">
                                 <div class="toast-body bg-success text-white rounded">
                                     <i class="ri-check-line me-2"></i> Banners reordered successfully
                                 </div>
                             </div>
                         `;
                         document.body.appendChild(toast);
                         setTimeout(() => toast.remove(), 3000);
                     })
                     .catch(() => {
                         Swal.fire('Error', 'Failed to save reorder', 'error');
                         location.reload();
                     });
            },
        });
    }

    // Initial preview render
    updatePreview();
});
</script>
@endsection
