<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Landlord\TenantManagementController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\Landlord\UserManagementController;
use Illuminate\Support\Facades\Route;

// Public routes (Main domain only)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Protected routes (Requires authentication)
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

Route::middleware(['auth:sanctum', 'landlord'])->group(function () {
    Route::prefix('landlord')->group(function () {
            #Tenants Routes
            Route::get('tenants', [TenantManagementController::class, 'index']);
            Route::get('tenants/trashed', [TenantManagementController::class, 'trashed']);
            Route::post('tenants', [TenantManagementController::class, 'store']);
            Route::get('tenants/{tenant}', [TenantManagementController::class, 'show']);
            Route::put('tenants/{tenant}', [TenantManagementController::class, 'update']);
            Route::delete('tenants/{tenant}', [TenantManagementController::class, 'destroy']);
            Route::post('tenants/restore/{id}', [TenantManagementController::class, 'restore']);
            Route::delete('tenants/force/{id}', [TenantManagementController::class, 'forceDelete']);

            #Users Routes
            Route::get('users', [UserManagementController::class, 'index']);
            Route::post('users', [UserManagementController::class, 'store']);
            Route::put('users/activate/{user}', [UserManagementController::class, 'activate']);
            Route::put('users/inactivate/{user}', [UserManagementController::class, 'inactivate']);
            Route::get('users/{user}', [UserManagementController::class, 'show']);
            Route::put('users/{user}', [UserManagementController::class, 'update']);
        });
    });

    // Tenant routes (Requires tenant context)
    Route::middleware(['tenant'])->group(function () {
        Route::prefix('tenant')->group(function () {
            Route::get('/', [TenantController::class, 'show']);
            Route::get('users', [TenantController::class, 'users']);
        });

        //Route lainnya
    });

});
