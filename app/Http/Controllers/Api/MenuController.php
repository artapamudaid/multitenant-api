<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    /**
     * Get sidebar menu for current user
     */
    public function getSidebar(Request $request): JsonResponse
    {
        $user = $request->user();

        // Determine scope based on user role
        $scope = $user->isSuperAdmin() ? 'super_admin' : 'tenant';

        // Get menu tree with permission check
        $menus = Menu::getMenuTree($scope, $user);

        return response()->json([
            'success' => true,
            'data' => [
                'menus' => $menus,
                'scope' => $scope,
            ],
        ]);
    }

    /**
     * Get all menus (for admin management)
     */
    public function index(Request $request): JsonResponse
    {
        $scope = $request->input('scope', 'tenant');

        $menus = Menu::where('scope', $scope)
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $menus,
        ]);
    }

    /**
     * Create menu
     */
    public function store(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'icon' => 'nullable|string',
            'route' => 'nullable|string',
            'url' => 'nullable|string',
            'parent_id' => 'nullable|exists:menus,id',
            'scope' => 'required|in:super_admin,tenant',
            'permission' => 'nullable',
            'package_feature' => 'nullable|string',
            'order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $menu = Menu::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Menu created successfully',
            'data' => $menu,
        ], 201);
    }

    /**
     * Update menu
     */
    public function update(Request $request, Menu $menu): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255',
            'icon' => 'nullable|string',
            'route' => 'nullable|string',
            'url' => 'nullable|string',
            'parent_id' => 'nullable|exists:menus,id',
            'permission' => 'nullable',
            'package_feature' => 'nullable|string',
            'order' => 'integer',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $menu->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Menu updated successfully',
            'data' => $menu,
        ]);
    }

    /**
     * Delete menu
     */
    public function destroy(Menu $menu): JsonResponse
    {
        $menu->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu deleted successfully',
        ]);
    }

    /**
     * Reorder menus
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'menus' => 'required|array',
            'menus.*.id' => 'required|exists:menus,id',
            'menus.*.order' => 'required|integer',
            'menus.*.parent_id' => 'nullable|exists:menus,id',
        ]);

        foreach ($validated['menus'] as $menuData) {
            Menu::where('id', $menuData['id'])->update([
                'order' => $menuData['order'],
                'parent_id' => $menuData['parent_id'] ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Menus reordered successfully',
        ]);
    }
}
