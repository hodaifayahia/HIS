<?php
// app/Http/Resources/RefundAuthorizationResource.php

namespace App\Http\Resources\manager;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundAuthorizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fiche_navette_item_id' => $this->fiche_navette_item_id,
            'item_dependency_id' => $this->item_dependency_id,
            'reason' => $this->reason,
            'requested_amount' => (float) $this->requested_amount,
            'authorized_amount' => (float) $this->authorized_amount,
            'status' => $this->status,
        ];
    }
}
