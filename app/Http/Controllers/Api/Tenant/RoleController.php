<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = config('app.current_tenant');

        $roles = Role::where('tenant_id', $tenant->id)
            ->with('permissions')
            ->get();

        return response()->json([
            'success' => true,
            'data' => RoleResource::collection($roles),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = config('app.current_tenant');

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = Role::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'scope' => 'tenant',
            'tenant_id' => $tenant->id,
            'is_system' => false,
        ]);

        // Attach permissions
        if ($request->has('permissions')) {
            $role->permissions()->attach($request->permissions);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully',
            'data' => new RoleResource($role->load('permissions')),
        ], 201);
    }

    public function show(Role $role): JsonResponse
    {
        // Check if role belongs to current tenant
        if ($role->tenant_id !== config('app.current_tenant')->id) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new RoleResource($role->load('permissions', 'users')),
        ]);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        // Check if role belongs to current tenant
        if ($role->tenant_id !== config('app.current_tenant')->id) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        // Cannot update system roles
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'System roles cannot be modified',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('name')) {
            $role->update([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
            ]);
        }

        if ($request->has('description')) {
            $role->update(['description' => $request->description]);
        }

        // Update permissions
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => new RoleResource($role->load('permissions')),
        ]);
    }

    public function destroy(Role $role): JsonResponse
    {
        // Check if role belongs to current tenant
        if ($role->tenant_id !== config('app.current_tenant')->id) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        // Cannot delete system roles
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'System roles cannot be deleted',
            ], 422);
        }

        // Check if role has users
        if ($role->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete role with assigned users',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully',
        ]);
    }

    public function getPermissions(): JsonResponse
    {
        $permissions = Permission::where('scope', 'tenant')
            ->orWhere('scope', 'both')
            ->get()
            ->groupBy('module');

        return response()->json([
            'success' => true,
            'data' => $permissions->map(function ($perms) {
                return PermissionResource::collection($perms);
            }),
        ]);
    }
}
