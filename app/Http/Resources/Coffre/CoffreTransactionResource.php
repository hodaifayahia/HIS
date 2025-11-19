<?php

namespace App\Http\Resources\Coffre;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Coffre\CoffreTransaction */
class CoffreTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'coffre_id' => $this->coffre_id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'transaction_type' => $this->transaction_type,
            'transaction_type_display' => $this->transaction_type_display,
            'payment_method' => $this->payment_method,
            'amount' => (float) $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'description' => $this->description,
            'source_caisse_session_id' => $this->source_caisse_session_id,
            'destination_banque_id' => $this->destination_banque_id,
            'dest_coffre_id' => $this->dest_coffre_id,
            
            // Relationships
            'coffre' => $this->whenLoaded('coffre', function () {
                return [
                    'id' => $this->coffre->id,
                    'name' => $this->coffre->name,
                    'location' => $this->coffre->location,
                ];
            }),
            
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            
            'destination_coffre' => $this->whenLoaded('destinationCoffre', function () {
                return [
                    'id' => $this->destinationCoffre->id,
                    'name' => $this->destinationCoffre->name,
                    'location' => $this->destinationCoffre->location,
                ];
            }),
            
            'source_caisse_session' => $this->whenLoaded('sourceCaisseSession', function () {
                return [
                    'id' => $this->sourceCaisseSession->id,
                    'caisse_id' => $this->sourceCaisseSession->caisse_id ?? null,
                    'cashier_id' => $this->sourceCaisseSession->cashier_id ?? null,
                    'cashier' => $this->sourceCaisseSession->cashier ? [
                        'id' => $this->sourceCaisseSession->cashier->id,
                        'name' => $this->sourceCaisseSession->cashier->name,
                    ] : null,
                ];
            }),
            
            'patient' => $this->whenLoaded('patient', function () {
                return [
                    'id' => $this->patient->id,
                    'name' => $this->patient->name,
                ];
            }),
            
            'prestation' => $this->whenLoaded('prestation', function () {
                return [
                    'id' => $this->prestation->id,
                    'name' => $this->prestation->name,
                ];
            }),
            
            'destination_banque' => $this->whenLoaded('destinationBanque', function () {
                return [
                    'id' => $this->destinationBanque->id,
                    'name' => $this->destinationBanque->name,
                ];
            }),
            'approval_request' => $this->whenLoaded('approvalRequest', function () {
                return [
                    'id' => $this->approvalRequest->id,
                    'status' => $this->approvalRequest->status,
                    'requested_by' => $this->approvalRequest->requested_by,
                    'candidate_user_ids' => $this->approvalRequest->candidate_user_ids,
                    'approved_by' => $this->approvalRequest->approved_by,
                    'created_at' => $this->approvalRequest->created_at?->toISOString(),
                ];
            }),
            
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}