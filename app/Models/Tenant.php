<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'subdomain',
        'domain',
        'package_id',
        'package_started_at',
        'package_expires_at',
        'subscription_status',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'package_id',
        'package_started_at' => 'datetime',
        'package_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'subscription_status',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (! $model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function owner(): HasOne
    {
        return $this->hasOne(User::class)
            ->whereHas('roles', function ($query) {
                $query->where('slug', 'tenant-admin');
            });
    }

    public static function generateUniqueSubdomain(string $name): string
    {
        $cleanedName = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($name));
        $subdomain = $cleanedName;
        $counter = 1;

        while (self::where('subdomain', $subdomain)->exists()) {
            $subdomain = $cleanedName . '' . $counter;
            $counter++;
        }

        return $subdomain;
    }

    public function getUrlAttribute(): string
    {
        $domain = $this->domain ?: $this->subdomain . '.' . config('app.domain');
        $protocol = config('app.env') === 'production' ? 'https' : 'https';
        return $protocol . '://' . $domain;
    }

    public function getApiUrlAttribute(): string
    {
        return $this->url . '/api';
    }

     public function isSubscriptionActive(): bool
    {
        return $this->subscription_status !== 'expired' &&
               $this->package_expires_at &&
               $this->package_expires_at->isFuture();
    }

    public function canAddUser(): bool
    {
        if (!$this->package) {
            return false;
        }

        return $this->users()->count() < $this->package->max_users;
    }

    public function hasFeature(string $feature): bool
    {
        return $this->package && $this->package->hasFeature($feature);
    }

    public function subscriptionTransactions(): HasMany
    {
        return $this->hasMany(SubscriptionTransaction::class);
    }

    public function latestTransaction()
    {
        return $this->hasOne(SubscriptionTransaction::class)->latestOfMany();
    }

    public function getSubscriptionHistoryAttribute(): Collection
    {
        return $this->subscriptionTransactions()
            ->with(['package', 'previousPackage', 'user'])
            ->latest()
            ->get();
    }
}
