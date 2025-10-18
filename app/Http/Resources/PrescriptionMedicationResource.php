<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionMedicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'medication_id' => $this->medication_id,
            'medication' => new MedicationResource($this->whenLoaded('medication')),
            
            // Basic medication information
            'form' => $this->form ?? '',
            'num_times' => $this->num_times ?? '1',
            'frequency' => $this->frequency ?? 'day',
            
            // Timing and duration fields
            'start_date' => $this->start_date ?? '',
            'end_date' => $this->end_date ?? '',
            'frequency_period' => $this->frequency_period ?? '',
            'period_intakes' => $this->period_intakes ?? '',
            'timing_preference' => $this->timing_preference ?? '',
            
            // Pill count fields
            'pills_matin' => $this->pills_matin ?? null,
            'pills_midi' => $this->pills_midi ?? null,
            'pills_apres_midi' => $this->pills_apres_midi ?? null,
            'pills_soir' => $this->pills_soir ?? null,
            
            // Additional information
            'description' => $this->description ?? '',
            
            // Relationship flag
            'isFromTemplate' => true,
            
            // If you need the prescription ID for any reason
            'prescription_id' => $this->prescription_id ?? null
        ];
    }
}