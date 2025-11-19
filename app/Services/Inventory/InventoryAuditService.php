<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryAudit;
use App\Models\Inventory\InventoryAuditsParticipante;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventoryAuditService
{
    /**
     * Get paginated audits with participants
     */
    public function paginate(array $filters = [], int $perPage = 15)
    {
        $query = InventoryAudit::with(['creator', 'participants.user', 'service', 'pharmacyStockage', 'generalStockage'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by creator
        if (!empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        // Search by name
        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate($perPage);
    }

    /**
     * Create audit with participants
     */
    public function create(array $data): InventoryAudit
    {
        return DB::transaction(function () use ($data) {
            // Create the audit
            $audit = InventoryAudit::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'created_by' => Auth::id(),
                'status' => 'draft',
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'is_global' => $data['is_global'] ?? false,
                'is_pharmacy_wide' => $data['is_pharmacy_wide'] ?? null,
                'service_id' => $data['service_id'] ?? null,
                'stockage_id' => $data['stockage_id'] ?? null,
            ]);

            // Attach participants if provided
            if (!empty($data['participants'])) {
                $this->syncParticipants($audit, $data['participants']);
            }

            return $audit->fresh(['participants.user', 'creator', 'service', 'pharmacyStockage', 'generalStockage']);
        });
    }

    /**
     * Update audit and participants
     */
    public function update(InventoryAudit $audit, array $data): InventoryAudit
    {
        return DB::transaction(function () use ($audit, $data) {
            // Update audit details
            $audit->update([
                'name' => $data['name'] ?? $audit->name,
                'description' => $data['description'] ?? $audit->description,
                'scheduled_at' => $data['scheduled_at'] ?? $audit->scheduled_at,
                'status' => $data['status'] ?? $audit->status,
                'is_global' => $data['is_global'] ?? $audit->is_global,
                'is_pharmacy_wide' => $data['is_pharmacy_wide'] ?? $audit->is_pharmacy_wide,
                'service_id' => $data['service_id'] ?? $audit->service_id,
                'stockage_id' => $data['stockage_id'] ?? $audit->stockage_id,
            ]);

            // Update participants if provided
            if (isset($data['participants'])) {
                $this->syncParticipants($audit, $data['participants']);
            }

            return $audit->fresh(['participants.user', 'creator', 'service', 'pharmacyStockage', 'generalStockage']);
        });
    }

    /**
     * Sync participants for an audit
     */
    public function syncParticipants(InventoryAudit $audit, array $participants): void
    {
        // Delete existing participants
        $audit->participants()->delete();

        // Create new participants
        foreach ($participants as $participant) {
            InventoryAuditsParticipante::create([
                'inventory_audit_id' => $audit->id,
                'user_id' => $participant['user_id'],
                'is_participant' => $participant['is_participant'] ?? true,
                'is_able_to_see' => $participant['is_able_to_see'] ?? true,
            ]);
        }
    }

    /**
     * Add a single participant
     */
    public function addParticipant(InventoryAudit $audit, int $userId, bool $isParticipant = true, bool $isAbleToSee = true): InventoryAuditsParticipante
    {
        // Check if participant already exists
        $existing = InventoryAuditsParticipante::where('inventory_audit_id', $audit->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            // Update existing participant
            $existing->update([
                'is_participant' => $isParticipant,
                'is_able_to_see' => $isAbleToSee,
            ]);
            return $existing;
        }

        // Create new participant
        return InventoryAuditsParticipante::create([
            'inventory_audit_id' => $audit->id,
            'user_id' => $userId,
            'is_participant' => $isParticipant,
            'is_able_to_see' => $isAbleToSee,
        ]);
    }

    /**
     * Remove a participant
     */
    public function removeParticipant(InventoryAudit $audit, int $userId): bool
    {
        return InventoryAuditsParticipante::where('inventory_audit_id', $audit->id)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Start an audit
     */
    public function start(InventoryAudit $audit): InventoryAudit
    {
        $audit->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return $audit->fresh();
    }

    /**
     * Complete an audit
     */
    public function complete(InventoryAudit $audit): InventoryAudit
    {
        $audit->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return $audit->fresh();
    }

    /**
     * Delete an audit
     */
    public function delete(InventoryAudit $audit): bool
    {
        return DB::transaction(function () use ($audit) {
            // Delete all participants
            $audit->participants()->delete();
            
            // Delete the audit
            return $audit->delete();
        });
    }

    /**
     * Get audits for a specific user (as participant or viewer)
     */
    public function getUserAudits(int $userId, int $perPage = 15)
    {
        return InventoryAudit::with(['creator', 'participants.user'])
            ->whereHas('participants', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where(function ($q) {
                        $q->where('is_participant', true)
                          ->orWhere('is_able_to_see', true);
                    });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Check if user is participant
     */
    public function isParticipant(InventoryAudit $audit, int $userId): bool
    {
        return InventoryAuditsParticipante::where('inventory_audit_id', $audit->id)
            ->where('user_id', $userId)
            ->where('is_participant', true)
            ->exists();
    }

    /**
     * Check if user can view
     */
    public function canView(InventoryAudit $audit, int $userId): bool
    {
        // Creator can always view
        if ($audit->created_by === $userId) {
            return true;
        }

        // Check if user has view permission
        return InventoryAuditsParticipante::where('inventory_audit_id', $audit->id)
            ->where('user_id', $userId)
            ->where('is_able_to_see', true)
            ->exists();
    }

    /**
     * Get audit items with participant status
     */
    public function getAuditItems(InventoryAudit $audit): array
    {
        // Get items from inventory_audits_product table with participant status
        return DB::table('inventory_audits_product as iap')
            ->leftJoin('inventory_audits_participantes as part', function($join) use ($audit) {
                $join->on('part.user_id', '=', 'iap.participant_id')
                     ->where('part.inventory_audit_id', '=', $audit->id);
            })
            ->where('iap.inventory_audit_id', $audit->id)
            ->select(
                'iap.*',
                'part.status as participant_status' // Add participant status
            )
            ->get()
            ->toArray();
    }

    /**
     * Bulk update audit items
     */
    public function bulkUpdateItems(InventoryAudit $audit, array $items, ?string $status = null): array
    {
        return DB::transaction(function () use ($audit, $items, $status) {
            $results = [];

            foreach ($items as $item) {
                $difference = null;
                $variancePercent = 0;
                
                if (isset($item['actual_quantity']) && isset($item['theoretical_quantity'])) {
                    $difference = $item['actual_quantity'] - $item['theoretical_quantity'];
                    if ($item['theoretical_quantity'] > 0) {
                        $variancePercent = ($difference / $item['theoretical_quantity']) * 100;
                    }
                }

                // Prepare participant_id (ensure it's properly set)
                $participantId = $item['participant_id'] ?? null;

                // Build unique constraint keys for updateOrInsert
                // CRITICAL: inventory_audit_id + product_id + product_type + participant_id = UNIQUE
                $uniqueKeys = [
                    'inventory_audit_id' => $audit->id,
                    'product_id' => $item['product_id'],
                    'product_type' => $item['product_type'] ?? 'stock',
                    'participant_id' => $participantId, // MUST be in WHERE clause
                ];

                // Check if record exists
                $existingRecord = DB::table('inventory_audits_product')
                    ->where($uniqueKeys)
                    ->exists();

                // Update or Insert (participant_id is in WHERE, not in UPDATE)
                DB::table('inventory_audits_product')
                    ->updateOrInsert(
                        $uniqueKeys, // WHERE clause with all unique fields
                        [
                            // UPDATE clause - don't include participant_id here since it's in WHERE
                            'stockage_id' => $item['stockage_id'] ?? null,
                            'theoretical_quantity' => $item['theoretical_quantity'],
                            'actual_quantity' => $item['actual_quantity'],
                            'difference' => $difference,
                            'variance_percent' => $variancePercent,
                            'notes' => $item['notes'] ?? null,
                            'audited_by' => auth()->id(),
                            'audited_at' => now(),
                            'status' => 'completed',
                            'created_at' => $existingRecord ? DB::raw('created_at') : now(),
                            'updated_at' => now(),
                        ]
                    );

                // Get the ID of the record using ALL unique keys
                $id = DB::table('inventory_audits_product')
                    ->where($uniqueKeys)
                    ->value('id');

                $results[] = $id;
            }

            // Update audit status if provided
            if ($status && $audit->status !== 'completed') {
               
                
                // Set started_at if moving to in_progress
                if ($status === 'in_progress' && !$audit->started_at) {
                    $audit->update(['started_at' => now()]);
                }
            }

            // Update participant status if participant_id is provided in any item
            $participantId = $items[0]['participant_id'] ?? null;
            if ($participantId && $status) {
                DB::table('inventory_audits_participantes')
                    ->where('inventory_audit_id', $audit->id)
                    ->where('user_id', $participantId)
                    ->update([
                        'status' => $status,
                        'updated_at' => now()
                    ]);
            }

            return $results;
        });
    }

    /**
     * Generate PDF report
     */
    public function generatePDF(InventoryAudit $audit)
    {
        // Load audit with relationships
        $audit->load(['creator', 'service', 'pharmacyStockage', 'generalStockage']);
        
        // Get audit items
        $items = DB::table('inventory_audits_product')
            ->where('inventory_audit_id', $audit->id)
            ->get();

        // TODO: Implement PDF generation using TCPDF or DomPDF
        // For now, return a simple response
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($audit->creator->name);
        $pdf->SetTitle('Inventory Audit Report - ' . $audit->name);
        
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        
        $html = view('pdfs.inventory-audit', compact('audit', 'items'))->render();
        $pdf->writeHTML($html, true, false, true, false, '');
        
        return $pdf;
    }
}
