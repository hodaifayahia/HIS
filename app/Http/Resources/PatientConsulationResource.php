<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientConsulationResource extends JsonResource
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
            'name' => $this->name ??'no name',
            'doctor_id' => $this->doctor_id ?? 'no doctor',
            'patient_id' => $this->patient_id ?? 'no patient',
            'template_id' => $this->template_id ?? 'no template',
            'appointment_id' => $this->appointment_id,
            'date' => $this->created_at,
            'updated_at' => $this->updated_at,
            'doctor' => new DoctorResource($this->whenLoaded('doctor')),
        ];
    }
}
