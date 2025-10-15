<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found',
            ], 404);
        }

        // Check if subscription is active
        if (!$user->tenant->isSubscriptionActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription has expired. Please renew your subscription.',
                'subscription_status' => $user->tenant->subscription_status,
                'expires_at' => $user->tenant->package_expires_at,
            ], 402); // 402 Payment Required
        }

        return $next($request);
    }
}
