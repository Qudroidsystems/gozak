<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Website sign-in / sign-up for shoppers. Same accounts and rules as the app
 * (email + password, email must be verified), but on the separate `customer`
 * guard, so it never signs anyone in to the admin panel.
 */
class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('store.account.orders');
        }
        return view('store.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email|max:255',
            'password' => 'required|string|max:255',
        ]);

        $key = 'store-login:' . Str::lower($data['email']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages(['email' => "Too many attempts. Please try again in {$seconds} seconds."]);
        }

        $user = User::where('email', $data['email'])->first();
        if (!$user || !$user->password || !Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'The email or password is not correct.']);
        }
        RateLimiter::clear($key);

        if (method_exists($user, 'hasVerifiedEmail') && !$user->hasVerifiedEmail()) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                Log::warning('Store login: verification email not sent: ' . $e->getMessage());
            }
            throw ValidationException::withMessages(['email' => 'Please verify your email first. We have sent you a new verification link.']);
        }

        Auth::guard('customer')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        try {
            $user->ensureAppUserRole();
        } catch (\Throwable $e) {
        }

        return redirect()->intended(route('store.home'))->with('store_success', 'Welcome back, ' . ($user->first_name ?: 'there') . '!');
    }

    public function showRegister()
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('store.account.orders');
        }
        return view('store.auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'email'        => 'required|email|max:255|unique:users,email',
            'phone_number' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\-]{7,20}$/'],
            'password'     => ['required', 'confirmed', Password::min(6)],
            'terms'        => 'accepted',
        ], [
            'email.unique'   => 'An account with this email already exists. Please sign in.',
            'terms.accepted' => 'Please accept the terms and privacy policy.',
        ]);

        $user = User::create([
            'first_name'                        => $data['first_name'],
            'last_name'                         => $data['last_name'],
            'username'                          => $data['email'],
            'email'                             => $data['email'],
            'phone_number'                      => $data['phone_number'] ?? null,
            'password'                          => Hash::make($data['password']),
            'push_notifications_enabled'        => true,
            'order_updates_enabled'             => true,
            'promotional_notifications_enabled' => true,
            'security_alerts_enabled'           => true,
            'email_notifications_enabled'       => true,
        ]);

        try {
            $user->ensureAppUserRole();
        } catch (\Throwable $e) {
        }

        try {
            event(new Registered($user));   // sends the verification email
        } catch (\Throwable $e) {
            Log::warning('Store register: verification email not sent: ' . $e->getMessage());
        }

        return redirect()->route('store.login')
            ->with('store_success', "Account created! We've sent a verification link to {$user->email}. Verify your email, then sign in here or in the app.");
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();
        return redirect()->route('store.home')->with('store_success', 'You have signed out.');
    }
}
