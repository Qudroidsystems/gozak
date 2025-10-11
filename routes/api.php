<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\APIAuthController;
use App\Http\Controllers\APIUserController;
use App\Http\Controllers\APIBrandController;
use App\Http\Controllers\APIOrderController;
use App\Http\Controllers\APIBannerController;
use App\Http\Controllers\APIUploadController;
use App\Http\Controllers\APIAddressController;
use App\Http\Controllers\APIProductController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\APICategoryController;
use App\Http\Controllers\APISettingsController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\APIProductReviewController;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

// Authentication Routes
Route::post('/register', [APIAuthController::class, 'register']);
Route::post('/login', [APIAuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/social-login', [APIAuthController::class, 'socialLogin'])->middleware('throttle:10,1');
Route::post('/password/email', [APIAuthController::class, 'sendPasswordResetEmail'])->middleware('throttle:6,1');
Route::post('/password/reset', [APIAuthController::class, 'resetPassword']);
Route::get('/email/verify/{id}/{hash}', [APIAuthController::class, 'verifyEmail'])->middleware(['throttle:6,1'])->name('verification.verify');

// Public Routes
Route::get('/categories', [APICategoryController::class, 'index']);

Route::get('/brands', [APIBrandController::class, 'index']);
Route::get('/brands/{id}', [APIBrandController::class, 'show']);
Route::get('/brands/isFeatured', [APIBrandController::class, 'featured']);
Route::get('/brands/category/{categoryId}', [APIBrandController::class, 'getbrandsForCategory']);
Route::post('/brands', [APIBrandController::class, 'store']);
Route::post('/brand-categories', [APIBrandController::class, 'storeBrandCategory']);

Route::get('/products', [APIProductController::class, 'index']);
Route::get('/products/{id}', [APIProductController::class, 'show']);
Route::post('/products', [APIProductController::class, 'store']);
Route::patch('/products/{id}', [APIProductController::class, 'updateSingleField']);
Route::put('/products/{id}', [APIProductController::class, 'update']);
Route::post('/upload', [APIProductController::class, 'uploadFile']);
Route::post('/product-categories', [APIProductController::class, 'storeProductCategory']);

Route::get('/product-reviews', [APIProductReviewController::class, 'index']);
Route::post('/product-reviews', [APIProductReviewController::class, 'store']);
Route::put('/product-reviews/{id}/company-comment', [APIProductReviewController::class, 'addCompanyComment']);
Route::put('/product-reviews/{id}', [APIProductReviewController::class, 'update']);
Route::delete('/product-reviews/{id}', [APIProductReviewController::class, 'destroy']);

Route::get('/banners', [APIBannerController::class, 'index']);
Route::post('/banners', [APIBannerController::class, 'store']);

// Privacy Policy and User Data Safety Routes
Route::get('/privacy-policy', [PrivacyPolicyController::class, 'show']);
Route::get('/user-data-safety', [PrivacyPolicyController::class, 'showUserDataSafety']);

// Authenticated Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [APIAuthController::class, 'logout']);
    Route::post('/email/verification-notification', [APIAuthController::class, 'sendEmailVerificationNotification']);
    
    // User Routes
    Route::get('/user', [APIUserController::class, 'show']);
    Route::put('/user', [APIUserController::class, 'update']);
    Route::patch('/user', [APIUserController::class, 'updateField']);
    Route::post('/user/profile-picture', [APIUserController::class, 'uploadProfilePicture']);
    Route::delete('/user', [APIUserController::class, 'destroy']);

    // Settings Routes
    Route::get('/settings', [APISettingsController::class, 'show']);
    Route::post('/settings', [APISettingsController::class, 'store']);
    Route::put('/settings', [APISettingsController::class, 'update']);
    Route::patch('/settings', [APISettingsController::class, 'updateField']);
    Route::get('/settings/global', [APISettingsController::class, 'global']);

    // Order Routes
    Route::get('/orders', [APIOrderController::class, 'index']);
    Route::post('/orders', [APIOrderController::class, 'store']);
    Route::get('/orders/{id}', [APIOrderController::class, 'show']);
    Route::put('/orders/{id}', [APIOrderController::class, 'update']);
    Route::delete('/orders/{id}', [APIOrderController::class, 'delete']);
    
    Route::get('/addresses', [APIAddressController::class, 'index']);
    Route::post('/addresses', [APIAddressController::class, 'store']);
    Route::put('/addresses/{id}', [APIAddressController::class, 'update']);
    Route::patch('/addresses/{id}', [APIAddressController::class, 'patch']);
    Route::delete('/addresses/{id}', [APIAddressController::class, 'destroy']);

    // Initialize payment
    Route::post('/payment/initialize', [PaymentController::class, 'initializePayment']);
    // Charge card
    Route::post('/payment/charge', [PaymentController::class, 'chargeCard']);
    
    // Submit OTP for verification
    Route::post('/payment/submit-otp', [PaymentController::class, 'submitOTP']);
    
    // Submit PIN for verification
    Route::post('/payment/submit-pin', [PaymentController::class, 'submitPIN']);
    
    // Verify payment
    Route::post('/payment/verify', [PaymentController::class, 'verifyPayment']);
    
    // Get public key
    Route::get('/payment/public-key', [PaymentController::class, 'getPublicKey']);
    
    // Get payment history
    Route::get('/payment/history', [PaymentController::class, 'getPaymentHistory']);
    // Get payment details
    Route::get('/payment/{reference}', [PaymentController::class, 'getPayment']);
});

// Webhook route (no authentication - Paystack calls this)
Route::post('/payment/webhook', [PaymentController::class, 'webhook']);
// CSRF Token Endpoint
Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show'])->name('sanctum.csrf-cookie');
?>