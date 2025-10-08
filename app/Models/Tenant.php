<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
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

    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->where('is_tenant_owner', true);
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
}
