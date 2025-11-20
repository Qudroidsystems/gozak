<?php

namespace App\Providers;

use App\Models\User;
use App\Services\FcmService;
use App\Services\BarcodeService;
use App\Services\PaystackService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Services\NotificationService;
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

        // Bind Lcobucci JWT Validator (if needed for Firebase)
        // $this->app->bind(Validator::class, function () {
        //     return new ConstraintValidator();
        // });
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

        // Custom validation rules (if needed)
        $this->registerCustomValidators();

        // Macros (if needed)
        $this->registerMacros();
    }

    /**
     * Register custom validation rules
     */
    protected function registerCustomValidators(): void
    {
        // Example: Custom phone number validation
        // \Validator::extend('phone', function ($attribute, $value, $parameters, $validator) {
        //     return preg_match('/^\+?[1-9]\d{1,14}$/', $value);
        // });

        // Example: Custom base64 image validation
        // \Validator::extend('base64image', function ($attribute, $value, $parameters, $validator) {
        //     return preg_match('/^data:image\/(png|jpg|jpeg|gif);base64,/', $value);
        // });
    }

    /**
     * Register custom macros
     */
    protected function registerMacros(): void
    {
        // Example: Response macro for API success
        \Illuminate\Routing\ResponseFactory::macro('success', function ($data = null, $message = 'Success', $status = 200) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data,
            ], $status);
        });

        // Example: Response macro for API error
        \Illuminate\Routing\ResponseFactory::macro('error', function ($message = 'Error', $errors = null, $status = 400) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $errors,
            ], $status);
        });

        // Example: String macro for currency formatting
        \Illuminate\Support\Str::macro('currency', function ($amount, $currency = 'NGN') {
            $symbols = [
                'NGN' => '₦',
                'USD' => '$',
                'EUR' => '€',
                'GBP' => '£',
            ];
            
            $symbol = $symbols[$currency] ?? $currency;
            return $symbol . number_format($amount, 2);
        });

        // Example: Collection macro for pagination
        \Illuminate\Support\Collection::macro('paginate', function ($perPage = 15, $page = null, $options = []) {
            $page = $page ?: (\Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1);
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