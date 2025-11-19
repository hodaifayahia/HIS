<?php

namespace App\Events\Purchasing;

use App\Models\Purchasing\ConsignmentReception;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when consignment goods are received from supplier
 * Broadcasts real-time notification to supplier-specific channel
 */
class ConsignmentReceivedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ConsignmentReception $consignment
    ) {}

    /**
     * Get the channels the event should broadcast on.
     * Private channel scoped to supplier (fournisseur)
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('consignments.'.$this->consignment->fournisseur_id);
    }

    /**
     * Broadcast event name
     */
    public function broadcastAs(): string
    {
        return 'consignment.received';
    }

    /**
     * Data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'consignment_id' => $this->consignment->id,
            'consignment_code' => $this->consignment->consignment_code,
            'supplier_id' => $this->consignment->fournisseur_id,
            'supplier_name' => $this->consignment->fournisseur->nom_fournisseur ?? 'Unknown',
            'reception_date' => $this->consignment->reception_date,
            'total_received' => $this->consignment->total_received,
            'total_items' => $this->consignment->items->count(),
            'received_at' => now()->toIso8601String(),
        ];
    }
}
