<?php

namespace App\Http\Resources\B2B;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConventionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'organisme_id' => $this->organisme_id,
            'contract_name' => $this->name, // Mapping 'name' to 'contract_name' for consistency with frontend
            'status' => $this->status,
            'annexes' => $this->whenLoaded('annexes', function () {
                return $this->annexes->map(function ($annexe) {
                    return [
                        'id' => $annexe->id,
                        'annex_name' => $annexe->name,
                        ''
                    ];
                });
            }, []), // Default to empty array if no annexes are loaded
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null, // Check for null dates
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null, // Check for null dates
        ];

        // Conditionally include company_name if the 'organisme' relationship is loaded
        $data['company_name'] = $this->whenLoaded('organisme', function () {
            // Assuming 'name' or 'company_name' is the field on your Organisme model
            return $this->organisme->name ?? null; // Adjust 'name' to your actual field for company name
        }, null); // Default to null if not loaded

        // Conditionally include conventionDetail attributes if the 'conventionDetail' relationship is loaded
        $data['start_date'] = $this->whenLoaded('conventionDetail', function () {
            return $this->conventionDetail->start_date ?? null;
        }, null); // Default to null if not loaded or detail is null

        $data['end_date'] = $this->whenLoaded('conventionDetail', function () {
            return $this->conventionDetail->end_date ?? null;
        }, null);

        $data['family_auth'] = $this->whenLoaded('conventionDetail', function () {
            return $this->conventionDetail->family_auth ?? null;
        }, null);

        $data['max_price'] = $this->whenLoaded('conventionDetail', function () {
            return $this->conventionDetail->max_price ?? null;
        }, null);

        $data['min_price'] = $this->whenLoaded('conventionDetail', function () {
            return $this->conventionDetail->min_price ?? null;
        }, null);

        $data['discount_percentage'] = $this->whenLoaded('conventionDetail', function () {
            return $this->conventionDetail->discount_percentage ?? null;
        }, null);

        return $data;
    }
}