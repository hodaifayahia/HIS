<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\PrescriptionMedication;
use App\Http\Resources\PrescriptionMedicationResource;


class PrescriptionTemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // 'this' refers to an instance of App\Models\Prescriptiontemplate
       return [
        'id' => $this->id,
        'name' => $this->name,
         
        
         'medications' => PrescriptionMedicationResource::collection($this->whenLoaded('medications'))    
        ];
    }
}
