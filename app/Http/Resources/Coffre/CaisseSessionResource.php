<?php
// app/Http/Resources/Coffre/CaisseSessionResource.php

namespace App\Http\Resources\Coffre;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Coffre\CaisseSession */
class CaisseSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'caisse_id' => $this->caisse_id,
            'user_id' => $this->user_id,
            'open_by' => $this->open_by,
            'closed_by' => $this->closed_by,
            'coffre_id_source' => $this->coffre_id_source,
            'coffre_id_destination' => $this->coffre_id_destination,
            'opened_at' => $this->opened_at,
            'closed_at' => $this->closed_at,
            'opening_amount' => (float) $this->opening_amount,
            'closing_amount' => $this->closing_amount ? (float) $this->closing_amount : null,
            'expected_closing_amount' => $this->expected_closing_amount ? (float) $this->expected_closing_amount : null,
            'total_cash_counted' => $this->total_cash_counted ? (float) $this->total_cash_counted : null,
            'cash_difference' => $this->cash_difference ? (float) $this->cash_difference : null,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'status_color' => $this->status_color,
            'opening_notes' => $this->opening_notes,
            'closing_notes' => $this->closing_notes,
            'duration' => $this->duration,
            'duration_in_minutes' => $this->duration_in_minutes,
            'variance' => $this->variance,
            'cash_variance' => $this->cash_variance,
            'variance_percentage' => $this->variance_percentage,
            'is_active' => $this->is_active,
            'total_coins' => $this->total_coins,
            'total_notes' => $this->total_notes,
            
            // Relationships
            'caisse' => $this->whenLoaded('caisse', function () {
                return [
                    'id' => $this->caisse->id,
                    'name' => $this->caisse->name,
                    'location' => $this->caisse->location,
                    'is_active' => $this->caisse->is_active,
                ];
            }),
            
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),

            'opened_by' => $this->whenLoaded('openedBy', function () {
                return [
                    'id' => $this->openedBy->id,
                    'name' => $this->openedBy->name,
                    'email' => $this->openedBy->email,
                ];
            }),

            'closed_by' => $this->whenLoaded('closedBy', function () {
                return $this->closedBy ? [
                    'id' => $this->closedBy->id,
                    'name' => $this->closedBy->name,
                    'email' => $this->closedBy->email,
                ] : null;
            }),

            'source_coffre' => $this->whenLoaded('sourceCoffre', function () {
                return $this->sourceCoffre ? [
                    'id' => $this->sourceCoffre->id,
                    'name' => $this->sourceCoffre->name,
                    'location' => $this->sourceCoffre->location,
                ] : null;
            }),

            'destination_coffre' => $this->whenLoaded('destinationCoffre', function () {
                return $this->destinationCoffre ? [
                    'id' => $this->destinationCoffre->id,
                    'name' => $this->destinationCoffre->name,
                    'location' => $this->destinationCoffre->location,
                ] : null;
            }),

            'denominations' => $this->whenLoaded('denominations'),
            
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
