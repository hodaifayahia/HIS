<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BonRetourItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bon_retour_id' => $this->bon_retour_id,
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product'),
            'bon_entree_item_id' => $this->bon_entree_item_id,
            'original_item' => $this->whenLoaded('originalItem', function () {
                return [
                    'id' => $this->originalItem->id,
                    'quantity' => $this->originalItem->quantity,
                    'unit_price' => $this->originalItem->purchase_price,
                    'batch_number' => $this->originalItem->batch_number,
                ];
            }),
            'batch_number' => $this->batch_number,
            'serial_number' => $this->serial_number,
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'formatted_expiry_date' => $this->formatted_expiry_date,
            'quantity_returned' => $this->quantity_returned,
            'unit_price' => $this->unit_price,
            'tva' => $this->tva,
            'unit_price_with_tva' => $this->unit_price_with_tva,
            'total_amount' => $this->total_amount,
            'return_reason' => $this->return_reason,
            'return_reason_label' => $this->return_reason_label,
            'remarks' => $this->remarks,
            'storage_location' => $this->storage_location,
            'stock_updated' => $this->stock_updated,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
