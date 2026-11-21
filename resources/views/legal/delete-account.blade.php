
@php($supportEmail = config('app.support_email', 'support@gozakmart.ng'))

@extends('legal.layout')

@section('title', 'Delete Your Account')

@section('content')

<h1>Delete Your Gozak Mart Account</h1>

<p class="meta">
    Gozak Mart &middot; Last updated: November 21, 2026
</p>

<p>
    Gozak Mart is operated by Gozak Supermarket.
    We respect your right to control your personal information.
    You may request deletion of your Gozak Mart account and
    associated personal information using either of the
    methods described below.
</p>

<h2>Option 1 – Delete your account inside the app</h2>

<ol>
    <li>Open the Gozak Mart mobile application.</li>

    <li>Sign in to your account.</li>

    <li>Navigate to <strong>Profile</strong>.</li>

    <li>Scroll to the bottom of the profile page.</li>

    <li>Tap <strong>Close Account</strong>.</li>

    <li>
        Review the account deletion information and
        confirm your request.
    </li>

    <li>
        If you registered using an email address and password,
        you may be asked to enter your password to verify
        your identity.
    </li>
</ol>

<h2>Option 2 – Request deletion without using the app</h2>

<p>
    If you no longer have access to the Gozak Mart application
    or prefer not to use it, you can request account deletion
    by sending an email to:
</p>

<p>
    <strong>
        <a href="mailto:{{ $supportEmail }}?subject=Delete%20my%20Gozak%20Mart%20account">
            {{ $supportEmail }}
        </a>
    </strong>
</p>

<p>
    Please send the request from the email address associated
    with your Gozak Mart account, using the subject:
    <em>Delete my Gozak Mart account</em>.
</p>

<p>
    Include your full name and registered phone number
    to help us identify and verify your account.
</p>

<p>
    If additional verification is reasonably necessary,
    we may contact you to confirm that the request
    was made by the account holder.
</p>

<p>
    We will acknowledge and process verified deletion
    requests within 30 days, subject to applicable legal
    requirements and any circumstances that may reasonably
    require additional processing time.
</p>

<h2>What information will be deleted?</h2>

<p>
    Once your account deletion request has been verified
    and processed, we will delete or anonymise personal
    information associated with your account, including
    the following, where applicable:
</p>

<ul>
    <li>
        Your account profile information, including
        your name, username, email address and phone number.
    </li>

    <li>
        Your profile picture and other profile information.
    </li>

    <li>
        Your saved delivery addresses.
    </li>

    <li>
        Your saved bank account details used for refunds.
    </li>

    <li>
        Your push notification registration token.
    </li>

    <li>
        Support chat messages and attachments associated
        with your account, subject to applicable retention
        requirements.
    </li>

    <li>
        Reviews and ratings linked to your account, where
        they can reasonably be deleted or anonymised.
    </li>

    <li>
        Other personal information associated with your
        account that is not required to be retained
        under applicable law.
    </li>
</ul>

<h2>Information we may retain</h2>

<p>
    Certain information may need to be retained even
    after your account has been deleted, where required
    or permitted by applicable law.
</p>

<p>
    This may include:
</p>

<ul>
    <li>
        Payment and transaction records required for
        accounting, tax, financial or regulatory purposes.
    </li>

    <li>
        Order and delivery records necessary to resolve
        disputes or fulfil legal obligations.
    </li>

    <li>
        Records required to investigate fraud, security
        incidents or other unlawful activities.
    </li>

    <li>
        Information necessary to establish, exercise
        or defend legal claims.
    </li>
</ul>

<p>
    Any information retained will be restricted to the
    relevant purposes and kept only for as long as
    necessary or legally required.
</p>

<h2>Important information before deleting your account</h2>

<div class="box">
    <p>
        <strong>Account deletion is permanent.</strong>
        Once your account has been deleted, you may not
        be able to recover your profile, saved addresses,
        account settings or other associated information.
    </p>

    <p>
        Deleting your account does not automatically
        cancel orders that have already been placed,
        reverse completed transactions or remove any
        outstanding payment obligations.
    </p>

    <p>
        If you have an ongoing order, refund request
        or payment dispute, please contact our support
        team for assistance.
    </p>
</div>

<h2>Need assistance?</h2>

<p>
    If you have questions about account deletion or
    need help submitting a request, contact:
</p>

<p>
    <strong>Gozak Supermarket</strong><br>
    Email:
    <a href="mailto:{{ $supportEmail }}">
        {{ $supportEmail }}
    </a><br>
    Website:
    <a href="https://gozakmart.ng">
        gozakmart.ng
    </a>
</p>

<p>
    For additional information about how we collect,
    use, store and protect your personal information,
    please read our
    <a href="/privacy">Privacy Policy</a>.
</p>

@endsection
