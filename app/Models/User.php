<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'avatar',
        'email_verified_at',
        'is_active'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withTimestamps();
    }

    public function generateToken(string $name = 'api-token'): string
    {
        // Hapus token lama
        $this->tokens()->delete();

        // Buat token baru dengan tenant info
        $token = $this->createToken($name, ['*']);

        return $token->plainTextToken;
    }

    // Check if user has role
    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return $this->roles()
            ->whereIn('slug', $roles)
            ->exists();
    }

    // Check if user has permission
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionSlug) {
                $query->where('slug', $permissionSlug);
            })
            ->exists();
    }

    // Check if user has any of the permissions
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissions) {
                $query->whereIn('slug', $permissions);
            })
            ->exists();
    }

    // Check if user has all permissions
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    // Get all user permissions
    public function getAllPermissions(): array
    {
        return $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id')
            ->pluck('slug')
            ->toArray();
    }

    // Assign role to user
    public function assignRole(string|array $roles): void
    {
        $roles = is_array($roles) ? $roles : [$roles];

        $roleModels = Role::whereIn('slug', $roles)->get();

        $this->roles()->syncWithoutDetaching($roleModels);
    }

    // Remove role from user
    public function removeRole(string|array $roles): void
    {
        $roles = is_array($roles) ? $roles : [$roles];

        $roleModels = Role::whereIn('slug', $roles)->get();

        $this->roles()->detach($roleModels);
    }

    // Sync roles (replace all roles)
    public function syncRoles(array $roles): void
    {
        $roleModels = Role::whereIn('slug', $roles)->get();

        $this->roles()->sync($roleModels);
    }

    // Check if user is Landlord
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    // Check if user is tenant admin
    public function isTenantAdmin(): bool
    {
        return $this->hasRole('tenant-admin');
    }
}
