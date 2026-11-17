<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API routes that change the catalogue (products, brands, banners, uploads…)
 * are for staff only. Customers ("customer", "user", "app user(s)") and
 * guests get 403. Use after auth:sanctum.
 */
class EnsureApiStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isStaff = $user
            && method_exists($user, 'getRoleNames')
            && $user->getRoleNames()
                ->map(fn ($r) => strtolower($r))
                ->diff(['customer', 'user', 'app users', 'app user'])
                ->isNotEmpty();

        if (! $isStaff) {
            return response()->json(['success' => false, 'message' => 'You are not allowed to do this.'], 403);
        }

        return $next($request);
    }
}
