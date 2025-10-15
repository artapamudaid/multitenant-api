<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class PermissionHelper
{
    public static function can(string $permission): bool
    {
        return Auth::check() && Auth::users()->hasPermission($permission);
    }

    public static function canAny(array $permissions): bool
    {
        return Auth::check() && Auth::users()->hasAnyPermission($permissions);
    }

    public static function canAll(array $permissions): bool
    {
        return Auth::check() && Auth::users()->hasAllPermissions($permissions);
    }

    public static function hasRole(string|array $roles): bool
    {
        return Auth::check() && Auth::users()->hasRole($roles);
    }

    public static function isSuperAdmin(): bool
    {
        return Auth::check() && Auth::users()->isSuperAdmin();
    }

    public static function isTenantAdmin(): bool
    {
        return Auth::check() && Auth::users()->isTenantAdmin();
    }
}
