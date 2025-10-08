<?php

namespace App\Http\Controllers\Api\Landlord;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Services\TenantService;

class TenantManagementController extends Controller
{

    public function __construct(
        protected TenantService $tenantService
    ) {}

    public function index()
    {
        $tenants = Tenant::withCount('users')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => TenantResource::collection($tenants),
        ]);
    }

    public function show(Tenant $tenant)
    {
        return response()->json([
            'success' => true,
            'data' => new TenantResource($tenant->load('users')),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'email'        => 'required|string|email|max:255|unique:users,email',
            'password'     => 'required|string|min:8|confirmed',
            'company_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            // Buat tenant baru
            $result = $this->tenantService->createTenant(
                $request->company_name,
                $request->name,
                $request->email,
                $request->password
            );


            return response()->json([
                'success' => true,
                'message' => 'Tenant created successfully.',
                'data'    => [
                    'tenant'       => new TenantResource($result['tenant']),
                    'user'         => new UserResource($result['user']->load('tenant')),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tenant: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, String $id)
    {
        try {
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'name'      => 'sometimes|required|string|max:255',
            'subdomain' => 'sometimes|string|max:255|unique:tenants,subdomain,' . $id,
            'is_active' => 'sometimes|boolean',
        ]);

        $tenant->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tenant updated successfully.',
            'data'    => new TenantResource($tenant),
        ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tenant: ' . $e->getMessage(),
            ], 500);
    }
    }

   public function destroy(Tenant $tenant)
    {
        try {
            $tenant->delete();

            if ($tenant->trashed()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tenant deleted successfully (soft deleted).',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to soft delete tenant.',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting tenant: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function trashed()
    {
        $tenants = Tenant::onlyTrashed()
            ->withCount('users')
            ->latest('deleted_at')
            ->paginate(20);


        return response()->json([
            'success' => true,
            'data' => TenantResource::collection($tenants),
        ]);
    }

    // 🔹 Restore tenant dari trash
    public function restore(string $id)
    {
        try {
            $tenant = Tenant::onlyTrashed()->findOrFail($id);
            $tenant->restore();

            return response()->json([
                'success' => true,
                'message' => 'Tenant restored successfully.',
                'data'    => new TenantResource($tenant),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore tenant: ' . $e->getMessage(),
            ], 500);
        }
    }

    // 🔹 Force delete (hapus permanen dari DB)
    public function forceDelete(string $id)
    {
        try {
            $tenant = Tenant::onlyTrashed()->findOrFail($id);
            $tenant->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Tenant permanently deleted.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to permanently delete tenant: ' . $e->getMessage(),
            ], 500);
        }
    }


}
