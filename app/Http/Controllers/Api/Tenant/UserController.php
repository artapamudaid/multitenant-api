<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = config('app.current_tenant');

        $query = User::where('tenant_id', $tenant->id)
            ->with('roles');

        // Search
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by role
        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('slug', $request->role);
            });
        }

        $users = $query->latest()->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users),
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = config('app.current_tenant');

        // Check if can add more users
        if (!$tenant->canAddUser()) {
            return response()->json([
                'success' => false,
                'message' => 'User limit reached for your package',
                'current_users' => $tenant->users()->count(),
                'max_users' => $tenant->package->max_users,
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        // Assign roles
        $user->roles()->attach($request->roles);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => new UserResource($user->load('roles')),
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        // Check if user belongs to current tenant
        if ($user->tenant_id !== config('app.current_tenant')->id) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new UserResource($user->load('roles')),
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        // Check if user belongs to current tenant
        if ($user->tenant_id !== config('app.current_tenant')->id) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'array',
            'roles.*' => 'exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only(['name', 'email']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Update roles if provided
        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => new UserResource($user->load('roles')),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        // Check if user belongs to current tenant
        if ($user->tenant_id !== config('app.current_tenant')->id) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Cannot delete self
        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete yourself',
            ], 422);
        }

        // Cannot delete tenant admin if it's the only one
        if ($user->isTenantAdmin()) {
            $adminCount = User::where('tenant_id', $user->tenant_id)
                ->whereHas('roles', function ($q) {
                    $q->where('slug', 'tenant-admin');
                })
                ->count();

            if ($adminCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete the last tenant admin',
                ], 422);
            }
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }
}
