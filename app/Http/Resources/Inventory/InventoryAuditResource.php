<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryAuditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'is_global' => $this->is_global,
            'is_pharmacy_wide' => $this->is_pharmacy_wide,
            'service_id' => $this->service_id,
            'stockage_id' => $this->stockage_id,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'service' => $this->when($this->service_id, function () {
                // Load service relationship if not loaded
                if ($this->relationLoaded('service') && $this->service) {
                    return [
                        'id' => $this->service->id,
                        'name' => $this->service->name,
                    ];
                }
                return null;
            }),
            'stockage' => $this->when($this->stockage_id, function () {
                // Determine which stockage relationship to load based on is_pharmacy_wide
                if ($this->is_pharmacy_wide) {
                    $stockage = $this->relationLoaded('pharmacyStockage') ? $this->pharmacyStockage : null;
                } else {
                    $stockage = $this->relationLoaded('generalStockage') ? $this->generalStockage : null;
                }
                
                if ($stockage) {
                    return [
                        'id' => $stockage->id,
                        'name' => $stockage->name,
                        'location' => $stockage->location ?? 'N/A',
                        'type' => $this->is_pharmacy_wide ? 'pharmacy' : 'general',
                    ];
                }
                return null;
            }),
            'participants' => InventoryAuditParticipantResource::collection($this->whenLoaded('participants')),
            'participants_count' => $this->when(
                $this->relationLoaded('participants'),
                function () {
                    return $this->participants->count();
                }
            ),
            'scheduled_at' => $this->scheduled_at,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
