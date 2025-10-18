<?php

namespace App\Http\Resources\Stock;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stock_movement_id' => $this->stock_movement_id,
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product'),
            'requested_quantity' => $this->requested_quantity,
            'approved_quantity' => $this->approved_quantity,
            'executed_quantity' => $this->executed_quantity,
            'provided_quantity' => $this->provided_quantity,
            'notes' => $this->notes,
            'quantity_by_box' => $this->quantity_by_box,
            'status' => $this->getStatus(),
            'is_approved' => $this->isApproved(),
            'is_rejected' => $this->isRejected(),
            'is_pending' => $this->isPending(),
            'is_editable' => $this->isEditable(),
            'display_unit' => $this->getDisplayUnit(),
            'actual_requested_quantity' => $this->getActualRequestedQuantity(),
            'actual_approved_quantity' => $this->getActualApprovedQuantity(),
            'actual_executed_quantity' => $this->getActualExecutedQuantity(),
            'available_stock' => $this->getAvailableStock(),
            'suggested_quantity' => $this->getSuggestedQuantity(),
            'inventory_selections' => $this->whenLoaded('inventorySelections'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
