<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
// Route::apiResource('categories', APICategoryController::class)->only(['index']);  // Alternative: Resourceful

Route::get('/brands', [APIBrandController::class, 'index'])->name('brands.index');
Route::get('/brands/{id}', [APIBrandController::class, 'show'])->name('brands.show');
Route::get('/brands/isFeatured', [APIBrandController::class, 'featured'])->name('brands.featured');
Route::get('/brands/category/{categoryId}', [APIBrandController::class, 'getbrandsForCategory'])->name('brands.category');
Route::post('/brands', [APIBrandController::class, 'store']);  // Assuming admin-only, but public for now
Route::post('/brand-categories', [APIBrandController::class, 'storeBrandCategory']);

Route::get('/products', [APIProductController::class, 'index'])->name('products.index');
Route::get('/products/{id}', [APIProductController::class, 'show'])->name('products.show');
Route::post('/products', [APIProductController::class, 'store']);  // Admin-only?
Route::patch('/products/{id}', [APIProductController::class, 'updateSingleField']);
Route::put('/products/{id}', [APIProductController::class, 'update']);
Route::post('/upload', [APIProductController::class, 'uploadFile']);
Route::post('/product-categories', [APIProductController::class, 'storeProductCategory']);
// Route::apiResource('products', APIProductController::class)->only(['index', 'show']);  // Public only

Route::get('/product-reviews', [APIProductReviewController::class, 'index'])->name('product-reviews.index');
Route::post('/product-reviews', [APIProductReviewController::class, 'store']);
Route::put('/product-reviews/{id}/company-comment', [APIProductReviewController::class, 'addCompanyComment']);
Route::put('/product-reviews/{id}', [APIProductReviewController::class, 'update']);
Route::delete('/product-reviews/{id}', [APIProductReviewController::class, 'destroy']);

Route::get('/banners', [APIBannerController::class, 'index'])->name('banners.index');
Route::post('/banners', [APIBannerController::class, 'store']);  // Admin-only?

// Privacy Policy and User Data Safety Routes
Route::get('/privacy-policy', [PrivacyPolicyController::class, 'show'])->name('privacy.policy');
Route::get('/user-data-safety', [PrivacyPolicyController::class, 'showUserDataSafety'])->name('user.data-safety');

// MOVED: Global settings endpoint - now public (guests can read, but not write)
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

    // User-specific Settings Routes (require authentication)
    Route::get('/settings', [APISettingsController::class, 'show'])->name('settings.show');
    Route::post('/settings', [APISettingsController::class, 'store'])->name('settings.store');
    Route::put('/settings', [APISettingsController::class, 'update'])->name('settings.update');
    Route::patch('/settings', [APISettingsController::class, 'updateField']);  // Note: PATCH to /settings (not /field) – consider /settings/field for clarity

    // Order Routes
    Route::get('/orders', [APIOrderController::class, 'index'])->name('orders.index');
    Route::post('/orders', [APIOrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{id}', [APIOrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{id}', [APIOrderController::class, 'update'])->name('orders.update');
    Route::delete('/orders/{id}', [APIOrderController::class, 'delete'])->name('orders.destroy');  // Use 'destroy' for consistency
    
    Route::get('/addresses', [APIAddressController::class, 'index'])->name('addresses.index');
    Route::post('/addresses', [APIAddressController::class, 'store'])->name('addresses.store');
    Route::put('/addresses/{id}', [APIAddressController::class, 'update'])->name('addresses.update');
    Route::patch('/addresses/{id}', [APIAddressController::class, 'patch']);
    Route::delete('/addresses/{id}', [APIAddressController::class, 'destroy'])->name('addresses.destroy');

    // Payment Routes
    Route::post('/payment/initialize', [PaymentController::class, 'initializePayment'])->name('payment.initialize');
    Route::post('/payment/charge', [PaymentController::class, 'chargeCard'])->name('payment.charge');
    Route::post('/payment/submit-otp', [PaymentController::class, 'submitOTP'])->name('payment.otp');
    Route::post('/payment/submit-pin', [PaymentController::class, 'submitPIN'])->name('payment.pin');
    Route::post('/payment/verify', [PaymentController::class, 'verifyPayment'])->name('payment.verify');
    Route::get('/payment/public-key', [PaymentController::class, 'getPublicKey'])->name('payment.public-key');
    Route::get('/payment/history', [PaymentController::class, 'getPaymentHistory'])->name('payment.history');
    Route::get('/payment/{reference}', [PaymentController::class, 'getPayment'])->name('payment.show');
});

// Webhook route (no authentication - Paystack calls this)
Route::post('/payment/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');

// CSRF Token Endpoint
Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show'])->name('sanctum.csrf-cookie');