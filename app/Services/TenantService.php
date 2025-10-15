<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Package;
use App\Models\Role;
use App\Models\SubscriptionTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TenantService
{
    public function __construct() {

    }

    public function createTenant(
        string $tenantName,
        string $ownerName,
        string $ownerEmail,
        string $password,
        ?string $packageSlug = 'atas'
    ): array {
        return DB::transaction(function () use ($tenantName, $ownerName, $ownerEmail, $password, $packageSlug) {
             // 1. Get package
            $package = Package::where('slug', $packageSlug)
                ->where('is_active', true)
                ->firstOrFail();

            // 2. Generate subdomain unik
            $subdomain = Tenant::generateUniqueSubdomain($tenantName);


            // 3. Buat tenant
            $createdBy = Auth::check()
                ? Auth::user()->email // Jika admin sedang login
                : $ownerEmail;          // Jika dari proses registrasi (belum login)

            $tenant = Tenant::create([
                'name' => $tenantName,
                'subdomain' => $subdomain,
                'domain' => null,
                'package_id' => $package->id,
                'package_started_at' => now(),
                'package_expires_at' => now()->addMonth(), // Trial 1 bulan
                'subscription_status' => 'trial',
                'is_active' => true,
                'settings' => [
                    'created_by' => $createdBy,
                    'timezone' => config('app.timezone'),
                ],
            ]);


           // 4. Create tenant-specific roles (copy from default)
            $this->createTenantRoles($tenant);

            // 5. Buat user sebagai owner
            $user = User::create([
                'name' => $ownerName,
                'email' => $ownerEmail,
                'password' => Hash::make($password),
                'tenant_id' => $tenant->id,
                'email_verified_at' => now(),
            ]);

            // 6. Assign tenant admin role
            $tenantAdminRole = Role::where('slug', 'tenant-admin')
                ->where('tenant_id', $tenant->id)
                ->first();

            $user->assignRole($tenantAdminRole->slug);

             // 7. Create subscription transaction (Registration)
            SubscriptionTransaction::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'package_id' => $package->id,
                'previous_package_id' => null,
                'type' => 'registration',
                'status' => 'completed',
                'amount' => 0, // Trial gratis
                'currency' => 'IDR',
                'duration_months' => 1,
                'started_at' => now(),
                'expires_at' => now()->addMonth(),
                'completed_at' => now(),
                'payment_method' => 'trial',
                'invoice_number' => SubscriptionTransaction::generateInvoiceNumber(),
                'metadata' => [
                    'is_trial' => true,
                    'package_name' => $package->name,
                    'tenant_name' => $tenant->name,
                ],
                'notes' => 'Trial period - 1 month free',
            ]);

            // 8. Generate API token
            $token = $user->createToken('api-token')->plainTextToken;

            return [
                'tenant' => $tenant->load('package'),
                'user' => $user->load('roles'),
                'token' => $token,
            ];
        });
    }

    protected function createTenantRoles(Tenant $tenant): void
    {
        // Get default tenant roles
        $defaultRoles = Role::where('scope', 'tenant')
            ->whereNull('tenant_id')
            ->with('permissions')
            ->get();

        foreach ($defaultRoles as $defaultRole) {
            $newRole = Role::create([
                'name' => $defaultRole->name,
                'slug' => $defaultRole->slug,
                'description' => $defaultRole->description,
                'scope' => 'tenant',
                'tenant_id' => $tenant->id,
                'is_system' => $defaultRole->is_system,
            ]);

            // Copy permissions
            $newRole->permissions()->attach($defaultRole->permissions);
        }
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

    public function upgradePackage(Tenant $tenant, string $packageSlug, ?User $user = null): array
    {
        $newPackage = Package::where('slug', $packageSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $oldPackage = $tenant->package;
        $user = $user ?? Auth::user();

        // Calculate amount (bisa custom logic untuk pro-rated, dsb)
        $amount = $newPackage->price_monthly;

        // Determine type (upgrade or downgrade)
        $type = $newPackage->price_monthly > $oldPackage->price_monthly
            ? 'upgrade'
            : 'downgrade';

        return DB::transaction(function () use ($tenant, $newPackage, $oldPackage, $user, $amount, $type) {
            // Update tenant
            $expiresAt = $tenant->package_expires_at && $tenant->package_expires_at->isFuture()
                ? $tenant->package_expires_at
                : now()->addMonth();

            $tenant->update([
                'package_id' => $newPackage->id,
                'package_started_at' => now(),
                'package_expires_at' => $expiresAt,
                'subscription_status' => 'active',
            ]);

            // Create transaction
            $transaction = SubscriptionTransaction::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user?->id,
                'package_id' => $newPackage->id,
                'previous_package_id' => $oldPackage->id,
                'type' => $type,
                'status' => 'pending',
                'amount' => $amount,
                'currency' => 'IDR',
                'duration_months' => 1,
                'started_at' => now(),
                'expires_at' => $expiresAt,
                'payment_method' => null, // Will be updated after payment
                'invoice_number' => SubscriptionTransaction::generateInvoiceNumber(),
                'metadata' => [
                    'old_package' => $oldPackage->name,
                    'new_package' => $newPackage->name,
                    'old_price' => $oldPackage->price_monthly,
                    'new_price' => $newPackage->price_monthly,
                ],
                'notes' => "Package {$type} from {$oldPackage->name} to {$newPackage->name}",
            ]);

            // Simulate payment success (in real app, wait for payment gateway callback)
            $transaction->markAsCompleted();

            return [
                'tenant' => $tenant->fresh()->load('package'),
                'transaction' => $transaction->fresh(),
            ];
        });
    }

    public function renewSubscription(Tenant $tenant, int $months = 1, ?User $user = null): array
    {
        $package = $tenant->package;
        $user = $user ?? Auth::user();

        // Calculate amount
        $amount = $package->price_monthly * $months;

        // Calculate new expiry date
        $expiresAt = $tenant->package_expires_at && $tenant->package_expires_at->isFuture()
            ? $tenant->package_expires_at->addMonths($months)
            : now()->addMonths($months);

        return DB::transaction(function () use ($tenant, $package, $user, $amount, $months, $expiresAt) {
            // Update tenant
            $tenant->update([
                'package_expires_at' => $expiresAt,
                'subscription_status' => 'active',
            ]);

            // Create transaction
            $transaction = SubscriptionTransaction::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user?->id,
                'package_id' => $package->id,
                'previous_package_id' => null,
                'type' => 'renewal',
                'status' => 'pending',
                'amount' => $amount,
                'currency' => 'IDR',
                'duration_months' => $months,
                'started_at' => now(),
                'expires_at' => $expiresAt,
                'payment_method' => null,
                'invoice_number' => SubscriptionTransaction::generateInvoiceNumber(),
                'metadata' => [
                    'package_name' => $package->name,
                    'months' => $months,
                ],
                'notes' => "Subscription renewal for {$months} month(s)",
            ]);

            // Simulate payment success
            $transaction->markAsCompleted();

            return [
                'tenant' => $tenant->fresh()->load('package'),
                'transaction' => $transaction->fresh(),
            ];
        });
    }
}
