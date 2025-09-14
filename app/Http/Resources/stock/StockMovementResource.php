<?php

namespace App\Http\Resources\stock;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'movement_number' => $this->movement_number,
            'providing_service' => $this->whenLoaded('providingService'),
            'requesting_service' => $this->whenLoaded('requestingService'),
            'requesting_user' => $this->whenLoaded('requestingUser'),
            'status' => $this->status,
            'created_at' => $this->created_at,
            'items' => $this->whenLoaded('items'),
        ];
    }
}
