<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'type' => $this->type,
            'type_display' => $this->type_display,
            'status' => $this->status,
            'status_badge' => $this->status_badge,
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'currency' => $this->currency,
            'duration_months' => $this->duration_months,
            'started_at' => $this->started_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'invoice_number' => $this->invoice_number,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'package' => new PackageResource($this->whenLoaded('package')),
            'previous_package' => new PackageResource($this->whenLoaded('previousPackage')),
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
