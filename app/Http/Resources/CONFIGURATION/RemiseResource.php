<?php

namespace App\Http\Resources\CONFIGURATION;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RemiseResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
            'prestation_id' => $this->prestation_id,
            'amount' => $this->amount ? (float) $this->amount : null,
            'percentage' => $this->percentage ? (float) $this->percentage : null,
            'type' => $this->type,
            'is_active' => (bool) $this->is_active,
            'formatted_value' => $this->getFormattedValue(),
            'status_label' => $this->is_active ? 'Active' : 'Inactive',
            'type_label' => $this->type === 'fixed' ? 'Fixed Amount' : 'Percentage',
            // Relationships
            'users' => $this->whenLoaded('users', function () {
                return $this->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                });
            }),
            
            'prestations' => $this->whenLoaded('prestations', function () {
                return $this->prestations->map(function ($prestation) {
                    return [
                        'id' => $prestation->id,
                        'name' => $prestation->name,
                    ];
                });
            }),
            
            // Counts
            'users_count' => $this->whenLoaded('users', function () {
                return $this->users->count();
            }),
            
            'prestations_count' => $this->whenLoaded('prestations', function () {
                return $this->prestations->count();
            }),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'updated_at_human' => $this->updated_at?->diffForHumans(),
        ];
    }
    
    /**
     * Get formatted value based on type
     */
    private function getFormattedValue(): string
    {
        if ($this->type === 'fixed') {
            return number_format($this->amount ?? 0, 2, ',', ' ') . ' €';
        }
        
        return ($this->percentage ?? 0) . '%';
    }
}
