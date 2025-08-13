<?php

use App\Http\Controllers\APIAddressController;
use App\Http\Controllers\APIAuthController;
use App\Http\Controllers\APIBannerController;
use App\Http\Controllers\APICategoryController;
use App\Http\Controllers\APIOrderController;
use App\Http\Controllers\APIProductController;
use App\Http\Controllers\APISettingsController;
use App\Http\Controllers\APIUploadController;
use App\Http\Controllers\APIUserController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

// Authentication Routes
Route::post('/register', [APIAuthController::class, 'register']);
Route::post('/login', [APIAuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/social-login', [APIAuthController::class, 'socialLogin'])->middleware('throttle:10,1');
Route::post('/password/email', [APIAuthController::class, 'sendPasswordResetEmail'])->middleware('throttle:6,1');
Route::post('/password/reset', [APIAuthController::class, 'resetPassword']);
Route::get('/email/verify/{id}/{hash}', [APIAuthController::class, 'verifyEmail'])
    ->middleware(['throttle:6,1'])
    ->name('verification.verify');

// Public Routes
Route::get('/categories', [APICategoryController::class, 'index']);

Route::get('/products', [APIProductController::class, 'index']);
Route::get('/products/{id}', [APIProductController::class, 'show']);
Route::post('/products', [APIProductController::class, 'store']);
Route::patch('/products/{id}', [APIProductController::class, 'updateSingleField']);
Route::put('/products/{id}', [APIProductController::class, 'update']);
Route::post('/upload', [APIProductController::class, 'upload']);


Route::get('/banners', [APIBannerController::class, 'index']);
Route::post('/banners', [APIBannerController::class, 'store']);
Route::post('/upload', [APIUploadController::class, 'upload']);

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

    // Address Routes
    Route::apiResource('addresses', APIAddressController::class);

    // Order Routes
    Route::get('/orders', [APIOrderController::class, 'index']);
    Route::post('/orders', [APIOrderController::class, 'store']);
});

// CSRF Token Endpoint
Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show'])->name('sanctum.csrf-cookie');