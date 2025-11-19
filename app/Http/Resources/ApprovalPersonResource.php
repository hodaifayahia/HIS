<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalPersonResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'max_amount' => (float) $this->max_amount,
            'formatted_max_amount' => number_format($this->max_amount, 2).' DZD',
            'title' => $this->title,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'priority' => $this->priority,
            'pending_approvals_count' => $this->pending_approvals_count ?? 0,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
