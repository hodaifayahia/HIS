<?php

namespace App\Http\Resources\Configuration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferApprovalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'maximum' => (float) $this->maximum,
            'formatted_maximum' => $this->formatted_maximum,
            'is_active' => $this->is_active,
            'status_text' => $this->status_text,
            'status_color' => $this->status_color,
            'notes' => $this->notes,
            
            // User information
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'role' => $this->user->role,
                    'avatar' => isset($this->user->avatar) && $this->user->avatar 
                        ? asset('storage/' . $this->user->avatar) 
                        : null,
                ];
            }),
            
            // Methods
            'can_approve_transfer' => function ($amount) {
                return $this->canApproveTransfer($amount);
            },
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Human readable dates
            'created_at_human' => $this->created_at?->diffForHumans(),
            'updated_at_human' => $this->updated_at?->diffForHumans(),
        ];
    }
}