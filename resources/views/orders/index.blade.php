@extends('layouts.master')
@section('title', 'Orders')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Stats -->
            <div class="row">
                @foreach(['total','pending','processing','shipped','delivered','cancelled'] as $key)
                <div class="col-xl-2 col-md-6">
                    <div class="card card-animate bg-{{ $key == 'total' ? 'primary' : ($key == 'cancelled' ? 'danger' : 'success') }}-subtle">
                        <div class="card-body">
                            <p class="text-uppercase fw-medium mb-0">{{ ucwords(str_replace('_', ' ', $key)) }}</p>
                            <h4 class="fs-22 fw-semibold mb-0">{{ $stats[$key] }}</h4>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="row mt-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <h5>All Orders</h5>
                            <select class="form-select w-auto" onchange="location.href=this.value">
                                <option value="{{ route('orders.index') }}">All</option>
                                @foreach(['pending','processing','shipped','delivered','cancelled'] as $s)
                                <option value="{{ route('orders.index', ['status' => $s]) }}" {{ request('status') == $s ? 'selected' : '' }}>
                                    {{ ucfirst($s) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($orders as $order)
                                        <tr>
                                            <td><strong>{{ $order->invoice_number ?? '—' }}</strong></td>
                                            <td>{{ $order->user->first_name }} {{ $order->user->last_name }}</td>
                                            <td>{{ $order->created_at->format('d M Y') }}</td>
                                            <td class="fw-bold">${{ number_format($order->total_amount, 2) }}</td>
                                            <td>
                                                <select class="form-select form-select-sm status-select" data-id="{{ $order->id }}">
                                                    @foreach(['pending','processing','shipped','delivered','cancelled'] as $s)
                                                    <option value="{{ $s }}" {{ $order->status == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-info">View</a>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="6" class="text-center py-5">No orders</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            {!! $orders->links() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.status-select').forEach(s => {
    s.addEventListener('change', function() {
        axios.post(`/orders/${this.dataset.id}/status`, { status: this.value })
            .then(() => Swal.fire('Success', 'Status updated', 'success'))
            .catch(() => this.value = this.dataset.previous || 'pending');
    });
});
</script>
@endsection