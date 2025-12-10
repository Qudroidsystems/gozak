@extends('layouts.master')
@section('title', 'Orders Management')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Stats -->
            <div class="row">
                @foreach(['total', 'pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $key)
                <div class="col-xl-2 col-md-4">
                    <div class="card card-animate bg-{{ $key == 'total' ? 'primary' : ($key == 'cancelled' ? 'danger' : 'success') }}-subtle">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium {{ $key == 'total' ? 'text-primary' : '' }} mb-0">
                                        {{ ucfirst(str_replace('_', ' ', $key)) }} Orders
                                    </p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ $stats[$key] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="row mt-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h5 class="card-title mb-0 flex-grow-1">All Orders</h5>
                            <select class="form-select w-auto" onchange="location = this.value;">
                                <option value="{{ route('orders.index') }}">All Status</option>
                                @foreach(['pending','processing','shipped','delivered','cancelled'] as $s)
                                <option value="{{ route('orders.index', ['status' => $s]) }}" {{ request('status') == $s ? 'selected' : '' }}>
                                    {{ ucfirst($s) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle table-nowrap">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Total</th>
                                            <th>Payment</th>
                                            <th>Status</th>
                                            <th>Items</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($orders as $order)
                                        <tr>
                                            <td><strong>{{ $order->invoice_number ?? '—' }}</strong></td>
                                            <td>
                                                {{ $order->user->first_name }} {{ $order->user->last_name }}
                                                <small class="d-block text-muted">{{ $order->user->email }}</small>
                                            </td>
                                            <td>{{ $order->created_at->format('d M Y') }}</td>
                                            <td class="fw-bold">${{ number_format($order->total_amount, 2) }}</td>
                                            <td>
                                                <span class="badge {{ $order->payment_status == 'paid' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                                    {{ ucfirst($order->payment_status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm status-select" data-id="{{ $order->id }}">
                                                    @foreach(['pending','processing','shipped','delivered','cancelled'] as $s)
                                                    <option value="{{ $s }}" {{ $order->status == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>{{ $order->items_count }}</td>
                                            <td>
                                                <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-info">View</a>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="8" class="text-center py-5 text-muted">No orders found</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            {!! $orders->appends(request()->query())->links() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.status-select').forEach(el => {
    el.addEventListener('change', function() {
        axios.post(`/orders/${this.dataset.id}/status`, { status: this.value })
            .then(res => Swal.fire('Updated!', res.data.message, 'success'))
            .catch(() => this.value = this.dataset.current || 'pending');
    });
});
</script>
@endsection