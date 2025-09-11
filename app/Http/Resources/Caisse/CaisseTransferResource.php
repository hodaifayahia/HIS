<?php
// app/Http/Resources/Caisse/CaisseTransferResource.php

namespace App\Http\Resources\Caisse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Caisse\CaisseTransfer */
class CaisseTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'caisse_id' => $this->caisse_id,
            'from_user_id' => $this->from_user_id,
            'to_user_id' => $this->to_user_id,
            'amount_sended' => (float) $this->amount_sended,
            'amount_received' => (float) $this->amount_received,
            'formatted_amount' => $this->formatted_amount,
            'description' => $this->description,
            'status' => $this->status,
            'formatted_status' => $this->formatted_status,
            'token_expires_at' => $this->token_expires_at?->toISOString(),
            'accepted_at' => $this->accepted_at?->toISOString(),
            'is_expired' => $this->is_expired,
            'can_be_accepted' => $this->can_be_accepted,

            // Relationships
            'caisse' => $this->whenLoaded('caisse', function () {
                return [
                    'id' => $this->caisse->id,
                    'name' => $this->caisse->name,
                    'code' => $this->caisse->code,
                ];
            }),

            'from_user' => $this->whenLoaded('fromUser', function () {
                return [
                    'id' => $this->fromUser->id,
                    'name' => $this->fromUser->name,
                    'email' => $this->fromUser->email,
                ];
            }),

            'to_user' => $this->whenLoaded('toUser', function () {
                return [
                    'id' => $this->toUser->id,
                    'name' => $this->toUser->name,
                    'email' => $this->toUser->email,
                ];
            }),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
