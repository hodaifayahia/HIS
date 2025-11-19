<?php

namespace App\Http\Resources\Purchasing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsignmentReceptionDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'consignment_code' => $this->consignment_code,
            'fournisseur_id' => $this->fournisseur_id,
            'fournisseur' => [
                'id' => $this->fournisseur->id ?? null,
                'nom_fournisseur' => $this->fournisseur->nom_fournisseur ?? null,
                'email' => $this->fournisseur->email ?? null,
                'telephone' => $this->fournisseur->telephone ?? null,
            ],
            'reception_date' => $this->reception_date,
            'unit_of_measure' => $this->unit_of_measure,
            'origin_note' => $this->origin_note,
            'reception_type' => $this->reception_type,
            'operation_type' => $this->operation_type,
            'total_received' => $this->total_received,
            'total_consumed' => $this->total_consumed,
            'total_uninvoiced' => $this->total_uninvoiced,
            'items' => ConsignmentReceptionItemResource::collection($this->whenLoaded('items')),
            'invoices' => $this->whenLoaded('invoices', function () {
                return $this->invoices->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'total_amount' => $invoice->total_amount,
                        'status' => $invoice->status,
                        'created_at' => $invoice->created_at,
                    ];
                });
            }),
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                ];
            }),
            'confirmed_by' => $this->whenLoaded('confirmedBy', function () {
                return [
                    'id' => $this->confirmedBy->id,
                    'name' => $this->confirmedBy->name,
                ];
            }),
            'confirmed_at' => $this->confirmed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
