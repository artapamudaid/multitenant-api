<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'subdomain' => $this->subdomain,
            'domain' => $this->domain,
            'url' => $this->url,
            'api_url' => $this->api_url,
            'is_active' => $this->is_active,
            'settings' => $this->settings,
            'users_count' => $this->when(
                $this->relationLoaded('users'),
                fn() => $this->users->count()
            ),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
