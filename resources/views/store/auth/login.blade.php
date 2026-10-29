@extends('store.layout')

@section('title', 'Sign in | ' . ($storeSettings['name'] ?? 'GozakMart'))
@push('meta')<meta name="robots" content="noindex">@endpush

@section('content')
<div class="wrap">
    <div class="auth box form">
        <h1>Welcome back</h1>
        <p class="sub">Sign in with the same email and password you use in the {{ $storeSettings['name'] }} app.</p>
        <form method="post" action="{{ route('store.login.submit') }}" novalidate>
            @csrf
            <div class="field">
                <label for="email">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                @error('email')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
                @error('password')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="field" style="display:flex;justify-content:space-between;align-items:center">
                <label class="check"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
                @if(Route::has('password.request'))<a href="{{ route('password.request') }}" style="color:var(--brand);font-size:13px">Forgot password?</a>@endif
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">Sign in</button>
        </form>
        <p class="sub" style="margin:16px 0 0">New here? <a href="{{ route('store.register') }}" style="color:var(--brand);font-weight:500">Create an account</a></p>
    </div>
</div>
@endsection
