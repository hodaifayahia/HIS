<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // 'this' refers to an instance of App\Models\Medication
        return [
            'id' => $this->id, // The ID of the base medication
            'designation' => $this->designation ?? '',
            'nom_commercial' => $this->nom_commercial ?? '',
            'forme' => $this->forme ?? '', // General pharmaceutical form of the medication
            'type_medicament' => $this->type_medicament ?? '',
            'boite_de' => $this->boite_de ?? '',
            'code_pch' => $this->code_pch ?? '',
            
        ];
    }
}
