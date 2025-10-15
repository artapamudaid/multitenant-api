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
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'is_super_admin' => $this->isSuperAdmin(),
            'is_tenant_admin' => $this->isTenantAdmin(),
            'tenant' => new TenantResource($this->whenLoaded('tenant')),
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'permissions' => $this->when(
                $request->user() && $request->user()->id === $this->id,
                fn() => $this->getAllPermissions()
            ),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
