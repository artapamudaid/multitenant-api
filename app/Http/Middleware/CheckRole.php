<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        if (!$request->user()->hasRole($roles)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have the required role',
                'required_roles' => $roles,
            ], 403);
        }

        return $next($request);
    }
}
