<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\APIAuthController;
use App\Http\Controllers\BiodataController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\DashboardController;        // <-- ADD THIS
use App\Http\Controllers\PermissionController;

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

    // ===================================================================
    // BRAND MANAGEMENT (New – Same style as Users)
    // ===================================================================
    Route::resource('brands', BrandController::class)->except(['show']);

    // Extra route needed for Edit modal (AJAX)
    Route::get('brands/{brand}/edit', [BrandController::class, 'edit'])
        ->name('brands.edit');

    // Optional: If you want a nice "show" page later
    // Route::get('brands/{brand}', [BrandController::class, 'show'])->name('brands.show');

    // ===================================================================
    // FUTURE: Categories, Products, etc. (Just add like this)
    // ===================================================================
    // Route::resource('categories', CategoryController::class)->except(['show']);
    // Route::resource('products', ProductController::class)->except(['show']);
});