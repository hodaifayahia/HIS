<?php

namespace App\Http\Resources\Purchasing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsignmentReceptionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'consignment_reception_id' => $this->consignment_reception_id,
            'product_id' => $this->product_id,
            'product' => [
                'id' => $this->product->id ?? null,
                'name' => $this->product->name ?? null,
                'code_interne' => $this->product->code_interne ?? null,
            ],
            'quantity_received' => $this->quantity_received,
            'quantity_consumed' => $this->quantity_consumed,
            'quantity_invoiced' => $this->quantity_invoiced,
            'quantity_uninvoiced' => $this->quantity_uninvoiced,
            'unit_price' => $this->unit_price,
            'total_value' => $this->total_value,
            'consumed_value' => $this->consumed_value,
            'uninvoiced_value' => $this->uninvoiced_value,
            'is_fully_consumed' => $this->isFullyConsumed(),
            'is_fully_invoiced' => $this->isFullyInvoiced(),
            'can_be_invoiced' => $this->canBeInvoiced(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
