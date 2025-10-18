<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\CONFIGURATION\Service;
use App\Models\PharmacyMovement;
use App\Models\PharmacyMovementAuditLog;
use App\Models\PharmacyMovementItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PharmacyMovementController extends Controller
{
    /**
     * Display a listing of pharmacy movements.
     */
    public function index(Request $request)
    {
        $query = PharmacyMovement::with([
            'requestingService',
            'providingService',
            'requestingUser',
            'approvingUser',
            'executingUser',
            'patient',
            'items.product',
        ]);

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by urgency level
        if ($request->has('urgency_level') && $request->urgency_level) {
            $query->where('urgency_level', $request->urgency_level);
        }

        // Filter by requesting service
        if ($request->has('requesting_service_id') && $request->requesting_service_id) {
            $query->where('requesting_service_id', $request->requesting_service_id);
        }

        // Filter by providing service
        if ($request->has('providing_service_id') && $request->providing_service_id) {
            $query->where('providing_service_id', $request->providing_service_id);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->where('requested_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('requested_at', '<=', $request->date_to);
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('movement_number', 'like', "%{$search}%")
                    ->orWhere('request_reason', 'like', "%{$search}%")
                    ->orWhere('prescription_reference', 'like', "%{$search}%");
            });
        }

        $movements = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($movements);
    }

    /**
     * Store a newly created pharmacy movement.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'requesting_service_id' => 'required|exists:services,id',
            'providing_service_id' => 'required|exists:services,id',
            'request_reason' => 'required|string|max:500',
            'urgency_level' => 'required|in:low,normal,high,urgent',
            'expected_delivery_date' => 'nullable|date|after:now',
            'prescription_reference' => 'nullable|string|max:100',
            'patient_id' => 'nullable|exists:patients,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.requested_quantity' => 'required|numeric|min:0.01',
            'items.*.dosage_instructions' => 'nullable|string|max:500',
            'items.*.administration_route' => 'nullable|string|max:50',
            'items.*.frequency' => 'nullable|string|max:50',
            'items.*.duration_days' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $movement = PharmacyMovement::create([
                'requesting_service_id' => $request->requesting_service_id,
                'providing_service_id' => $request->providing_service_id,
                'requesting_user_id' => Auth::id(),
                'request_reason' => $request->request_reason,
                'urgency_level' => $request->urgency_level,
                'expected_delivery_date' => $request->expected_delivery_date,
                'prescription_reference' => $request->prescription_reference,
                'patient_id' => $request->patient_id,
                'status' => 'draft',
                'requested_at' => now(),
                'pharmacy_notes' => $request->pharmacy_notes,
            ]);

            // Create movement items
            foreach ($request->items as $itemData) {
                PharmacyMovementItem::create([
                    'pharmacy_movement_id' => $movement->id,
                    'product_id' => $itemData['product_id'],
                    'requested_quantity' => $itemData['requested_quantity'],
                    'dosage_instructions' => $itemData['dosage_instructions'] ?? null,
                    'administration_route' => $itemData['administration_route'] ?? null,
                    'frequency' => $itemData['frequency'] ?? null,
                    'duration_days' => $itemData['duration_days'] ?? null,
                    'contraindications' => $itemData['contraindications'] ?? null,
                    'pharmacist_notes' => $itemData['pharmacist_notes'] ?? null,
                ]);
            }

            // Log the creation
            PharmacyMovementAuditLog::create([
                'pharmacy_movement_id' => $movement->id,
                'user_id' => Auth::id(),
                'action' => 'created',
                'notes' => 'Pharmacy movement created',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Pharmacy movement created successfully',
                'movement' => $movement->load(['items.product', 'requestingService', 'providingService']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to create pharmacy movement: '.$e->getMessage()], 500);
        }
    }

    /**
     * Display the specified pharmacy movement.
     */
    public function show($id)
    {
        $movement = PharmacyMovement::with([
            'requestingService',
            'providingService',
            'requestingUser',
            'approvingUser',
            'executingUser',
            'patient',
            'items.product',
            'items.inventorySelections.inventory',
        ])->findOrFail($id);

        return response()->json($movement);
    }

    /**
     * Update the specified pharmacy movement.
     */
    public function update(Request $request, $id)
    {
        $movement = PharmacyMovement::findOrFail($id);

        if (! $movement->isEditable()) {
            return response()->json(['error' => 'Movement cannot be edited in current status'], 422);
        }

        $validator = Validator::make($request->all(), [
            'request_reason' => 'sometimes|string|max:500',
            'urgency_level' => 'sometimes|in:low,normal,high,urgent',
            'expected_delivery_date' => 'nullable|date|after:now',
            'prescription_reference' => 'nullable|string|max:100',
            'pharmacy_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $oldValues = $movement->toArray();
            $movement->update($request->only([
                'request_reason',
                'urgency_level',
                'expected_delivery_date',
                'prescription_reference',
                'pharmacy_notes',
            ]));

            // Log the update
            PharmacyMovementAuditLog::create([
                'pharmacy_movement_id' => $movement->id,
                'user_id' => Auth::id(),
                'action' => 'updated',
                'old_values' => $oldValues,
                'new_values' => $movement->toArray(),
                'notes' => 'Pharmacy movement updated',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Pharmacy movement updated successfully',
                'movement' => $movement->load(['items.product', 'requestingService', 'providingService']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to update pharmacy movement: '.$e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified pharmacy movement.
     */
    public function destroy($id)
    {
        $movement = PharmacyMovement::findOrFail($id);

        if (! $movement->isEditable()) {
            return response()->json(['error' => 'Movement cannot be deleted in current status'], 422);
        }

        try {
            DB::beginTransaction();

            // Log the deletion
            PharmacyMovementAuditLog::create([
                'pharmacy_movement_id' => $movement->id,
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'notes' => 'Pharmacy movement deleted',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $movement->delete();

            DB::commit();

            return response()->json(['message' => 'Pharmacy movement deleted successfully']);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to delete pharmacy movement: '.$e->getMessage()], 500);
        }
    }

    /**
     * Send movement for approval.
     */
    public function send(Request $request, $id)
    {
        $movement = PharmacyMovement::findOrFail($id);

        if (! $movement->canBeSent()) {
            return response()->json(['error' => 'Movement cannot be sent for approval'], 422);
        }

        try {
            DB::beginTransaction();

            $movement->update([
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            // Log the action
            PharmacyMovementAuditLog::create([
                'pharmacy_movement_id' => $movement->id,
                'user_id' => Auth::id(),
                'action' => 'sent_for_approval',
                'notes' => 'Movement sent for approval',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Movement sent for approval successfully',
                'movement' => $movement,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to send movement: '.$e->getMessage()], 500);
        }
    }

    /**
     * Approve pharmacy movement.
     */
    public function approve(Request $request, $id)
    {
        $movement = PharmacyMovement::findOrFail($id);

        if (! $movement->canBeApproved()) {
            return response()->json(['error' => 'Movement cannot be approved'], 422);
        }

        $validator = Validator::make($request->all(), [
            'approval_notes' => 'nullable|string|max:1000',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:pharmacy_stock_movement_items,id',
            'items.*.approved_quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $movement->update([
                'status' => 'approved',
                'approving_user_id' => Auth::id(),
                'approved_at' => now(),
                'approval_notes' => $request->approval_notes,
            ]);

            // Update item quantities
            foreach ($request->items as $itemData) {
                $item = PharmacyMovementItem::findOrFail($itemData['id']);
                $item->update(['approved_quantity' => $itemData['approved_quantity']]);
            }

            // Log the approval
            PharmacyMovementAuditLog::create([
                'pharmacy_movement_id' => $movement->id,
                'user_id' => Auth::id(),
                'action' => 'approved',
                'notes' => $request->approval_notes ?? 'Movement approved',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Movement approved successfully',
                'movement' => $movement->load(['items.product']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to approve movement: '.$e->getMessage()], 500);
        }
    }

    /**
     * Get pending movements for approval.
     */
    public function getPending(Request $request)
    {
        $query = PharmacyMovement::pending()
            ->with(['requestingService', 'providingService', 'requestingUser', 'items.product']);

        if ($request->has('providing_service_id')) {
            $query->where('providing_service_id', $request->providing_service_id);
        }

        $movements = $query->orderBy('requested_at', 'asc')->get();

        return response()->json($movements);
    }

    /**
     * Get movement statistics.
     */
    public function getStatistics(Request $request)
    {
        $serviceId = $request->get('service_id');

        $query = PharmacyMovement::query();

        if ($serviceId) {
            $query->forService($serviceId);
        }

        $stats = [
            'total_movements' => $query->count(),
            'pending_movements' => $query->where('status', 'pending')->count(),
            'approved_movements' => $query->where('status', 'approved')->count(),
            'urgent_movements' => $query->where('urgency_level', 'urgent')->count(),
            'movements_this_month' => $query->whereMonth('created_at', now()->month)->count(),
        ];

        return response()->json($stats);
    }
}
