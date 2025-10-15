<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Landlord Permissions
            [
                'name' => 'View All Tenants',
                'slug' => 'tenants.view',
                'module' => 'tenants',
                'scope' => 'super_admin',
            ],
            [
                'name' => 'Create Tenant',
                'slug' => 'tenants.create',
                'module' => 'tenants',
                'scope' => 'super_admin',
            ],
            [
                'name' => 'Edit Tenant',
                'slug' => 'tenants.edit',
                'module' => 'tenants',
                'scope' => 'super_admin',
            ],
            [
                'name' => 'Delete Tenant',
                'slug' => 'tenants.delete',
                'module' => 'tenants',
                'scope' => 'super_admin',
            ],
            [
                'name' => 'Manage Packages',
                'slug' => 'packages.manage',
                'module' => 'packages',
                'scope' => 'super_admin',
            ],

            // Tenant Permissions - Users
            [
                'name' => 'View Users',
                'slug' => 'users.view',
                'module' => 'users',
                'scope' => 'tenant',
            ],
            [
                'name' => 'Create User',
                'slug' => 'users.create',
                'module' => 'users',
                'scope' => 'tenant',
            ],
            [
                'name' => 'Edit User',
                'slug' => 'users.edit',
                'module' => 'users',
                'scope' => 'tenant',
            ],
            [
                'name' => 'Delete User',
                'slug' => 'users.delete',
                'module' => 'users',
                'scope' => 'tenant',
            ],

            // Tenant Permissions - Roles
            [
                'name' => 'View Roles',
                'slug' => 'roles.view',
                'module' => 'roles',
                'scope' => 'tenant',
            ],
            [
                'name' => 'Create Role',
                'slug' => 'roles.create',
                'module' => 'roles',
                'scope' => 'tenant',
            ],
            [
                'name' => 'Edit Role',
                'slug' => 'roles.edit',
                'module' => 'roles',
                'scope' => 'tenant',
            ],
            [
                'name' => 'Delete Role',
                'slug' => 'roles.delete',
                'module' => 'roles',
                'scope' => 'tenant',
            ],

            // Tenant Permissions - Dashboard
            [
                'name' => 'View Dashboard',
                'slug' => 'dashboard.view',
                'module' => 'dashboard',
                'scope' => 'tenant',
            ],
            [
                'name' => 'View Analytics',
                'slug' => 'analytics.view',
                'module' => 'dashboard',
                'scope' => 'tenant',
            ],

            // Tenant Permissions - Reports
            [
                'name' => 'View Reports',
                'slug' => 'reports.view',
                'module' => 'reports',
                'scope' => 'tenant',
            ],
            [
                'name' => 'Create Report',
                'slug' => 'reports.create',
                'module' => 'reports',
                'scope' => 'tenant',
            ],
            [
                'name' => 'Export Report',
                'slug' => 'reports.export',
                'module' => 'reports',
                'scope' => 'tenant',
            ],

            // Tenant Permissions - Settings
            [
                'name' => 'View Settings',
                'slug' => 'settings.view',
                'module' => 'settings',
                'scope' => 'tenant',
            ],
            [
                'name' => 'Edit Settings',
                'slug' => 'settings.edit',
                'module' => 'settings',
                'scope' => 'tenant',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
