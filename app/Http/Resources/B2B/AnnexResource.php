<?php

namespace App\Http\Resources\B2B;

use Illuminate\Http\Resources\Json\JsonResource;

class AnnexResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'annex_name' => $this->annex_name,
            'convention_id' => $this->convention_id,
            'convention_status' => $this->convention->status ?? null,
            'service_name' => $this->service ? $this->service->name : 'N/A',
            'service_id' => $this->service_id,
            'service_id' => $this->service_id,
            'created_by' => $this->creator->name ?? 'N/A',
            'created_id' => $this->created_by ?? 'N/A',
            'created_at' => $this->created_at->format('d/m/Y'),
            'description' => $this->description,
            'min_price' => $this->min_price ?? null,

            'is_active' => $this->is_active,
            // Convention details
            'min_price_convention' => $this->convention->conventionDetail->min_price ?? null,
            'max_price_convention' => $this->convention->conventionDetail->max_price ?? null,
            'discount_percentage' => $this->convention->conventionDetail->discount_percentage ?? null,
        ];
    }
}
