<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = config('app.current_tenant');

        $stats = [
            'total_users' => $tenant->users()->count(),
            'max_users' => $tenant->package->max_users,
            'users_remaining' => $tenant->package->max_users - $tenant->users()->count(),
            'storage_used_mb' => 0, // Implement actual storage calculation
            'max_storage_mb' => $tenant->package->max_storage_mb,
            'subscription_status' => $tenant->subscription_status,
            'subscription_expires_at' => $tenant->package_expires_at?->toISOString(),
            'days_remaining' => $tenant->package_expires_at
                ? now()->diffInDays($tenant->package_expires_at, false)
                : null,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'tenant' => new TenantResource($tenant->load('package')),
                'stats' => $stats,
                'recent_users' => $tenant->users()
                    ->with('roles')
                    ->latest()
                    ->take(5)
                    ->get(),
            ],
        ]);
    }
}
