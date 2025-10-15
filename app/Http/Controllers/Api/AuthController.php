<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    /**
     * Register user + tenant baru
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'email'        => 'required|string|email|max:255|unique:users,email',
            'password'     => 'required|string|min:8|confirmed',
            'entity_name' => 'required|string|max:255',
            'package' => 'nullable|string|in:bawah,menengah,atas',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->tenantService->createTenant(
                $request->entity_name,
                $request->name,
                $request->email,
                $request->password,
                $request->package ?? 'atas'

            );

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data'    => [
                    'user'         => new UserResource($result['user']->load('tenant')),
                    'tenant'       => new TenantResource($result['tenant']),
                    'token'        => $result['token'],
                    'redirect_url' => $result['tenant']->url,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login dengan email + password (native Laravel)
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        // Jika user tenant biasa → wajib tenant aktif
        if ($user->tenant_id && (!$user->tenant || !$user->tenant->is_active)) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant is not active',
            ], 403);
        }

        // Buat token Sanctum
        $token = $user->createToken('api-token')->plainTextToken;

        // Siapkan data tenant (jika ada)
        $tenantResource = $user->tenant ? new TenantResource($user->tenant) : null;

        // Tentukan redirect URL
        $redirectUrl = $user->tenant?->url ?? config('app.url'); // fallback ke main domain

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data'    => [
                'user'         => new UserResource($user->load('tenant')),
                'tenant'       => $tenantResource,
                'token'        => $token,
                'redirect_url' => $redirectUrl,
            ],
        ]);
    }


    /**
     * Info user yang sedang login
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'user' => new UserResource($request->user()->load('tenant')),
            ],
        ]);
    }

    /**
     * Logout (hapus token saat ini)
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}
