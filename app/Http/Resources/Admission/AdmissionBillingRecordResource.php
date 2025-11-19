<?php

namespace App\Http\Resources\Admission;

use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionBillingRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'admission_id' => $this->admission_id,
            'amount' => (float) ($this->amount ?? 0),
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => ucfirst(str_replace('_', ' ', $this->status)),
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            'invoice_number' => $this->invoice_number,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
