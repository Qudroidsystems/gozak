@extends('layouts.master')
@section('title', $pagetitle)

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4>Order Details</h4>
                    <div>
                        <button onclick="emailInvoice('{{ $order->id }}')" class="btn btn-info me-2">
                            Email Invoice
                        </button>
                        <a href="{{ route('orders.invoice', $order->id) }}" target="_blank" class="btn btn-primary">
                            PDF Invoice
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Customer</h6>
                            <p>{{ $order->user->first_name }} {{ $order->user->last_name }}<br>
                               {{ $order->user->email }}</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <p><strong>Invoice:</strong> {{ $order->invoice_number ?? 'Not generated' }}<br>
                               <strong>Date:</strong> {{ $order->created_at->format('d M Y') }}</p>
                        </div>
                    </div>

                    <h5>Items</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
                            <tbody>
                                @foreach($order->items as $item)
                                <tr>
                                    <td>{{ $item->title }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>${{ number_format($item->price, 2) }}</td>
                                    <td>${{ number_format($item->price * $item->quantity, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="row justify-content-end">
                        <div class="col-md-4">
                            <table class="table table-sm">
                                <tr><td>Total</td><td class="text-end fw-bold">${{ number_format($order->total_amount, 2) }}</td></tr>
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
        .then(() => Swal.fire('Sent!', 'Invoice emailed', 'success'))
        .catch(() => Swal.fire('Error', 'Failed to send', 'error'));
}
</script>
@endsection