<?php
// app/Http/Resources/Coffre/CaisseResource.php

namespace App\Http\Resources\Coffre;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Coffre\Caisse */
class CaisseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'is_active' => $this->is_active,
            'status_text' => $this->status_text,
            'status_color' => $this->status_color,
            'service_id' => $this->service_id,
            
            // Relationships
            'service' => $this->whenLoaded('service', function () {
                return [
                    'id' => $this->service->id,
                    'name' => $this->service->name,
                ];
            }),
            
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
