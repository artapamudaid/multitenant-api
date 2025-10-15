<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PackageController extends Controller
{
    public function index(): JsonResponse
    {
        $packages = Package::orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'data' => PackageResource::collection($packages),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:packages,slug',
            'description' => 'nullable|string',
            'price_monthly' => 'required|numeric|min:0',
            'features' => 'required|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $package = Package::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Package created successfully',
            'data' => new PackageResource($package),
        ], 201);
    }

    public function show(Package $package): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new PackageResource($package),
        ]);
    }

    public function update(Request $request, Package $package): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'slug' => 'string|unique:packages,slug,' . $package->id,
            'description' => 'nullable|string',
            'price_monthly' => 'numeric|min:0',
            'features' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $package->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Package updated successfully',
            'data' => new PackageResource($package),
        ]);
    }

    public function destroy(Package $package): JsonResponse
    {
        // Check if package has tenants
        if ($package->tenants()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete package with active tenants',
            ], 422);
        }

        $package->delete();

        return response()->json([
            'success' => true,
            'message' => 'Package deleted successfully',
        ]);
    }
}
