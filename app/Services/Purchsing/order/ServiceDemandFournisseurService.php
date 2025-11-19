<?php

namespace App\Services\Purchsing\order;

use App\Models\FactureProforma;
use App\Models\FactureProformaProduct;
use App\Models\ServiceDemandItemFournisseur;
use App\Models\ServiceDemendPurchcing;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceDemandFournisseurService
{
    /**
     * Assign a fournisseur to a specific service demand item
     */
    public function assignFournisseur($demandId, $itemId, array $assignmentData)
    {
        try {
            $demand = ServiceDemendPurchcing::findOrFail($demandId);
            $item = $demand->items()->findOrFail($itemId);

            // Check if item status allows assignment
            if ($item->status !== 'pending') {
                throw new \Exception('Can only assign fournisseurs to pending items');
            }

            // Check if this fournisseur is already assigned to this item
            $existingAssignment = ServiceDemandItemFournisseur::where([
                'service_demand_purchasing_item_id' => $itemId,
                'fournisseur_id' => $assignmentData['fournisseur_id'],
            ])->first();

            if ($existingAssignment) {
                throw new \Exception('This supplier is already assigned to this item');
            }

            // Check if total assigned quantity doesn't exceed item quantity
            $totalAssigned = $item->fournisseurAssignments->sum('assigned_quantity');
            if ($totalAssigned + $assignmentData['assigned_quantity'] > $item->quantity) {
                throw new \Exception('Assigned quantity exceeds remaining item quantity');
            }

            $assignment = ServiceDemandItemFournisseur::create([
                'service_demand_purchasing_item_id' => $itemId,
                'fournisseur_id' => $assignmentData['fournisseur_id'],
                'assigned_quantity' => $assignmentData['assigned_quantity'],
                'unit_price' => $assignmentData['unit_price'] ?? null,
                'unit' => $assignmentData['unit'] ?? ($item->product->unit ?? $item->pharmacyProduct->unit ?? 'unit'),
                'notes' => $assignmentData['notes'] ?? null,
                'assigned_by' => Auth::id(),
                'status' => 'pending',
            ]);

            $assignment->load(['fournisseur:id,company_name,contact_person,email,phone', 'assignedBy:id,name']);

            return $assignment;
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Item or demand not found');
        }
    }

    /**
     * Bulk assign fournisseurs to multiple items
     */
    public function bulkAssignFournisseurs($demandId, array $assignments)
    {
        try {
            DB::beginTransaction();

            $demand = ServiceDemendPurchcing::with(['items.product', 'items.pharmacyProduct'])->findOrFail($demandId);
            $createdAssignments = [];
            $errors = [];

            foreach ($assignments as $assignmentData) {
                try {
                    $item = $demand->items()->findOrFail($assignmentData['item_id']);

                    // Check if item status allows assignment
                    if ($item->status !== 'pending') {
                        $errors[] = 'Item '.($item->product->name ?? $item->pharmacyProduct->name ?? 'Unknown').' is not in pending status';

                        continue;
                    }

                    // Check for existing assignment
                    $existingAssignment = ServiceDemandItemFournisseur::where([
                        'service_demand_purchasing_item_id' => $assignmentData['item_id'],
                        'fournisseur_id' => $assignmentData['fournisseur_id'],
                    ])->first();

                    if ($existingAssignment) {
                        $errors[] = 'Supplier already assigned to item '.($item->product->name ?? $item->pharmacyProduct->name ?? 'Unknown');

                        continue;
                    }

                    // Check quantity constraints
                    $totalAssigned = $item->fournisseurAssignments->sum('assigned_quantity');

                    $assignment = ServiceDemandItemFournisseur::create([
                        'service_demand_purchasing_item_id' => $assignmentData['item_id'],
                        'fournisseur_id' => $assignmentData['fournisseur_id'],
                        'assigned_quantity' => $assignmentData['assigned_quantity'],
                        'unit_price' => $assignmentData['unit_price'] ?? null,
                        'unit' => $assignmentData['unit'] ?? ($item->product->unit ?? $item->pharmacyProduct->unit ?? 'unit'),
                        'notes' => $assignmentData['notes'] ?? null,
                        'assigned_by' => Auth::id(),
                        'status' => 'pending',
                    ]);

                    $assignment->load(['fournisseur:id,company_name,contact_person,email,phone', 'assignedBy:id,name']);
                    $createdAssignments[] = $assignment;
                } catch (\Exception $e) {
                    $errors[] = 'Failed to assign item: '.$e->getMessage();
                }
            }

            DB::commit();

            return [
                'assignments' => $createdAssignments,
                'errors' => $errors,
            ];
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw new \Exception('Demand not found');
        }
    }

    /**
     * Update a fournisseur assignment
     */
    public function updateFournisseurAssignment($demandId, $itemId, $assignmentId, array $updateData)
    {
        try {
            $assignment = ServiceDemandItemFournisseur::where([
                'id' => $assignmentId,
                'service_demand_purchasing_item_id' => $itemId,
            ])->firstOrFail();

            $item = $assignment->item;

            // Check quantity constraints (excluding current assignment)
            $totalAssigned = $item->fournisseurAssignments()
                ->where('id', '!=', $assignmentId)
                ->sum('assigned_quantity');

            if ($totalAssigned + $updateData['assigned_quantity'] > $item->quantity) {
                throw new \Exception('Updated quantity exceeds item quantity');
            }

            $assignment->update([
                'assigned_quantity' => $updateData['assigned_quantity'],
                'unit_price' => $updateData['unit_price'] ?? null,
                'unit' => $updateData['unit'] ?? $assignment->unit,
                'notes' => $updateData['notes'] ?? null,
                'status' => $updateData['status'] ?? $assignment->status,
            ]);

            $assignment->load(['fournisseur:id,company_name,contact_person,email,phone', 'assignedBy:id,name']);

            return $assignment;
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Assignment not found');
        }
    }

    /**
     * Remove a fournisseur assignment
     */
    public function removeFournisseurAssignment($demandId, $itemId, $assignmentId)
    {
        try {
            $assignment = ServiceDemandItemFournisseur::where([
                'id' => $assignmentId,
                'service_demand_purchasing_item_id' => $itemId,
            ])->firstOrFail();

            // Check if assignment can be removed
            if ($assignment->status === 'received') {
                throw new \Exception('Cannot remove received assignments');
            }

            $assignment->delete();

            return true;
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Assignment not found');
        }
    }

    /**
     * Create facture proforma from service demand assignments
     */
    public function createFactureProformaFromAssignments($demandId, $fournisseurId, array $assignmentIds)
    {
        try {
            DB::beginTransaction();

            $demand = ServiceDemendPurchcing::findOrFail($demandId);

            // Verify all assignments belong to the specified fournisseur and demand
            $assignments = ServiceDemandItemFournisseur::with(['item.product', 'item.pharmacyProduct'])
                ->whereIn('id', $assignmentIds)
                ->where('fournisseur_id', $fournisseurId)
                ->whereHas('item', function ($query) use ($demandId) {
                    $query->where('service_demand_purchasing_id', $demandId);
                })
                ->get();

            if ($assignments->count() !== count($assignmentIds)) {
                throw new \Exception('Some assignments not found or do not belong to the specified supplier');
            }

            // Create facture proforma
            $facture = FactureProforma::create([
                'factureProformaCode' => $this->generateFactureProformaCode(),
                'fournisseur_id' => $fournisseurId,
                'service_demand_purchasing_id' => $demandId,
                'created_by' => Auth::id(),
                'status' => 'draft',
            ]);

            // Create facture proforma products from assignments
            foreach ($assignments as $assignment) {
                $fpData = [
                    'factureproforma_id' => $facture->id,
                    'quantity' => $assignment->assigned_quantity,
                    'price' => $assignment->unit_price ?? 0,
                    'unit' => $assignment->unit ?? ($assignment->item->product->unit ?? $assignment->item->pharmacyProduct->unit ?? 'unit'),
                ];

                if (! empty($assignment->item->product_id)) {
                    $fpData['product_id'] = $assignment->item->product_id;
                } elseif (! empty($assignment->item->pharmacy_product_id)) {
                    $fpData['pharmacy_product_id'] = $assignment->item->pharmacy_product_id;
                }

                FactureProformaProduct::create($fpData);

                // Update assignment status
                $assignment->update(['status' => 'ordered']);
            }

            DB::commit();

            $facture->load([
                'fournisseur:id,company_name,contact_person,email,phone',
                'serviceDemand.service:id,name',
                'creator:id,name',
                'products.product:id,name,product_code',
                'products.pharmacyProduct:id,name,sku',
            ]);

            return $facture;
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw new \Exception('Demand not found');
        }
    }

    /**
     * Get service demand assignment summary
     */
    public function getAssignmentSummary($demandId)
    {
        try {
            $demand = ServiceDemendPurchcing::with([
                'items.product:id,name,product_code',
                'items.pharmacyProduct:id,name,sku',
                'items.fournisseurAssignments.fournisseur:id,company_name',
            ])->findOrFail($demandId);

            $summary = [
                'demand_id' => $demand->id,
                'demand_code' => $demand->demand_code,
                'total_items' => $demand->items->count(),
                'fully_assigned_items' => 0,
                'partially_assigned_items' => 0,
                'unassigned_items' => 0,
                'assignments_by_supplier' => [],
            ];

            $supplierAssignments = [];

            foreach ($demand->items as $item) {
                $totalAssigned = $item->fournisseurAssignments->sum('assigned_quantity');

                if ($totalAssigned === 0) {
                    $summary['unassigned_items']++;
                } elseif ($totalAssigned >= $item->quantity) {
                    $summary['fully_assigned_items']++;
                } else {
                    $summary['partially_assigned_items']++;
                }

                // Group by supplier
                foreach ($item->fournisseurAssignments as $assignment) {
                    $supplierId = $assignment->fournisseur_id;

                    if (! isset($supplierAssignments[$supplierId])) {
                        $supplierAssignments[$supplierId] = [
                            'supplier_id' => $supplierId,
                            'supplier_name' => $assignment->fournisseur->company_name,
                            'total_items' => 0,
                            'total_quantity' => 0,
                            'total_amount' => 0,
                        ];
                    }

                    $supplierAssignments[$supplierId]['total_items']++;
                    $supplierAssignments[$supplierId]['total_quantity'] += $assignment->assigned_quantity;
                    $supplierAssignments[$supplierId]['total_amount'] += $assignment->total_amount ?? 0;
                }
            }

            $summary['assignments_by_supplier'] = array_values($supplierAssignments);

            return $summary;
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Demand not found');
        }
    }

    /**
     * Generate unique facture proforma code
     */
    private function generateFactureProformaCode()
    {
        $count = FactureProforma::count() + 1;

        return 'FP-'.date('Ymd').'-'.str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
