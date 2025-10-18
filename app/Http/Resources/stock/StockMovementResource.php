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
            'approving_user' => $this->whenLoaded('approvingUser'),
            'transfer_initiated_by_user' => $this->whenLoaded('transferInitiatedByUser'),
            'delivery_confirmed_by_user' => $this->whenLoaded('deliveryConfirmedByUser'),
            'status' => $this->status,
            'request_reason' => $this->request_reason,
            'approval_notes' => $this->approval_notes,
            'delivery_status' => $this->delivery_status,
            'delivery_notes' => $this->delivery_notes,
            'missing_quantity' => $this->missing_quantity,
            'requested_at' => $this->requested_at,
            'approved_at' => $this->approved_at,
            'transfer_initiated_at' => $this->transfer_initiated_at,
            'delivery_confirmed_at' => $this->delivery_confirmed_at,
            'expected_delivery_date' => $this->expected_delivery_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'items' => $this->whenLoaded('items'),
        ];
    }
}
