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
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\StockLocationController;

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

    // Banner Management
    Route::resource('banners', BannerController::class)->except(['show']);
    Route::get('banners/{banner}/edit', [BannerController::class, 'edit'])->name('banners.edit');

    // Brand Management
    Route::resource('brands', BrandController::class)->except(['show']);
    Route::get('brands/{brand}/edit', [BrandController::class, 'edit'])->name('brands.edit');

    // Category Management
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');

    // Product Management
    Route::resource('products', ProductController::class);
    Route::delete('/products/{id}/images/{imageId}', [ProductController::class, 'deleteImage'])->name('products.images.destroy');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::get('/products/{product}/inventory', [ProductController::class, 'inventoryLog'])->name('products.inventory');
    Route::get('/products/template', [ProductController::class, 'template'])->name('products.template');
    Route::get('/products/search', [ProductController::class, 'search'])->name('products.search');
    Route::get('/products/realtime-stock', [ProductController::class, 'realtimeStock'])->name('products.realtime-stock');
    Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');
    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
    Route::post('/products/bulk-update', [ProductController::class, 'bulkUpdate'])->name('products.bulkUpdate');
 

    // Product Reviews
    Route::group(['prefix' => 'products/{product}/reviews'], function() {
        Route::post('/', [ProductReviewController::class, 'store'])->name('products.reviews.store');
    });
    
    // Admin Reviews Management
    Route::resource('reviews', ProductReviewController::class)->except(['show']);
    Route::get('/reviews/{id}/edit', [ProductReviewController::class, 'edit'])->name('reviews.edit');
    Route::post('/reviews/{id}/company-comment', [ProductReviewController::class, 'addCompanyComment'])->name('reviews.company-comment');
    
    // ===================================================================
    // INVENTORY MANAGEMENT ROUTES
    // ===================================================================
    

    // Stock Locations Management - SIMPLIFIED VERSION
    // Remove ALL duplicate routes and use this clean version:

    // Custom routes that need to come BEFORE resource
    Route::get('stock-locations/{id}/stock', [StockLocationController::class, 'getLocationStock'])->name('stock-locations.stock');
    Route::get('stock-locations/{id}/view', [StockLocationController::class, 'show'])->name('stock-locations.view'); // Alternative show route
    Route::post('stock-locations/{id}/set-default', [StockLocationController::class, 'setAsDefault'])->name('stock-locations.set-default');
    Route::post('stock-locations/{id}/toggle-status', [StockLocationController::class, 'toggleStatus'])->name('stock-locations.toggle-status');

    // Main resource route - this creates ALL standard routes (index, create, store, show, edit, update, destroy)
    Route::resource('stock-locations', StockLocationController::class);

    // Other non-conflicting routes
    Route::post('stock-locations/update-sort', [StockLocationController::class, 'updateSortOrder'])->name('stock-locations.update-sort');
    Route::get('stock-locations/export', [StockLocationController::class, 'exportLocations'])->name('stock-locations.export');

    
    // Inventory Management Routes
    Route::prefix('inventory')->group(function () {
        // Main inventory routes
        Route::get('/', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/dashboard', [InventoryController::class, 'dashboard'])->name('inventory.dashboard');
        Route::get('/stock-levels', [InventoryController::class, 'stockLevels'])->name('inventory.stock-levels');
        Route::get('/history/{id}', [InventoryController::class, 'stockHistory'])->name('inventory.history');
        // In your routes file (web.php)
        // ADD THIS LINE - API endpoint for stock history
        Route::get('/history/{id}', [InventoryController::class, 'getStockHistory'])->name('inventory.history.api');
      
        
        // Report pages
        Route::get('/low-stock-alerts', [InventoryController::class, 'lowStockAlerts'])->name('inventory.low-stock-alerts');
        Route::get('/stock-value-report', [InventoryController::class, 'stockValueReport'])->name('inventory.stock-value-report');


        
        // Stock operations (AJAX)
        Route::post('/adjust', [InventoryController::class, 'adjustStock'])->name('inventory.adjust');
        Route::post('/transfer', [InventoryController::class, 'transferStock'])->name('inventory.transfer');
        Route::post('/bulk-adjust', [InventoryController::class, 'bulkAdjust'])->name('inventory.bulk-adjust');
        Route::post('/import', [InventoryController::class, 'import'])->name('inventory.import');
        
        // Export routes
        Route::get('/export/transactions', [InventoryController::class, 'exportTransactions'])->name('inventory.export.transactions');
        Route::get('/export/stock-levels', [InventoryController::class, 'exportStockLevels'])->name('inventory.export.stock-levels');
        
        // API endpoints for AJAX requests
        Route::get('/{id}', [InventoryController::class, 'show'])->name('inventory.show');
        Route::delete('/{id}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
        Route::get('/stock-level/{productId}/{locationId}', [InventoryController::class, 'getProductStock'])->name('inventory.get-product-stock');
        
        // API endpoints for data
        Route::get('/api/low-stock-alerts', [InventoryController::class, 'getLowStockAlerts'])->name('inventory.api.low-stock-alerts');
        Route::get('/api/stock-value-report', [InventoryController::class, 'getStockValueReport'])->name('inventory.api.stock-value-report');
    });
 
    // Real-time stock updates
    Route::get('/inventory/realtime-product-stock', [InventoryController::class, 'realtimeProductStock']);
    Route::get('/inventory/product/{productId}/location/{locationId}/stock', [InventoryController::class, 'getLocationStock']);
    // Sync product stock (admin only)
    Route::post('/inventory/sync-stocks', [InventoryController::class, 'syncAllProductStocks'])
        ->name('inventory.sync-stocks');
});