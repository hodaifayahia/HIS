<?php

namespace App\Http\Resources\Admission;

use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionTreatmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'admission_id' => $this->admission_id,
            'doctor_id' => $this->doctor_id,
            'doctor' => $this->whenLoaded('doctor', [
                'id' => $this->doctor->id ?? null,
                'name' => $this->doctor->user->name ?? 'N/A',
            ]),
            'prestation_id' => $this->prestation_id,
            'prestation' => $this->whenLoaded('prestation', [
                'id' => $this->prestation->id ?? null,
                'name' => $this->prestation->name ?? null,
                'code' => $this->prestation->internal_code ?? null,
            ]),
            'entered_at' => $this->entered_at?->format('Y-m-d H:i:s'),
            'exited_at' => $this->exited_at?->format('Y-m-d H:i:s'),
            'duration_minutes' => $this->duration,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', [
                'id' => $this->creator->id ?? null,
                'name' => $this->creator->name ?? null,
            ]),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
