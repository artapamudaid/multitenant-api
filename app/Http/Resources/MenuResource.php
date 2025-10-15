<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class MenuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'icon' => $this->icon,
            'route' => $this->route,
            'url' => $this->url,
            'scope' => $this->scope,
            'permission' => $this->permission,
            'package_feature' => $this->package_feature,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'meta' => $this->meta,
            'children' => MenuResource::collection($this->whenLoaded('children')),
            'can_access' => $this->when(
                Auth::check(),
                fn() => $this->canAccess(Auth::user())
            ),
        ];
    }
}
