<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsulationResource extends JsonResource
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
            'patient_id' => $this->patient->id,
            'patient_name' => $this->patient->Firstname . ' ' . $this->patient->Lastname,
            'doctor_id' => $this->doctor->id,
            'doctor_name' => $this->doctor->user->name,
            'appointment_id' => $this->appointment_id,
            'status' => $this->status ?? 'pending',
            'consultation_workspace_id' => $this->consultation_workspace_id ?? null,
            'consultation_end_at' => $this->consultation_end_at ?? 'Null',
            'codebash' => $this->codebash ?? 'Null',
            'date' => $this->created_at->format('Y-m-d H:i'),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i'),
            // Flattened placeholder attributes
            'placeholder_attributes' => $this->whenLoaded('placeholderAttributes', function() {
                return $this->placeholderAttributes->map(function($item) {
                    return [
                        'placeholder_name' => $item->placeholder->name,
                        'attribute_name' => $item->attribute->name,
                        'attribute_value' => $item->attribute_value,
                        'input_type' => $item->attribute->input_type
                    ];
                });
            }),
        ];
    }
}
