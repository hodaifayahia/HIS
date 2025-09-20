<?php

namespace App\Listeners;

use App\Events\StockMovementItemApproved;
use App\Events\StockMovementItemRejected;
use App\Models\StockMovementAuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Http\Request;

class LogStockMovementAudit implements ShouldQueue
{
    use InteractsWithQueue;

    protected $request;

    /**
     * Create the event listener.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Handle stock movement item approved events.
     */
    public function handleApproved(StockMovementItemApproved $event): void
    {
        $this->logAuditEvent(
            $event->item,
            $event->approvedBy,
            'approved',
            ['approved_quantity' => null],
            ['approved_quantity' => $event->item->approved_quantity],
            "Item approved by {$event->approvedBy->name}"
        );
    }

    /**
     * Handle stock movement item rejected events.
     */
    public function handleRejected(StockMovementItemRejected $event): void
    {
        $notes = "Item rejected by {$event->rejectedBy->name}";
        if ($event->rejectionReason) {
            $notes .= ". Reason: {$event->rejectionReason}";
        }

        $this->logAuditEvent(
            $event->item,
            $event->rejectedBy,
            'rejected',
            ['approved_quantity' => null],
            ['approved_quantity' => 0],
            $notes
        );
    }

    /**
     * Log an audit event.
     */
    private function logAuditEvent($item, $user, $action, $oldValues, $newValues, $notes): void
    {
        StockMovementAuditLog::create([
            'stock_movement_id' => $item->stock_movement_id,
            'stock_movement_item_id' => $item->id,
            'user_id' => $user->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'notes' => $notes,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }

    /**
     * Get the name of the listener's queue.
     */
    public function viaQueue(): string
    {
        return 'audit';
    }
}