<?php

namespace App\Http\Resources\Stock;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductReserveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'reserve_id'           => $this->reserve_id,
            'reserve'              => $this->whenLoaded('reserve'),
            'reservation_code'     => $this->reservation_code,
            'product'              => $this->whenLoaded('product'),
            'pharmacy_product'     => $this->whenLoaded('pharmacyProduct'),
            'stockage'             => $this->whenLoaded('stockage'),
            'pharmacy_stockage'    => $this->whenLoaded('pharmacyStockage'),
            'reserver'             => $this->whenLoaded('reserver'),
            'destination_service_id' => $this->destination_service_id,
            'destination_service'  => $this->whenLoaded('destinationService'),
            'quantity'             => $this->quantity,
            'status'               => $this->status,
            'reserved_at'          => $this->reserved_at,
            'expires_at'           => $this->expires_at,
            'fulfilled_at'         => $this->fulfilled_at,
            'cancelled_at'         => $this->cancelled_at,
            'cancel_reason'        => $this->cancel_reason,
            'source'               => $this->source,
            'reservation_notes'    => $this->reservation_notes,
            'meta'                 => $this->meta,
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
