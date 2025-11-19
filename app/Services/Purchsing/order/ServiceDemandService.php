<?php

namespace App\Services\Purchsing\order;

use App\Models\ServiceDemendPurchcing;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceDemandService
{
    /**
     * Get a single service demand by ID
     */
    public function getById($id)
    {
        try {
            $demand = ServiceDemendPurchcing::with([
                'service',
                'items.product',
                'items.pharmacyProduct',
                'creator',
                'items.fournisseurAssignments.fournisseur:id,company_name,contact_person,email,phone',
                'items.fournisseurAssignments.assignedBy:id,name',
            ])->findOrFail($id);

            // Check authorization: only creator or admin can view
            $currentUser = Auth::user();
            if ($currentUser && !in_array($currentUser->role, ['admin', 'SuperAdmin'])) {
                if ($demand->created_by !== $currentUser->id) {
                    throw new \Exception('Unauthorized to view this demand');
                }
            }

            return $demand;
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Demand not found');
        }
    }

    /**
     * Create a new service demand
     */
    public function create(array $data)
    {
        try {
            DB::beginTransaction();

            $demand = ServiceDemendPurchcing::create([
                'service_id' => $data['service_id'] ?? null,
                'expected_date' => $data['expected_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'is_pharmacy_order' => $data['is_pharmacy_order'] ?? false,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return $demand->load(['service', 'items.product', 'items.pharmacyProduct', 'creator']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing service demand
     */
    public function update($id, array $data)
    {
        try {
            $demand = ServiceDemendPurchcing::findOrFail($id);

            // Only allow updates if status is draft
            if ($demand->status !== 'draft') {
                throw new \Exception('Cannot update demand that has been sent');
            }

            $demand->update([
                'service_id' => $data['service_id'],
                'expected_date' => $data['expected_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_pharmacy_order' => $data['is_pharmacy_order'] ?? $demand->is_pharmacy_order,
            ]);

            return $demand->load(['service', 'items.product', 'items.pharmacyProduct']);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Demand not found');
        }
    }

    /**
     * Delete a service demand
     */
    public function delete($id)
    {
        try {
            $demand = ServiceDemendPurchcing::findOrFail($id);

            // Only allow deletion if status is draft
            if ($demand->status !== 'draft') {
                throw new \Exception('Cannot delete demand that has been sent');
            }

            $demand->delete();

            return true;
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Demand not found');
        }
    }

    /**
     * Send a service demand (change status from draft to sent)
     */
    public function send($id)
    {
        try {
            $demand = ServiceDemendPurchcing::findOrFail($id);

            // Only allow sending if status is draft
            if ($demand->status !== 'draft') {
                throw new \Exception('Demand has already been sent');
            }

            // Check if demand has items
            if ($demand->items()->count() === 0) {
                throw new \Exception('Cannot send demand without items');
            }

            $demand->update(['status' => 'sent']);

            return $demand->load(['service', 'items.product', 'items.pharmacyProduct']);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Demand not found');
        }
    }

    /**
     * Add a workflow tracking note
     */
    public function addWorkflowNote($id, string $note)
    {
        try {
            $serviceDemand = ServiceDemendPurchcing::findOrFail($id);

            // Add the note to existing notes or create new
            $existingNotes = $serviceDemand->notes ? $serviceDemand->notes . "\n" : '';
            $newNote = now()->format('Y-m-d H:i:s') . ' - ' . $note;
            $serviceDemand->notes = $existingNotes . $newNote;
            $serviceDemand->save();

            return $serviceDemand->notes;
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Demand not found');
        }
    }
}
