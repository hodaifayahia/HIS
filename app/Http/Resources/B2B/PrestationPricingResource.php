<?php

namespace App\Http\Resources\B2B;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrestationPricingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        // Access prestation name directly from the loaded relationship.
        // The 'prestation' relationship is eager loaded by the service.
        // Use optional() helper for safer access if prestation might be null
        $prestationName = optional($this->prestation)->name;

        // Initialize variables for nested relationship data
        $organismeAbrv = null;
        $serviceId = null;

        // Safely access nested relationships using optional() and checking loaded status
        // This prevents "Call to a member function on null" errors if a relationship is not loaded
        // or if an intermediate model in the chain is null.
        if ($this->relationLoaded('annex')) {
            $annex = $this->annex; // Get the loaded annex model

            // Get service_id from the loaded annex
            $serviceId = optional($annex)->service_id;

            // Check if the 'convention' relationship on the annex is loaded
            if (optional($annex)->relationLoaded('convention')) {
                $convention = $annex->convention; // Get the loaded convention model

                // Check if the 'organisme' relationship on the convention is loaded
                if (optional($convention)->relationLoaded('organisme')) {
                    $organismeAbrv = optional($convention->organisme)->abrv;
                }
            }
        }

        // Ensure organismeAbrv is always a string for formatted_id generation AND for direct output.
        // If it's null from the database, it will default to 'N/A'.
        $displayOrganismeAbrv = $organismeAbrv ;

        // Generate formatted_id using the retrieved values
        $formattedId = null;
        // Only generate formatted_id if serviceId and prestation_id are available
        
            $formattedId = $this->prestation->service->id . '_' . $this->prestation_id;
        

        return [
            'id'=> $this->id,
            'prestation_id' => $this->prestation_id,
            'prestation_name' => $prestationName,
            'subname' => $this->subname,
            'service' => $this->prestation->service->name,

            'organisme_abrv' => $displayOrganismeAbrv, // Now uses the guaranteed non-null value
            'formatted_id' => $formattedId,
            'pricing' => [
                'prix' =>  $this->prix,
                'company_price' =>  $this->company_price,
                'patient_price' =>  $this->patient_price,
            ],
            'details' => [
                'max_price_exceeded' => (bool) $this->max_price_exceeded, // Ensure boolean cast
                'original_company_share' =>  $this->original_company_share,
                'original_patient_share' =>  $this->original_patient_share,
            ]
        ];
    }
}
