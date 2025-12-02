<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\APIAuthController;
use App\Http\Controllers\BiodataController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductReviewController;        // <-- ADD THIS

// Public Routes
Route::get('/', function () {
    return view('welcome');
});

Route::get('/email/verify/{id}/{hash}', [APIAuthController::class, 'verifyEmail'])
    ->middleware('signed')
    ->name('verification.verify');

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::get('/payment-callback', function () {
    return view('payment-callback');
})->name('payment.callback');

// ===================================================================
// AUTHENTICATED ROUTES (Admin Panel)
// ===================================================================
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export');

    // Roles & Permissions
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class);

    Route::get('/adduser/{id}', [RoleController::class, 'adduser'])->name('roles.adduser');
    Route::post('/updateuserrole', [RoleController::class, 'updateuserrole'])->name('roles.updateuserrole');
    Route::delete('roles/removeuserrole/{userid}/{roleid}', [RoleController::class, 'removeuserrole'])
        ->name('roles.removeuserrole');

    // Users Management
    Route::resource('users', UserController::class);
    Route::get('/users/all', [UserController::class, 'allUsers'])->name('users.all');
    Route::get('/users/paginate', [UserController::class, 'paginate'])->name('users.paginate');
    Route::get('/users/roles', [UserController::class, 'roles'])->name('users.roles');

    // Biodata / Profile
    Route::resource('biodata', BiodataController::class);
    Route::get('/users/{user}/overview', [OverviewController::class, 'show'])->name('user.overview');
    Route::get('/users/{user}/settings', [BiodataController::class, 'show'])->name('user.settings');

    Route::resource('banners', BannerController::class)->except(['show']);
    Route::get('banners/{banner}/edit', [BannerController::class, 'edit'])->name('banners.edit');

    
    Route::resource('brands', BrandController::class)->except(['show']);

    // Extra route needed for Edit modal (AJAX)
    Route::get('brands/{brand}/edit', [BrandController::class, 'edit'])
        ->name('brands.edit');

    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');

    Route::resource('products', ProductController::class);
    Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::delete('/products/{id}/images/{imageId}', [ProductController::class, 'deleteImage'])->name('products.images.destroy');

    // Product Reviews Routes
    Route::group(['prefix' => 'products/{product}/reviews'], function() {
        Route::post('/', [ProductReviewController::class, 'store'])->name('products.reviews.store');
    });

    // Admin Reviews Routes
    Route::resource('reviews', ProductReviewController::class)->except(['show']);
    Route::get('/reviews/{id}/edit', [ProductReviewController::class, 'edit'])->name('reviews.edit');
    Route::post('/reviews/{id}/company-comment', [ProductReviewController::class, 'addCompanyComment'])->name('reviews.company-comment');
        
});