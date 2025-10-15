<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'icon',
        'route',
        'url',
        'parent_id',
        'scope',
        'permission',
        'package_feature',
        'order',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')
            ->orderBy('order');
    }

    /**
     * Check if user can access this menu
     */
    public function canAccess(?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return false;
        }

        // Landlord can access all Landlord menus
        if ($this->scope === 'super_admin' && $user->isSuperAdmin()) {
            return true;
        }

        // Step 1: Check subscription status
        if (!$user->tenant->isSubscriptionActive()) {
            // Subscription expired, only show subscription menu
            if ($this->route !== 'tenant.subscription') {
                return false; // Hide all other menus
            }
        }

        // Step 2: Check permission
        if ($this->permission && !$user->hasPermission($this->permission)) {
            return false; // User doesn't have required permission
        }


        // Step 3: Check package feature (AUTO-FILTER)
        if ($this->package_feature) {
            // Get tenant's package features
            $packageFeatures = $user->tenant->package->features; // Array from DB

            // Example: ["Dashboard Analytics", "Export Data (Excel, PDF)"]

            // Check if menu's required feature exists in package
            $hasFeature = false;
            foreach ($packageFeatures as $feature) {
                // Case-insensitive partial match
                if (stripos($feature, $this->package_feature) !== false) {
                    $hasFeature = true;
                    break;
                }
            }

            if (!$hasFeature) {
                return false; // Package doesn't have this feature
            }
        }

        return true;
    }

    /**
     * Get menu tree with access check
     */
    public static function getMenuTree(string $scope, ?User $user = null): array
    {
        $user = $user ?? Auth::user();

        $menus = self::where('scope', $scope)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with('children')
            ->orderBy('order')
            ->get();

        return $menus->filter(function ($menu) use ($user) {
            return $menu->canAccess($user);
        })->map(function ($menu) use ($user) {
            return $menu->toMenuArray($user);
        })->values()->toArray();
    }

    /**
     * Convert to menu array with children
     */
    public function toMenuArray(?User $user = null): array
    {
        $user = $user ?? Auth::user();

        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'icon' => $this->icon,
            'route' => $this->route,
            'url' => $this->url,
            'meta' => $this->meta,
        ];

        // Filter children by access
        $children = $this->children
            ->filter(fn($child) => $child->canAccess($user))
            ->map(fn($child) => $child->toMenuArray($user))
            ->values()
            ->toArray();

        if (!empty($children)) {
            $data['children'] = $children;
        }

        return $data;
    }
}
