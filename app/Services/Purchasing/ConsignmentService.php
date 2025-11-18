<?php

namespace App\Services\Purchasing;

use App\Events\Purchasing\ConsignmentReceivedEvent;
use App\Models\BonCommend;
use App\Models\BonEntree;
use App\Models\BonReception;
use App\Models\BonReceptionItem;
use App\Models\Fournisseur;
use App\Models\Purchasing\ConsignmentReception;
use App\Models\Purchasing\ConsignmentReceptionItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConsignmentService
{
    /**
     * Get all consignment receptions with pagination and filters
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ConsignmentReception::with([
            'fournisseur',
            'items.product',
            'createdBy',
            'confirmedBy',
        ])->latest('reception_date');

        // Filter by supplier
        if (! empty($filters['fournisseur_id'])) {
            $query->where('fournisseur_id', $filters['fournisseur_id']);
        }

        // Filter by date range
        if (! empty($filters['date_from'])) {
            $query->where('reception_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->where('reception_date', '<=', $filters['date_to']);
        }

        // Filter by consignment code
        if (! empty($filters['consignment_code'])) {
            $query->where('consignment_code', 'like', '%'.$filters['consignment_code'].'%');
        }

        // Filter for uninvoiced consignments
        if (! empty($filters['has_uninvoiced'])) {
            $query->hasUninvoiced();
        }

        return $query->paginate($perPage);
    }

    /**
     * Create new consignment reception
     *
     * IMPORTANT: Only creates ConsignmentReception and items.
     * BonReception + BonEntree are created later during invoicing workflow.
     */
    public function createReception(array $data): ConsignmentReception
    {
        return DB::transaction(function () use ($data) {
            // Validate supplier
            $supplier = Fournisseur::findOrFail($data['fournisseur_id']);

            // Create consignment reception (NO BonReception yet)
            $consignment = ConsignmentReception::create([
                'fournisseur_id' => $supplier->id,
                'reception_date' => $data['reception_date'] ?? now(),
                'unit_of_measure' => $data['unit_of_measure'] ?? 'unit',
                'origin_note' => $data['origin_note'] ?? null,
                'reception_type' => $data['reception_type'] ?? 'consignment',
                'operation_type' => $data['operation_type'] ?? 'manual',
                'created_by' => Auth::id(),
                'bon_reception_id' => null,  // Will be set during invoicing
                'bon_entree_id' => null,     // Will be set during invoicing
            ]);

            // Add items if provided
            if (! empty($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    // Determine product_id based on type
                    $productId = null;

                    if (isset($itemData['product_type'])) {
                        if ($itemData['product_type'] === 'stock' && ! empty($itemData['product_id'])) {
                            $productId = $itemData['product_id'];
                        } elseif ($itemData['product_type'] === 'pharmacy' && ! empty($itemData['pharmacy_product_id'])) {
                            // For pharmacy products, store the pharmacy_product_id as product_id
                            // (they can coexist since they reference different tables)
                            $productId = $itemData['pharmacy_product_id'];
                        }
                    } else {
                        // Fallback to product_id for backward compatibility
                        $productId = $itemData['product_id'] ?? null;
                    }

                    if ($productId) {
                        ConsignmentReceptionItem::create([
                            'consignment_reception_id' => $consignment->id,
                            'product_id' => $productId,
                            'product_type' => $itemData['product_type'] ?? 'stock',
                            'quantity_received' => $itemData['quantity_received'],
                            'unit_price' => $itemData['unit_price'] ?? 0,
                            'quantity_consumed' => 0,
                            'quantity_invoiced' => 0,
                        ]);
                    }
                }
            }

            // Broadcast real-time event
            event(new ConsignmentReceivedEvent($consignment));

            \Log::info('ConsignmentReception created (BonReception will be created during invoicing)', [
                'consignment_id' => $consignment->id,
                'consignment_code' => $consignment->consignment_code,
                'supplier_id' => $supplier->id,
                'items_count' => $consignment->items()->count(),
            ]);

            return $consignment->fresh([
                'fournisseur',
                'items.product',
                'createdBy',
            ]);
        });
    }

    /**
     * Get single consignment by ID
     */
    public function getById(int $id): ConsignmentReception
    {
        return ConsignmentReception::with([
            'fournisseur',
            'items.product',
            'createdBy',
            'confirmedBy',
            'invoices',
        ])->findOrFail($id);
    }

    /**
     * Get uninvoiced items for a consignment
     */
    public function getUninvoicedItems(int $consignmentId): Collection
    {
        $consignment = ConsignmentReception::findOrFail($consignmentId);

        return $consignment->items()
            ->with('product')
            ->where('quantity_consumed', '>', DB::raw('quantity_invoiced'))
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? 'Unknown',
                    'quantity_received' => $item->quantity_received,
                    'quantity_consumed' => $item->quantity_consumed,
                    'quantity_invoiced' => $item->quantity_invoiced,
                    'quantity_uninvoiced' => $item->quantity_uninvoiced,
                    'unit_price' => $item->unit_price,
                    'uninvoiced_value' => $item->uninvoiced_value,
                ];
            });
    }

    /**
     * Create invoice (BonCommend) from consumed consignment items
     *
     * This workflow automatically creates:
     * 1. BonReception (purchasing receipt document) - if not already exists
     * 2. BonEntree (warehouse entry) - linked to BonReception
     * 3. BonCommend (invoice to supplier)
     */
    public function createInvoiceFromConsumption(int $consignmentId, array $data): BonCommend
    {
        return DB::transaction(function () use ($consignmentId, $data) {
            $consignment = ConsignmentReception::with('fournisseur')->findOrFail($consignmentId);

            // Validate that there are uninvoiced items
            if (! $consignment->hasUninvoicedItems()) {
                throw new \Exception('No uninvoiced items found for this consignment.');
            }

            // Get items to invoice (either specified or all uninvoiced)
            $itemsToInvoice = ! empty($data['items'])
                ? $data['items']
                : $this->getUninvoicedItems($consignmentId)->pluck('id')->toArray();

            if (empty($itemsToInvoice)) {
                throw new \Exception('No items specified for invoicing.');
            }

            // Calculate total amount and validate payment status
            $totalAmount = 0;
            $invoiceItems = [];

            foreach ($itemsToInvoice as $itemId) {
                $consignmentItem = ConsignmentReceptionItem::with('product')->findOrFail($itemId);

                // Validate item belongs to this consignment
                if ($consignmentItem->consignment_reception_id !== $consignmentId) {
                    throw new \Exception("Item {$itemId} does not belong to this consignment.");
                }

                // Validate item can be invoiced
                if (! $consignmentItem->canBeInvoiced()) {
                    throw new \Exception("Item {$itemId} has no uninvoiced consumption.");
                }

                // CRITICAL: Validate that all ficheNavetteItems are paid before creating invoice
                $this->validateConsignmentItemPaid($consignmentItem);

                $quantityToInvoice = $consignmentItem->quantity_uninvoiced;
                $itemTotal = $quantityToInvoice * $consignmentItem->unit_price;
                $totalAmount += $itemTotal;

                $invoiceItems[] = [
                    'consignment_item_id' => $consignmentItem->id,
                    'product_id' => $consignmentItem->product_id,
                    'quantity' => $quantityToInvoice,
                    'unit_price' => $consignmentItem->unit_price,
                    'total' => $itemTotal,
                ];

                // Update invoiced quantity
                $consignmentItem->incrementInvoiced($quantityToInvoice);
            }

            // STEP 1: Create BonReception (if not already created)
            if (! $consignment->bon_reception_id) {
                $bonReception = $this->createBonReceptionForConsignment($consignment, $data);
                $consignment->update(['bon_reception_id' => $bonReception->id]);
            } else {
                $bonReception = $consignment->bonReception;
            }

            // STEP 2: Create BonCommend (purchase order/invoice)
            $bonCommend = BonCommend::create([
                'fournisseur_id' => $consignment->fournisseur_id,
                'is_from_consignment' => true,
                'consignment_source_id' => $consignment->id,
                'total_amount' => $totalAmount,
                'status' => $data['status'] ?? 'pending',
                'notes' => $data['notes'] ?? "Invoice for consignment {$consignment->consignment_code}",
                'created_by' => Auth::id(),
            ]);

            \Log::info('Complete consignment invoice workflow executed', [
                'consignment_id' => $consignment->id,
                'bon_commend_id' => $bonCommend->id,
                'bon_reception_id' => $bonReception->id,
                'total_amount' => $totalAmount,
                'items_count' => count($invoiceItems),
            ]);

            return $bonCommend->fresh(['fournisseur', 'consignmentSource', 'bonReception']);
        });
    }

    /**
     * Get consignment statistics by supplier
     */
    public function getSupplierStats(int $supplierId): array
    {
        $consignments = ConsignmentReception::bySupplier($supplierId)->get();

        return [
            'total_consignments' => $consignments->count(),
            'total_received' => $consignments->sum('total_received'),
            'total_consumed' => $consignments->sum('total_consumed'),
            'total_uninvoiced' => $consignments->sum('total_uninvoiced'),
            'uninvoiced_value' => $consignments->sum(fn ($c) => $c->items->sum('uninvoiced_value')),
        ];
    }

    /**
     * Confirm consignment reception
     */
    public function confirmReception(int $consignmentId): ConsignmentReception
    {
        return DB::transaction(function () use ($consignmentId) {
            $consignment = ConsignmentReception::findOrFail($consignmentId);

            if ($consignment->confirmed_at) {
                throw new \Exception('Consignment already confirmed.');
            }

            $consignment->update([
                'confirmed_at' => now(),
                'confirmed_by' => Auth::id(),
            ]);

            return $consignment->fresh(['fournisseur', 'items.product', 'confirmedBy']);
        });
    }

    /**
     * Get consumption history for a consignment
     */
    public function getConsumptionHistory(int $consignmentId): Collection
    {
        $consignment = ConsignmentReception::findOrFail($consignmentId);

        return $consignment->items()
            ->with(['product', 'ficheItems.ficheNavette.patient'])
            ->where('quantity_consumed', '>', 0)
            ->get()
            ->flatMap(function ($item) {
                return $item->ficheItems->map(function ($ficheItem) use ($item) {
                    return [
                        'consignment_item_id' => $item->id,
                        'product_name' => $item->product->name ?? 'Unknown',
                        'patient_name' => $ficheItem->ficheNavette->patient->full_name ?? 'Unknown',
                        'fiche_navette_id' => $ficheItem->fiche_navette_id,
                        'quantity' => $ficheItem->quantity ?? 1,
                        'payment_status' => $ficheItem->payment_status,
                        'consumed_at' => $ficheItem->updated_at,
                    ];
                });
            })
            ->sortByDesc('consumed_at')
            ->values();
    }

    /**
     * Create BonReception and BonEntree for consignment (purchasing audit trail)
     */
    protected function createBonReceptionForConsignment(ConsignmentReception $consignment, array $data): BonReception
    {
        // Create BonReception (goods receipt document)
        $bonReception = BonReception::create([
            'fournisseur_id' => $consignment->fournisseur_id,
            'date_reception' => $consignment->reception_date,
            'is_from_consignment' => true,
            'consignment_reception_id' => $consignment->id,
            'status' => 'completed',
            'is_confirmed' => true,
            'confirmed_at' => now(),
            'confirmed_by' => Auth::id(),
            'created_by' => Auth::id(),
            'received_by' => Auth::id(),
            'nombre_colis' => $consignment->items()->count(),
            'observation' => $data['origin_note'] ?? "Auto-created from consignment {$consignment->consignment_code}",
        ]);

        // Create BonReception items from consignment items
        if ($consignment->items()->count() > 0) {
            foreach ($consignment->items as $consignmentItem) {
                BonReceptionItem::create([
                    'bon_reception_id' => $bonReception->id,
                    'product_id' => $consignmentItem->product_id,
                    'quantity_ordered' => $consignmentItem->quantity_received,
                    'quantity_received' => $consignmentItem->quantity_received,
                    'unit_price' => $consignmentItem->unit_price,
                    'total' => $consignmentItem->quantity_received * $consignmentItem->unit_price,
                    'status' => 'received',
                ]);
            }
        }

        // Create BonEntree (warehouse entry document) if service_id provided
        if (! empty($data['service_id'])) {
            $bonEntree = BonEntree::create([
                'bon_reception_id' => $bonReception->id,
                'service_id' => $data['service_id'],
                'date_entree' => $consignment->reception_date,
                'status' => 'completed',
                'created_by' => Auth::id(),
                'notes' => "Auto-created from consignment {$consignment->consignment_code}",
            ]);

            // Link BonEntree back to BonReception
            $bonReception->update(['bon_entree_id' => $bonEntree->id]);
        }

        \Log::info('BonReception auto-created for consignment', [
            'consignment_id' => $consignment->id,
            'consignment_code' => $consignment->consignment_code,
            'bon_reception_id' => $bonReception->id,
            'bon_reception_code' => $bonReception->bonReceptionCode,
        ]);

        return $bonReception;
    }

    /**
     * Validate that all ficheNavetteItems linked to this consignment item are paid
     *
     * Business Rule: Cannot create invoice (BonCommend) until patient has paid for the service
     *
     * @throws \Exception if any unpaid ficheNavetteItem exists
     */
    private function validateConsignmentItemPaid(ConsignmentReceptionItem $consignmentItem): void
    {
        // Get all ficheNavetteItems that reference this consignment item
        $unpaidItems = \App\Models\Reception\ficheNavetteItem::where('consignment_item_id', $consignmentItem->id)
            ->whereIn('payment_status', ['unpaid', 'partially_paid', 'pending'])
            ->with(['ficheNavette', 'prestation'])
            ->get();

        if ($unpaidItems->isNotEmpty()) {
            $unpaidCount = $unpaidItems->count();
            $firstUnpaid = $unpaidItems->first();

            throw new \Exception(
                "Cannot create invoice: {$unpaidCount} unpaid ficheNavetteItem(s) exist for product '{$consignmentItem->product->name}'. ".
                'All patient consultations must be paid before invoicing the supplier. '.
                "First unpaid: FicheNavette #{$firstUnpaid->fiche_navette_id}, Payment Status: {$firstUnpaid->payment_status}"
            );
        }
    }
}
