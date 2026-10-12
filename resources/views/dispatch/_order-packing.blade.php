{{-- Order page: packing, parcels, labels and packer / checker sign-off. --}}
@php
    use App\Models\Dispatch\OrderParcel;
    $pkParcels   = $order->parcels()->get();
    $pkBatch     = $order->dispatchBatch;
    $pkCanPack   = auth()->user()?->can('Pack orders') || auth()->user()?->can('Manage order');
    $pkEditable  = $pkCanPack && !$order->dispatch_batch_id && in_array($order->status, ['pending', 'processing'], true);
    $pkBranches  = \App\Models\StockLocation::active()->orderBy('name')->get(['id', 'name']);
    $pkZones     = \App\Models\Dispatch\DeliveryZone::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'code', 'name']);
    $pkStatus    = $order->packing_status ?: 'unpacked';
    $pkBadge     = ['unpacked' => 'secondary', 'packing' => 'warning', 'packed' => 'warning', 'verified' => 'success'][$pkStatus] ?? 'secondary';
    $pkLabel     = ['unpacked' => 'Not packed', 'packing' => 'Packing', 'packed' => 'Packed · awaiting check', 'verified' => 'Packed & verified'][$pkStatus] ?? ucfirst($pkStatus);
    $pkRows      = old('parcels', $pkParcels->map(fn ($p) => [
        'package_type' => $p->package_type, 'handling' => $p->handling ?? [], 'weight_kg' => $p->weight_kg, 'contents' => $p->contents,
    ])->all() ?: [['package_type' => 'bag', 'handling' => [], 'weight_kg' => null, 'contents' => null]]);
    $pkPacker    = $order->packer()->first();
    $pkChecker   = $order->checker()->first();
@endphp

<div class="card border-0 shadow-sm" id="packing">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0"><i class="ri-archive-2-line me-1"></i>Packing &amp; parcels
            <span class="badge bg-{{ $pkBadge }}-subtle text-{{ $pkBadge }} ms-1">{{ $pkLabel }}</span>
        </h5>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('adminorders.packing-slip', $order->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="ri-file-list-3-line"></i> Packing slip</a>
            @if($pkParcels->count())
                <div class="btn-group">
                    <a href="{{ route('adminorders.labels', $order->id) }}" target="_blank" class="btn btn-sm btn-dark"><i class="ri-barcode-line"></i> Print {{ $pkParcels->count() }} label{{ $pkParcels->count() > 1 ? 's' : '' }}</a>
                    <button type="button" class="btn btn-sm btn-dark dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" target="_blank" href="{{ route('adminorders.labels', [$order->id, 'size' => 'standard']) }}">100 × 150 mm (4×6")</a></li>
                        <li><a class="dropdown-item" target="_blank" href="{{ route('adminorders.labels', [$order->id, 'size' => 'compact']) }}">100 × 75 mm (compact)</a></li>
                        <li><hr class="dropdown-divider"></li>
                        @foreach($pkParcels as $p)
                            <li><a class="dropdown-item" target="_blank" href="{{ route('adminorders.labels', [$order->id, 'parcel' => $p->id]) }}">Reprint parcel {{ $p->sequence }} ({{ $p->code }})</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
    <div class="card-body">

        {{-- Summary --}}
        <div class="row g-3 small mb-2">
            <div class="col-sm-3"><div class="text-muted">Branch</div><div class="fw-semibold">{{ $order->branch?->name ?? '—' }}</div></div>
            <div class="col-sm-3"><div class="text-muted">Delivery zone</div><div class="fw-semibold">{{ $order->deliveryZone ? $order->deliveryZone->code . ' · ' . $order->deliveryZone->name : 'Not matched' }}</div></div>
            <div class="col-sm-3"><div class="text-muted">Packed by</div><div class="fw-semibold">{{ $pkPacker?->full_name ?? '—' }}</div><div class="text-muted">{{ $order->packed_at?->format('j M H:i') }}</div></div>
            <div class="col-sm-3"><div class="text-muted">Checked by</div><div class="fw-semibold">{{ $pkChecker?->full_name ?? '—' }}</div><div class="text-muted">{{ $order->checked_at?->format('j M H:i') }}</div></div>
        </div>

        @if($pkBatch)
            <div class="alert alert-primary py-2 small d-flex justify-content-between align-items-center">
                <span><i class="ri-truck-line"></i> On batch <b>{{ $pkBatch->code }}</b> · route <b>{{ $pkBatch->route_code }}</b> · stop {{ $order->stop_sequence }}
                    · {{ $pkBatch->statusLabel() }}@if($pkBatch->rider) · rider {{ $pkBatch->rider->name }}@endif</span>
                @can('View dispatch')<a href="{{ route('dispatch.show', $pkBatch) }}" class="btn btn-sm btn-primary">Open batch</a>@endcan
            </div>
        @elseif($order->dispatch_status === 'returned' && $order->dispatch_note)
            <div class="alert alert-warning py-2 small"><i class="ri-arrow-go-back-line"></i> {{ $order->dispatch_note }}</div>
        @endif

        @if($pkParcels->count())
            <div class="table-responsive mb-3">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Parcel</th><th>Code</th><th>Type</th><th>Handling</th><th>Weight</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($pkParcels as $p)
                        <tr>
                            <td class="fw-semibold">{{ $p->sequence }} of {{ $pkParcels->count() }}</td>
                            <td class="font-monospace fw-semibold">{{ $p->code }}</td>
                            <td>{{ $p->typeLabel() }}@if($p->contents)<div class="text-muted small">{{ $p->contents }}</div>@endif</td>
                            <td>@forelse($p->handlingLabels() as $t)<span class="badge bg-dark me-1">{{ $t }}</span>@empty <span class="text-muted">—</span> @endforelse</td>
                            <td>{{ $p->weight_kg ? rtrim(rtrim(number_format($p->weight_kg, 2), '0'), '.') . ' kg' : '—' }}</td>
                            <td><span class="badge bg-{{ $p->statusBadge() }}">{{ str_replace('_', ' ', $p->status) }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($order->packing_notes)
            <div class="small mb-3"><span class="text-muted">Packing notes:</span> {{ $order->packing_notes }}</div>
        @endif

        {{-- Verify --}}
        @if($pkEditable && $pkStatus === 'packed')
            <form method="POST" action="{{ route('adminorders.packing.verify', $order->id) }}" class="border rounded p-3 mb-3 bg-success-subtle">
                @csrf
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="small">
                        <b>Second check:</b> count the parcels, compare items against the packing slip, check every label is on the right bag.
                        @if((int) $order->packed_by === auth()->id())
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" name="self_check" value="1" id="pkSelf">
                                <label class="form-check-label" for="pkSelf">I packed this order and checked it myself</label>
                            </div>
                        @endif
                    </div>
                    <button class="btn btn-success"><i class="ri-shield-check-line"></i> Verify packing</button>
                </div>
            </form>
        @endif

        {{-- Packing form --}}
        @if($pkEditable)
            <a class="btn btn-sm {{ $pkStatus === 'unpacked' ? 'btn-primary' : 'btn-outline-primary' }}" data-bs-toggle="collapse" href="#pkForm">
                <i class="ri-edit-box-line"></i> {{ $pkStatus === 'unpacked' ? 'Pack this order' : 'Edit packing' }}
            </a>
            <div class="collapse {{ $pkStatus === 'unpacked' || $errors->has('parcels.*') || old('parcels') ? 'show' : '' }} mt-3" id="pkForm">
            <form method="POST" action="{{ route('adminorders.packing.save', $order->id) }}" id="pkFormEl">
                @csrf
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small mb-1">Packing branch</label>
                        <select name="stock_location_id" class="form-select form-select-sm">
                            @foreach($pkBranches as $b)
                                <option value="{{ $b->id }}" @selected(old('stock_location_id', $order->stock_location_id ?? \App\Models\StockLocation::getDefault()?->id) == $b->id)>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small mb-1">Delivery zone</label>
                        <select name="delivery_zone_id" class="form-select form-select-sm">
                            <option value="">Auto-detect from address</option>
                            @foreach($pkZones as $z)
                                <option value="{{ $z->id }}" @selected(old('delivery_zone_id', $order->delivery_zone_id) == $z->id)>{{ $z->code }} · {{ $z->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <h6 class="small text-uppercase text-muted">1 · Pick the items</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Item</th><th class="text-center" style="width:80px;">Ordered</th><th style="width:100px;">Picked</th><th>Substitution / note</th></tr></thead>
                        <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td>{{ $item->title ?? $item->product?->name }}@if($item->sku)<div class="text-muted small">{{ $item->sku }}</div>@endif</td>
                                <td class="text-center fw-semibold">{{ $item->quantity }}</td>
                                <td><input type="number" min="0" class="form-control form-control-sm pk-picked" data-ordered="{{ $item->quantity }}"
                                           name="items[{{ $item->id }}][picked_quantity]" value="{{ old("items.{$item->id}.picked_quantity", $item->picked_quantity ?? $item->quantity) }}"></td>
                                <td><input type="text" class="form-control form-control-sm" maxlength="500" placeholder="e.g. Replaced with Peak 400g — customer agreed"
                                           name="items[{{ $item->id }}][substitution]" value="{{ old("items.{$item->id}.substitution", $item->substitution) }}"></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="small text-uppercase text-muted mb-0">2 · Bags &amp; cartons</h6>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="small text-muted">Number of parcels</span>
                        <input type="number" min="1" max="99" class="form-control form-control-sm" style="width:70px;" id="pkCount" value="{{ count($pkRows) }}">
                    </div>
                </div>
                <div id="pkParcels"></div>
                <template id="pkRowTpl">
                    <div class="border rounded p-2 mb-2 pk-row">
                        <div class="row g-2 align-items-center">
                            <div class="col-auto"><span class="badge bg-dark pk-num">1</span></div>
                            <div class="col-md-3">
                                <select class="form-select form-select-sm" data-f="package_type">
                                    @foreach(OrderParcel::TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-2"><input type="number" step="0.01" min="0" class="form-control form-control-sm" data-f="weight_kg" placeholder="kg"></div>
                            <div class="col"><input type="text" class="form-control form-control-sm" data-f="contents" maxlength="500" placeholder="Contents (optional) e.g. frozen chicken, ice cream"></div>
                            <div class="col-auto"><button type="button" class="btn btn-sm btn-outline-danger pk-del" title="Remove"><i class="ri-delete-bin-line"></i></button></div>
                        </div>
                        <div class="mt-2 d-flex flex-wrap gap-1">
                            @foreach(OrderParcel::HANDLING as $k => [$l])
                                <label class="btn btn-sm btn-outline-dark py-0 px-2 pk-tag mb-0"><input type="checkbox" class="d-none" data-h="{{ $k }}"> {{ ucwords(strtolower($l)) }}</label>
                            @endforeach
                        </div>
                    </div>
                </template>

                <h6 class="small text-uppercase text-muted mt-3">3 · Special packing notes</h6>
                <textarea name="packing_notes" class="form-control form-control-sm mb-3" rows="2" maxlength="1000" placeholder="e.g. Eggs packed on top, cold items in cooler box">{{ old('packing_notes', $order->packing_notes) }}</textarea>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-muted"><span id="pkShort" class="text-danger fw-semibold"></span></span>
                    <button class="btn btn-primary"><i class="ri-save-3-line"></i> Save packing</button>
                </div>
            </form>
            </div>

            @push('scripts')
            <script>
            (function () {
                const rows = @json(array_values($pkRows));
                const box = document.getElementById('pkParcels'), tpl = document.getElementById('pkRowTpl'), count = document.getElementById('pkCount');
                const style = l => l.classList.toggle('active', l.querySelector('input').checked);

                function addRow(d) {
                    d = d || { package_type: 'bag', handling: [] };
                    const n = tpl.content.firstElementChild.cloneNode(true);
                    n.querySelector('[data-f=package_type]').value = d.package_type || 'bag';
                    n.querySelector('[data-f=weight_kg]').value = d.weight_kg ?? '';
                    n.querySelector('[data-f=contents]').value = d.contents ?? '';
                    n.querySelectorAll('[data-h]').forEach(cb => { cb.checked = (d.handling || []).includes(cb.dataset.h); style(cb.closest('label')); });
                    n.querySelectorAll('.pk-tag').forEach(l => l.addEventListener('click', e => { e.preventDefault(); const cb = l.querySelector('input'); cb.checked = !cb.checked; style(l); }));
                    n.querySelector('.pk-del').addEventListener('click', () => { if (box.children.length > 1) { n.remove(); renumber(); } });
                    box.appendChild(n);
                }
                function renumber() {
                    [...box.children].forEach((r, i) => {
                        r.querySelector('.pk-num').textContent = (i + 1) + ' of ' + box.children.length;
                        r.querySelectorAll('[data-f]').forEach(el => el.name = `parcels[${i}][${el.dataset.f}]`);
                        r.querySelectorAll('[data-h]').forEach(cb => { cb.name = `parcels[${i}][handling][]`; cb.value = cb.dataset.h; });
                    });
                    count.value = box.children.length;
                }
                rows.forEach(addRow); renumber();
                count.addEventListener('change', () => {
                    const want = Math.max(1, Math.min(99, parseInt(count.value || '1', 10)));
                    while (box.children.length < want) addRow(box.lastElementChild ? null : undefined);
                    while (box.children.length > want) box.lastElementChild.remove();
                    renumber();
                });
                const shortEl = document.getElementById('pkShort');
                const checkShort = () => {
                    const s = [...document.querySelectorAll('.pk-picked')].filter(i => i.value !== '' && +i.value < +i.dataset.ordered).length;
                    shortEl.textContent = s ? `${s} item(s) picked short — add a substitution or note.` : '';
                };
                document.querySelectorAll('.pk-picked').forEach(i => i.addEventListener('input', checkShort)); checkShort();
            })();
            </script>
            @endpush
        @elseif(!$pkCanPack && $pkStatus === 'unpacked')
            <div class="text-muted small">Not packed yet.</div>
        @endif
    </div>
</div>
