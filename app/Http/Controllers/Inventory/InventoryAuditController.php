<?php

namespace App\Http\Controllers\Inventory;
use App\Http\Controllers\Controller;

use App\Http\Requests\Inventory\StoreInventoryAuditRequest;
use App\Http\Requests\Inventory\UpdateInventoryAuditRequest;
use App\Http\Resources\Inventory\InventoryAuditResource;
use App\Models\Inventory\InventoryAudit;
use App\Services\Inventory\InventoryAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryAuditController extends Controller
{
    protected InventoryAuditService $service;

    public function __construct(InventoryAuditService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of inventory audits
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->input('status'),
            'created_by' => $request->input('created_by'),
            'search' => $request->input('search'),
        ];

        $perPage = $request->input('per_page', 15);
        $audits = $this->service->paginate($filters, $perPage);

        return response()->json([
            'data' => InventoryAuditResource::collection($audits),
            'meta' => [
                'current_page' => $audits->currentPage(),
                'last_page' => $audits->lastPage(),
                'per_page' => $audits->perPage(),
                'total' => $audits->total(),
            ],
        ]);
    }

    /**
     * Store a newly created audit with participants
     */
    public function store(StoreInventoryAuditRequest $request): JsonResponse
    {
        $audit = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Inventory audit created successfully',
            'data' => new InventoryAuditResource($audit),
        ], 201);
    }

    /**
     * Display the specified audit
     */
    public function show(InventoryAudit $inventoryAudit): JsonResponse
    {
        $inventoryAudit->load(['creator', 'participants.user', 'service']);
        
        // Load the appropriate stockage based on type
        if ($inventoryAudit->stockage_id) {
            if ($inventoryAudit->is_pharmacy_wide) {
                $inventoryAudit->load('pharmacyStockage');
            } else {
                $inventoryAudit->load('generalStockage');
            }
        }

        return response()->json([
            'data' => new InventoryAuditResource($inventoryAudit),
        ]);
    }

    /**
     * Update the specified audit
     */
    public function update(UpdateInventoryAuditRequest $request, InventoryAudit $inventoryAudit): JsonResponse
    {
        $audit = $this->service->update($inventoryAudit, $request->validated());

        return response()->json([
            'message' => 'Inventory audit updated successfully',
            'data' => new InventoryAuditResource($audit),
        ]);
    }

    /**
     * Remove the specified audit
     */
    public function destroy(InventoryAudit $inventoryAudit): JsonResponse
    {
        $this->service->delete($inventoryAudit);

        return response()->json([
            'message' => 'Inventory audit deleted successfully',
        ], 204);
    }

    /**
     * Add a participant to an audit
     */
    public function addParticipant(Request $request, InventoryAudit $inventoryAudit): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'is_participant' => ['nullable', 'boolean'],
            'is_able_to_see' => ['nullable', 'boolean'],
        ]);

        $participant = $this->service->addParticipant(
            $inventoryAudit,
            $request->input('user_id'),
            $request->input('is_participant', true),
            $request->input('is_able_to_see', true)
        );

        return response()->json([
            'message' => 'Participant added successfully',
            'data' => $participant,
        ], 201);
    }

    /**
     * Remove a participant from an audit
     */
    public function removeParticipant(Request $request, InventoryAudit $inventoryAudit): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $this->service->removeParticipant($inventoryAudit, $request->input('user_id'));

        return response()->json([
            'message' => 'Participant removed successfully',
        ]);
    }

    /**
     * Start an audit
     */
    public function start(InventoryAudit $inventoryAudit): JsonResponse
    {
        $audit = $this->service->start($inventoryAudit);

        return response()->json([
            'message' => 'Audit started successfully',
            'data' => new InventoryAuditResource($audit),
        ]);
    }

    /**
     * Complete an audit
     */
    public function complete(InventoryAudit $inventoryAudit): JsonResponse
    {
        $audit = $this->service->complete($inventoryAudit);

        return response()->json([
            'message' => 'Audit completed successfully',
            'data' => new InventoryAuditResource($audit),
        ]);
    }

    /**
     * Get audits for current user
     */
    public function myAudits(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $audits = $this->service->getUserAudits(auth()->id(), $perPage);

        return response()->json([
            'data' => InventoryAuditResource::collection($audits),
            'meta' => [
                'current_page' => $audits->currentPage(),
                'last_page' => $audits->lastPage(),
                'per_page' => $audits->perPage(),
                'total' => $audits->total(),
            ],
        ]);
    }

    /**
     * Get audit items (products)
     */
    public function getItems(InventoryAudit $inventoryAudit): JsonResponse
    {
        $items = $this->service->getAuditItems($inventoryAudit);

        return response()->json([
            'data' => $items,
        ]);
    }

    /**
     * Bulk update audit items
     */
    public function bulkUpdateItems(Request $request, InventoryAudit $inventoryAudit): JsonResponse
    {
        $request->validate([
            'participant_id' => ['nullable', 'integer', 'exists:users,id'],
            'items' => ['required', 'array'],
            'items.*.audit_item_id' => ['nullable', 'integer'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.product_type' => ['required', 'string', 'in:pharmacy,stock'],
            'items.*.theoretical_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.actual_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
            'items.*.stockage_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'in:draft,in_progress,completed,sent,canceled'],
        ]);

        $items = $this->service->bulkUpdateItems(
            $inventoryAudit,
            $request->input('items'),
            $request->input('status')
        );

        return response()->json([
            'message' => 'Items updated successfully',
            'data' => $items,
        ]);
    }

    /**
     * Generate PDF report for audit
     */
    public function generatePDF(InventoryAudit $inventoryAudit)
    {
        $pdf = $this->service->generatePDF($inventoryAudit);

        return response()->streamDownload(
            fn() => print($pdf->output()),
            "audit-{$inventoryAudit->id}-" . now()->format('Y-m-d') . ".pdf"
        );
    }

    /**
     * Analyze discrepancies between participants
     */
    public function analyzeDiscrepancies(InventoryAudit $inventoryAudit): JsonResponse
    {
        $reconciliationService = app(\App\Services\Inventory\InventoryReconciliationService::class);
        $analysis = $reconciliationService->analyzeDiscrepancies($inventoryAudit);

        return response()->json($analysis);
    }

    /**
     * Assign recount to participant for disputed products
     */
    public function assignRecount(Request $request, InventoryAudit $inventoryAudit): JsonResponse
    {
        $request->validate([
            'participant_id' => ['required', 'integer', 'exists:users,id'],
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['required', 'integer'],
        ]);

        $reconciliationService = app(\App\Services\Inventory\InventoryReconciliationService::class);
        $result = $reconciliationService->assignRecount(
            $inventoryAudit,
            $request->input('product_ids'),
            $request->input('participant_id')
        );

        return response()->json([
            'message' => 'Recount assigned successfully',
            'data' => $result,
        ]);
    }

    /**
     * Finalize reconciliation
     */
    public function finalizeReconciliation(InventoryAudit $inventoryAudit): JsonResponse
    {
        $reconciliationService = app(\App\Services\Inventory\InventoryReconciliationService::class);
        $result = $reconciliationService->finalizeReconciliation($inventoryAudit);

        return response()->json($result);
    }

    /**
     * Assign specific products to participant for recount
     */
    public function assignProductsForRecount(Request $request, InventoryAudit $inventoryAudit): JsonResponse
    {
        try {
            $validated = $request->validate([
                'participant_id' => ['required', 'integer', 'exists:users,id'],
                'product_ids' => ['required', 'array', 'min:1'],
                'product_ids.*' => ['required', 'integer'],
                'show_other_counts' => ['nullable', 'boolean'],
            ]);

            \Log::info('Assigning recount products', [
                'audit_id' => $inventoryAudit->id,
                'participant_id' => $validated['participant_id'],
                'product_ids' => $validated['product_ids'],
                'show_other_counts' => $validated['show_other_counts'] ?? false,
            ]);

            $recountService = app(\App\Services\Inventory\InventoryAuditRecountService::class);
            $result = $recountService->assignProductsForRecount(
                $inventoryAudit,
                $request->input('participant_id'),
                $request->input('product_ids'),
                $request->input('show_other_counts', false)
            );

            return response()->json([
                'message' => $result['message'] ?? 'Products assigned for recount successfully',
                'data' => $result,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error in assignProductsForRecount', [
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error assigning recount products', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => $e->getMessage(),
                'error' => true,
            ], 500);
        }
    }

    /**
     * Get recount products for a participant
     */
    public function getRecountProducts(InventoryAudit $inventoryAudit, int $participantId): JsonResponse
    {
        $recountService = app(\App\Services\Inventory\InventoryAuditRecountService::class);
        $products = $recountService->getRecountProducts($inventoryAudit, $participantId);

        return response()->json([
            'data' => $products,
        ]);
    }

    /**
     * Remove recount and restore original quantities
     */
    public function removeRecount(Request $request, InventoryAudit $inventoryAudit, int $participantId): JsonResponse
    {
        $recountService = app(\App\Services\Inventory\InventoryAuditRecountService::class);
        $result = $recountService->removeRecount($inventoryAudit, $participantId);

        return response()->json($result);
    }

    /**
     * Complete recount for participant
     */
    public function completeRecount(Request $request, InventoryAudit $inventoryAudit, int $participantId): JsonResponse
    {
        $recountService = app(\App\Services\Inventory\InventoryAuditRecountService::class);
        $result = $recountService->completeRecount($inventoryAudit, $participantId);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Recount completed successfully' : 'Failed to complete recount',
        ]);
    }
}
