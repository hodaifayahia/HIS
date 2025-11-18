<?php

namespace App\Http\Resources\Purchasing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsignmentReceptionResource extends JsonResource
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
            ],
            'reception_date' => $this->reception_date,
            'unit_of_measure' => $this->unit_of_measure,
            'reception_type' => $this->reception_type,
            'operation_type' => $this->operation_type,
            'total_received' => $this->total_received,
            'total_consumed' => $this->total_consumed,
            'total_uninvoiced' => $this->total_uninvoiced,
            'confirmed_at' => $this->confirmed_at,
            'confirmed_by' => $this->confirmed_by,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
