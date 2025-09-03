<?php
// app/Http/Controllers/RefundAuthorizationController.php

namespace App\Http\Controllers\manager;


use App\Http\Controllers\Controller;

use App\Models\manager\RefundAuthorization;
use App\Http\Requests\manager\StoreRefundAuthorizationRequest;
use App\Http\Requests\manager\UpdateRefundAuthorizationRequest;
use App\Http\Resources\manager\RefundAuthorizationResource;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Reception\ItemDependency;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RefundAuthorizationController extends Controller
{
    /**
     * Display a listing of refund authorizations
     */
    public function index(Request $request): JsonResponse
    {
        $query = RefundAuthorization::with(['ficheNavetteItem', 'itemDependency', 'requestedBy', 'authorizedBy'])
                    ->latest();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('requested_by_id')) {
            $query->where('requested_by_id', $request->requested_by_id);
        }

        if ($request->filled('fiche_navette_id')) {
            $query->whereHas('ficheNavetteItem', function($q) use ($request) {
                $q->where('fiche_navette_id', $request->fiche_navette_id);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = $request->integer('per_page', 15);
        $refundAuthorizations = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => RefundAuthorizationResource::collection($refundAuthorizations->items()),
            'meta' => [
                'total' => $refundAuthorizations->total(),
                'count' => $refundAuthorizations->count(),
                'per_page' => $refundAuthorizations->perPage(),
                'current_page' => $refundAuthorizations->currentPage(),
                'total_pages' => $refundAuthorizations->lastPage(),
            ],
            'summary' => $this->getRefundSummary($request),
        ]);
    }

    /**
     * Store a newly created refund authorization request
     */
    public function store(StoreRefundAuthorizationRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['requested_by_id'] = Auth::id();
            $data['status'] = 'pending';

            // Set default expiration if not provided
            if (!isset($data['expires_at'])) {
                $data['expires_at'] = now()->addDays(30); // Default 30 days
            }

            $refundAuthorization = RefundAuthorization::create($data);
            $refundAuthorization->load(['ficheNavetteItem', 'itemDependency', 'requestedBy']);

            // You can add notification logic here
            // $this->notifyManagers($refundAuthorization);

            return response()->json([
                'success' => true,
                'data' => new RefundAuthorizationResource($refundAuthorization),
                'message' => 'Refund authorization request created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create refund authorization request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified refund authorization
     */
    public function show(RefundAuthorization $refundAuthorization): JsonResponse
    {
        $refundAuthorization->load(['ficheNavetteItem', 'itemDependency', 'requestedBy', 'authorizedBy']);

        return response()->json([
            'success' => true,
            'data' => new RefundAuthorizationResource($refundAuthorization)
        ]);
    }

    /**
     * Update the specified refund authorization
     */
    public function update(UpdateRefundAuthorizationRequest $request, RefundAuthorization $refundAuthorization): JsonResponse
    {
        try {
            // Only allow updates on pending requests
            if ($refundAuthorization->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update non-pending authorization requests'
                ], 422);
            }

            $refundAuthorization->update($request->validated());
            $refundAuthorization->load(['ficheNavetteItem', 'itemDependency', 'requestedBy', 'authorizedBy']);

            return response()->json([
                'success' => true,
                'data' => new RefundAuthorizationResource($refundAuthorization),
                'message' => 'Refund authorization updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update refund authorization',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve a refund authorization
     */
    public function approve(Request $request, RefundAuthorization $refundAuthorization): JsonResponse
    {
        $request->validate([
            'authorized_amount' => 'nullable|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
        ]);

        if (!$refundAuthorization->canBeApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'This refund authorization cannot be approved'
            ], 422);
        }

        try {
            $refundAuthorization->approve(
                Auth::id(),
                $request->authorized_amount,
                $request->notes
            );

            $refundAuthorization->load(['ficheNavetteItem', 'itemDependency', 'requestedBy', 'authorizedBy']);

            return response()->json([
                'success' => true,
                'data' => new RefundAuthorizationResource($refundAuthorization),
                'message' => 'Refund authorization approved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve refund authorization',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a refund authorization
     */
    public function reject(Request $request, RefundAuthorization $refundAuthorization): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        if (!$refundAuthorization->canBeRejected()) {
            return response()->json([
                'success' => false,
                'message' => 'This refund authorization cannot be rejected'
            ], 422);
        }

        try {
            $refundAuthorization->reject(Auth::id(), $request->reason);
            $refundAuthorization->load(['ficheNavetteItem', 'itemDependency', 'requestedBy', 'authorizedBy']);

            return response()->json([
                'success' => true,
                'data' => new RefundAuthorizationResource($refundAuthorization),
                'message' => 'Refund authorization rejected'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject refund authorization',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get refund authorization for a specific fiche navette item
     */
    public function getByFicheNavetteItem(Request $request, int $ficheNavetteItemId): JsonResponse
    {
        $query = RefundAuthorization::with(['ficheNavetteItem', 'itemDependency', 'requestedBy', 'authorizedBy'])
                    ->where('fiche_navette_item_id', $ficheNavetteItemId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $authorizations = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => RefundAuthorizationResource::collection($authorizations)
        ]);
    }

    /**
     * Remove the specified refund authorization
     */
    public function destroy(RefundAuthorization $refundAuthorization): JsonResponse
    {
        try {
            // Only allow deletion of pending requests
            if ($refundAuthorization->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete non-pending authorization requests'
                ], 422);
            }

            $refundAuthorization->delete();

            return response()->json([
                'success' => true,
                'message' => 'Refund authorization deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete refund authorization',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary statistics
     */
    private function getRefundSummary(Request $request): array
    {
        $query = RefundAuthorization::query();

        // Apply same filters as main query
        if ($request->filled('fiche_navette_id')) {
            $query->whereHas('ficheNavetteItem', function($q) use ($request) {
                $q->where('fiche_navette_id', $request->fiche_navette_id);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return [
            'total_requests' => $query->count(),
            'pending' => $query->clone()->where('status', 'pending')->count(),
            'approved' => $query->clone()->where('status', 'approved')->count(),
            'rejected' => $query->clone()->where('status', 'rejected')->count(),
            'total_requested_amount' => $query->clone()->sum('requested_amount'),
            'total_authorized_amount' => $query->clone()->where('status', 'approved')->sum('authorized_amount'),
        ];
    }
}
