<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FournisseurResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'tax_id' => $this->tax_id,
            'website' => $this->website,
            'notes' => $this->notes,
            'is_active' => (bool) $this->is_active,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
            'contacts' => FournisseurContactResource::collection($this->whenLoaded('contacts')),
        ];
    }
}
