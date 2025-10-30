<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryAudit;
use Illuminate\Support\Facades\DB;

class InventoryReconciliationService
{
    /**
     * Analyze audit for discrepancies between participants
     * 
     * @param InventoryAudit $audit
     * @return array
     */
    public function analyzeDiscrepancies(InventoryAudit $audit): array
    {
        // Get all participants with 'sent' status
        $sentParticipants = DB::table('inventory_audits_participantes')
            ->where('inventory_audit_id', $audit->id)
            ->where('status', 'sent')
            ->pluck('user_id')
            ->toArray();

        if (count($sentParticipants) < 2) {
            return [
                'can_reconcile' => false,
                'message' => 'Need at least 2 participants with sent status',
                'participants_sent' => count($sentParticipants),
            ];
        }

        // Get all products counted by these participants
        $productCounts = DB::table('inventory_audits_product')
            ->where('inventory_audit_id', $audit->id)
            ->whereIn('participant_id', $sentParticipants)
            ->whereNotNull('actual_quantity')
            ->select('product_id', 'product_type', 'participant_id', 'actual_quantity', 'notes')
            ->get()
            ->groupBy(fn($item) => $item->product_id . '_' . $item->product_type);

        $consensus = [];
        $discrepancies = [];

        foreach ($productCounts as $productKey => $counts) {
            [$productId, $productType] = explode('_', $productKey);

            // Get unique quantities
            $quantities = $counts->pluck('actual_quantity')->unique();

            $productData = [
                'product_id' => (int)$productId,
                'product_type' => $productType,
                'participant_counts' => $counts->map(fn($c) => [
                    'participant_id' => $c->participant_id,
                    'actual_quantity' => $c->actual_quantity,
                    'notes' => $c->notes,
                ])->toArray(),
            ];

            if ($quantities->count() === 1) {
                // All participants agree
                $productData['agreed_quantity'] = $quantities->first();
                $consensus[] = $productData;
            } else {
                // Discrepancy found
                $productData['quantities'] = $quantities->values()->toArray();
                $productData['variance'] = $quantities->max() - $quantities->min();
                $discrepancies[] = $productData;
            }
        }

        return [
            'can_reconcile' => true,
            'participants_sent' => count($sentParticipants),
            'total_products' => count($productCounts),
            'consensus_products' => count($consensus),
            'disputed_products' => count($discrepancies),
            'consensus' => $consensus,
            'discrepancies' => $discrepancies,
            'participants' => $sentParticipants,
        ];
    }

    /**
     * Create recount assignment for disputed products
     * 
     * @param InventoryAudit $audit
     * @param array $productIds - Array of product_id values
     * @param int $participantId - User ID to assign recount
     * @return array
     */
    public function assignRecount(InventoryAudit $audit, array $productIds, int $participantId): array
    {
        return DB::transaction(function () use ($audit, $productIds, $participantId) {
            // Update participant status back to 'in_progress' for recount
            DB::table('inventory_audits_participantes')
                ->where('inventory_audit_id', $audit->id)
                ->where('user_id', $participantId)
                ->update([
                    'status' => 'recount',
                    'updated_at' => now(),
                ]);

            // Mark products for recount
            $affectedProducts = DB::table('inventory_audits_product')
                ->where('inventory_audit_id', $audit->id)
                ->where('participant_id', $participantId)
                ->whereIn('product_id', $productIds)
                ->update([
                    'status' => 'recount_required',
                    'updated_at' => now(),
                ]);

            \Log::info('Recount assigned', [
                'audit_id' => $audit->id,
                'participant_id' => $participantId,
                'products' => $productIds,
                'affected' => $affectedProducts,
            ]);

            return [
                'success' => true,
                'participant_id' => $participantId,
                'products_to_recount' => count($productIds),
                'affected_records' => $affectedProducts,
            ];
        });
    }

    /**
     * Get recount items for a participant
     * 
     * @param InventoryAudit $audit
     * @param int $participantId
     * @return array
     */
    public function getRecountItems(InventoryAudit $audit, int $participantId): array
    {
        return DB::table('inventory_audits_product')
            ->where('inventory_audit_id', $audit->id)
            ->where('participant_id', $participantId)
            ->where('status', 'recount_required')
            ->get()
            ->toArray();
    }

    /**
     * Finalize reconciliation - apply consensus and resolved discrepancies
     * 
     * @param InventoryAudit $audit
     * @return array
     */
    public function finalizeReconciliation(InventoryAudit $audit): array
    {
        return DB::transaction(function () use ($audit) {
            // Update audit status to completed
            $audit->update(['status' => 'completed', 'completed_at' => now()]);

            // Update all participant statuses to completed
            DB::table('inventory_audits_participantes')
                ->where('inventory_audit_id', $audit->id)
                ->update(['status' => 'completed', 'updated_at' => now()]);

            // Mark all products as completed
            DB::table('inventory_audits_product')
                ->where('inventory_audit_id', $audit->id)
                ->update(['status' => 'completed', 'updated_at' => now()]);

            \Log::info('Reconciliation finalized', ['audit_id' => $audit->id]);

            return [
                'success' => true,
                'message' => 'Audit completed successfully',
                'completed_at' => now()->toDateTimeString(),
            ];
        });
    }
}
