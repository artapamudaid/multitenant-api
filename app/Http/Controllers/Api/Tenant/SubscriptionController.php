<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\PackageResource;
use App\Http\Resources\TenantResource;
use App\Models\Package;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Resources\SubscriptionTransactionResource;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    public function current(): JsonResponse
    {
        $tenant = config('app.current_tenant');

        return response()->json([
            'success' => true,
            'data' => [
                'tenant' => new TenantResource($tenant->load('package')),
                'subscription_status' => $tenant->subscription_status,
                'started_at' => $tenant->package_started_at?->toISOString(),
                'expires_at' => $tenant->package_expires_at?->toISOString(),
                'is_active' => $tenant->isSubscriptionActive(),
                'days_remaining' => $tenant->package_expires_at
                    ? now()->diffInDays($tenant->package_expires_at, false)
                    : null,
            ],
        ]);
    }

    public function availablePackages(): JsonResponse
    {
        $packages = Package::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => PackageResource::collection($packages),
        ]);
    }

    public function upgrade(Request $request): JsonResponse
    {
        $tenant = config('app.current_tenant');

        $request->validate([
            'package_slug' => 'required|exists:packages,slug',
        ]);

        $newPackage = Package::where('slug', $request->package_slug)->first();

        // Check if it's actually an upgrade
        if ($newPackage->price_monthly <= $tenant->package->price_monthly) {
            return response()->json([
                'success' => false,
                'message' => 'You can only upgrade to a higher package',
            ], 422);
        }

        try {
            $result = $this->tenantService->upgradePackage(
                $tenant,
                $request->package_slug,
                Auth::user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Package upgraded successfully',
                'data' => [
                    'tenant' => new TenantResource($result['tenant']),
                    'transaction' => new SubscriptionTransactionResource($result['transaction']),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upgrade package: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function renew(Request $request): JsonResponse
    {
        $tenant = config('app.current_tenant');

        $request->validate([
            'months' => 'required|integer|min:1|max:12',
        ]);

        try {
        $result = $this->tenantService->renewSubscription(
            $tenant,
            $request->months,
            Auth::user()
        );

        return response()->json([
                'success' => true,
                'message' => 'Subscription renewed successfully',
                'data' => [
                    'tenant' => new TenantResource($result['tenant']),
                    'transaction' => new SubscriptionTransactionResource($result['transaction']),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to renew subscription: ' . $e->getMessage(),
            ], 500);
        }
    }
}
