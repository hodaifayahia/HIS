<?php

namespace App\Services;

use App\Events\StockMovementItemApproved;
use App\Events\StockMovementItemRejected;
use App\Models\StockMovement;
use App\Models\StockMovementItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockMovementApprovalService
{
    /**
     * Approve selected items in a stock movement
     */
    public function approveItems(StockMovement $movement, array $itemIds): array
    {
        return DB::transaction(function () use ($movement, $itemIds) {
            // Get items that belong to this movement and are editable
            $items = StockMovementItem::where('stock_movement_id', $movement->id)
                ->whereIn('id', $itemIds)
                ->get();

            $processedItems = [];
            $errors = [];

            foreach ($items as $item) {
                if (! $item->isEditable()) {
                    $errors[] = "Item {$item->id} has already been processed";

                    continue;
                }

                try {
                    $item->approved_quantity = $item->requested_quantity;
                    $item->notes = $this->appendNote(
                        $item->notes,
                        'Approved by '.Auth::user()->name.' on '.now()->format('Y-m-d H:i:s')
                    );
                    $item->save();

                    $processedItems[] = $item;

                    // Dispatch event
                    event(new StockMovementItemApproved($item, Auth::user()));

                    Log::info('Stock movement item approved', [
                        'item_id' => $item->id,
                        'movement_id' => $movement->id,
                        'approved_by' => Auth::id(),
                        'approved_quantity' => $item->approved_quantity,
                    ]);
                } catch (\Exception $e) {
                    $errors[] = "Failed to approve item {$item->id}: {$e->getMessage()}";
                }
            }

            // Update movement status
            $this->updateMovementStatus($movement);

            return [
                'success' => true,
                'processed_items' => $processedItems,
                'errors' => $errors,
                'message' => count($processedItems).' items approved successfully',
            ];
        });
    }

    /**
     * Reject selected items in a stock movement
     */
    public function rejectItems(StockMovement $movement, array $itemIds, ?string $rejectionReason = null): array
    {
        return DB::transaction(function () use ($movement, $itemIds, $rejectionReason) {
            // Get items that belong to this movement and are editable
            $items = StockMovementItem::where('stock_movement_id', $movement->id)
                ->whereIn('id', $itemIds)
                ->get();

            $processedItems = [];
            $errors = [];

            foreach ($items as $item) {
                if (! $item->isEditable()) {
                    $errors[] = "Item {$item->id} has already been processed";

                    continue;
                }

                try {
                    $item->approved_quantity = 0;

                    $rejectionNote = 'Rejected by '.Auth::user()->name.' on '.now()->format('Y-m-d H:i:s');
                    if ($rejectionReason) {
                        $rejectionNote .= ". Reason: {$rejectionReason}";
                    }

                    $item->notes = $this->appendNote($item->notes, $rejectionNote);
                    $item->save();

                    $processedItems[] = $item;

                    // Dispatch event
                    event(new StockMovementItemRejected($item, Auth::user(), $rejectionReason));

                    Log::info('Stock movement item rejected', [
                        'item_id' => $item->id,
                        'movement_id' => $movement->id,
                        'rejected_by' => Auth::id(),
                        'rejection_reason' => $rejectionReason,
                    ]);
                } catch (\Exception $e) {
                    $errors[] = "Failed to reject item {$item->id}: {$e->getMessage()}";
                }
            }

            // Update movement status
            $this->updateMovementStatus($movement);

            return [
                'success' => true,
                'processed_items' => $processedItems,
                'errors' => $errors,
                'message' => count($processedItems).' items rejected successfully',
            ];
        });
    }

    /**
     * Update the movement status based on item approval states
     */
    private function updateMovementStatus(StockMovement $movement): void
    {
        $totalItems = $movement->items()->count();
        $processedItems = $movement->items()->whereNotNull('approved_quantity')->count();
        $approvedItems = $movement->items()->where('approved_quantity', '>', 0)->count();

        if ($processedItems === 0) {
            $movement->status = 'pending';
        } elseif ($processedItems === $totalItems) {
            $movement->status = $approvedItems > 0 ? 'approved' : 'rejected';
        } else {
            $movement->status = 'partially_approved';
        }

        $movement->save();

        Log::info('Stock movement status updated', [
            'movement_id' => $movement->id,
            'new_status' => $movement->status,
            'total_items' => $totalItems,
            'processed_items' => $processedItems,
            'approved_items' => $approvedItems,
        ]);
    }

    /**
     * Append a note to existing notes
     */
    private function appendNote(?string $existingNotes, string $newNote): string
    {
        if (empty($existingNotes)) {
            return $newNote;
        }

        return $existingNotes."\n".$newNote;
    }

    /**
     * Check if a movement can be edited
     */
    public function canEditMovement(StockMovement $movement): bool
    {
        return in_array($movement->status, ['pending', 'partially_approved']);
    }

    /**
     * Get movement statistics
     */
    public function getMovementStatistics(StockMovement $movement): array
    {
        $items = $movement->items;

        return [
            'total_items' => $items->count(),
            'pending_items' => $items->filter(fn ($item) => $item->isPending())->count(),
            'approved_items' => $items->filter(fn ($item) => $item->isApproved())->count(),
            'rejected_items' => $items->filter(fn ($item) => $item->isRejected())->count(),
            'editable_items' => $items->filter(fn ($item) => $item->isEditable())->count(),
        ];
    }
}
