<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // Landlord Menus
        $this->createSuperAdminMenus();

        // Tenant Menus
        $this->createTenantMenus();
    }

    protected function createSuperAdminMenus(): void
    {
        $menus = [
            [
                'title' => 'Dashboard',
                'icon' => 'LayoutDashboard',
                'route' => 'landlord.dashboard',
                'scope' => 'super_admin',
                'permission' => null,
                'order' => 1,
            ],
            [
                'title' => 'Tenants',
                'icon' => 'Building2',
                'route' => 'landlord.tenants',
                'scope' => 'super_admin',
                'permission' => 'tenants.view',
                'order' => 2,
            ],
            [
                'title' => 'Packages',
                'icon' => 'Package',
                'route' => 'landlord.packages',
                'scope' => 'super_admin',
                'permission' => 'packages.manage',
                'order' => 3,
            ],
            [
                'title' => 'Users',
                'icon' => 'Users',
                'route' => null,
                'scope' => 'super_admin',
                'permission' => null,
                'order' => 4,
                'children' => [
                    [
                        'title' => 'All Users',
                        'icon' => 'UserCircle',
                        'route' => 'landlord.users.index',
                        'permission' => 'tenants.view',
                        'order' => 1,
                    ],
                    [
                        'title' => 'Super Admin',
                        'icon' => 'Shield',
                        'route' => 'landlord.users.admins',
                        'permission' => 'tenants.view',
                        'order' => 2,
                    ],
                ],
            ],
            [
                'title' => 'Settings',
                'icon' => 'Settings',
                'route' => 'landlord.settings',
                'scope' => 'super_admin',
                'permission' => null,
                'order' => 10,
            ],
        ];

        $this->insertMenus($menus, 'super_admin');
    }

    protected function createTenantMenus(): void
    {
        $menus = [
            [
                'title' => 'Dashboard',
                'icon' => 'LayoutDashboard',
                'route' => 'tenant.dashboard',
                'scope' => 'tenant',
                'permission' => 'dashboard.view',
                'order' => 1,
            ],
            [
                'title' => 'Analytics',
                'icon' => 'TrendingUp',
                'route' => 'tenant.analytics',
                'scope' => 'tenant',
                'permission' => 'analytics.view',
                'package_feature' => 'Advanced Reports & Analytics',
                'order' => 2,
            ],
            [
                'title' => 'Users',
                'icon' => 'Users',
                'route' => null,
                'scope' => 'tenant',
                'permission' => 'users.view',
                'order' => 3,
                'children' => [
                    [
                        'title' => 'All Users',
                        'icon' => 'UserCircle',
                        'route' => 'tenant.users.index',
                        'permission' => 'users.view',
                        'order' => 1,
                    ],
                    [
                        'title' => 'Add User',
                        'icon' => 'UserPlus',
                        'route' => 'tenant.users.create',
                        'permission' => 'users.create',
                        'order' => 2,
                    ],
                ],
            ],
            [
                'title' => 'Roles & Permissions',
                'icon' => 'Shield',
                'route' => null,
                'scope' => 'tenant',
                'permission' => 'roles.view',
                'order' => 4,
                'children' => [
                    [
                        'title' => 'Roles',
                        'icon' => 'ShieldCheck',
                        'route' => 'tenant.roles.index',
                        'permission' => 'roles.view',
                        'order' => 1,
                    ],
                    [
                        'title' => 'Permissions',
                        'icon' => 'Lock',
                        'route' => 'tenant.permissions.index',
                        'permission' => 'roles.view',
                        'order' => 2,
                    ],
                ],
            ],
            [
                'title' => 'Reports',
                'icon' => 'FileText',
                'route' => null,
                'scope' => 'tenant',
                'permission' => 'reports.view',
                'order' => 5,
                'children' => [
                    [
                        'title' => 'View Reports',
                        'icon' => 'Eye',
                        'route' => 'tenant.reports.index',
                        'permission' => 'reports.view',
                        'order' => 1,
                    ],
                    [
                        'title' => 'Create Report',
                        'icon' => 'FilePlus',
                        'route' => 'tenant.reports.create',
                        'permission' => 'reports.create',
                        'order' => 2,
                    ],
                    [
                        'title' => 'Export Data',
                        'icon' => 'Download',
                        'route' => 'tenant.reports.export',
                        'permission' => 'reports.export',
                        'package_feature' => 'Export Data (Excel, PDF)',
                        'order' => 3,
                    ],
                ],
            ],
            [
                'title' => 'Settings',
                'icon' => 'Settings',
                'route' => null,
                'scope' => 'tenant',
                'permission' => 'settings.view',
                'order' => 8,
                'children' => [
                    [
                        'title' => 'General',
                        'icon' => 'Sliders',
                        'route' => 'tenant.settings.general',
                        'permission' => 'settings.view',
                        'order' => 1,
                    ],
                    [
                        'title' => 'Subscription',
                        'icon' => 'CreditCard',
                        'route' => 'tenant.subscription',
                        'permission' => 'settings.view',
                        'order' => 2,
                    ],
                    [
                        'title' => 'Branding',
                        'icon' => 'Palette',
                        'route' => 'tenant.settings.branding',
                        'permission' => 'settings.edit',
                        'package_feature' => 'Custom Branding',
                        'order' => 3,
                    ],
                ],
            ],
        ];

        $this->insertMenus($menus, 'tenant');
    }

    protected function insertMenus(array $menus, string $scope, ?int $parentId = null): void
    {
        foreach ($menus as $menuData) {
            $children = $menuData['children'] ?? [];
            unset($menuData['children']);

            $menu = Menu::create([
                'title' => $menuData['title'],
                'icon' => $menuData['icon'] ?? null,
                'route' => $menuData['route'] ?? null,
                'url' => $menuData['url'] ?? null,
                'parent_id' => $parentId,
                'scope' => $scope,
                'permission' => $menuData['permission'] ?? null,
                'package_feature' => $menuData['package_feature'] ?? null,
                'order' => $menuData['order'] ?? 0,
                'is_active' => true,
                'meta' => $menuData['meta'] ?? null,
            ]);

            // Insert children recursively
            if (!empty($children)) {
                $this->insertMenus($children, $scope, $menu->id);
            }
        }
    }
}
