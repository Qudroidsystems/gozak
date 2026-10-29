@extends('store.layout')

@section('title', 'Help centre | ' . ($storeSettings['name'] ?? 'GozakMart'))

@section('content')
<div class="wrap" style="max-width:860px">
    <div class="box">
        <div class="sec-head"><h2>Help centre</h2></div>
        <div class="desc">
            <h3>How do I place an order?</h3>
            <p>Add items to your cart, open the cart and press <strong>Checkout</strong>. Sign in (or create an account), choose your delivery address and delivery option, then pay securely with Paystack or OPay.</p>
            <h3>How much is delivery?</h3>
            <p>You'll see the delivery options and their fees at checkout before you pay. You can pick GozakMart Delivery or a partner courier.</p>
            <h3>How do I track my order?</h3>
            <p>Go to <a href="{{ route('store.account.orders') }}" style="color:var(--brand)">My orders</a> and open the order. You'll see each step from processing to delivery. In the app you can also follow the rider live on a map.</p>
            <h3>Is it safe to pay online?</h3>
            <p>Yes. Payments are handled by Paystack or OPay. We never see or store your card number or PIN.</p>
            <h3>Can I use the same account in the app?</h3>
            <p>Yes. Your website account and app account are the same: sign in with the same email and password and you'll see the same orders and addresses.</p>
            <h3>I want to cancel or return something</h3>
            <p>Contact us with your order number
                @if($storeSettings['phone']) on <a href="tel:{{ preg_replace('/\s+/', '', $storeSettings['phone']) }}" style="color:var(--brand)">{{ $storeSettings['phone'] }}</a>@endif
                @if($storeSettings['email']) or <a href="mailto:{{ $storeSettings['email'] }}" style="color:var(--brand)">{{ $storeSettings['email'] }}</a>@endif.
                You can also chat with us in the app.</p>
        </div>
    </div>
</div>
@endsection
