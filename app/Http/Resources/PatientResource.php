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
            'Firstname' => $this->Firstname,
            'Lastname' => $this->Lastname,
            'full_name' => $this->fullname,
            'fullname' => $this->fullname,
            'first_name' => $this->Firstname,
            'last_name' => $this->Lastname,
            'Parent' => $this->Parent,
            'phone' => $this->phone,
            'fax_number' => $this->fax_number,
            'email' => $this->email,
            'address' => $this->address,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'Idnum' => $this->Idnum,
            'identity_document_type' => $this->identity_document_type,
            'identity_issued_on' => $this->identity_issued_on,
            'identity_issued_by' => $this->identity_issued_by,
            'passport_number' => $this->passport_number,
            'professional_badge_number' => $this->professional_badge_number,
            'foreigner_card_number' => $this->foreigner_card_number,
            'nss' => $this->nss,
            'dateOfBirth' => $this->dateOfBirth,
            'birth_place' => $this->birth_place,
            'is_birth_place_presumed' => $this->is_birth_place_presumed,
            'additional_ids' => $this->additional_ids,
            'gender' => $this->gender, // This will now use the accessor to return "Male" or "Female"
            'gender_display' => $this->gender, // Explicit display format
            'age' => $this->calculateAge(),
            'height' => $this->height,
            'weight' => $this->weight,
            'blood_group' => $this->blood_group,
            'marital_status' => $this->marital_status,
            'mother_firstname' => $this->mother_firstname,
            'mother_lastname' => $this->mother_lastname,
            'balance' => $this->balance,
            'is_faithful' => $this->is_faithful,
            'firstname_ar' => $this->firstname_ar,
            'lastname_ar' => $this->lastname_ar,
            'other_clinical_info' => $this->other_clinical_info,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
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