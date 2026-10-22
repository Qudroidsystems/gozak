@extends('layouts.master')

@section('title', 'Attribute Library')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Attribute Library</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('web.products.index') }}">Products</a></li>
                                <li class="breadcrumb-item active">Attribute Library</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info d-flex gap-2 align-items-start">
                <i class="bi bi-info-circle fs-5"></i>
                <div>
                    Set up options like <strong>Size</strong>, <strong>Colour</strong> or <strong>Shoe Size</strong> once, then pick them when adding a
                    variable product: the values fill in for you. Colours get a swatch that shoppers see in the app.
                    Removing an attribute here never changes products that already use it.
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Attributes ({{ $attributes->count() }})</h5>
                    <button type="button" class="btn btn-primary btn-sm" onclick="openAttribute()">
                        <i class="ri-add-line me-1"></i>New attribute
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:22%">Name</th>
                                    <th style="width:12%">Shown as</th>
                                    <th>Values</th>
                                    <th style="width:10%" class="text-center">Used by</th>
                                    <th style="width:120px"></th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($attributes as $attr)
                                <tr class="{{ $attr->is_active ? '' : 'opacity-50' }}">
                                    <td>
                                        <div class="fw-semibold">{{ $attr->name }}</div>
                                        @if($attr->hint)<small class="text-muted">{{ $attr->hint }}</small>@endif
                                        @unless($attr->is_active)<span class="badge bg-secondary ms-1">Hidden</span>@endunless
                                    </td>
                                    <td>
                                        @php $labels = ['button' => 'Text chips', 'size' => 'Size boxes', 'color' => 'Colour swatches', 'image' => 'Photo chips']; @endphp
                                        <span class="badge bg-light text-dark border">{{ $labels[$attr->display_type] ?? $attr->display_type }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($attr->values as $val)
                                                <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1 fw-normal">
                                                    @if($val->color_hex)
                                                        <span style="width:12px;height:12px;border-radius:50%;background:{{ $val->color_hex }};border:1px solid rgba(0,0,0,.2);display:inline-block"></span>
                                                    @endif
                                                    {{ $val->value }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="text-center">{{ $attr->product_attributes_count }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-soft-primary" onclick='openAttribute(@json($attr))'><i class="ri-pencil-line"></i></button>
                                        <button class="btn btn-sm btn-soft-danger" onclick="deleteAttribute({{ $attr->id }}, @json($attr->name))"><i class="ri-delete-bin-line"></i></button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        No attributes yet. Click <strong>New attribute</strong>, or run
                                        <code>php artisan db:seed --class=AttributeLibrarySeeder</code> to load a starter set
                                        (Colour, Size, Shoe Size, Storage, Weight …).
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ── Edit modal ── --}}
            <div class="modal fade" id="attributeModal" tabindex="-1" data-bs-backdrop="static">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <form id="attributeForm">
                            <input type="hidden" id="attr_id">
                            <div class="modal-header">
                                <h5 class="modal-title" id="attrModalTitle">New attribute</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label">Name *</label>
                                        <input type="text" id="attr_name" class="form-control" placeholder="e.g. Size" required maxlength="60">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Shown in the app as</label>
                                        <select id="attr_display_type" class="form-select">
                                            <option value="button">Text chips</option>
                                            <option value="size">Size boxes</option>
                                            <option value="color">Colour swatches</option>
                                            <option value="image">Photo chips (variant photo)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="attr_active" checked>
                                            <label class="form-check-label" for="attr_active">Available</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Hint <span class="text-muted">(optional, shown under the name)</span></label>
                                        <input type="text" id="attr_hint" class="form-control" placeholder="e.g. UK sizing" maxlength="120">
                                    </div>
                                </div>

                                <hr>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Values *</label>
                                    <div class="input-group input-group-sm" style="max-width:340px">
                                        <input type="text" id="attr_quick" class="form-control" placeholder="Quick add: S, M, L, XL">
                                        <button type="button" class="btn btn-outline-secondary" onclick="quickAdd()">Add</button>
                                    </div>
                                </div>
                                <small class="text-muted d-block mb-2">Drag <i class="ri-drag-move-2-line"></i> to set the order shoppers see.</small>
                                <div id="valuesList" class="d-flex flex-column gap-2"></div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addValueRow()">+ Add value</button>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="attrSaveBtn">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.7.7/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const token = document.querySelector('meta[name="csrf-token"]');
    if (token) axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');

    const base = @json(url('web/catalog/attributes'));
    const modalEl = document.getElementById('attributeModal');
    const list = document.getElementById('valuesList');
    let modal;

    Sortable.create(list, { handle: '.drag-handle', animation: 150 });

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    function isColour() { return document.getElementById('attr_display_type').value === 'color'; }

    window.addValueRow = function (value = '', hex = '') {
        list.insertAdjacentHTML('beforeend', `
            <div class="d-flex gap-2 align-items-center value-row">
                <span class="drag-handle text-muted" style="cursor:grab"><i class="ri-drag-move-2-line fs-5"></i></span>
                <input type="text" class="form-control form-control-sm val-value" value="${esc(value)}" placeholder="Value" maxlength="60">
                <div class="colour-fields d-flex gap-1 align-items-center" style="${isColour() ? '' : 'display:none!important'}">
                    <input type="color" class="form-control form-control-color form-control-sm val-picker" value="${hex || '#000000'}" title="Pick colour">
                    <input type="text" class="form-control form-control-sm val-hex" value="${esc(hex)}" placeholder="#RRGGBB" style="width:100px" maxlength="9">
                </div>
                <button type="button" class="btn btn-sm btn-link text-danger" onclick="this.closest('.value-row').remove()"><i class="ri-close-line"></i></button>
            </div>`);
    };

    list.addEventListener('input', e => {
        const row = e.target.closest('.value-row');
        if (!row) return;
        if (e.target.classList.contains('val-picker')) row.querySelector('.val-hex').value = e.target.value.toUpperCase();
        if (e.target.classList.contains('val-hex') && /^#?[0-9a-f]{6}$/i.test(e.target.value)) {
            row.querySelector('.val-picker').value = '#' + e.target.value.replace('#', '');
        }
    });

    document.getElementById('attr_display_type').addEventListener('change', () => {
        list.querySelectorAll('.colour-fields').forEach(el => el.style.cssText = isColour() ? '' : 'display:none!important');
    });

    window.quickAdd = function () {
        const input = document.getElementById('attr_quick');
        input.value.split(',').map(v => v.trim()).filter(Boolean).forEach(v => addValueRow(v));
        input.value = '';
    };
    document.getElementById('attr_quick').addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); quickAdd(); }
    });

    window.openAttribute = function (attr = null) {
        document.getElementById('attrModalTitle').textContent = attr ? 'Edit ' + attr.name : 'New attribute';
        document.getElementById('attr_id').value = attr?.id ?? '';
        document.getElementById('attr_name').value = attr?.name ?? '';
        document.getElementById('attr_hint').value = attr?.hint ?? '';
        document.getElementById('attr_display_type').value = attr?.display_type ?? 'button';
        document.getElementById('attr_active').checked = attr ? !!attr.is_active : true;
        list.innerHTML = '';
        (attr?.values ?? []).forEach(v => addValueRow(v.value, v.color_hex ?? ''));
        if (!attr) addValueRow();
        modal = modal || new bootstrap.Modal(modalEl);
        modal.show();
    };

    document.getElementById('attributeForm').addEventListener('submit', async e => {
        e.preventDefault();
        const id = document.getElementById('attr_id').value;
        const values = [...list.querySelectorAll('.value-row')].map(r => ({
            value: r.querySelector('.val-value').value.trim(),
            color_hex: isColour() ? (r.querySelector('.val-hex').value.trim() || null) : null,
        })).filter(v => v.value);

        const payload = {
            name: document.getElementById('attr_name').value.trim(),
            hint: document.getElementById('attr_hint').value.trim() || null,
            display_type: document.getElementById('attr_display_type').value,
            is_active: document.getElementById('attr_active').checked ? 1 : 0,
            values,
        };

        const btn = document.getElementById('attrSaveBtn');
        btn.disabled = true;
        try {
            if (id) await axios.put(`${base}/${id}`, payload);
            else await axios.post(base, payload);
            Swal.fire({ icon: 'success', title: 'Saved', timer: 1200, showConfirmButton: false }).then(() => location.reload());
        } catch (err) {
            const errors = err.response?.data?.errors;
            Swal.fire({ icon: 'error', title: 'Could not save',
                html: errors ? Object.values(errors).flat().map(esc).join('<br>') : esc(err.response?.data?.message || 'Something went wrong') });
        } finally {
            btn.disabled = false;
        }
    });

    window.deleteAttribute = function (id, name) {
        Swal.fire({
            icon: 'warning', title: `Remove “${name}”?`,
            text: 'Products that already use it keep their options. It just won’t be offered for new products.',
            showCancelButton: true, confirmButtonText: 'Remove', confirmButtonColor: '#d33',
        }).then(r => {
            if (!r.isConfirmed) return;
            axios.delete(`${base}/${id}`).then(() => location.reload())
                .catch(err => Swal.fire('Error', err.response?.data?.message || 'Could not remove', 'error'));
        });
    };
})();
</script>
@endsection
