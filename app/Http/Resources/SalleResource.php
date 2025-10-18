<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'number' => $this->number,
            'description' => $this->description,
            'defult_specialization_id' => $this->defult_specialization_id,
            'full_name' => $this->full_name,

            // Default specialization relationship
            'default_specialization' => $this->whenLoaded('defaultSpecialization', function () {
                return [
                    'id' => $this->defaultSpecialization->id,
                    'name' => $this->defaultSpecialization->name,
                ];
            }),

            // Assigned specializations relationship
            'specializations' => $this->whenLoaded('specializations', function () {
                return $this->specializations->map(function ($specialization) {
                    return [
                        'id' => $specialization->id,
                        'name' => $specialization->name,
                        'pivot' => [
                            'assigned_at' => $specialization->pivot->created_at,
                        ],
                    ];
                });
            }),

            // Count of assigned specializations
            'specializations_count' => $this->whenLoaded('specializations', function () {
                return $this->specializations->count();
            }),

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_at_formatted' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at_formatted' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
