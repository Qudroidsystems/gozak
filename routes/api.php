<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FcmController;
use App\Http\Controllers\APIAuthController;
use App\Http\Controllers\APIUserController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\APIBrandController;
use App\Http\Controllers\APIOrderController;
use App\Http\Controllers\APIBannerController;
use App\Http\Controllers\APIUploadController;
use App\Http\Controllers\APIAddressController;
use App\Http\Controllers\APIProductController;
use App\Http\Controllers\APICategoryController;
use App\Http\Controllers\APISettingsController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\APIProductReviewController;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

// Authentication Routes
Route::post('/register', [APIAuthController::class, 'register'])->name('auth.register');
Route::post('/login', [APIAuthController::class, 'login'])->middleware('throttle:10,1')->name('auth.login');
Route::post('/social-login', [APIAuthController::class, 'socialLogin'])->middleware('throttle:10,1')->name('auth.social');
Route::post('/password/email', [APIAuthController::class, 'sendPasswordResetEmail'])->middleware('throttle:6,1')->name('password.email');
Route::post('/password/reset', [APIAuthController::class, 'resetPassword'])->name('password.reset');
Route::get('/email/verify/{id}/{hash}', [APIAuthController::class, 'verifyEmail'])->middleware(['throttle:6,1'])->name('verification.verify');

// Public Routes
Route::get('/categories', [APICategoryController::class, 'index'])->name('categories.index');
Route::get('/brands', [APIBrandController::class, 'index'])->name('brands.index');
Route::get('/brands/{id}', [APIBrandController::class, 'show'])->name('brands.show');
Route::get('/brands/isFeatured', [APIBrandController::class, 'featured'])->name('brands.featured');
Route::get('/brands/category/{categoryId}', [APIBrandController::class, 'getbrandsForCategory'])->name('brands.category');
Route::post('/brands', [APIBrandController::class, 'store']);
Route::post('/brand-categories', [APIBrandController::class, 'storeBrandCategory']);

Route::get('/products', [APIProductController::class, 'index'])->name('products.index');
Route::get('/products/{id}', [APIProductController::class, 'show'])->name('products.show');
Route::post('/products', [APIProductController::class, 'store']);
Route::patch('/products/{id}', [APIProductController::class, 'updateSingleField']);
Route::put('/products/{id}', [APIProductController::class, 'update']);
Route::post('/upload', [APIProductController::class, 'uploadFile']);
Route::post('/product-categories', [APIProductController::class, 'storeProductCategory']);
Route::get('/products/{id}/related', [APIProductController::class, 'related'])->name('products.related');

Route::get('/product-reviews', [APIProductReviewController::class, 'index'])->name('product-reviews.index');
Route::post('/product-reviews', [APIProductReviewController::class, 'store']);
Route::put('/product-reviews/{id}/company-comment', [APIProductReviewController::class, 'addCompanyComment']);
Route::put('/product-reviews/{id}', [APIProductReviewController::class, 'update']);
Route::delete('/product-reviews/{id}', [APIProductReviewController::class, 'destroy']);

Route::get('/banners', [APIBannerController::class, 'index'])->name('banners.index');
Route::post('/banners', [APIBannerController::class, 'store']);

// Privacy Policy and User Data Safety Routes
Route::get('/privacy-policy', [PrivacyPolicyController::class, 'show'])->name('privacy.policy');
Route::get('/user-data-safety', [PrivacyPolicyController::class, 'showUserDataSafety'])->name('user.data-safety');

// Global settings endpoint - public
Route::get('/settings/global', [APISettingsController::class, 'global'])->name('settings.global');

// Authenticated Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [APIAuthController::class, 'logout'])->name('auth.logout');
    Route::post('/email/verification-notification', [APIAuthController::class, 'sendEmailVerificationNotification'])->name('verification.send');

    // User Routes
    Route::get('/user', [APIUserController::class, 'show'])->name('user.show');
    Route::put('/user', [APIUserController::class, 'update'])->name('user.update');
    Route::patch('/user', [APIUserController::class, 'updateField']);
    Route::post('/user/profile-picture', [APIUserController::class, 'uploadProfilePicture']);
    Route::delete('/user', [APIUserController::class, 'destroy'])->name('user.destroy');

    // User-specific Settings Routes
    Route::get('/settings', [APISettingsController::class, 'show'])->name('settings.show');
    Route::post('/settings', [APISettingsController::class, 'store'])->name('settings.store');
    Route::put('/settings', [APISettingsController::class, 'update'])->name('settings.update');
    Route::patch('/settings', [APISettingsController::class, 'updateField']);

    // =====================
    // ENHANCED ORDER ROUTES
    // =====================
    Route::get('/orders', [APIOrderController::class, 'index'])->name('orders.index');
    Route::post('/orders', [APIOrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{id}', [APIOrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{id}', [APIOrderController::class, 'update'])->name('orders.update');
    Route::patch('/orders/{id}', [APIOrderController::class, 'patch']);
    Route::delete('/orders/{id}', [APIOrderController::class, 'destroy'])->name('orders.destroy');

    // Enhanced Order Status & Barcode Routes
    Route::patch('/orders/{id}/status', [APIOrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::get('/orders/{id}/barcode', [APIOrderController::class, 'getBarcode'])->name('orders.barcode');
    Route::post('/orders/scan-barcode', [APIOrderController::class, 'scanBarcode'])->name('orders.scan-barcode');

    // Address Routes
    Route::get('/addresses', [APIAddressController::class, 'index'])->name('addresses.index');
    Route::post('/addresses', [APIAddressController::class, 'store'])->name('addresses.store');
    Route::put('/addresses/{id}', [APIAddressController::class, 'update'])->name('addresses.update');
    Route::patch('/addresses/{id}', [APIAddressController::class, 'patch']);
    Route::delete('/addresses/{id}', [APIAddressController::class, 'destroy'])->name('addresses.destroy');

    // // =====================
    // // ENHANCED PAYMENT ROUTES
    // // =====================
    // Route::post('/payment/initialize', [PaymentController::class, 'initializePayment'])->name('payment.initialize');
    // Route::post('/payment/charge', [PaymentController::class, 'chargeCard'])->name('payment.charge');
    // Route::post('/payment/submit-otp', [PaymentController::class, 'submitOtp'])->name('payment.otp');
    // Route::post('/payment/submit-pin', [PaymentController::class, 'submitPin'])->name('payment.pin');
    // Route::post('/payment/verify', [PaymentController::class, 'verifyPayment'])->name('payment.verify');
    // Route::get('/payment/public-key', [PaymentController::class, 'getPublicKey'])->name('payment.public-key');
    // Route::get('/payment/history', [PaymentController::class, 'getPaymentHistory'])->name('payment.history');
    // Route::get('/payment/{reference}', [PaymentController::class, 'getPayment'])->name('payment.show');

    // =====================
// ENHANCED PAYMENT ROUTES
// =====================
Route::post('/payment/initialize', [PaymentController::class, 'initializePayment'])->name('payment.initialize');
Route::post('/payment/charge', [PaymentController::class, 'chargeCard'])->name('payment.charge');
Route::post('/payment/submit-otp', [PaymentController::class, 'submitOtp'])->name('payment.otp');
Route::post('/payment/submit-pin', [PaymentController::class, 'submitPin'])->name('payment.pin');
Route::post('/payment/verify', [PaymentController::class, 'verifyPayment'])->name('payment.verify');
Route::get('/payment/public-key', [PaymentController::class, 'getPublicKey'])->name('payment.public-key');
Route::get('/payment/history', [PaymentController::class, 'getPaymentHistory'])->name('payment.history');
Route::get('/payment/{reference}', [PaymentController::class, 'getPayment'])->name('payment.show');
Route::post('/payment/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');

// NEW: Success page route (accessible via web)
Route::get('/payment/success', [PaymentController::class, 'successPage'])->name('payment.success');

    // =====================
    // ENHANCED FCM ROUTES
    // =====================
    Route::prefix('fcm')->group(function () {
        // Token Management
        Route::post('/token', [FcmController::class, 'storeToken'])->name('fcm.store-token');
        Route::delete('/token', [FcmController::class, 'removeToken'])->name('fcm.remove-token');

        // Notification Preferences
        Route::get('/preferences', [FcmController::class, 'getPreferences'])->name('fcm.preferences');
        Route::put('/preferences', [FcmController::class, 'updatePreferences'])->name('fcm.update-preferences');

        // Testing
        Route::post('/test', [FcmController::class, 'sendTestNotification'])->name('fcm.test');

        // Notification History
        Route::get('/notifications', [FcmController::class, 'getNotifications'])->name('fcm.notifications');
        Route::post('/notifications/{notification}/read', [FcmController::class, 'markAsRead'])->name('fcm.mark-read');
        Route::post('/notifications/read-all', [FcmController::class, 'markAllAsRead'])->name('fcm.mark-all-read');
    });
});

// =====================
// PUBLIC FCM TEST ROUTES (for development)
// =====================
Route::prefix('fcm')->group(function () {
    Route::get('/test-connection', function () {
        return response()->json([
            'success' => true,
            'message' => 'FCM Test API is accessible',
            'timestamp' => now()->toDateTimeString()
        ]);
    });

    Route::post('/send-test', function (Request $request) {
        return response()->json([
            'success' => false,
            'message' => 'Please authenticate to send test notifications',
            'hint' => 'Use the authenticated /api/fcm/test endpoint'
        ], 401);
    });
});

// Webhook route (no authentication - Paystack calls this)
Route::post('/payment/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');

// CSRF Token Endpoint
Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show'])->name('sanctum.csrf-cookie');

// Health Check Route
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toDateTimeString(),
        'services' => [
            'api' => 'running',
            'database' => 'connected',
            'cache' => 'available'
        ]
    ]);
});
