<?php

namespace App\Http\Middleware;

use App\Services\Storefront\CartService;
use App\Services\Storefront\StoreCatalog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

/**
 * Every shopping-website request runs as the `customer` guard, so existing
 * code that calls Auth::id() (orders, delivery options, payments) sees the
 * shopper, while the admin panel's `web` guard is never touched.
 */
class UseCustomerGuard
{
    public function handle(Request $request, Closure $next)
    {
        Auth::shouldUse('customer');

        View::composer('store.*', function ($view) {
            static $shared = null;
            $shared ??= [
                'storeCustomer'   => Auth::guard('customer')->user(),
                'storeCartCount'  => app(CartService::class)->count(),
                'storeCategories' => app(StoreCatalog::class)->menuCategories(),
                'storeSettings'   => app(StoreCatalog::class)->settings(),
            ];
            $view->with($shared);
        });

        $response = $next($request);

        // Shop pages show the customer's cart and account, so browsers and
        // proxies must not cache them.
        if (method_exists($response, 'header') && Auth::guard('customer')->check()) {
            $response->header('Cache-Control', 'private, no-store');
        }

        return $response;
    }
}
