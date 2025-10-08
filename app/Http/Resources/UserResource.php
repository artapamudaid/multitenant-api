<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'is_tenant_owner' => $this->is_tenant_owner,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'tenant' => new TenantResource($this->whenLoaded('tenant')),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
