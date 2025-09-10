<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RemiseRequestNotificationResource extends JsonResource
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
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'patient_id' => $this->patient_id,
            'status' => $this->status,
            'message' => $this->message,
            'total_amount' => $this->calculateTotalAmount(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Patient information
            'patient' => $this->when($this->relationLoaded('patient'), function () {
                return [
                    'id' => $this->patient->id,
                    'name' => $this->patient->fullname ?? ($this->patient->Firstname . ' ' . $this->patient->Lastname),
                    'firstname' => $this->patient->Firstname,
                    'lastname' => $this->patient->Lastname,
                    'phone' => $this->patient->phone,
                    'date_of_birth' => $this->patient->dateOfBirth,
                    'id_number' => $this->patient->Idnum,
                    'nss' => $this->patient->nss,
                    'gender' => $this->patient->gender,
                    'balance' => $this->patient->balance,
                ];
            }),
            
            // Sender information
            'sender' => $this->when($this->relationLoaded('sender'), function () {
                return [
                    'id' => $this->sender->id,
                    'name' => $this->sender->name,
                    'email' => $this->sender->email,
                    'phone' => $this->sender->phone,
                    'role' => $this->sender->role,
                    'avatar' => $this->sender->avatar,
                    'job_title' => $this->sender->job_title,
                ];
            }),
            
            // Receiver information
            'receiver' => $this->when($this->relationLoaded('receiver'), function () {
                return [
                    'id' => $this->receiver->id,
                    'name' => $this->receiver->name,
                    'email' => $this->receiver->email,
                    'phone' => $this->receiver->phone,
                    'role' => $this->receiver->role,
                    'avatar' => $this->receiver->avatar,
                    'job_title' => $this->receiver->job_title,
                    'salary' => $this->receiver->salary,
                ];
            }),
            
            // Prestations with contributions
            'prestations' => $this->when($this->relationLoaded('prestations'), function () {
                return $this->prestations->map(function ($prestation) {
                    return [
                        'id' => $prestation->id,
                        'prestation_id' => $prestation->prestation_id,
                        'proposed_amount' => number_format((float)$prestation->proposed_amount, 2),
                        'proposed_amount_raw' => (float)$prestation->proposed_amount,
                        'created_at' => $prestation->created_at,
                        
                        // Prestation details if loaded
                        'prestation' => $this->when($prestation->relationLoaded('prestation'), function () use ($prestation) {
                            return [
                                'id' => $prestation->prestation->id,
                                'name' => $prestation->prestation->name ?? 'Unknown Prestation',
                                'code' => $prestation->prestation->code ?? null,
                                'price' => $prestation->prestation->price ?? null,
                            ];
                        }),
                        
                        // Contributions
                        'contributions' => $this->when($prestation->relationLoaded('contributions'), function () use ($prestation) {
                            return $prestation->contributions->map(function ($contribution) {
                                return [
                                    'id' => $contribution->id,
                                    'user_id' => $contribution->user_id,
                                    'role' => $contribution->role,
                                    'proposed_amount' => number_format((float)$contribution->proposed_amount, 2),
                                    'proposed_amount_raw' => (float)$contribution->proposed_amount,
                                    'approved_amount' => $contribution->approved_amount ? number_format((float)$contribution->approved_amount, 2) : null,
                                    'approved_amount_raw' => (float)($contribution->approved_amount ?? 0),
                                    'final_amount' => $contribution->final_amount ?? $contribution->approved_amount ?? $contribution->proposed_amount,
                                    'approved_by' => $contribution->approved_by,
                                    'created_at' => $contribution->created_at,
                                    
                                    // User details
                                    'user' => $this->when($contribution->relationLoaded('user'), function () use ($contribution) {
                                        return [
                                            'id' => $contribution->user->id,
                                            'name' => $contribution->user->name,
                                            'email' => $contribution->user->email,
                                            'role' => $contribution->user->role,
                                            'avatar' => $contribution->user->avatar,
                                            'salary' => $contribution->user->salary,
                                        ];
                                    }),
                                ];
                            });
                        }),
                    ];
                });
            }),
            
            // Calculated totals
            'summary' => [
                'total_prestations' => $this->prestations->count() ?? 0,
                'total_contributions' => $this->getTotalContributions(),
                'total_amount_formatted' => number_format($this->calculateTotalAmount(), 2) . ' DZD',
                'can_approve' => $this->canCurrentUserApprove(),
                'can_reject' => $this->canCurrentUserReject(),
            ],
        ];
    }
    
    /**
     * Calculate total amount from all contributions
     */
    private function calculateTotalAmount(): float
    {
        if (!$this->relationLoaded('prestations')) {
            return 0.0;
        }
        
        return $this->prestations->sum(function ($prestation) {
            if (!$prestation->relationLoaded('contributions')) {
                return (float)($prestation->proposed_amount ?? 0);
            }
            
            return $prestation->contributions->sum(function ($contribution) {
                return (float)($contribution->final_amount ?? $contribution->approved_amount ?? $contribution->proposed_amount ?? 0);
            });
        });
    }
    
    /**
     * Get total number of contributions
     */
    private function getTotalContributions(): int
    {
        if (!$this->relationLoaded('prestations')) {
            return 0;
        }
        
        return $this->prestations->sum(function ($prestation) {
            return $prestation->relationLoaded('contributions') ? $prestation->contributions->count() : 0;
        });
    }
    
    /**
     * Check if current user can approve this request
     */
    private function canCurrentUserApprove(): bool
    {
        $currentUserId = auth()->id();
        return $this->status === 'pending' && 
               ($this->receiver_id === $currentUserId || $this->hasApprovalPermission($currentUserId));
    }
    
    /**
     * Check if current user can reject this request
     */
    private function canCurrentUserReject(): bool
    {
        return $this->canCurrentUserApprove();
    }
    
    /**
     * Check if user has approval permission (manager, admin, etc.)
     */
    private function hasApprovalPermission(int $userId): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['SuperAdmin', 'admin', 'manager']);
    }
}
