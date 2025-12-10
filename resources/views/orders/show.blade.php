@extends('layouts.master')
@section('title', $pagetitle)

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Order Details</h4>
                    <div>
                        <button onclick="emailInvoice('{{ $order->id }}')" class="btn btn-info me-2">
                            Email Invoice
                        </button>
                        <a href="{{ route('orders.invoice', $order->id) }}" target="_blank" class="btn btn-primary">
                            View PDF Invoice
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Customer & Order Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Customer</h6>
                            <p>{{ $order->user->first_name }} {{ $order->user->last_name }}<br>
                               {{ $order->user->email }}<br>
                               {{ $order->user->phone ?? '—' }}</p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p><strong>Invoice:</strong> {{ $order->invoice_number ?? 'Not generated' }}<br>
                               <strong>Date:</strong> {{ $order->created_at->format('d M Y h:i A') }}<br>
                               <strong>Status:</strong> <span class="badge bg-success-subtle text-success">{{ ucfirst($order->status) }}</span></p>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <h5>Ordered Items</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Product</th><th>Variation</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
                            <tbody>
                                @foreach($order->items as $item)
                                <tr>
                                    <td>{{ $item->title }}</td>
                                    <td>
                                        @if($item->selected_variation)
                                            @foreach(json_decode($item->selected_variation, true) as $k => $v)
                                                <small><strong>{{ ucfirst($k) }}:</strong> {{ $v }}</small><br>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>${{ number_format($item->price, 2) }}</td>
                                    <td>${{ number_format($item->price * $item->quantity, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals -->
                    <div class="row justify-content-end">
                        <div class="col-md-4">
                            <table class="table table-sm">
                                <tr><td>Subtotal</td><td class="text-end">${{ number_format($order->total, 2) }}</td></tr>
                                <tr><td>Shipping</td><td class="text-end">${{ number_format($order->shipping_cost, 2) }}</td></tr>
                                <tr><td>Tax</td><td class="text-end">${{ number_format($order->tax_cost, 2) }}</td></tr>
                                <tr class="table-active"><td><strong>Total</strong></td><td class="text-end"><strong>${{ number_format($order->total_amount, 2) }}</strong></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function emailInvoice(id) {
    axios.post(`/orders/${id}/email-invoice`)
        .then(res => Swal.fire('Sent!', res.data.message, 'success'))
        .catch(() => Swal.fire('Error', 'Failed to send email', 'error'));
}
</script>
@endsection