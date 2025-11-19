<?php

namespace App\Http\Resources\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExternalPrescriptionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_prescription_id' => $this->external_prescription_id,
            'pharmacy_product_id' => $this->pharmacy_product_id,
            'pharmacy_product' => $this->whenLoaded('pharmacyProduct', function () {
                return [
                    'id' => $this->pharmacyProduct->id,
                    'name' => $this->pharmacyProduct->name,
                    'code' => $this->pharmacyProduct->code,
                    'category' => $this->pharmacyProduct->category,
                ];
            }),
            'quantity' => $this->quantity,
            'quantity_by_box' => $this->quantity_by_box,
            'unit' => $this->unit,
            'quantity_sended' => $this->quantity_sended,
            'quantity_display' => $this->quantity_display,
            'service_id' => $this->service_id,
            'service' => $this->whenLoaded('service', function () {
                return [
                    'id' => $this->service->id,
                    'name' => $this->service->name,
                ];
            }),
            'status' => $this->status,
            'status_label' => $this->status_label,
            'cancel_reason' => $this->cancel_reason,
            'modified_by' => $this->modified_by,
            'modifier' => $this->whenLoaded('modifier', function () {
                return [
                    'id' => $this->modifier->id,
                    'name' => $this->modifier->name,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
