<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BonRetourResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bon_retour_code' => $this->bon_retour_code,
            'bon_entree_id' => $this->bon_entree_id,
            'fournisseur_id' => $this->fournisseur_id,
            'fournisseur' => new FournisseurResource($this->whenLoaded('fournisseur')),
            'return_type' => $this->return_type,
            'return_type_label' => $this->return_type_label,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'service_abv' => $this->service_abv,
            'service' => $this->whenLoaded('service', function () {
                return [
                    'id' => $this->service->id,
                    'name' => $this->service->name,
                    'service_abv' => $this->service->service_abv,
                ];
            }),
            'total_amount' => $this->total_amount,
            'reason' => $this->reason,
            'return_date' => $this->return_date?->format('Y-m-d'),
            'reference_invoice' => $this->reference_invoice,
            'credit_note_received' => $this->credit_note_received,
            'credit_note_number' => $this->credit_note_number,
            'attachments' => $this->attachments,
            'items' => BonRetourItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->whenCounted('items'),
            'bon_entree' => $this->whenLoaded('bonEntree', function () {
                return [
                    'id' => $this->bonEntree->id,
                    'code' => $this->bonEntree->bon_entree_code,
                    'date' => $this->bonEntree->created_at->format('Y-m-d'),
                ];
            }),
            'is_editable' => $this->is_editable,
            'created_by' => $this->created_by,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'approved_by' => $this->approved_by,
            'approver' => new UserResource($this->whenLoaded('approver')),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
