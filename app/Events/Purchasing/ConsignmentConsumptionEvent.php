<?php

namespace App\Events\Purchasing;

use App\Models\Purchasing\ConsignmentReceptionItem;
use App\Models\Reception\ficheNavetteItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when consignment item is consumed (patient payment completed)
 * Broadcasts real-time notification to supplier-specific channel
 */
class ConsignmentConsumptionEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ConsignmentReceptionItem $consignmentItem,
        public ficheNavetteItem $ficheItem,
        public int $quantityConsumed = 1
    ) {}

    /**
     * Get the channels the event should broadcast on.
     * Private channel scoped to supplier (fournisseur)
     */
    public function broadcastOn(): Channel
    {
        $consignment = $this->consignmentItem->consignmentReception;

        return new PrivateChannel('consignments.'.$consignment->fournisseur_id);
    }

    /**
     * Broadcast event name
     */
    public function broadcastAs(): string
    {
        return 'consignment.consumption';
    }

    /**
     * Data to broadcast
     */
    public function broadcastWith(): array
    {
        $consignment = $this->consignmentItem->consignmentReception;

        return [
            'consignment_id' => $consignment->id,
            'consignment_code' => $consignment->consignment_code,
            'consignment_item_id' => $this->consignmentItem->id,
            'product_id' => $this->consignmentItem->product_id,
            'product_name' => $this->consignmentItem->product->name ?? 'Unknown',
            'quantity_consumed' => $this->quantityConsumed,
            'quantity_consumed_total' => $this->consignmentItem->quantity_consumed,
            'quantity_uninvoiced' => $this->consignmentItem->quantity_uninvoiced,
            'patient_id' => $this->ficheItem->ficheNavette->patient_id ?? null,
            'fiche_navette_id' => $this->ficheItem->fiche_navette_id,
            'consumed_at' => now()->toIso8601String(),
        ];
    }
}
