@extends('store.layout')

@section('title', 'Create an account | ' . ($storeSettings['name'] ?? 'GozakMart'))
@push('meta')<meta name="robots" content="noindex">@endpush

@section('content')
<div class="wrap">
    <div class="auth box form">
        <h1>Create your account</h1>
        <p class="sub">One account for the website and the app.</p>
        <form method="post" action="{{ route('store.register.submit') }}" novalidate>
            @csrf
            <div class="row2">
                <div class="field">
                    <label for="first_name">First name</label>
                    <input id="first_name" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name">
                    @error('first_name')<div class="err">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="last_name">Last name</label>
                    <input id="last_name" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name">
                    @error('last_name')<div class="err">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="field">
                <label for="email">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                @error('email')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="phone_number">Phone number <span class="muted">(optional)</span></label>
                <input id="phone_number" type="tel" name="phone_number" value="{{ old('phone_number') }}" placeholder="0803 123 4567" autocomplete="tel">
                @error('phone_number')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="row2">
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password" minlength="6">
                    @error('password')<div class="err">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                </div>
            </div>
            <div class="field">
                <label class="check"><input type="checkbox" name="terms" value="1" @checked(old('terms'))> I agree to the <a href="{{ route('legal.terms') }}" target="_blank" style="color:var(--brand)">terms</a> and <a href="{{ route('legal.privacy') }}" target="_blank" style="color:var(--brand)">privacy policy</a></label>
                @error('terms')<div class="err">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">Create account</button>
        </form>
        <p class="sub" style="margin:16px 0 0">Already have an account? <a href="{{ route('store.login') }}" style="color:var(--brand);font-weight:500">Sign in</a></p>
    </div>
</div>
@endsection
