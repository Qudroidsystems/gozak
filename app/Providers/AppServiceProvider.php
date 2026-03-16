<?php

namespace App\Providers;

use App\Models\User;
use App\Services\FcmService;
use App\Services\BarcodeService;
use App\Services\PaystackService;
use App\Services\NotificationService;
use App\Services\LightningDealNotificationService;  // ← ADD THIS
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register FCM Service
        $this->app->singleton(FcmService::class, function ($app) {
            return new FcmService();
        });

        // Register Barcode Service
        $this->app->singleton(BarcodeService::class, function ($app) {
            return new BarcodeService();
        });

        // Register Paystack Service
        $this->app->singleton(PaystackService::class, function ($app) {
            return new PaystackService();
        });

        // Register Notification Service
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService(
                $app->make(FcmService::class),
                $app->make(BarcodeService::class)
            );
        });

        // ── ADD THIS BLOCK ────────────────────────────────────────────────────
        // Register Lightning Deal Notification Service
        $this->app->singleton(LightningDealNotificationService::class, function ($app) {
            return new LightningDealNotificationService(
                $app->make(FcmService::class)
            );
        });
        // ─────────────────────────────────────────────────────────────────────
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share user data with all views (if needed for email templates)
        View::composer('*', function ($view) {
            if (Auth::check()) {
                $view->with('currentUser', Auth::user());
            }
        });

        // Set default string length for MySQL
        \Illuminate\Support\Facades\Schema::defaultStringLength(191);

        $this->registerCustomValidators();
        $this->registerMacros();
    }

    protected function registerCustomValidators(): void
    {
        // Add custom validators here if needed
    }

    protected function registerMacros(): void
    {
        // Response macro for API success
        \Illuminate\Routing\ResponseFactory::macro('success', function ($data = null, $message = 'Success', $status = 200) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data'    => $data,
            ], $status);
        });

        // Response macro for API error
        \Illuminate\Routing\ResponseFactory::macro('error', function ($message = 'Error', $errors = null, $status = 400) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors'  => $errors,
            ], $status);
        });

        // String macro for currency formatting
        \Illuminate\Support\Str::macro('currency', function ($amount, $currency = 'NGN') {
            $symbols = ['NGN' => '₦', 'USD' => '$', 'EUR' => '€', 'GBP' => '£'];
            $symbol  = $symbols[$currency] ?? $currency;
            return $symbol . number_format($amount, 2);
        });

        // Collection macro for pagination
        \Illuminate\Support\Collection::macro('paginate', function ($perPage = 15, $page = null, $options = []) {
            $page      = $page ?: (\Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1);
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $this->forPage($page, $perPage),
                $this->count(),
                $perPage,
                $page,
                $options
            );
            return $paginator->withPath('');
        });
    }
}
