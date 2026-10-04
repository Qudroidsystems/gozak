<?php

namespace App\Providers;

use App\Models\User;
use App\Services\FcmService;
use App\Services\BarcodeService;
use App\Services\PaystackService;
use App\Services\NotificationService;
use App\Services\LightningDealNotificationService;
use App\Services\OrderNotificationService;          // ← ADD THIS
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // FCM Service
        $this->app->singleton(FcmService::class, function ($app) {
            return new FcmService();
        });

        // Barcode Service
        $this->app->singleton(BarcodeService::class, function ($app) {
            return new BarcodeService();
        });

        // Paystack Service
        $this->app->singleton(PaystackService::class, function ($app) {
            return new PaystackService();
        });

        // Notification Service (legacy — email/database)
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService(
                $app->make(FcmService::class),
                $app->make(BarcodeService::class)
            );
        });

        // Lightning Deal Notification Service
        $this->app->singleton(LightningDealNotificationService::class, function ($app) {
            return new LightningDealNotificationService(
                $app->make(FcmService::class)
            );
        });

        // ── ADD THIS ──────────────────────────────────────────────────────────
        // Order Notification Service (FCM push for order placed + status updates)
        $this->app->singleton(OrderNotificationService::class, function ($app) {
            return new OrderNotificationService(
                $app->make(FcmService::class)
            );
        });
    }

    public function boot(): void
    {
        // Order tracking: timestamps, status timeline and in-app notifications
        // for every status change, wherever it comes from (admin, app, scheduler).
        \App\Models\Order::updating(function (\App\Models\Order $order) {
            try {
                app(\App\Services\OrderTrackingService::class)->stampTimestamps($order);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Order timestamps: ' . $e->getMessage());
            }
        });
        \App\Models\Order::created(function (\App\Models\Order $order) {
            try {
                app(\App\Services\OrderTrackingService::class)->orderCreated($order);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Order created hook: ' . $e->getMessage());
            }
        });
        \App\Models\Order::updated(function (\App\Models\Order $order) {
            if (!$order->wasChanged('status')) {
                return;
            }
            try {
                app(\App\Services\OrderTrackingService::class)->statusChanged($order, $order->getOriginal('status'), (string) $order->status);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Order status hook: ' . $e->getMessage());
            }
        });

        // Gozak Credit: a cancelled order paid with credit gives the money back.
        \App\Models\Order::updated(function (\App\Models\Order $order) {
            if ($order->wasChanged('status') && $order->status === 'cancelled' && $order->payment_method === \App\Services\Credit\CreditService::GATEWAY) {
                try {
                    app(\App\Services\Credit\CreditService::class)->reverseOrder($order, auth()->id());
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Credit reversal failed for order ' . $order->id . ': ' . $e->getMessage());
                }
            }
        });

        View::composer('*', function ($view) {
            if (Auth::check()) {
                $view->with('currentUser', Auth::user());
            }
        });

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
        \Illuminate\Routing\ResponseFactory::macro('success', function ($data = null, $message = 'Success', $status = 200) {
            return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
        });

        \Illuminate\Routing\ResponseFactory::macro('error', function ($message = 'Error', $errors = null, $status = 400) {
            return response()->json(['success' => false, 'message' => $message, 'errors' => $errors], $status);
        });

        \Illuminate\Support\Str::macro('currency', function ($amount, $currency = 'NGN') {
            $symbols = ['NGN' => '₦', 'USD' => '$', 'EUR' => '€', 'GBP' => '£'];
            return ($symbols[$currency] ?? $currency) . number_format($amount, 2);
        });

        \Illuminate\Support\Collection::macro('paginate', function ($perPage = 15, $page = null, $options = []) {
            $page      = $page ?: (\Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1);
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $this->forPage($page, $perPage), $this->count(), $perPage, $page, $options
            );
            return $paginator->withPath('');
        });
    }
}
