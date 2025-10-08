<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TenantService
{
    public function __construct(
        protected AuthentikService $authentikService
    ) {}

    public function createTenant(
        string $tenantName,
        string $ownerName,
        string $ownerEmail,
        string $password
    ): array {
        return DB::transaction(function () use ($tenantName, $ownerName, $ownerEmail, $password) {
            // 1. Generate subdomain unik
            $subdomain = Tenant::generateUniqueSubdomain($tenantName);

            // 1. Tentukan created_by
            $createdBy = Auth::check()
                ? Auth::user()->email // Jika admin sedang login
                : $ownerEmail;          // Jika dari proses registrasi (belum login)

            // 2. Buat tenant
            $tenant = Tenant::create([
                'name' => $tenantName,
                'subdomain' => $subdomain,
                'domain' => null,
                'is_active' => true,
                'settings' => [
                    'created_by' => $createdBy,
                    'timezone' => config('app.timezone'),
                ],
            ]);


            // 4. Buat user di Laravel sebagai owner
            $user = User::create([
                'name' => $ownerName,
                'email' => $ownerEmail,
                'password' => Hash::make($password),
                'tenant_id' => $tenant->id,
                'authentik_id' => null,
                'is_tenant_owner' => true,
                'email_verified_at' => now(),
            ]);

            // 5. Generate API token
            $token = $user->createToken('api-token')->plainTextToken;

            return [
                'tenant' => $tenant,
                'user' => $user,
                'token' => $token,
            ];
        });
    }

    public function getTenantBySubdomain(string $subdomain): ?Tenant
    {
        return Tenant::where('subdomain', $subdomain)
            ->where('is_active', true)
            ->first();
    }

    public function getTenantFromRequest(): ?Tenant
    {
        $host = request()->getHost();
        $appDomain = config('app.domain');

        // Jika main domain, return null
        if ($host === $appDomain || $host === 'www.' . $appDomain) {
            return null;
        }

        // Extract subdomain
        $subdomain = str_replace('.' . $appDomain, '', $host);

        return $this->getTenantBySubdomain($subdomain);
    }
}
