@extends('layouts.master')

@section('title', 'Lightning Deals')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">⚡ Lightning Deals</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0)">Ecommerce</a></li>
                                <li class="breadcrumb-item active">Lightning Deals</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-warning-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-warning mb-0">Total Deals</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ $dealStats['total'] }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-warning rounded-circle fs-3 text-white">⚡</span>
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
                                    <p class="text-uppercase fw-medium text-success mb-0">Active Now</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ $dealStats['active'] }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-success rounded-circle fs-3">
                                        <i class="bi bi-check-circle text-white"></i>
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
                                    <p class="text-uppercase fw-medium text-danger mb-0">Inactive</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ $dealStats['inactive'] }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-danger rounded-circle fs-3">
                                        <i class="bi bi-pause-circle text-white"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-info-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-info mb-0">Products Available</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ $availableProducts->count() }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-info rounded-circle fs-3">
                                        <i class="bi bi-box-seam text-white"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">

                {{-- ── Left: Add/Edit Form ── --}}
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-plus-circle me-2 text-warning"></i>Add / Update Deal
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="dealForm">
                                @csrf
                                <input type="hidden" id="deal_product_id_hidden" name="product_id">

                                {{-- Product Search --}}
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Select Product <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <input type="text" id="productSearch" class="form-control"
                                               placeholder="Search product name or SKU..." autocomplete="off">
                                        <div id="productDropdown"
                                             class="position-absolute w-100 bg-white border rounded shadow-sm"
                                             style="z-index:1050;max-height:260px;overflow-y:auto;display:none;top:100%;left:0;">
                                        </div>
                                    </div>
                                    <div id="selectedProductCard" class="mt-2" style="display:none;">
                                        <div class="d-flex align-items-center p-2 bg-light rounded border">
                                            <img id="selectedProductThumb" src="" alt=""
                                                 class="rounded me-2" style="width:44px;height:44px;object-fit:cover;">
                                            <div class="flex-grow-1">
                                                <div id="selectedProductName" class="fw-semibold small"></div>
                                                <div id="selectedProductMeta" class="text-muted small"></div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-danger" id="clearProduct">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Discount % --}}
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Discount % <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="discount_percentage" id="discount_percentage"
                                               class="form-control" min="1" max="99" placeholder="e.g. 40" required>
                                        <span class="input-group-text fw-bold text-warning">%</span>
                                    </div>
                                    <div id="discountPreview" class="mt-1 text-success small fw-semibold" style="display:none;"></div>
                                </div>

                                {{-- Stock Limit --}}
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Stock Limit</label>
                                    <input type="number" name="stock_limit" id="stock_limit"
                                           class="form-control" min="1" placeholder="Leave empty = use product stock">
                                    <small class="text-muted">Caps how many units are part of the deal</small>
                                </div>

                                {{-- Date Range --}}
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label fw-semibold">Starts At</label>
                                        <input type="datetime-local" name="starts_at" id="starts_at" class="form-control">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-semibold">Ends At</label>
                                        <input type="datetime-local" name="ends_at" id="ends_at" class="form-control">
                                    </div>
                                </div>

                                {{-- Quick duration --}}
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Quick Duration</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm quick-dur" data-h="6">6h</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm quick-dur" data-h="12">12h</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm quick-dur" data-h="24">1 day</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm quick-dur" data-h="48">2 days</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm quick-dur" data-h="168">1 week</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="clearDates">Clear</button>
                                    </div>
                                </div>

                                {{-- Sort order --}}
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Sort Order</label>
                                    <input type="number" name="sort_order" id="sort_order"
                                           class="form-control" value="0" min="0">
                                    <small class="text-muted">Lower = shown first in app</small>
                                </div>

                                {{-- Active --}}
                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox"
                                               name="is_active" id="is_active" checked>
                                        <label class="form-check-label fw-semibold" for="is_active">
                                            Active (visible in app)
                                        </label>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-warning fw-semibold" id="dealSubmitBtn">
                                        <span class="spinner-border spinner-border-sm d-none me-1" id="dealSpinner"></span>
                                        <i class="bi bi-lightning-fill me-1"></i> Save Deal
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- ── Right: Deals Table (server-side DataTable) ── --}}
                <div class="col-xl-8">
                    <x-cb.card title="Current Deals" icon="ri-flashlight-line" :count="$dealStats['total']" :flush="true">
                        <x-slot:tools>
                            <select id="f-deal-status" class="form-select form-select-sm" data-dt-filter="#dealsTable" style="width:auto;">
                                <option value="">All deals</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="expired">Expired</option>
                            </select>
                        </x-slot:tools>
                        <div class="p-3 gz-dt-wrap">
                            <table class="table gz-dt align-middle w-100 mb-0" id="dealsTable">
                                <thead>
                                    <tr>
                                        <th style="width:36px;">#</th>
                                        <th>Product</th>
                                        <th class="text-center">Discount</th>
                                        <th class="text-center">Deal Price</th>
                                        <th class="text-center">Stock Left</th>
                                        <th>Ends At</th>
                                        <th class="text-center">Active</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </x-cb.card>
                </div>

            </div>
        </div>
    </div>
</div>

@php
    $jsProducts = $availableProducts->map(fn($p) => [
        'id'    => $p->id,
        'title' => $p->title,
        'sku'   => $p->sku ?? '',
        'price' => $p->price,
        'stock' => $p->stock,
        'thumb' => $p->thumbnail ? asset('storage/'.$p->thumbnail) : '',
    ]);
    $jsDealsProducts = $dealProducts->map(fn($p) => [
        'id'    => $p->id,
        'title' => $p->title,
        'sku'   => $p->sku ?? '',
        'price' => $p->price,
        'stock' => $p->stock,
        'thumb' => $p->thumbnail ? asset('storage/'.$p->thumbnail) : '',
    ]);
    $allProductsJson = $jsProducts->merge($jsDealsProducts)->unique('id')->values()->toJson();
@endphp

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf;

    const allProducts = {!! $allProductsJson !!};

    // ── Product search ────────────────────────────────────────────────────────
    const elSearch   = document.getElementById('productSearch');
    const elDrop     = document.getElementById('productDropdown');
    const elCard     = document.getElementById('selectedProductCard');
    const elThumb    = document.getElementById('selectedProductThumb');
    const elName     = document.getElementById('selectedProductName');
    const elMeta     = document.getElementById('selectedProductMeta');
    const elHidden   = document.getElementById('deal_product_id_hidden');
    let   selected   = null;

    elSearch.addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();
        if (!term) { elDrop.style.display = 'none'; return; }

        const results = allProducts.filter(p =>
            p.title.toLowerCase().includes(term) || (p.sku ?? '').toLowerCase().includes(term)
        ).slice(0, 8);

        elDrop.innerHTML = results.length
            ? results.map(p => `
                <div class="d-flex align-items-center p-2 border-bottom product-opt"
                     style="cursor:pointer;"
                     data-p='${JSON.stringify(p).replace(/'/g,"&#39;")}'>
                    <img src="${p.thumb}" onerror="this.style.visibility='hidden'"
                         class="rounded me-2 flex-shrink-0"
                         style="width:36px;height:36px;object-fit:cover;background:#eee;">
                    <div>
                        <div class="fw-semibold small">${p.title}</div>
                        <small class="text-muted">SKU: ${p.sku || '-'} &nbsp;·&nbsp; ₦${Number(p.price).toLocaleString()} &nbsp;·&nbsp; Stock: ${p.stock}</small>
                    </div>
                </div>`).join('')
            : '<div class="p-3 text-muted small">No products found</div>';
        elDrop.style.display = 'block';
    });

    document.addEventListener('click', function (e) {
        const opt = e.target.closest('.product-opt');
        if (opt) {
            selected      = JSON.parse(opt.dataset.p);
            elHidden.value     = selected.id;
            elSearch.value     = selected.title;
            elThumb.src        = selected.thumb || '';
            elName.textContent = selected.title;
            elMeta.textContent = `₦${Number(selected.price).toLocaleString()} · Stock: ${selected.stock}`;
            elCard.style.display  = 'block';
            elDrop.style.display  = 'none';
            refreshPreview();
            return;
        }
        if (!elDrop.contains(e.target) && e.target !== elSearch) elDrop.style.display = 'none';
    });

    document.getElementById('clearProduct').addEventListener('click', () => {
        selected = null; elHidden.value = ''; elSearch.value = '';
        elCard.style.display = 'none';
        document.getElementById('discountPreview').style.display = 'none';
    });

    // ── Discount preview ──────────────────────────────────────────────────────
    function refreshPreview() {
        const el  = document.getElementById('discountPreview');
        const pct = parseFloat(document.getElementById('discount_percentage').value) || 0;
        if (selected && pct > 0 && pct < 100) {
            const after  = selected.price * (1 - pct / 100);
            const saving = selected.price - after;
            el.textContent = `Deal price: ₦${after.toLocaleString(undefined,{maximumFractionDigits:0})} · Save ₦${saving.toLocaleString(undefined,{maximumFractionDigits:0})}`;
            el.style.display = 'block';
        } else {
            el.style.display = 'none';
        }
    }
    document.getElementById('discount_percentage').addEventListener('input', refreshPreview);

    // ── Quick duration ────────────────────────────────────────────────────────
    function pad(n) { return String(n).padStart(2,'0'); }
    function toIso(d) { return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`; }

    document.querySelectorAll('.quick-dur').forEach(btn => {
        btn.addEventListener('click', function () {
            const now = new Date();
            const end = new Date(now.getTime() + parseInt(this.dataset.h) * 3600000);
            document.getElementById('starts_at').value = toIso(now);
            document.getElementById('ends_at').value   = toIso(end);
            document.querySelectorAll('.quick-dur').forEach(b => b.classList.remove('btn-secondary','btn-outline-secondary'));
            this.classList.add('btn-secondary');
        });
    });
    document.getElementById('clearDates').addEventListener('click', () => {
        document.getElementById('starts_at').value = '';
        document.getElementById('ends_at').value   = '';
        document.querySelectorAll('.quick-dur').forEach(b => {
            b.classList.remove('btn-secondary');
            b.classList.add('btn-outline-secondary');
        });
    });

    // ── Submit ────────────────────────────────────────────────────────────────
    document.getElementById('dealForm').addEventListener('submit', function (e) {
        e.preventDefault();
        if (!elHidden.value) {
            Swal.fire('Missing Product', 'Please select a product first.', 'warning');
            elSearch.focus(); return;
        }
        const btn     = document.getElementById('dealSubmitBtn');
        const spinner = document.getElementById('dealSpinner');
        btn.disabled  = true; spinner.classList.remove('d-none');

        axios.post('{{ route("lightning-deals.store") }}', {
            product_id:          elHidden.value,
            discount_percentage: document.getElementById('discount_percentage').value,
            stock_limit:         document.getElementById('stock_limit').value || null,
            starts_at:           document.getElementById('starts_at').value   || null,
            ends_at:             document.getElementById('ends_at').value     || null,
            is_active:           document.getElementById('is_active').checked ? 1 : 0,
            sort_order:          document.getElementById('sort_order').value  || 0,
        })
        .then(res => Swal.fire({ icon:'success', title:'Saved!', text: res.data.message, timer:1500, showConfirmButton:false })
              .then(() => location.reload()))
        .catch(err => {
            const errors = err.response?.data?.errors;
            Swal.fire({ icon:'error', title:'Error',
                html: errors ? Object.values(errors).flat().join('<br>') : (err.response?.data?.message || 'Failed') });
        })
        .finally(() => { btn.disabled = false; spinner.classList.add('d-none'); });
    });

    // ── Edit: populate form ───────────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.edit-deal-btn')) return;
        const btn = e.target.closest('.edit-deal-btn');
        // Decode base64 then parse JSON
        let d;
        try {
            d = JSON.parse(atob(btn.getAttribute('data-deal')));
        } catch (err) {
            console.error('Edit parse error:', err);
            Swal.fire('Error', 'Could not read deal data. Check console.', 'error');
            return;
        }

        selected = { id: d.product_id, price: d.product_price };
        elHidden.value      = d.product_id;
        elSearch.value      = d.product_title;
        elThumb.src         = d.product_thumb || '';
        elName.textContent  = d.product_title;
        elMeta.textContent  = `₦${Number(d.product_price).toLocaleString()} · SKU: ${d.product_sku}`;
        elCard.style.display = 'block';

        document.getElementById('discount_percentage').value = d.discount_percentage;
        document.getElementById('stock_limit').value         = d.stock_limit ?? '';
        document.getElementById('starts_at').value           = d.starts_at   ?? '';
        document.getElementById('ends_at').value             = d.ends_at     ?? '';
        document.getElementById('is_active').checked         = !!d.is_active;
        document.getElementById('sort_order').value          = d.sort_order  ?? 0;
        refreshPreview();

        document.getElementById('dealForm').closest('.card').scrollIntoView({ behavior:'smooth' });
    });

    // ── Toggle active ─────────────────────────────────────────────────────────
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('toggle-deal')) return;
        const id = e.target.dataset.id;
        axios.patch(`/lightning-deals/${id}/toggle`)
            .then(res => {
                const t = document.createElement('div');
                t.className = 'alert alert-success position-fixed bottom-0 end-0 m-3 shadow';
                t.style.zIndex = 9999;
                t.textContent  = res.data.message;
                document.body.appendChild(t);
                setTimeout(() => t.remove(), 2000);
            })
            .catch(() => {
                e.target.checked = !e.target.checked;
                Swal.fire('Error','Failed to update status','error');
            });
    });

    // ── Delete ────────────────────────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.delete-deal-btn')) return;
        const id = e.target.closest('.delete-deal-btn').dataset.id;
        Swal.fire({ title:'Remove Deal?', text:'The product is not affected.',
            icon:'warning', showCancelButton:true,
            confirmButtonText:'Yes, remove!', confirmButtonColor:'#dc3545' })
        .then(r => {
            if (!r.isConfirmed) return;
            axios.delete(`/lightning-deals/${id}`)
                .then(() => {
                    window.dealsTable && window.dealsTable.ajax.reload(null, false);
                    Swal.fire({ icon:'success', title:'Removed!', timer:1200, showConfirmButton:false });
                })
                .catch(() => Swal.fire('Error','Failed to delete deal','error'));
        });
    });

    // ── Server-side table ─────────────────────────────────────────────────────
    window.dealsTable = GZ.dt('#dealsTable', {
        url: @json(route('lightning-deals.data')),
        order: [[0, 'asc']],
        filters: function () { return { status: $('#f-deal-status').val() }; },
        columns: [
            { data: 'sort_order',          name: 'lightning_deals.sort_order', searchable: false },
            { data: 'product',             name: 'product' },
            { data: 'discount_percentage', name: 'lightning_deals.discount_percentage', searchable: false, className: 'text-center' },
            { data: 'deal_price',          name: 'deal_price', orderable: false, searchable: false, className: 'text-center' },
            { data: 'stock_left',          name: 'stock_left', orderable: false, searchable: false, className: 'text-center' },
            { data: 'ends_at',             name: 'lightning_deals.ends_at', searchable: false },
            { data: 'is_active',           name: 'lightning_deals.is_active', searchable: false, className: 'text-center' },
            { data: 'action',              name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ]
    });

});
</script>
@endsection
