<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Tenant\DashboardController;
use App\Http\Controllers\Api\Tenant\RoleController;
use App\Http\Controllers\Api\Tenant\SubscriptionController;
use App\Http\Controllers\Api\Tenant\UserController;
use App\Http\Controllers\Api\SuperAdmin\PackageController;
use App\Http\Controllers\Api\SuperAdmin\TenantManagementController;
use App\Http\Controllers\Api\SuperAdmin\UserManagementController;
use Illuminate\Support\Facades\Route;

// Public routes (Main domain only)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Protected routes (Requires authentication)
Route::middleware(['auth:sanctum'])->group(function () {
    // Get sidebar menu (available for all authenticated users)
    Route::get('menu/sidebar', [App\Http\Controllers\Api\MenuController::class, 'getSidebar']);

    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

    Route::prefix('landlord')->middleware(['landlord'])->group(function () {
            // Package management
            Route::apiResource('packages', PackageController::class);

            #Tenants Routes
            Route::get('tenants', [TenantManagementController::class, 'index']);
            Route::get('tenants/trashed', [TenantManagementController::class, 'trashed']);
            Route::post('tenants', [TenantManagementController::class, 'store']);
            Route::get('tenants/{tenant}', [TenantManagementController::class, 'show']);
            Route::put('tenants/{tenant}', [TenantManagementController::class, 'update']);
            Route::delete('tenants/{tenant}', [TenantManagementController::class, 'destroy']);
            Route::post('tenants/toggle-status/{tenant}', [TenantManagementController::class, 'toggleStatus']);
            Route::post('tenants/restore/{id}', [TenantManagementController::class, 'restore']);
            Route::delete('tenants/force/{id}', [TenantManagementController::class, 'forceDelete']);

            #Users Routes
            Route::get('users', [UserManagementController::class, 'index']);
            Route::post('users', [UserManagementController::class, 'store']);
            Route::put('users/activate/{user}', [UserManagementController::class, 'activate']);
            Route::put('users/inactivate/{user}', [UserManagementController::class, 'inactivate']);
            Route::get('users/{user}', [UserManagementController::class, 'show']);
            Route::put('users/{user}', [UserManagementController::class, 'update']);

            Route::prefix('menus')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\MenuController::class, 'index']);
                Route::post('/', [App\Http\Controllers\Api\MenuController::class, 'store']);
                Route::put('{menu}', [App\Http\Controllers\Api\MenuController::class, 'update']);
                Route::delete('{menu}', [App\Http\Controllers\Api\MenuController::class, 'destroy']);
                Route::post('reorder', [App\Http\Controllers\Api\MenuController::class, 'reorder']);
            });

             Route::prefix('transactions')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\SuperAdmin\TransactionController::class, 'index']);
                Route::get('statistics', [App\Http\Controllers\Api\SuperAdmin\TransactionController::class, 'statistics']);
            });
    });

    // Tenant routes (Requires tenant context)
    Route::middleware(['tenant', 'subscription'])->group(function () {

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index'])
            ->middleware(['permission:dashboard.view']);

        // User Management
        Route::prefix('users')->middleware(['permission:users.view'])->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store'])
                ->middleware(['permission:users.create']);
            Route::get('{user}', [UserController::class, 'show']);
            Route::put('{user}', [UserController::class, 'update'])
                ->middleware(['permission:users.edit']);
            Route::delete('{user}', [UserController::class, 'destroy'])
                ->middleware(['permission:users.delete']);
        });

        // Role Management
        Route::prefix('roles')->middleware(['permission:roles.view'])->group(function () {
            Route::get('/', [RoleController::class, 'index']);
            Route::get('permissions', [RoleController::class, 'getPermissions']);
            Route::post('/', [RoleController::class, 'store'])
                ->middleware(['permission:roles.create']);
            Route::get('{role}', [RoleController::class, 'show']);
            Route::put('{role}', [RoleController::class, 'update'])
                ->middleware(['permission:roles.edit']);
            Route::delete('{role}', [RoleController::class, 'destroy'])
                ->middleware(['permission:roles.delete']);
        });

        // Subscription Management
        Route::prefix('subscription')->group(function () {
            Route::get('/', [SubscriptionController::class, 'current']);
            Route::get('packages', [SubscriptionController::class, 'availablePackages']);
            Route::post('upgrade', [SubscriptionController::class, 'upgrade']);
            Route::post('renew', [SubscriptionController::class, 'renew']);
        });

        // Subscription history
        Route::prefix('subscription/history')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\Tenant\SubscriptionHistoryController::class, 'index']);
            Route::get('statistics', [App\Http\Controllers\Api\Tenant\SubscriptionHistoryController::class, 'statistics']);
            Route::get('{transaction}', [App\Http\Controllers\Api\Tenant\SubscriptionHistoryController::class, 'show']);
            Route::get('{transaction}/invoice', [App\Http\Controllers\Api\Tenant\SubscriptionHistoryController::class, 'downloadInvoice']);
        });
    });
});
