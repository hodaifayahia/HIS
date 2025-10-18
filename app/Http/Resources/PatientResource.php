<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PatientResource extends JsonResource
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
            'first_name' => $this->Firstname,
            'last_name' => $this->Lastname,
            'full_name' => $this->fullname, 
            'gender'=>$this->gender,
            'Parent' => $this->Parent,
            'phone' => $this->phone,
            'dateOfBirth' => $this->dateOfBirth,
            'age' => $this->calculateAge(),
            'weight' => $this->weight,
            'Idnum' => $this->Idnum,
        ];
    }
    
    /**
     * Calculate age from date of birth
     *
     * @return int|null
     */
    protected function calculateAge(): ?int
    {
        if (empty($this->dateOfBirth)) {
            return null;
        }
        
        return Carbon::parse($this->dateOfBirth)->age;
    }
}