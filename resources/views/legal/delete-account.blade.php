@php($supportEmail = config('app.support_email', 'support@gozakmart.ng'))
@extends('legal.layout')
@section('title','Delete Your Account')
@section('content')
<h1>Delete Your Gozak Mart Account</h1>
<p class="meta">Last updated: October 7, 2026</p>

<h2>Option 1 &ndash; Inside the app</h2>
<ol>
    <li>Open the Gozak Mart app and sign in.</li>
    <li>Go to <strong>Profile</strong>.</li>
    <li>Scroll to the bottom and tap <strong>Close account</strong>.</li>
    <li>Confirm. If you signed up with an email and password you will be asked to enter your password first.</li>
</ol>

<h2>Option 2 &ndash; Without the app</h2>
<p>Email <a href="mailto:{{ $supportEmail }}?subject=Delete%20my%20Gozak%20Mart%20account">{{ $supportEmail }}</a> from the email address on your account with the subject <em>"Delete my Gozak Mart account"</em>. Include your full name and phone number so we can verify it is you. We will confirm and complete the deletion within 30 days.</p>

<h2>What is deleted</h2>
<ul>
    <li>Your profile (name, email, phone number, profile picture), saved addresses and saved bank accounts.</li>
    <li>Your push notification token, support chat history, and reviews linked to your account.</li>
    <li>Your Gozak Credit application data, including encrypted BVN and bank details, once any outstanding balance is settled.</li>
</ul>
<div class="box"><strong>What we may keep:</strong> records the law requires us to keep, such as payment and credit-repayment records, kept only for as long as required. Deleting your account cannot be undone.</div>

<p>See our <a href="/privacy">Privacy Policy</a> for details.</p>
@endsection
