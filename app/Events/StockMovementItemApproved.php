<?php

namespace App\Events;

use App\Models\StockMovementItem;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockMovementItemApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $item;
    public $approvedBy;
    public $approvedAt;

    /**
     * Create a new event instance.
     */
    public function __construct(StockMovementItem $item, User $approvedBy)
    {
        $this->item = $item;
        $this->approvedBy = $approvedBy;
        $this->approvedAt = now();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('stock-movement.' . $this->item->stock_movement_id),
        ];
    }
}