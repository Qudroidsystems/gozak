<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BiodataController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PermissionController;

// Public Routes
Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/payment-callback', function () {
    return view('payment-callback');
})->name('payment.callback');

// Admin Routes (Authenticated + Admin Middleware)
Route::group(['middleware' => ['auth']], function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export');

    // Permissions Resource
    Route::resource('permissions', PermissionController::class);

    // Roles Resource & Actions
    Route::resource('roles', RoleController::class);
    Route::get('/roles/{role}/add-user', [RoleController::class, 'adduser'])->name('roles.add-user'); // Renamed for clarity
    Route::post('/roles/update-user-role', [RoleController::class, 'updateuserrole'])->name('roles.update-user-role');
    Route::delete('/roles/{role}/remove-user/{user}', [RoleController::class, 'removeuserrole'])->name('roles.remove-user'); // Better param binding

    // Users Resource & Custom Actions
    Route::resource('users', UserController::class);
    // Removed redundant destroy—resource handles it
    Route::get('/users/all', [UserController::class, 'allUsers'])->name('users.all');
    Route::get('/users/paginate', [UserController::class, 'paginate'])->name('users.paginate');
    Route::get('/users/roles', [UserController::class, 'roles'])->name('users.roles');

    // Student-Specific User Creation
    Route::name('users.')->group(function () {
        Route::get('add-student', [UserController::class, 'createFromStudentForm'])->name('add-student-form');
        Route::post('create-from-student', [UserController::class, 'createFromStudent'])->name('create-from-student');
    });

    // AJAX Endpoints
    Route::get('/users/students', [UserController::class, 'getStudents'])->name('users.students'); // Renamed from 'get.students' for consistency

    // Biodata Resource
    Route::resource('biodata', BiodataController::class);

    // User Overview & Settings
    Route::get('/users/{user}/overview', [OverviewController::class, 'show'])->name('users.overview');
    Route::get('/users/{user}/settings', [BiodataController::class, 'show'])->name('users.settings'); // Assuming {id} is user
});