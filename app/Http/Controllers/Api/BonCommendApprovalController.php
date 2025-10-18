<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessApprovalRequest;
use App\Http\Resources\BonCommendApprovalResource;
use App\Models\BonCommend;
use App\Models\BonCommendApproval;
use App\Services\BonCommendApprovalService;
use Illuminate\Http\Request;

class BonCommendApprovalController extends Controller
{
    protected $approvalService;

    public function __construct(BonCommendApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Get all approval requests (optionally filtered).
     */
    public function index(Request $request)
    {
        $query = BonCommendApproval::with([
            'bonCommend.fournisseur',
            'approvalPerson.user',
            'requester',
        ]);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by approval person
        if ($request->has('approval_person_id')) {
            $query->where('approval_person_id', $request->get('approval_person_id'));
        }

        // Filter by bon commend
        if ($request->has('bon_commend_id')) {
            $query->where('bon_commend_id', $request->get('bon_commend_id'));
        }

        // Order by creation date (newest first)
        $query->orderBy('created_at', 'desc');

        $approvals = $request->has('per_page')
            ? $query->paginate($request->get('per_page', 15))
            : $query->get();

        return BonCommendApprovalResource::collection($approvals);
    }

    /**
     * Get pending approvals for the authenticated user.
     */
    public function myPendingApprovals(Request $request)
    {
        $userId = auth()->id();
        $approvals = $this->approvalService->getPendingApprovalsForUser($userId);

        return BonCommendApprovalResource::collection($approvals);
    }

    /**
     * Display a specific approval.
     */
    public function show(BonCommendApproval $approval)
    {
        $approval->load([
            'bonCommend.items.product',
            'bonCommend.fournisseur',
            'approvalPerson.user',
            'requester',
        ]);

        return new BonCommendApprovalResource($approval);
    }

    /**
     * Request approval for a bon commend.
     */
    public function requestApproval(Request $request, BonCommend $bonCommend)
    {
        $request->validate([
            'threshold_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $threshold = $request->get('threshold_amount', 10000);
        $notes = $request->get('notes');

        $result = $this->approvalService->checkAndRequestApproval(
            $bonCommend,
            $threshold,
            auth()->id(),
            $notes
        );

        if (isset($result['error'])) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'],
            ], 422);
        }

        if (! $result['requires_approval']) {
            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'requires_approval' => false,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => $result['message'],
            'requires_approval' => true,
            'data' => new BonCommendApprovalResource($result['approval']->load([
                'bonCommend.fournisseur',
                'approvalPerson.user',
                'requester',
            ])),
        ], 201);
    }

    /**
     * Approve a bon commend with optional quantity modifications.
     */
    public function approve(ProcessApprovalRequest $request, BonCommendApproval $approval)
    {
        // // Check if user has permission to approve
        // if (!$this->approvalService->canUserApproveBonCommend(auth()->id(), $approval->bonCommend)) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'You do not have permission to approve this bon commend'
        //     ], 403);
        // }

        // // Check if approval is still pending
        // if (!$approval->isPending()) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'This approval has already been processed'
        //     ], 422);
        // }

        // Handle quantity modifications if provided
        $modifiedItems = $request->get('modified_items', []);
        if (! empty($modifiedItems)) {
            $this->applyQuantityModifications($approval->bonCommend, $modifiedItems);
        }

        $success = $this->approvalService->approveBonCommend(
            $approval,
            $request->get('approval_notes')
        );

        if (! $success) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve bon commend',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Bon commend approved successfully',
            'data' => new BonCommendApprovalResource($approval->fresh()->load([
                'bonCommend.fournisseur',
                'approvalPerson.user',
                'requester',
            ])),
        ]);
    }

    /**
     * Reject a bon commend.
     */
    public function reject(ProcessApprovalRequest $request, BonCommendApproval $approval)
    {
        // // Check if user has permission to reject
        // if (!$this->approvalService->canUserApproveBonCommend(auth()->id(), $approval->bonCommend)) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'You do not have permission to reject this bon commend'
        //     ], 403);
        // }

        // // Check if approval is still pending
        // if (!$approval->isPending()) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'This approval has already been processed'
        //     ], 422);
        // }

        $success = $this->approvalService->rejectBonCommend(
            $approval,
            $request->get('approval_notes')
        );

        if (! $success) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reject bon commend',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Bon commend rejected successfully',
            'data' => new BonCommendApprovalResource($approval->fresh()->load([
                'bonCommend.fournisseur',
                'approvalPerson.user',
                'requester',
            ])),
        ]);
    }

    /**
     * Cancel an approval request (only if pending).
     */
    public function cancel(BonCommendApproval $approval)
    {
        // Only requester or admin can cancel
        if ($approval->requested_by !== auth()->id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to cancel this approval request',
            ], 403);
        }

        $success = $this->approvalService->cancelApprovalRequest($approval);

        if (! $success) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot cancel approval - it may have already been processed',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Approval request cancelled successfully',
        ]);
    }

    /**
     * Send back approval request to requester with optional quantity modifications.
     * The approver can adjust quantities and send the request back for correction.
     */
    public function sendBack(Request $request, BonCommendApproval $approval)
    {
        // Check if user has permission to act on this approval
        // if (!$this->approvalService->canUserApproveBonCommend(auth()->id(), $approval->bonCommend)) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'You do not have permission to send back this bon commend'
        //     ], 403);
        // }

        // // Check if approval is still pending
        // if (!$approval->isPending()) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'This approval has already been processed'
        //     ], 422);
        // }

        $data = $request->validate([
            'modified_items' => ['nullable', 'array'],
            'modified_items.*.item_id' => ['required_with:modified_items', 'integer', 'exists:bon_commend_items,id'],
            'modified_items.*.quantity_desired' => ['required_with:modified_items', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Apply quantity modifications if provided
        $modifiedItems = $data['modified_items'] ?? [];
        if (! empty($modifiedItems)) {
            $this->applyQuantityModifications($approval->bonCommend, $modifiedItems);
        }

        // Mark approval as sent back
        $approval->update([
            'status' => 'sent_back',
            'approval_notes' => $data['notes'] ?? null,
            'rejected_at' => now(),
        ]);

        // Update bon commend status so requester knows it needs correction
        $approval->bonCommend->update(['status' => 'needs_correction']);

        return response()->json([
            'status' => 'success',
            'message' => 'Approval request sent back to requester for correction',
            'data' => new BonCommendApprovalResource($approval->fresh()->load([
                'bonCommend.fournisseur',
                'approvalPerson.user',
                'requester',
            ])),
        ]);
    }

    /**
     * Apply quantity modifications from approver.
     */
    private function applyQuantityModifications(BonCommend $bonCommend, array $modifiedItems)
    {
        foreach ($modifiedItems as $modification) {
            $itemId = $modification['item_id'];
            $newQuantity = $modification['quantity_desired'];

            $item = $bonCommend->items()->find($itemId);
            if ($item && $item->quantity_desired != $newQuantity) {
                // Store original quantity if not already stored
                if (is_null($item->original_quantity_desired)) {
                    $item->original_quantity_desired = $item->quantity_desired;
                }

                // Update quantity and mark as modified by approver
                $item->update([
                    'quantity_desired' => $newQuantity,
                    'modified_by_approver' => true,
                ]);
            }
        }

        // Update bon commend to indicate approver made modifications
        if (! empty($modifiedItems)) {
            $bonCommend->update(['has_approver_modifications' => true]);
        }
    }

    /**
     * Get all approvals for the authenticated approver (dashboard view).
     */
    public function myApprovals(Request $request)
    {
        $userId = auth()->id();

        // Get the approval person record for this user
        $approvalPerson = \App\Models\ApprovalPerson::where('user_id', $userId)->first();

        if (! $approvalPerson) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not configured as an approval person',
            ], 403);
        }

        $query = BonCommendApproval::with([
            'bonCommend.items.product',
            'bonCommend.fournisseur',
            'requester',
        ])->where('approval_person_id', $approvalPerson->id);

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Order by creation date (newest first)
        $query->orderBy('created_at', 'desc');

        $approvals = $request->has('per_page')
            ? $query->paginate($request->get('per_page', 15))
            : $query->get();

        return BonCommendApprovalResource::collection($approvals);
    }

    /**
     * Get approval statistics.
     */
    public function statistics(Request $request)
    {
        $approvalPersonId = $request->get('approval_person_id');
        $stats = $this->approvalService->getApprovalStatistics($approvalPersonId);

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }
}
