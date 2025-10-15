<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Paket Bawah',
                'slug' => 'bawah',
                'description' => 'Paket untuk usaha kecil dan startup',
                'price_monthly' => 99000,
                'sort_order' => 1,
                'features' => [
                    'Dashboard Analytics',
                    'User Management (max 5 users)',
                    'Basic Reports',
                    'Email Support',
                    '1GB Storage',
                ],
            ],
            [
                'name' => 'Paket Menengah',
                'slug' => 'menengah',
                'description' => 'Paket untuk bisnis yang berkembang',
                'price_monthly' => 299000,
                'sort_order' => 2,
                'features' => [
                    'Semua fitur Paket Bawah',
                    'User Management (max 25 users)',
                    'Advanced Reports & Analytics',
                    'Custom Branding',
                    'API Access',
                    'Priority Email Support',
                    '5GB Storage',
                    'Export Data (Excel, PDF)',
                ],
            ],
            [
                'name' => 'Paket Atas',
                'slug' => 'atas',
                'description' => 'Paket untuk enterprise dengan fitur lengkap',
                'price_monthly' => 999000,
                'sort_order' => 3,
                'features' => [
                    'Semua fitur Paket Menengah',
                    'Unlimited Users',
                    'Advanced Analytics & BI',
                    'White Label',
                    'Custom Domain',
                    'Dedicated Account Manager',
                    'Phone & WhatsApp Support',
                    '50GB Storage',
                    'API Rate Limit Tinggi',
                    'Webhook Integration',
                    'Multi-Currency Support',
                    'Audit Logs',
                ],
            ],
        ];

        foreach ($packages as $package) {
            Package::create($package);
        }
    }
}
