<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@mail.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Assign Landlord role
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $superAdmin->roles()->attach($superAdminRole);
    }
}
