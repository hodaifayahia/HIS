<?php

namespace App\Http\Resources\Admission;

use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionProcedureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'admission_id' => $this->admission_id,
            'prestation_id' => $this->prestation_id,
            'prestation' => $this->whenLoaded('prestation', function () {
                return [
                    'id' => $this->prestation->id,
                    'name' => $this->prestation->name,
                    'code' => $this->prestation->internal_code,
                    'price' => (float) ($this->prestation->price ?? 0),
                ];
            }),
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => ucfirst(str_replace('_', ' ', $this->status)),
            'is_medication_conversion' => (bool) $this->is_medication_conversion,

            // Staff
            'performed_by' => $this->performed_by,
            'performer' => $this->whenLoaded('performedBy', function () {
                return [
                    'id' => $this->performedBy->id,
                    'name' => $this->performedBy->name,
                ];
            }),

            // Timing
            'scheduled_at' => $this->scheduled_at?->format('Y-m-d H:i:s'),
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),

            // System fields
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
