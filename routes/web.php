<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\APIAuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\BiodataController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\PromoBannerController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StockLocationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CreditAdminController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


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

// OPay sends the customer's browser / app WebView back here after paying
Route::get('/payment/opay/return', [PaymentController::class, 'opayReturn'])->name('payment.opay.return');

// ===================================================================
// AUTHENTICATED ROUTES (Admin Panel)
// ===================================================================
Route::middleware(['auth'])->group(function () {

    // Settings › Payment Gateways (Paystack / OPay keys) — permission: Manage payment gateways
    Route::prefix('admin/payment-gateways')->name('admin.payment-gateways.')->group(function () {
        Route::get('/',                [PaymentGatewayController::class, 'index'])->name('index');
        Route::put('checkout-settings',[PaymentGatewayController::class, 'updateCheckout'])->name('checkout');
        Route::put('{gateway}',        [PaymentGatewayController::class, 'updateConfig'])->name('update');
        Route::post('{gateway}/toggle',[PaymentGatewayController::class, 'toggleGateway'])->name('toggle');
        Route::post('{gateway}/test',  [PaymentGatewayController::class, 'testGateway'])->name('test');
    });
    // Dashboard
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/export', [DashboardController::class, 'export'])->name('export');
    });

    // ⌘K spotlight search (layouts/master)
    Route::get('/search/global', SearchController::class)->name('search.global');

    // Roles & Permissions (CSS Kabba port — DataTables endpoints must come BEFORE the resources)
    Route::get('/roles/{role}/users', [RoleController::class, 'users'])->name('roles.users');
    Route::get('/roles/{role}/candidates', [RoleController::class, 'candidates'])->name('roles.candidates');
    Route::post('/roles/bulk-remove-users', [RoleController::class, 'bulkRemoveUsers'])->name('roles.bulkremoveusers');
    Route::get('/permissions/data', [PermissionController::class, 'data'])->name('permissions.data');

    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class);

    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/adduser/{id}', [RoleController::class, 'adduser'])->name('adduser');
        Route::post('/updateuserrole', [RoleController::class, 'updateuserrole'])->name('updateuserrole');
        Route::delete('/removeuserrole/{userid}/{roleid}', [RoleController::class, 'removeuserrole'])
            ->name('removeuserrole');
    });

    // Users Management
    Route::get('/users/data', [UserController::class, 'data'])->name('users.data');
    Route::resource('users', UserController::class);
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/all', [UserController::class, 'allUsers'])->name('all');
        Route::get('/paginate', [UserController::class, 'paginate'])->name('paginate');
        Route::get('/roles', [UserController::class, 'roles'])->name('roles');
        Route::get('/users/{user}/overview', [UserController::class, 'overview'])->name('overview');
        // Route::get('/{user}/overview', [OverviewController::class, 'show'])->name('overview');
        Route::get('/{user}/settings', [BiodataController::class, 'show'])->name('settings');
    });

    // Biodata / Profile
    Route::resource('biodata', BiodataController::class);

    // Web Resources Management (with consistent naming)
    Route::prefix('web')->name('web.')->group(function () {
        // Server-side DataTables endpoints (must be registered before the resources)
        Route::get('banners/data', [BannerController::class, 'data'])->name('banners.data');
        Route::get('brands/data', [BrandController::class, 'data'])->name('brands.data');
        Route::get('categories/data', [CategoryController::class, 'data'])->name('categories.data');
        Route::get('products/data', [ProductController::class, 'data'])->name('products.data');
        Route::get('reviews/data', [ProductReviewController::class, 'data'])->name('reviews.data');
        Route::get('promo-banners/data', [PromoBannerController::class, 'data'])->name('promo-banners.data');

        // Banner Management
        Route::resource('banners', BannerController::class)->except(['show']);
        Route::get('banners/{banner}/edit', [BannerController::class, 'edit'])->name('banners.edit');

        // Brand Management
        Route::resource('brands', BrandController::class)->except(['show']);
        Route::get('brands/{brand}/edit', [BrandController::class, 'edit'])->name('brands.edit');

        Route::resource('promo-banners', PromoBannerController::class)->except(['create', 'edit', 'show']);

        // Drag-and-drop reorder endpoint
        Route::post('promo-banners/reorder', [PromoBannerController::class, 'reorder'])->name('promo-banners.reorder');
        // Fixed: these were registered as /web/web/... (double prefix) so the page's AJAX calls 404'd
        Route::post('promo-banners/bulk', [PromoBannerController::class, 'bulkAction'])->name('promo-banners.bulk');
        Route::patch('promo-banners/{id}/toggle-status', [PromoBannerController::class, 'toggleStatus'])->name('promo-banners.toggle-status');

        // Category Management
       Route::resource('categories', CategoryController::class)->except(['show']);
        // Bulk update route
       Route::post('categories/bulk-update', [CategoryController::class, 'bulkUpdate'])->name('categories.bulk-update');


        // Product Management
        Route::resource('products', ProductController::class);
        Route::prefix('products')->name('products.')->group(function () {
            Route::delete('/{id}/images/{imageId}', [ProductController::class, 'deleteImage'])->name('images.destroy');
            Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
            Route::get('/{product}/inventory', [ProductController::class, 'inventoryLog'])->name('inventory');
            Route::get('/template', [ProductController::class, 'template'])->name('template');
            Route::get('/search', [ProductController::class, 'search'])->name('search');
            Route::get('/realtime-stock', [ProductController::class, 'realtimeStock'])->name('realtime-stock');
            Route::post('/import', [ProductController::class, 'import'])->name('import');
            Route::get('/export', [ProductController::class, 'export'])->name('export');
            Route::post('/bulk-update', [ProductController::class, 'bulkUpdate'])->name('bulkUpdate');

            // Product Reviews
            Route::post('/{product}/reviews', [ProductReviewController::class, 'store'])->name('reviews.store');
            // NEW: Update product flags (is_new, is_trending, is_top_rated)
           Route::patch('/{product}/flags', [ProductController::class, 'updateFlags']) ->name('flags.update');
           // NEW: Bulk flag update
           Route::post('/bulk-flags', [ProductController::class, 'bulkUpdateFlags']) ->name('bulk-flags.update');
        });

        // Product Reviews Management
        Route::resource('reviews', ProductReviewController::class)->except(['show']);
        Route::prefix('reviews')->name('reviews.')->group(function () {
            Route::get('/{id}/edit', [ProductReviewController::class, 'edit'])->name('edit');
            Route::post('/{id}/company-comment', [ProductReviewController::class, 'addCompanyComment'])->name('company-comment');
        });
    });

    // ===================================================================
    // STOCK LOCATIONS MANAGEMENT
    // ===================================================================
    Route::prefix('stock-locations')->name('stock-locations.')->group(function () {
        // Custom routes that need to come BEFORE resource
        Route::get('/{id}/stock', [StockLocationController::class, 'getLocationStock'])->name('stock');
        Route::get('/{id}/view', [StockLocationController::class, 'show'])->name('view'); // Alternative show route
        Route::post('/{id}/set-default', [StockLocationController::class, 'setAsDefault'])->name('set-default');
        Route::post('/{id}/toggle-status', [StockLocationController::class, 'toggleStatus'])->name('toggle-status');

        // Standard resource routes
        Route::get('/', [StockLocationController::class, 'index'])->name('index');
        Route::get('/create', [StockLocationController::class, 'create'])->name('create');
        Route::post('/', [StockLocationController::class, 'store'])->name('store');
        Route::get('/{stock_location}', [StockLocationController::class, 'show'])->name('show');
        Route::get('/{stock_location}/edit', [StockLocationController::class, 'edit'])->name('edit');
        Route::put('/{stock_location}', [StockLocationController::class, 'update'])->name('update');
        Route::delete('/{stock_location}', [StockLocationController::class, 'destroy'])->name('destroy');

        // Additional routes
        Route::post('/update-sort', [StockLocationController::class, 'updateSortOrder'])->name('update-sort');
        Route::get('/export', [StockLocationController::class, 'exportLocations'])->name('export');
    });

    // ===================================================================
    // INVENTORY MANAGEMENT ROUTES
    // ===================================================================
    // Inventory Management Routes
    Route::prefix('inventory')->group(function () {
        // Server-side DataTables endpoints (before the /{id} routes below)
        Route::get('/transactions/data', [InventoryController::class, 'transactionsData'])->name('inventory.transactions.data');
        Route::get('/stock-levels/data', [InventoryController::class, 'stockLevelsData'])->name('inventory.stock-levels.data');

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

    Route::get('/inventory/export/stock-levels/pdf', [InventoryController::class, 'exportStockLevelsPDF'])
    ->name('inventory.export.stock-levels.pdf');
    // Real-time stock updates
    Route::get('/inventory/realtime-product-stock', [InventoryController::class, 'realtimeProductStock']);
    Route::get('/inventory/product/{productId}/location/{locationId}/stock', [InventoryController::class, 'getLocationStock']);
    // Sync product stock (admin only)
    Route::post('/inventory/sync-stocks', [InventoryController::class, 'syncAllProductStocks']) ->name('inventory.sync-stocks');
    Route::get('/inventory/export/stock-levels/pdf', [InventoryController::class, 'exportStockLevelsPDF'])->name('inventory.export.stock-levels.pdf');
    // Add this route
    Route::get('/inventory/recalculate-stock', [InventoryController::class, 'recalculateStock']) ->name('inventory.recalculate-stock');


    // ===================================================================
    // ORDER MANAGEMENT
    // ===================================================================
    Route::prefix('adminorders')->name('adminorders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/data', [OrderController::class, 'data'])->name('data');
        Route::get('/export', [OrderController::class, 'export'])->name('export');
        Route::put('/delivery-settings', [OrderController::class, 'saveDeliverySettings'])->name('delivery-settings');

        Route::prefix('{order}')->group(function () {
            Route::get('/', [OrderController::class, 'show'])->name('show');
            Route::post('/status', [OrderController::class, 'updateStatus'])->name('status');
            Route::get('/invoice', [OrderController::class, 'invoice'])->name('invoice');
            Route::post('/email-invoice', [OrderController::class, 'emailInvoice'])->name('emailInvoice');
            Route::post('/note', [OrderController::class, 'addNote'])->name('note');
            Route::post('/refund', [OrderController::class, 'refund'])->name('refund');
            Route::post('/refunds/{refund}/status', [OrderController::class, 'refundStatus'])->name('refund-status');
            Route::get('/packing-slip', [OrderController::class, 'packingSlip'])->name('packing-slip');
        });
    });

    // Address Management
    Route::get('/adminaddresses/data', [AddressController::class, 'data'])->name('adminaddresses.data');
    Route::get('/adminaddresses/customers', [AddressController::class, 'customers'])->name('adminaddresses.customers');
    Route::resource('adminaddresses', AddressController::class);

    // Gozak Credit — permissions: View credit | Review credit applications | Manage credit | Manage credit settings
    Route::prefix('admin/credit')->name('admin.credit.')->group(function () {
        Route::get('/',                                     [CreditAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/applications',                         [CreditAdminController::class, 'applications'])->name('applications');
        Route::get('/applications/{application}',           [CreditAdminController::class, 'application'])->name('application');
        Route::post('/applications/{application}/approve',  [CreditAdminController::class, 'approve'])->name('approve');
        Route::post('/applications/{application}/reject',   [CreditAdminController::class, 'reject'])->name('reject');
        Route::post('/applications/{application}/bvn',      [CreditAdminController::class, 'recheckBvn'])->name('bvn');
        Route::get('/accounts',                             [CreditAdminController::class, 'accounts'])->name('accounts');
        Route::get('/accounts/export',                      [CreditAdminController::class, 'export'])->name('accounts.export');
        Route::get('/accounts/{account}',                   [CreditAdminController::class, 'account'])->name('account');
        Route::post('/accounts/{account}/limit',            [CreditAdminController::class, 'setLimit'])->name('limit');
        Route::post('/accounts/{account}/status',           [CreditAdminController::class, 'setStatus'])->name('status');
        Route::post('/accounts/{account}/markup',           [CreditAdminController::class, 'setMarkup'])->name('markup');
        Route::post('/accounts/{account}/adjust',           [CreditAdminController::class, 'adjust'])->name('adjust');
        Route::post('/accounts/{account}/payment',          [CreditAdminController::class, 'recordPayment'])->name('payment');
        Route::post('/accounts/{account}/debit',            [CreditAdminController::class, 'debit'])->name('debit');
        Route::post('/accounts/{account}/recalculate',      [CreditAdminController::class, 'recalculate'])->name('recalculate');
        Route::post('/mandates/{mandate}/revoke',           [CreditAdminController::class, 'revokeMandate'])->name('mandate.revoke');
        Route::post('/mandates/{mandate}/primary',          [CreditAdminController::class, 'primaryMandate'])->name('mandate.primary');
        Route::post('/mandates/{mandate}/refresh',          [CreditAdminController::class, 'refreshMandate'])->name('mandate.refresh');
        Route::get('/statements',                           [CreditAdminController::class, 'statements'])->name('statements');
        Route::get('/statements/{statement}',               [CreditAdminController::class, 'statement'])->name('statement');
        Route::post('/statements/{statement}/waive-fee',    [CreditAdminController::class, 'waiveFee'])->name('waive');
        Route::get('/collections',                          [CreditAdminController::class, 'collections'])->name('collections');
        Route::get('/activity',                             [CreditAdminController::class, 'audit'])->name('audit');
        Route::get('/settings',                             [CreditAdminController::class, 'settings'])->name('settings');
        Route::put('/settings',                             [CreditAdminController::class, 'saveSettings'])->name('settings.save');
        Route::post('/run',                                 [CreditAdminController::class, 'runBilling'])->name('run');
    });

    // Live chat inbox — permission: Chat with customers | Manage chat
    Route::prefix('admin/chat')->name('admin.chat.')->group(function () {
        Route::get('/',                                         [ChatController::class, 'index'])->name('index');
        Route::get('/summary',                                  [ChatController::class, 'summary'])->name('summary');
        Route::get('/agents',                                   [ChatController::class, 'agents'])->name('agents');
        Route::post('/presence',                                [ChatController::class, 'presence'])->name('presence');
        Route::post('/pusher/auth',                             [ChatController::class, 'pusherAuth'])->name('pusher-auth');
        Route::get('/conversations',                            [ChatController::class, 'conversations'])->name('conversations');
        Route::get('/conversations/{conversation}',             [ChatController::class, 'show'])->name('show');
        Route::get('/conversations/{conversation}/messages',    [ChatController::class, 'messages'])->name('messages');
        Route::post('/conversations/{conversation}/messages',   [ChatController::class, 'send'])->name('send');
        Route::post('/conversations/{conversation}/order-card', [ChatController::class, 'sendOrder'])->name('order-card');
        Route::post('/conversations/{conversation}/take',       [ChatController::class, 'take'])->name('take');
        Route::post('/conversations/{conversation}/assign',     [ChatController::class, 'assign'])->name('assign');
        Route::post('/conversations/{conversation}/close',      [ChatController::class, 'close'])->name('close');
        Route::post('/conversations/{conversation}/read',       [ChatController::class, 'read'])->name('read');
        Route::post('/conversations/{conversation}/typing',     [ChatController::class, 'typing'])->name('typing');
    });

    // Customer Management
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('/data', [CustomerController::class, 'data'])->name('data');
        Route::get('/export', [CustomerController::class, 'export'])->name('export');
    });

    // Notifications
    Route::post('/notifications/mark-all-read', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return back()->with('success', 'All notifications marked as read.');
    })->name('notifications.markAllRead');


    // ============================================================
    //  web.php  — Admin routes (inside your auth middleware group)
    // ============================================================

    Route::prefix('lightning-deals')->name('lightning-deals.')->group(function () {
        Route::get('/',              [ProductController::class, 'lightningDealsIndex'])->name('index');
        Route::get('/data',          [ProductController::class, 'lightningDealsData'])->name('data');
        Route::post('/',             [ProductController::class, 'lightningDealStore'])->name('store');
        Route::patch('{id}/toggle',  [ProductController::class, 'lightningDealToggle'])->name('toggle');
        Route::delete('{id}',        [ProductController::class, 'lightningDealDestroy'])->name('destroy');
    });
});


// ── Public legal pages (Google Play: privacy policy, terms, account deletion) ──
Route::view('/privacy', 'legal.privacy')->name('legal.privacy');
Route::view('/terms', 'legal.terms')->name('legal.terms');
Route::view('/delete-account', 'legal.delete-account')->name('legal.delete-account');
