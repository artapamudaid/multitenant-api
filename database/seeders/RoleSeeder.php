<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Landlord Role
        $superAdmin = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'description' => 'Full system access',
            'scope' => 'super_admin',
            'is_system' => true,
        ]);

        // Assign all Landlord permissions
        $superAdminPermissions = Permission::where('scope', 'super_admin')->get();
        $superAdmin->permissions()->attach($superAdminPermissions);

        // Tenant Default Roles
        $tenantRoles = [
            [
                'name' => 'Tenant Admin',
                'slug' => 'tenant-admin',
                'description' => 'Full access within tenant',
                'scope' => 'tenant',
                'is_system' => true,
                'permissions' => Permission::where('scope', 'tenant')->pluck('slug')->toArray(),
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Manage users and view reports',
                'scope' => 'tenant',
                'is_system' => false,
                'permissions' => [
                    'users.view', 'users.create', 'users.edit',
                    'dashboard.view', 'analytics.view',
                    'reports.view', 'reports.export',
                    'settings.view',
                ],
            ],
            [
                'name' => 'Staff',
                'slug' => 'staff',
                'description' => 'Basic access for daily operations',
                'scope' => 'tenant',
                'is_system' => false,
                'permissions' => [
                    'dashboard.view',
                    'reports.view',
                    'settings.view',
                ],
            ],
        ];

        foreach ($tenantRoles as $roleData) {
            $role = Role::create([
                'name' => $roleData['name'],
                'slug' => $roleData['slug'],
                'description' => $roleData['description'],
                'scope' => $roleData['scope'],
                'is_system' => $roleData['is_system'],
            ]);

            // Attach permissions
            $permissions = Permission::whereIn('slug', $roleData['permissions'])->get();
            $role->permissions()->attach($permissions);
        }
    }
}
