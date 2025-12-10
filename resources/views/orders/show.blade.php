{{-- resources/views/orders/show.blade.php --}}
@extends('layouts.master')

@section('title', 'Order #' . ($order->invoice_number ?? substr($order->id, 0, 8)))

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

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
                            <a href="{{ route('orders.invoice', $order->id) }}" target="_blank" class="btn btn-primary">
                                PDF Invoice
                            </a>
                            <a href="{{ route('orders.packing-slip', $order->id) }}" target="_blank" class="btn btn-secondary">
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
                                    <p class="text-muted mb-0">{{ $order->user->email }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Refund Section -->
                    @if($order->refundableAmount() > 0)
                    <div class="card border-danger">
                        <div class="card-header bg-danger-subtle">
                            <h5 class="text-danger mb-0">Request Refund</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('orders.refund', $order->id) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label>Amount (Max: ${{ number_format($order->refundableAmount(), 2) }})</label>
                                    <input type="number" step="0.01" name="amount" class="form-control" max="{{ $order->refundableAmount() }}" required>
                                </div>
                                <div class="mb-3">
                                    <label>Reason</label>
                                    <textarea name="reason" class="form-control" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger w-100">Process Refund</button>
                            </form>
                        </div>
                    </div>
                    @endif

                    <!-- Refund History -->
                    @if($order->refunds->count())
                    <div class="card">
                        <div class="card-header">
                            <h5>Refund History</h5>
                        </div>
                        <div class="card-body p-0">
                            @foreach($order->refunds as $refund)
                            <div class="p-3 border-bottom">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>${{ number_format($refund->amount, 2) }}</strong>
                                        <small class="d-block text-muted">{{ $refund->reason }}</small>
                                    </div>
                                    <span class="badge {{ $refund->status == 'processed' ? 'bg-success' : 'bg-warning' }}-subtle">
                                        {{ ucfirst($refund->status) }}
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
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
                                            <td>${{ number_format($item->price, 2) }}</td>
                                            <td>${{ number_format($item->price * $item->quantity, 2) }}</td>
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
                                        <tr><td>Subtotal</td><td class="text-end">${{ number_format($order->total, 2) }}</td></tr>
                                        <tr><td>Shipping</td><td class="text-end">${{ number_format($order->shipping_cost, 2) }}</td></tr>
                                        <tr><td>Tax</td><td class="text-end">${{ number_format($order->tax_cost, 2) }}</td></tr>
                                        @if($order->totalRefunded() > 0)
                                        <tr class="text-danger">
                                            <td>Refunded</td>
                                            <td class="text-end">-${{ number_format($order->totalRefunded(), 2) }}</td>
                                        </tr>
                                        @endif
                                        <tr class="table-active fw-bold fs-5">
                                            <td>Total Paid</td>
                                            <td class="text-end text-success">
                                                ${{ number_format($order->total_amount - $order->totalRefunded(), 2) }}
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
                    <form action="{{ route('orders.note', $order->id) }}" method="POST">
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
@endsection