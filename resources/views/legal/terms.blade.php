@php($supportEmail = config('app.support_email', 'support@gozakmart.ng'))
@extends('legal.layout')
@section('title','Terms of Use')
@section('content')
<h1>Terms of Use</h1>
<p class="meta">Gozak Mart &middot; Last updated: October 7, 2026</p>
<div class="box"><strong>Draft for review:</strong> have these terms checked against your actual business rules (delivery, returns, credit) before publishing.</div>

<h2>1. About these terms</h2>
<p>These terms govern your use of the Gozak Mart mobile app and gozakmart.ng, operated by Qudroid Systems. By creating an account or placing an order you agree to them and to our <a href="/privacy">Privacy Policy</a>.</p>

<h2>2. Your account</h2>
<p>You must be at least 18 to create an account. Give accurate information, keep your password secure, and tell us promptly if you think your account has been misused. You are responsible for activity on your account.</p>

<h2>3. Orders, prices and payment</h2>
<p>Prices are shown in Nigerian naira. An order is accepted when we confirm it. We may cancel an order if an item is unavailable, a price is wrong, or we suspect fraud, and we will refund any amount already paid. Card and bank payments are processed by Paystack.</p>

<h2>4. Delivery, returns and refunds</h2>
<p>Delivery times are estimates. Refunds for cancelled or returned orders are paid to your original payment method or to the refund bank account you saved in the app, as described in the order details at the time of purchase.</p>

<h2>5. Gozak Credit</h2>
<p>Gozak Credit is an optional pay-later facility, available to approved customers only. Approval, your credit limit and the fees that apply are shown in the app before you apply and before you use it. By activating it you authorise repayments by direct debit from the bank account or backup card you add, in the amounts and on the dates shown in your statement. Late payment can result in a late fee and in your credit being paused until the overdue amount is paid. You can view statements and repayment methods in the app.</p>

<h2>6. Acceptable use</h2>
<p>Do not misuse the app: no fraud, no attempts to access other users' accounts or our systems, no posting unlawful, abusive or misleading reviews, and no use of the app to resell it or disrupt it.</p>

<h2>7. Reviews and content</h2>
<p>When you post a review you give us permission to display it in the app and on our website. We may remove reviews that break these terms.</p>

<h2>8. Closing your account</h2>
<p>You can delete your account at any time (see <a href="/delete-account">Delete Account</a>). We may suspend or close accounts that break these terms.</p>

<h2>9. Liability</h2>
<p>To the extent allowed by law, Gozak Mart is not liable for indirect or consequential losses, or for losses caused by events outside our reasonable control. Nothing in these terms limits rights you have under Nigerian consumer-protection law.</p>

<h2>10. Changes and governing law</h2>
<p>We may update these terms and will change the date above when we do. These terms are governed by the laws of the Federal Republic of Nigeria.</p>

<h2>11. Contact</h2>
<p>Qudroid Systems &ndash; <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a></p>
@endsection
