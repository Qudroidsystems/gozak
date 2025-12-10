{{-- resources/views/orders/show.blade.php --}}
@extends('layouts.master')

@section('title', 'Order #' . ($order->invoice_number ?? substr($order->id, 0, 8)))

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Page Title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-sm-0">
                                Order Details 
                                <span class="text-primary fw-bold">
                                    #{{ $order->invoice_number ?? substr($order->id, 0, 8) }}
                                </span>
                            </h4>
                            <ol class="breadcrumb m-0 mt-2">
                                <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">Orders</a></li>
                                <li class="breadcrumb-item active">Detail</li>
                            </ol>
                        </div>
                        <div class="d-flex gap-2">
                            <button onclick="emailInvoice('{{ $order->id }}')" class="btn btn-info">
                                <i class="bi bi-envelope"></i> Email Invoice
                            </button>
                            <a href="{{ route('orders.invoice', $order->id) }}" target="_blank" class="btn btn-primary">
                                <i class="bi bi-file-earmark-pdf"></i> PDF Invoice
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Left Column: Customer & Addresses -->
                <div class="col-xl-4">
                    <!-- Customer Card -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="bi bi-person me-2"></i>Customer</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <div class="avatar-lg me-3">
                                    <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                        {{ Str::substr($order->user->first_name, 0, 1) }}
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-1">{{ $order->user->first_name }} {{ $order->user->last_name }}</h5>
                                    <p class="text-muted mb-1">{{ $order->user->email }}</p>
                                    <p class="text-muted mb-0">{{ $order->user->phone ?? '—' }}</p>
                                </div>
                            </div>
                            <hr>
                            <div class="mt-3">
                                <p><strong>Total Orders:</strong> {{ $order->user->orders_count ?? 0 }}</p>
                                <p><strong>Total Spent:</strong> ${{ number_format($order->user->orders_sum_total_amount ?? 0, 2) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Addresses -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="bi bi-geo-alt me-2"></i>Addresses</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted text-uppercase small">Shipping</h6>
                                    <p class="mb-0">{{ $order->shippingAddress?->name ?? '—' }}</p>
                                    <p class="mb-0">{{ $order->shippingAddress?->street }}</p>
                                    <p class="mb-0">{{ $order->shippingAddress?->city }}, {{ $order->shippingAddress?->country }}</p>
                                    <p class="mb-0">{{ $order->shippingAddress?->phone_number ?? '—' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted text-uppercase small">Billing</h6>
                                    @if($order->billing_address_same_as_shipping)
                                        <span class="badge bg-success-subtle text-success">Same as shipping</span>
                                    @else
                                        <p class="mb-0">{{ $order->billingAddress?->name ?? '—' }}</p>
                                        <p class="mb-0">{{ $order->billingAddress?->street }}</p>
                                        <p class="mb-0">{{ $order->billingAddress?->city }}, {{ $order->billingAddress?->country }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- QR Code -->
                    @if($order->hasBarcode())
                    <div class="card text-center">
                        <div class="card-body">
                            <h6>Delivery Barcode</h6>
                            <img src="{{ $order->barcodeUrl }}" alt="Barcode" class="img-fluid" style="max-height: 150px;">
                            <p class="mt-2 text-muted small">Scan to verify delivery</p>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Right Column: Order Details -->
                <div class="col-xl-8">
                    <!-- Order Info & Status -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Order Information</h5>
                            <select class="form-select w-auto status-select" data-id="{{ $order->id }}">
                                @foreach(['pending','processing','shipped','delivered','cancelled'] as $s)
                                    <option value="{{ $s }}" {{ $order->status == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <small class="text-muted">Order Date</small>
                                    <p class="fw-semibold">{{ $order->created_at->format('d M Y, h:i A') }}</p>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Payment Method</small>
                                    <p class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</p>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Payment Status</small><br>
                                    <span class="badge {{ $order->payment_status == 'paid' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} fs-6">
                                        {{ ucfirst($order->payment_status) }}
                                        @if($order->paid_at)
                                            <br><small>on {{ $order->paid_at->format('d M Y') }}</small>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Order Items ({{ $order->items_count }})</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product</th>
                                            <th>Variation</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end">Price</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($order->items as $item)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($item->image)
                                                        <img src="{{ $item->image }}" class="rounded me-3" style="width:50px;height:50px;object-fit:cover;">
                                                    @endif
                                                    <div>
                                                        <h6 class="mb-0">{{ $item->title }}</h6>
                                                        <small class="text-muted">{{ $item->brand_name }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if($item->selected_variation)
                                                    @foreach(json_decode($item->selected_variation, true) as $k => $v)
                                                        <small><strong>{{ ucfirst($k) }}:</strong> {{ $v }}</small><br>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center fw-semibold">{{ $item->quantity }}</td>
                                            <td class="text-end">${{ number_format($item->price, 2) }}</td>
                                            <td class="text-end fw-bold">${{ number_format($item->price * $item->quantity, 2) }}</td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="5" class="text-center text-muted py-4">No items</td></tr>
                                        @endforelse
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
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td>Subtotal</td>
                                            <td class="text-end">${{ number_format($order->total, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Shipping</td>
                                            <td class="text-end">${{ number_format($order->shipping_cost, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Tax</td>
                                            <td class="text-end">${{ number_format($order->tax_cost, 2) }}</td>
                                        </tr>
                                        <tr class="table-active fw-bold fs-5">
                                            <td>Total Amount</td>
                                            <td class="text-end text-success">${{ number_format($order->total_amount, 2) }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Timeline -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Order Timeline</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled timeline-sm">
                                <li class="timeline-sm-item">
                                    <span class="timeline-sm-date">{{ $order->created_at->format('d M Y') }}</span>
                                    <h5 class="mt-0 mb-1">Order Placed</h5>
                                    <p class="text-muted">Customer placed the order</p>
                                </li>
                                @if($order->payment_status == 'paid')
                                <li class="timeline-sm-item">
                                    <span class="timeline-sm-date">{{ $order->paid_at?->format('d M Y') ?? $order->created_at->format('d M Y') }}</span>
                                    <h5 class="mt-0 mb-1">Payment Received</h5>
                                    <p class="text-muted">Payment confirmed</p>
                                </li>
                                @endif
                                @if($order->status !== 'pending')
                                <li class="timeline-sm-item">
                                    <span class="timeline-sm-date">{{ now()->format('d M Y') }}</span>
                                    <h5 class="mt-0 mb-1">Status Updated</h5>
                                    <p class="text-muted">Current status: <strong>{{ ucfirst($order->status) }}</strong></p>
                                </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelector('.status-select')?.addEventListener('change', function () {
        axios.post(`/orders/${this.dataset.id}/status`, { status: this.value })
            .then(() => {
                Swal.fire('Updated!', 'Order status changed', 'success');
                setTimeout(() => location.reload(), 1000);
            })
            .catch(() => this.value = this.dataset.previousValue || 'pending');
    });

    window.emailInvoice = (id) => {
        axios.post(`/orders/${id}/email-invoice`)
            .then(() => Swal.fire('Sent!', 'Invoice emailed to customer', 'success'))
            .catch(() => Swal.fire('Error', 'Failed to send invoice', 'error'));
    };
});
</script>
@endsection