<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryAuditRecountService
{
    /**
     * Assign specific products to a participant for recount
     */
    public function assignProductsForRecount(
        InventoryAudit $audit,
        int $participantId,
        array $productIds,
        bool $showOtherCounts = false
    ): array {
        try {
            DB::beginTransaction();

            // Get participant record using DB facade
            $participant = DB::table('inventory_audits_participantes')
                ->where('inventory_audit_id', $audit->id)
                ->where('user_id', $participantId)
                ->first();

            // If participant doesn't exist, add them to the audit
            if (!$participant) {
                Log::info("Adding new participant to audit for recount", [
                    'audit_id' => $audit->id,
                    'participant_id' => $participantId,
                ]);
                
                DB::table('inventory_audits_participantes')->insert([
                    'inventory_audit_id' => $audit->id,
                    'user_id' => $participantId,
                    'is_participant' => true,
                    'is_able_to_see' => true,
                    'status' => 'recount',
                    'is_in_recount_mode' => true,
                    'recount_products_count' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Fetch the newly created participant
                $participant = DB::table('inventory_audits_participantes')
                    ->where('inventory_audit_id', $audit->id)
                    ->where('user_id', $participantId)
                    ->first();
            }

            // Get original counts for these products (if they exist)
            $originalCounts = DB::table('inventory_audits_product')
                ->where('inventory_audit_id', $audit->id)
                ->where('participant_id', $participantId)
                ->whereIn('product_id', $productIds)
                ->get();

            // If participant hasn't counted these products yet (new participant),
            // we'll create recount records without original quantities
            $isNewParticipant = $originalCounts->isEmpty();
            
            if ($isNewParticipant) {
                Log::info("New participant - will show only selected products", [
                    'audit_id' => $audit->id,
                    'participant_id' => $participantId,
                    'product_ids' => $productIds,
                ]);
                
                // For new participants, we need to get product info from any participant's count
                // to know the theoretical quantity and product details
                $productInfo = DB::table('inventory_audits_product')
                    ->where('inventory_audit_id', $audit->id)
                    ->whereIn('product_id', $productIds)
                    ->select('product_id', 'product_type', 'theoretical_quantity', 'stockage_id')
                    ->distinct()
                    ->get()
                    ->keyBy('product_id');
            }

            $recountRecords = [];
            $skippedProducts = [];
            
            // Handle new participants vs existing participants differently
            if ($isNewParticipant) {
                // NEW PARTICIPANT: Create recount records for selected products only
                foreach ($productIds as $productId) {
                    $info = $productInfo->get($productId);
                    
                    if (!$info) {
                        Log::warning("Product not found in any participant's count", [
                            'product_id' => $productId,
                        ]);
                        $skippedProducts[] = $productId;
                        continue;
                    }

                    // Verify product exists in products table
                    $productExists = DB::table('products')->where('id', $productId)->exists();
                    if (!$productExists) {
                        Log::warning("Skipping non-existent product", ['product_id' => $productId]);
                        $skippedProducts[] = $productId;
                        continue;
                    }

                    try {
                        // Create recount record (will be used to show ONLY these products to participant)
                        $recountRecord = DB::table('inventory_audit_recounts')->insertGetId([
                            'inventory_audit_id' => $audit->id,
                            'participant_id' => $participantId,
                            'product_id' => $productId,
                            'product_type' => $info->product_type ?? 'stock',
                            'is_recount_mode' => true,
                            'show_other_counts' => $showOtherCounts,
                            'original_quantity' => null, // No original for new participant
                            'recount_quantity' => null,
                            'recounted_at' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $recountRecords[] = $recountRecord;
                    } catch (\Exception $e) {
                        Log::error("Failed to create recount record for new participant", [
                            'product_id' => $productId,
                            'error' => $e->getMessage(),
                        ]);
                        $skippedProducts[] = $productId;
                    }
                }
            } else {
                // EXISTING PARTICIPANT: Use their original counts
                foreach ($originalCounts as $count) {
                    // Verify product exists before creating recount record
                    $productExists = DB::table('products')
                        ->where('id', $count->product_id)
                        ->exists();

                    if (!$productExists) {
                        Log::warning("Skipping recount for non-existent product", [
                            'product_id' => $count->product_id,
                            'audit_id' => $audit->id,
                            'participant_id' => $participantId,
                        ]);
                        $skippedProducts[] = $count->product_id;
                        continue;
                    }

                    try {
                        // Create recount tracking record
                        $recountRecord = DB::table('inventory_audit_recounts')->insertGetId([
                            'inventory_audit_id' => $audit->id,
                            'participant_id' => $participantId,
                            'product_id' => $count->product_id,
                            'product_type' => $count->product_type,
                            'is_recount_mode' => true,
                            'show_other_counts' => $showOtherCounts,
                            'original_quantity' => $count->actual_quantity,
                            'recount_quantity' => null,
                            'recounted_at' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // Update the product count record to mark as recount
                        DB::table('inventory_audits_product')
                            ->where('id', $count->id)
                            ->update([
                                'original_quantity' => $count->actual_quantity,
                                'is_recount' => true,
                                'updated_at' => now(),
                            ]);

                        $recountRecords[] = $recountRecord;
                    } catch (\Exception $e) {
                        Log::error("Failed to create recount record", [
                            'product_id' => $count->product_id,
                            'error' => $e->getMessage(),
                        ]);
                        $skippedProducts[] = $count->product_id;
                    }
                }
            }

            // Update participant status using DB facade
            DB::table('inventory_audits_participantes')
                ->where('inventory_audit_id', $audit->id)
                ->where('user_id', $participantId)
                ->update([
                    'status' => 'recount',
                    'is_in_recount_mode' => true,
                    'recount_products_count' => count($productIds),
                    'updated_at' => now(),
                ]);

            DB::commit();

            Log::info("Recount assigned", [
                'audit_id' => $audit->id,
                'participant_id' => $participantId,
                'products_count' => count($productIds),
                'successful' => count($recountRecords),
                'skipped' => count($skippedProducts),
            ]);

            $successCount = count($recountRecords);
            $skipCount = count($skippedProducts);
            
            return [
                'success' => true,
                'recount_records' => $recountRecords,
                'products_count' => $successCount,
                'skipped_products' => $skippedProducts,
                'skipped_count' => $skipCount,
                'message' => $skipCount > 0 
                    ? "Assigned {$successCount} products. Skipped {$skipCount} invalid products."
                    : "Successfully assigned {$successCount} products for recount.",
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to assign recount: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get recount products for a participant
     */
    public function getRecountProducts(InventoryAudit $audit, int $participantId): array
    {
        $recounts = DB::table('inventory_audit_recounts as r')
            ->join('products as p', 'p.id', '=', 'r.product_id')
            ->leftJoin('stockages as s', 's.id', '=', 'p.stockage_id')
            ->where('r.inventory_audit_id', $audit->id)
            ->where('r.participant_id', $participantId)
            ->where('r.is_recount_mode', true)
            ->select([
                'r.id as recount_id',
                'r.product_id',
                'r.product_type',
                'r.original_quantity',
                'r.recount_quantity',
                'r.show_other_counts',
                'r.recounted_at',
                'p.name as product_name',
                'p.code as product_code',
                'p.quantity as current_stock',
                's.name as stockage_name',
            ])
            ->get();

        // If show_other_counts is enabled, fetch other participants' counts
        if ($recounts->isNotEmpty() && $recounts->first()->show_other_counts) {
            foreach ($recounts as $recount) {
                $recount->other_participant_counts = $this->getOtherParticipantCounts(
                    $audit->id,
                    $recount->product_id,
                    $participantId
                );
            }
        }

        return $recounts->toArray();
    }

    /**
     * Get other participants' counts for a product
     */
    private function getOtherParticipantCounts(int $auditId, int $productId, int $excludeParticipantId): array
    {
        return DB::table('inventory_audits_product as iap')
            ->join('users as u', 'u.id', '=', 'iap.participant_id')
            ->where('iap.inventory_audit_id', $auditId)
            ->where('iap.product_id', $productId)
            ->where('iap.participant_id', '!=', $excludeParticipantId)
            ->select([
                'u.id as participant_id',
                'u.name as participant_name',
                'iap.actual_quantity',
                'iap.original_quantity',
                'iap.is_recount',
            ])
            ->get()
            ->toArray();
    }

    /**
     * Update recount quantity for a product
     */
    public function updateRecountQuantity(
        InventoryAudit $audit,
        int $participantId,
        int $productId,
        float $recountQuantity
    ): bool {
        try {
            DB::beginTransaction();

            // Update recount record
            DB::table('inventory_audit_recounts')
                ->where('inventory_audit_id', $audit->id)
                ->where('participant_id', $participantId)
                ->where('product_id', $productId)
                ->update([
                    'recount_quantity' => $recountQuantity,
                    'recounted_at' => now(),
                    'updated_at' => now(),
                ]);

            // Update product count with new recount value
            DB::table('inventory_audits_product')
                ->where('inventory_audit_id', $audit->id)
                ->where('participant_id', $participantId)
                ->where('product_id', $productId)
                ->update([
                    'actual_quantity' => $recountQuantity,
                    'is_recount' => true,
                    'updated_at' => now(),
                ]);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update recount quantity: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove recount mode and restore original quantities
     */
    public function removeRecount(InventoryAudit $audit, int $participantId): array
    {
        try {
            DB::beginTransaction();

            // Get all recount records for this participant
            $recounts = DB::table('inventory_audit_recounts')
                ->where('inventory_audit_id', $audit->id)
                ->where('participant_id', $participantId)
                ->get();

            $restoredCount = 0;

            foreach ($recounts as $recount) {
                // Restore original quantity in product counts
                DB::table('inventory_audits_product')
                    ->where('inventory_audit_id', $audit->id)
                    ->where('participant_id', $participantId)
                    ->where('product_id', $recount->product_id)
                    ->update([
                        'actual_quantity' => $recount->original_quantity,
                        'original_quantity' => null,
                        'is_recount' => false,
                        'updated_at' => now(),
                    ]);

                $restoredCount++;
            }

            // Delete recount records
            DB::table('inventory_audit_recounts')
                ->where('inventory_audit_id', $audit->id)
                ->where('participant_id', $participantId)
                ->delete();

            // Update participant status using DB facade
            DB::table('inventory_audits_participantes')
                ->where('inventory_audit_id', $audit->id)
                ->where('user_id', $participantId)
                ->update([
                    'status' => 'sent', // Return to sent status
                    'is_in_recount_mode' => false,
                    'recount_products_count' => 0,
                    'updated_at' => now(),
                ]);

            DB::commit();

            Log::info("Recount removed and quantities restored", [
                'audit_id' => $audit->id,
                'participant_id' => $participantId,
                'restored_count' => $restoredCount,
            ]);

            return [
                'success' => true,
                'restored_count' => $restoredCount,
                'message' => "Recount removed. Original quantities restored for {$restoredCount} products.",
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to remove recount: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Complete recount for a participant
     */
    public function completeRecount(InventoryAudit $audit, int $participantId): bool
    {
        try {
            DB::beginTransaction();

            // Update participant status using DB facade
            DB::table('inventory_audits_participantes')
                ->where('inventory_audit_id', $audit->id)
                ->where('user_id', $participantId)
                ->update([
                    'status' => 'sent',
                    'is_in_recount_mode' => false,
                    'updated_at' => now(),
                ]);

            // Mark recount records as completed
            DB::table('inventory_audit_recounts')
                ->where('inventory_audit_id', $audit->id)
                ->where('participant_id', $participantId)
                ->update([
                    'is_recount_mode' => false,
                    'updated_at' => now(),
                ]);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to complete recount: " . $e->getMessage());
            return false;
        }
    }
}
