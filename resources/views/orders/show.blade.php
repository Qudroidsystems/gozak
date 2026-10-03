{{-- resources/views/orders/show.blade.php --}}
@extends('layouts.master')

@section('title', 'Order #' . ($order->invoice_number ?? substr($order->id, 0, 8)))

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            {{-- Result of refunds / notes / status changes --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ $errors->first() }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-sm-0">
                                Order #<span class="text-primary fw-bold">{{ $order->invoice_number ?? substr($order->id, 0, 8) }}</span>
                            </h4>
                        </div>
                        <div class="d-flex gap-2">
                            <button onclick="emailInvoice('{{ $order->id }}')" class="btn btn-info">
                                Email Invoice
                            </button>
                            <a href="{{ route('adminorders.invoice', $order->id) }}" target="_blank" class="btn btn-primary">
                                PDF Invoice
                            </a>
                            <a href="{{ route('adminorders.packing-slip', $order->id) }}" target="_blank" class="btn btn-secondary">
                                Print Packing Slip
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Left Column -->
                <div class="col-xl-4">
                    <!-- Customer Card -->
                    <div class="card">
                        <div class="card-header">
                            <h5>Customer</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar-lg me-3">
                                    <div class="avatar-title bg-primary-subtle rounded-circle fs-3">
                                        {{ Str::substr($order->user->first_name, 0, 1) }}
                                    </div>
                                </div>
                                <div>
                                    <h5>{{ $order->user->first_name }} {{ $order->user->last_name }}</h5>
                                    <p class="text-muted mb-1">{{ $order->user->email }}</p>
                                    @if($order->user->phone_number)
                                        <p class="text-muted mb-0">
                                            <i class="bi bi-telephone"></i> {{ $order->user->phone_number }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Refunds -->
                    @php
                        $rs = app(\App\Services\Payment\RefundService::class)->summary($order);
                        $canRefund = auth()->user()?->can('Refund order');
                        $refundReasons = ['Customer cancelled', 'Item out of stock', 'Damaged item', 'Wrong item sent', 'Late delivery', 'Duplicate payment', 'Price adjustment', 'Other'];
                    @endphp
                    @if($canRefund && $rs['paid'] && ($rs['refundable'] > 0 || $order->refunds->count()))
                    <div class="card border-0 shadow-sm" id="refunds">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="ri-refund-2-line me-1"></i>Refunds</h5>
                            @if($order->payment_status === 'refunded')
                                <span class="badge bg-info-subtle text-info">Fully refunded</span>
                            @endif
                        </div>
                        <div class="card-body">
                            {{-- Summary --}}
                            <div class="row g-2 text-center mb-3">
                                <div class="col-4">
                                    <div class="p-2 rounded bg-light">
                                        <div class="small text-muted">Paid</div>
                                        <div class="fw-bold">₦{{ number_format($order->total_amount, 2) }}</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 rounded bg-light">
                                        <div class="small text-muted">Refunded{{ $rs['pending'] > 0 ? ' / pending' : '' }}</div>
                                        <div class="fw-bold text-danger">₦{{ number_format($rs['refunded'], 2) }}@if($rs['pending'] > 0)<span class="text-warning"> / ₦{{ number_format($rs['pending'], 2) }}</span>@endif</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 rounded bg-light">
                                        <div class="small text-muted">Can refund</div>
                                        <div class="fw-bold text-success">₦{{ number_format($rs['refundable'], 2) }}</div>
                                    </div>
                                </div>
                            </div>
                            @if($rs['transaction'])
                                <p class="small text-muted mb-3">
                                    Paid with <strong>{{ ucfirst($rs['gateway']) }}</strong> · ref <code>{{ $rs['transaction']->reference }}</code>
                                    @if($rs['transaction']->paid_at) · {{ $rs['transaction']->paid_at->format('d M Y, H:i') }}@endif
                                </p>
                            @endif

                            @if($rs['refundable'] > 0)
                            <form action="{{ route('adminorders.refund', $order->id) }}" method="POST" id="refundForm">
                                @csrf
                                <label class="form-label fw-semibold">How should the customer get the money back?</label>
                                <div class="d-grid gap-2 mb-3">
                                    <label class="border rounded p-2 d-flex gap-2 align-items-start {{ $rs['can_auto_refund'] ? '' : 'opacity-50' }}">
                                        <input class="form-check-input mt-1" type="radio" name="method" value="gateway" {{ $rs['can_auto_refund'] ? 'checked' : 'disabled' }}>
                                        <span>
                                            <strong>Refund automatically via Paystack</strong>
                                            <small class="d-block text-muted">
                                                @if($rs['can_auto_refund'])
                                                    Money goes back to the card / bank account the customer paid with. Usually takes a few minutes to a few working days.
                                                @else
                                                    Only for orders paid with Paystack{{ $rs['gateway'] ? ' (this one was paid with ' . ucfirst($rs['gateway']) . ')' : '' }}.
                                                @endif
                                            </small>
                                        </span>
                                    </label>
                                    <label class="border rounded p-2 d-flex gap-2 align-items-start">
                                        <input class="form-check-input mt-1" type="radio" name="method" value="manual" {{ $rs['can_auto_refund'] ? '' : 'checked' }}>
                                        <span>
                                            <strong>I already refunded the customer</strong>
                                            <small class="d-block text-muted">Cash, bank transfer, or from the OPay / Paystack dashboard — just record it here.</small>
                                        </span>
                                    </label>
                                </div>

                                <div id="manualFields" class="row g-2 mb-3" style="display:none;">
                                    @php $gzBanks = $order->user ? $order->user->bankAccounts()->get() : collect(); @endphp
                                    <div class="col-12">
                                        <div class="border rounded p-2 small" style="background:var(--cb-surface-2,#f8fafc);">
                                            <div class="fw-semibold mb-1"><i class="ri-bank-line me-1"></i>Customer's bank account{{ $gzBanks->count() > 1 ? 's' : '' }}</div>
                                            @forelse($gzBanks as $acct)
                                                <div class="d-flex justify-content-between align-items-center gap-2 py-1 {{ !$loop->last ? 'border-bottom' : '' }}">
                                                    <div>
                                                        <div><b>{{ $acct->account_name }}</b>
                                                            @if($acct->is_default)<span class="badge bg-success-subtle text-success ms-1">default</span>@endif
                                                            @unless($acct->name_matches)<span class="badge bg-warning-subtle text-warning ms-1" title="The bank name doesn't look like the customer's name">name differs</span>@endunless
                                                        </div>
                                                        <div class="text-muted">{{ $acct->bank_name }} · <span class="font-monospace">{{ $acct->account_number }}</span></div>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-light" onclick="navigator.clipboard && navigator.clipboard.writeText('{{ $acct->account_number }}'); this.innerText='Copied';">Copy</button>
                                                </div>
                                            @empty
                                                <div class="text-muted">No bank account saved. Ask the customer to add one in the app (Account → Bank Accounts).</div>
                                            @endforelse
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Refunded via</label>
                                        <select name="channel" class="form-select form-select-sm">
                                            <option value="bank_transfer">Bank transfer</option>
                                            <option value="cash">Cash</option>
                                            <option value="opay" {{ $rs['gateway'] === 'opay' ? 'selected' : '' }}>OPay dashboard</option>
                                            <option value="paystack">Paystack dashboard</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Reference (optional)</label>
                                        <input type="text" name="manual_reference" class="form-control form-control-sm" placeholder="Transfer / receipt no.">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold d-flex justify-content-between">
                                        <span>Amount</span>
                                        <small class="text-muted">max ₦{{ number_format($rs['refundable'], 2) }}</small>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">₦</span>
                                        <input type="number" step="0.01" min="1" max="{{ $rs['refundable'] }}" name="amount" id="refundAmount"
                                               class="form-control" value="{{ old('amount') }}" required>
                                        <button class="btn btn-outline-secondary" type="button" id="refundFull" data-amount="{{ $rs['refundable'] }}">Full</button>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fw-semibold">Reason</label>
                                    <select name="reason" class="form-select" required>
                                        @foreach($refundReasons as $r)
                                            <option value="{{ $r }}">{{ $r }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <textarea name="note" class="form-control" rows="2" maxlength="500" placeholder="Note (optional) — shown in the order history"></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger w-100" id="refundSubmit">
                                    <i class="ri-refund-2-line me-1"></i><span>Refund</span>
                                </button>
                            </form>
                            @endif
                        </div>

                        {{-- History --}}
                        @if($order->refunds->count())
                        <div class="border-top">
                            @foreach($order->refunds as $refund)
                                @php
                                    $badge = [
                                        'processed'  => 'bg-success-subtle text-success',
                                        'pending'    => 'bg-warning-subtle text-warning',
                                        'processing' => 'bg-warning-subtle text-warning',
                                        'failed'     => 'bg-danger-subtle text-danger',
                                        'rejected'   => 'bg-secondary-subtle text-secondary',
                                    ][$refund->status] ?? 'bg-light text-dark';
                                @endphp
                                <div class="p-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <strong>₦{{ number_format($refund->amount, 2) }}</strong>
                                            <span class="text-muted small">· {{ $refund->method_label }}</span>
                                            <small class="d-block text-muted">{{ $refund->reason }}</small>
                                            <small class="d-block text-muted">
                                                {{ $refund->created_at?->format('d M Y, H:i') }}
                                                @if($refund->admin) · by {{ trim($refund->admin->first_name . ' ' . $refund->admin->last_name) }}@endif
                                                @if($refund->manual_reference) · ref {{ $refund->manual_reference }}@endif
                                                @if($refund->gateway_refund_id) · Paystack #{{ $refund->gateway_refund_id }}@endif
                                            </small>
                                            @if($refund->status === 'failed' && $refund->failure_reason)
                                                <small class="d-block text-danger">{{ $refund->failure_reason }}</small>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            <span class="badge {{ $badge }}">{{ ucfirst($refund->status) }}</span>
                                            @if($canRefund && $refund->isOpen() && $refund->method === 'gateway' && $refund->gateway_refund_id)
                                                <form action="{{ route('adminorders.refund-status', [$order->id, $refund->id]) }}" method="POST" class="mt-1">
                                                    @csrf
                                                    <button class="btn btn-link btn-sm p-0">Check status</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    @push('scripts')
                    <script>
                    (function () {
                        const form = document.getElementById('refundForm');
                        if (!form) return;
                        const manual = document.getElementById('manualFields');
                        const amount = document.getElementById('refundAmount');
                        const btn = document.getElementById('refundSubmit');
                        const sync = () => {
                            const m = form.querySelector('input[name="method"]:checked')?.value;
                            manual.style.display = m === 'manual' ? '' : 'none';
                            btn.querySelector('span').textContent = m === 'gateway' ? 'Refund via Paystack' : 'Record refund';
                        };
                        form.querySelectorAll('input[name="method"]').forEach(r => r.addEventListener('change', sync));
                        document.getElementById('refundFull').addEventListener('click', e => { amount.value = e.currentTarget.dataset.amount; });
                        sync();
                        form.addEventListener('submit', e => {
                            const m = form.querySelector('input[name="method"]:checked')?.value;
                            const v = Number(amount.value || 0).toLocaleString('en-NG', { minimumFractionDigits: 2 });
                            const msg = m === 'gateway'
                                ? `Send ₦${v} back to the customer through Paystack? This cannot be undone.`
                                : `Record a refund of ₦${v} that you already paid to the customer?`;
                            if (!confirm(msg)) { e.preventDefault(); return; }
                            btn.disabled = true;
                            btn.querySelector('span').textContent = 'Processing…';
                        });
                    })();
                    </script>
                    @endpush
                    @endif
                </div>

                <!-- Right Column -->
                <div class="col-xl-8">
                    <!-- Status Update -->
                    <div class="card mb-3">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h5>Current Status:
                                    <span class="badge bg-primary-subtle text-primary fs-6">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </h5>
                            </div>
                            <select class="form-select w-auto status-select" data-id="{{ $order->id }}">
                                @foreach(['pending','processing','shipped','delivered','cancelled'] as $s)
                                    <option value="{{ $s }}" {{ $order->status == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="card">
                        <div class="card-header">
                            <h5>Items ({{ $order->items_count }})</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Qty</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($order->items as $item)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($item->image)
                                                        <img src="{{ $item->image }}" class="rounded me-3" style="width:50px;height:50px;">
                                                    @endif
                                                    {{ $item->title }}
                                                </div>
                                            </td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>₦{{ number_format($item->price, 2) }}</td>
                                            <td>₦{{ number_format($item->price * $item->quantity, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="card">
                        <div class="card-body">
                            <div class="row justify-content-end">
                                <div class="col-md-5">
                                    <table class="table table-sm">
                                        <tr><td>Subtotal</td><td class="text-end">₦{{ number_format($order->total, 2) }}</td></tr>
                                        <tr><td>Shipping</td><td class="text-end">₦{{ number_format($order->shipping_cost, 2) }}</td></tr>
                                        <tr><td>Tax</td><td class="text-end">₦{{ number_format($order->tax_cost, 2) }}</td></tr>
                                        @if($order->totalRefunded() > 0)
                                        <tr class="text-danger">
                                            <td>Refunded</td>
                                            <td class="text-end">-₦{{ number_format($order->totalRefunded(), 2) }}</td>
                                        </tr>
                                        @endif
                                        <tr class="table-active fw-bold fs-5">
                                            <td>Total Paid</td>
                                            <td class="text-end text-success">
                                                ₦{{ number_format($order->total_amount - $order->totalRefunded(), 2) }}
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Notes -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <h5>Order Notes</h5>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                                Add Note
                            </button>
                        </div>
                        <div class="card-body">
                            @if($order->notes->count())
                                @foreach($order->notes as $note)
                                <div class="border-start border-primary border-3 ps-3 mb-3">
                                    <small class="text-muted">
                                        {{ $note->user?->name ?? 'Customer' }} • {{ $note->created_at->diffForHumans() }}
                                        @if($note->is_customer_visible)
                                            <span class="badge bg-info-subtle text-info ms-2">Visible to customer</span>
                                        @endif
                                    </small>
                                    <p class="mb-0">{{ $note->note }}</p>
                                </div>
                                @endforeach
                            @else
                                <p class="text-muted text-center py-4">No notes yet</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Note Modal -->
            <div class="modal fade" id="addNoteModal" tabindex="-1">
                <div class="modal-dialog">
                    <form action="{{ route('adminorders.note', $order->id) }}" method="POST">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add Order Note</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <textarea name="note" class="form-control" rows="4" required placeholder="Enter note..."></textarea>
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" name="is_customer_visible" id="visible">
                                    <label class="form-check-label" for="visible">
                                        Visible to customer
                                    </label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">Save Note</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function emailInvoice(id) {
    axios.post('{{ route("adminorders.emailInvoice", ":id") }}'.replace(':id', id))
        .then(() => Swal.fire('Success', 'Invoice sent to customer', 'success'))
        .catch(() => Swal.fire('Error', 'Failed to send invoice', 'error'));
}
</script>
@endsection
