<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPackageFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (!$user || !$user->tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found',
            ], 404);
        }

        if (!$user->tenant->hasFeature($feature)) {
            return response()->json([
                'success' => false,
                'message' => 'This feature is not available in your current package',
                'required_feature' => $feature,
                'current_package' => $user->tenant->package->name ?? 'None',
            ], 403);
        }

        return $next($request);
    }
}
