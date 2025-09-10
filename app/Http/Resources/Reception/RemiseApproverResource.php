<?php

namespace App\Http\Resources\Reception;

use Illuminate\Http\Resources\Json\JsonResource;

class RemiseApproverResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'role' => $this->user->role,
                    'avatar' => $this->user->avatar,
                ];
            }),
            'approver' => $this->whenLoaded('approver', function () {
                return [
                    'id' => $this->approver->id,
                    'name' => $this->approver->name,
                    'email' => $this->approver->email,
                    'role' => $this->approver->role,
                    'avatar' => $this->approver->avatar,
                ];
            }),
            'is_approved' => (bool) $this->is_approved,
            'comments' => $this->comments,
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'approved_by' => $this->approved_by,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            
            // Additional computed fields
            'status_label' => $this->is_approved ? 'Approved' : 'Pending',
            'can_edit' => $this->canCurrentUserEdit(),
            'can_delete' => $this->canCurrentUserDelete(),
        ];
    }

    /**
     * Check if current user can edit this approver record
     */
    private function canCurrentUserEdit(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        return in_array($user->role, ['SuperAdmin', 'admin']) || 
               $user->id === $this->approver_id;
    }

    /**
     * Check if current user can delete this approver record
     */
    private function canCurrentUserDelete(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        return in_array($user->role, ['SuperAdmin', 'admin']);
    }
}
