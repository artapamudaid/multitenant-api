<?php

namespace App\Http\Controllers\Api\Landlord;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::where('tenant_id', null)->paginate(20);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:6',
            'tenant_id' => Rule::in([null]),
        ]);

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'tenant_id' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data'    => new UserResource($user),
        ]);
    }

    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'data'    => new UserResource($user->load('tenant')),
        ]);
    }

    public function update(Request $request, String $id)
    {


        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'      => 'sometimes|required|string|max:255',
            'password'  => 'nullable|string|min:6',
            'is_active' => 'sometimes|required|boolean',
            'tenant_id' => Rule::in([null]),
        ]);

        $user->update([
            'name'      => $validated['name'] ?? $user->name,
            'is_active' => $validated['is_active'] ?? $user->is_active,
            'tenant_id' => null,
            'password'  => isset($validated['password'])
                ? Hash::make($validated['password'])
                : $user->password,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data'    => new UserResource($user),
        ]);
    }

    public function activate(string $id)
    {
        $user = User::findOrFail($id);

        $user->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'User activated successfully.',
        ]);
    }

    public function inactivate(string $id)
    {
        $user = User::findOrFail($id);

        $user->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'User inactivated successfully.',
        ]);
    }

}
