<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Shopping website pages that need a signed-in customer (checkout, account). */
class StoreAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('customer')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Please sign in to continue.'], 401);
            }
            return redirect()->guest(route('store.login'))
                ->with('store_notice', 'Please sign in or create an account to continue.');
        }

        return $next($request);
    }
}
