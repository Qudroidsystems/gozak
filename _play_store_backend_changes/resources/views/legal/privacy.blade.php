{{-- ONE-LINE EDIT: set the real support email/phone before publishing --}}
@php($supportEmail = config('app.support_email', 'support@gozakmart.ng'))
@extends('legal.layout')
@section('title','Privacy Policy')
@section('content')
<h1>Privacy Policy</h1>
<p class="meta">Gozak Mart (package ng.gozakmart.shop) &middot; Last updated: October 7, 2026</p>

<p>Gozak Mart ("we", "us") is an online shopping app and website operated by Qudroid Systems. This policy explains what information we collect when you use the Gozak Mart mobile app and gozakmart.ng, why we collect it, who we share it with, and the choices you have.</p>

<h2>1. Information we collect</h2>
<p><strong>Information you give us</strong></p>
<ul>
    <li><strong>Account details:</strong> name, username, email address, phone number, password (stored hashed), and optionally gender and date of birth. If you sign in with Google or Apple, we receive your name and email from that provider; we never see your Google or Apple password.</li>
    <li><strong>Delivery details:</strong> shipping addresses and phone numbers you add.</li>
    <li><strong>Orders and payments:</strong> items ordered, amounts, order status and payment references. Card and bank payments are processed by Paystack; we do not receive or store your full card number, PIN or CVV.</li>
    <li><strong>Refund bank account (optional):</strong> bank name, account name and account number, so we can pay refunds. The account number is stored encrypted.</li>
    <li><strong>Gozak Credit (optional):</strong> if you apply for Gozak Credit, we collect your name, phone number, address, employer, bank account number and Bank Verification Number (BVN). These are checked with your bank through Paystack. BVN and account numbers are stored encrypted. If you approve a direct-debit mandate or add a backup card, Paystack gives us an authorization token (not your card number or PIN) so repayments can be collected.</li>
    <li><strong>Photos and messages:</strong> a profile picture, and photos or messages you send to customer support chat. We access only the photos you choose to select or take.</li>
    <li><strong>Reviews:</strong> ratings and comments you post about products, and, if you choose to type it, your city. Reviews may be shown publicly.</li>
</ul>
<p><strong>Information collected automatically</strong></p>
<ul>
    <li>Device and app information needed to send notifications and keep the app working: push notification token, device platform (Android), app version, and IP address.</li>
    <li>Usage and diagnostic information about how the app is used, collected through Google Firebase (for example Firebase Analytics and Firebase Cloud Messaging).</li>
</ul>
<p>The app does <strong>not</strong> access your contacts, SMS messages, call logs, microphone or precise location.</p>

<h2>2. How we use your information</h2>
<ul>
    <li>To create and secure your account and sign you in.</li>
    <li>To process, deliver, track and support your orders, including refunds.</li>
    <li>To assess and operate Gozak Credit, including identity and bank verification, billing and collecting repayments you authorised.</li>
    <li>To send order updates, security alerts and, if you allow them, promotional notifications.</li>
    <li>To provide customer support through chat and email.</li>
    <li>To detect fraud, protect our users, comply with legal obligations and improve the app.</li>
</ul>

<h2>3. Who we share it with</h2>
<p>We do not sell your personal information. We share it only with service providers who help us run Gozak Mart, and only as needed:</p>
<ul>
    <li><strong>Paystack</strong> – payments, bank-account and BVN verification, direct debit.</li>
    <li><strong>Google (Firebase) and Apple</strong> – sign-in, push notifications and analytics.</li>
    <li><strong>Pusher</strong> – real-time delivery of support chat messages.</li>
    <li><strong>Delivery and logistics partners</strong> – your name, address and phone number to deliver orders.</li>
    <li><strong>Authorities and professional advisers</strong> – where required by law or to protect rights and safety.</li>
    <li>A successor business, if Gozak Mart is merged or sold.</li>
</ul>

<h2>4. Security</h2>
<p>Data is sent over HTTPS. Passwords are hashed; BVN, bank account numbers and payment authorization tokens are encrypted at rest. No system is perfectly secure, but we work to protect your information.</p>

<h2>5. Retention and deletion</h2>
<p>We keep your information while your account is active. You can delete your account at any time inside the app (<strong>Profile &rarr; Close account</strong>) or by following the steps at <a href="/delete-account">gozakmart.ng/delete-account</a>. When you delete your account we delete your profile and associated personal data. We may keep limited records that the law requires us to keep (for example, payment or credit-repayment records), only for as long as required.</p>

<h2>6. Your choices and rights</h2>
<p>You can view and edit your profile, addresses and bank accounts in the app, turn notifications on or off in the app and in your phone settings, and request a copy or correction of your data or its deletion by writing to <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>. Under the Nigeria Data Protection Act you may also lodge a complaint with the Nigeria Data Protection Commission.</p>

<h2>7. Children</h2>
<p>Gozak Mart is not directed at children under 18, and Gozak Credit is available only to adults. We do not knowingly collect personal information from children. If you believe a child has given us information, contact us and we will delete it.</p>

<h2>8. Third-party links</h2>
<p>Payment pages (Paystack) and some links open pages we do not control. Their own privacy policies apply there.</p>

<h2>9. Changes to this policy</h2>
<p>If we change this policy we will update the date above and, for material changes, notify you in the app.</p>

<h2>10. Contact</h2>
<p>Qudroid Systems &ndash; <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a> &ndash; <a href="https://qudroids.com">qudroids.com</a></p>
@endsection
