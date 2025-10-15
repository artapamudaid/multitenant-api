<?php

namespace App\Http\Middleware;

use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->tenantService->getTenantFromRequest();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant tidak ditemukan atau tidak aktif',
            ], 404);
        }

        // Set tenant ke config
        config(['app.current_tenant' => $tenant]);

        $user = $request->user();

        if ($user) {
            // Jika Landlord (tenant_id null)
            if (is_null($user->tenant_id)) {
                // Bisa akses semua tenant
                return $next($request);
            }

            // Jika user tenant biasa tapi akses tenant lain → tolak
            if ($user->tenant_id !== $tenant->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kamu tidak memiliki akses ke tenant ini',
                ], 403);
            }
        }


        return $next($request);
    }
}
