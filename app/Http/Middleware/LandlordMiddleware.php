<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LandlordMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Hanya user dengan tenant_id NULL yang boleh lewat
        if (!is_null($user->tenant_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses hanya untuk Landlord (Super Admin).',
            ], 403);
        }

        return $next($request);
    }
}
