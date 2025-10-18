<?php

namespace App\Services;

use App\Models\BonRetour;
use App\Models\BonRetourItem;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class BonRetourService
{
    /**
     * Create a new bon retour
     */
    public function create(array $data): BonRetour
    {
        DB::beginTransaction();
        try {
            // Create bon retour
            $bonRetour = BonRetour::create([
                'bon_entree_id' => $data['bon_entree_id'] ?? null,
                'fournisseur_id' => $data['fournisseur_id'],
                'return_type' => $data['return_type'],
                'status' => $data['status'] ?? 'draft',
                'service_id' => $data['service_id'] ?? null,
                'reason' => $data['reason'] ?? null,
                'return_date' => $data['return_date'] ?? now(),
                'reference_invoice' => $data['reference_invoice'] ?? null,
                'credit_note_received' => $data['credit_note_received'] ?? false,
                'credit_note_number' => $data['credit_note_number'] ?? null,
                'attachments' => $data['attachments'] ?? [],
                'created_by' => auth()->id(),
            ]);

            // Create items
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $this->createItem($bonRetour, $itemData);
                }
            }

            // Calculate total
            $bonRetour->calculateTotal();

            DB::commit();
            return $bonRetour;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update bon retour
     */
    public function update(BonRetour $bonRetour, array $data): BonRetour
    {
        DB::beginTransaction();
        try {
            // Update bon retour
            $bonRetour->update([
                'fournisseur_id' => $data['fournisseur_id'] ?? $bonRetour->fournisseur_id,
                'return_type' => $data['return_type'] ?? $bonRetour->return_type,
                'service_id' => $data['service_id'] ?? $bonRetour->service_id,
                'reason' => $data['reason'] ?? $bonRetour->reason,
                'return_date' => $data['return_date'] ?? $bonRetour->return_date,
                'reference_invoice' => $data['reference_invoice'] ?? $bonRetour->reference_invoice,
                'credit_note_received' => $data['credit_note_received'] ?? $bonRetour->credit_note_received,
                'credit_note_number' => $data['credit_note_number'] ?? $bonRetour->credit_note_number,
                'attachments' => $data['attachments'] ?? $bonRetour->attachments,
            ]);

            // Handle items update
            if (isset($data['items'])) {
                // Delete removed items
                $existingItemIds = collect($data['items'])->pluck('id')->filter();
                $bonRetour->items()->whereNotIn('id', $existingItemIds)->delete();

                // Update or create items
                foreach ($data['items'] as $itemData) {
                    if (isset($itemData['id'])) {
                        $this->updateItem($itemData['id'], $itemData);
                    } else {
                        $this->createItem($bonRetour, $itemData);
                    }
                }
            }

            // Recalculate total
            $bonRetour->calculateTotal();

            DB::commit();
            return $bonRetour;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create bon retour item
     */
    protected function createItem(BonRetour $bonRetour, array $data): BonRetourItem
    {
        return BonRetourItem::create([
            'bon_retour_id' => $bonRetour->id,
            'product_id' => $data['product_id'],
            'bon_entree_item_id' => $data['bon_entree_item_id'] ?? null,
            'batch_number' => $data['batch_number'] ?? null,
            'serial_number' => $data['serial_number'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'quantity_returned' => $data['quantity_returned'],
            'unit_price' => $data['unit_price'],
            'tva' => $data['tva'] ?? 0,
            'total_amount' => $data['total_amount'] ?? 0,
            'return_reason' => $data['return_reason'],
            'remarks' => $data['remarks'] ?? null,
            'storage_location' => $data['storage_location'] ?? null,
        ]);
    }

    /**
     * Update bon retour item
     */
    protected function updateItem(int $itemId, array $data): BonRetourItem
    {
        $item = BonRetourItem::findOrFail($itemId);
        
        $item->update([
            'product_id' => $data['product_id'] ?? $item->product_id,
            'batch_number' => $data['batch_number'] ?? $item->batch_number,
            'serial_number' => $data['serial_number'] ?? $item->serial_number,
            'expiry_date' => $data['expiry_date'] ?? $item->expiry_date,
            'quantity_returned' => $data['quantity_returned'] ?? $item->quantity_returned,
            'unit_price' => $data['unit_price'] ?? $item->unit_price,
            'tva' => $data['tva'] ?? $item->tva,
            'return_reason' => $data['return_reason'] ?? $item->return_reason,
            'remarks' => $data['remarks'] ?? $item->remarks,
            'storage_location' => $data['storage_location'] ?? $item->storage_location,
        ]);

        $item->calculateTotal();

        return $item;
    }

    /**
     * Delete bon retour
     */
    public function delete(BonRetour $bonRetour): bool
    {
        return $bonRetour->delete();
    }

    /**
     * Approve bon retour
     */
    public function approve(BonRetour $bonRetour, int $userId): BonRetour
    {
        return $bonRetour->approve($userId);
    }

    /**
     * Generate PDF
     */
    public function generatePdf(BonRetour $bonRetour, string $template = 'default')
    {
        $bonRetour->load(['fournisseur', 'items.product', 'creator', 'approver']);

        $data = [
            'bonRetour' => $bonRetour,
            'company' => config('app.company_name', 'Hospital Management System'),
            'date' => now()->format('Y-m-d'),
        ];

        $view = $template === 'pch' 
            ? 'pdf.bon-retour-pch' 
            : 'pdf.bon-retour';

        return Pdf::loadView($view, $data)
                  ->setPaper('a4');
    }

    /**
     * Get statistics
     */
    public function getStatistics(array $filters = []): array
    {
        $query = BonRetour::query();

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->whereDate('return_date', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('return_date', '<=', $filters['date_to']);
        }
        if (isset($filters['service_id'])) {
            $query->where('service_id', $filters['service_id']);
        }

        return [
            'total_returns' => $query->count(),
            'total_amount' => $query->sum('total_amount'),
            'by_status' => $query->groupBy('status')
                                ->selectRaw('status, count(*) as count, sum(total_amount) as total')
                                ->get(),
            'by_return_type' => $query->groupBy('return_type')
                                     ->selectRaw('return_type, count(*) as count, sum(total_amount) as total')
                                     ->get(),
            'pending_approvals' => BonRetour::pending()->count(),
            'credit_notes_pending' => BonRetour::where('credit_note_received', false)
                                              ->where('status', 'completed')
                                              ->count(),
        ];
    }
}
