<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price_monthly' => $this->price_monthly,
            'formatted_price' => $this->formatted_price,
            'features' => $this->features,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
