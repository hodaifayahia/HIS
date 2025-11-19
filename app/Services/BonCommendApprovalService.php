<?php

namespace App\Services;

use App\Models\ApprovalPerson;
use App\Models\BonCommend;
use App\Models\BonCommendApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BonCommendApprovalService
{
    /**
     * Check if a bon commend requires approval and create approval request if needed.
     * Now checks both amount threshold AND product-level approval requirements.
     *
     * @param  float|null  $thresholdAmount  The amount threshold that requires approval (default: 10000)
     * @param  int|null  $requestedBy  User ID of the person requesting approval
     * @param  string|null  $notes  Notes for the approval request
     * @return array ['requires_approval' => bool, 'approval' => BonCommendApproval|null, 'approver' => ApprovalPerson|null]
     */
    public function checkAndRequestApproval(
        BonCommend $bonCommend,
        ?float $thresholdAmount = 10000,
        ?int $requestedBy = null,
        ?string $notes = null
    ): array {
        $totalAmount = $bonCommend->total_amount;

        // Check if any products require approval
        $productsRequiringApproval = $this->checkProductsRequireApproval($bonCommend);

        // Determine if approval is needed based on amount OR products
        $amountExceedsThreshold = $totalAmount > $thresholdAmount;
        $hasProductsRequiringApproval = ! empty($productsRequiringApproval);

        // Build reason message
        $reasons = [];
        if ($amountExceedsThreshold) {
            $reasons[] = 'Amount exceeds threshold ('.number_format($totalAmount, 2).' DZD > '.number_format($thresholdAmount, 2).' DZD)';
        }
        if ($hasProductsRequiringApproval) {
            $productNames = array_map(function ($item) {
                return $item['product_name'];
            }, $productsRequiringApproval);
            $reasons[] = 'Products require approval: '.implode(', ', $productNames);
        }

        // If neither condition requires approval, return early
        if (! $amountExceedsThreshold && ! $hasProductsRequiringApproval) {
            return [
                'requires_approval' => false,
                'approval' => null,
                'approver' => null,
                'message' => 'No approval required',
                'products_requiring_approval' => [],
            ];
        }

        // Find appropriate approver based on amount
        $approver = $this->findApproverForAmount($totalAmount);

        if (! $approver) {
            return [
                'requires_approval' => true,
                'approval' => null,
                'approver' => null,
                'message' => 'No approver found for this amount',
                'error' => true,
            ];
        }

        // Check if there's already a pending approval
        $existingApproval = $bonCommend->approvals()
            ->where('status', 'pending')
            ->first();

        if ($existingApproval) {
            return [
                'requires_approval' => true,
                'approval' => $existingApproval,
                'approver' => $existingApproval->approvalPerson,
                'message' => 'Approval request already exists',
            ];
        }

        // Create new approval request
        $approval = BonCommendApproval::create([
            'bon_commend_id' => $bonCommend->id,
            'approval_person_id' => $approver->id,
            'requested_by' => $requestedBy ?? auth()->id(),
            'amount' => $totalAmount,
            'status' => 'pending',
            'notes' => $notes,
            'requested_at' => now(),
        ]);

        // Update bon commend status to indicate it's waiting for approval
        $bonCommend->update(['status' => 'pending_approval']);

        return [
            'requires_approval' => true,
            'products_requiring_approval' => $productsRequiringApproval,
            'approval_reasons' => $reasons,
            'approval' => $approval,
            'approver' => $approver,
            'message' => 'Approval request created successfully',
        ];
    }

    /**
     * Find the most appropriate approver for the given amount.
     * Returns the approver with the lowest max_amount that can still handle this amount.
     */
    public function findApproverForAmount(float $amount): ?ApprovalPerson
    {
        return ApprovalPerson::active()
            ->where('max_amount', '>=', $amount)
            ->orderBy('max_amount', 'asc') // Get the person with the lowest sufficient max_amount
            ->orderBy('priority', 'asc')   // Then by priority
            ->first();
    }

    /**
     * Approve a bon commend approval request.
     */
    public function approveBonCommend(BonCommendApproval $approval, ?string $approvalNotes = null): bool
    {
        try {
            DB::beginTransaction();

            // Update approval status
            $approval->approve($approvalNotes);

            // Update bon commend status back to draft or sent so it can be confirmed
            $bonCommend = $approval->bonCommend;
            $bonCommend->update([
                'status' => 'confirmed',
                'approval_status' => 'confirmed',
                'is_confirmed' => true,
                'confirmed_by' => $approval->approved_by,
                'confirmed_at' => now(),

            ]);

            DB::commit();

            Log::info('Bon commend approved', [
                'bon_commend_id' => $bonCommend->id,
                'approval_id' => $approval->id,
                'amount' => $approval->amount,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving bon commend', [
                'error' => $e->getMessage(),
                'approval_id' => $approval->id,
            ]);

            return false;
        }
    }

    /**
     * Reject a bon commend approval request.
     */
    public function rejectBonCommend(BonCommendApproval $approval, ?string $rejectionNotes = null): bool
    {
        try {
            DB::beginTransaction();

            // Update approval status
            $approval->reject($rejectionNotes);

            // Update bon commend status to rejected
            $bonCommend = $approval->bonCommend;
            $bonCommend->update(['status' => 'rejected']);

            DB::commit();

            Log::info('Bon commend rejected', [
                'bon_commend_id' => $bonCommend->id,
                'approval_id' => $approval->id,
                'amount' => $approval->amount,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rejecting bon commend', [
                'error' => $e->getMessage(),
                'approval_id' => $approval->id,
            ]);

            return false;
        }
    }

    /**
     * Get pending approvals for a specific approver.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingApprovalsForApprover(int $approvalPersonId)
    {
        return BonCommendApproval::with(['bonCommend.items.product', 'bonCommend.fournisseur', 'requester'])
            ->where('approval_person_id', $approvalPersonId)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

    }

    /**
     * Get all pending approvals for a user (checks if user is an approval person).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingApprovalsForUser(int $userId)
    {

        $approvalPerson = ApprovalPerson::where('user_id', 12)
            ->first();
        if (! $approvalPerson) {
            return collect([]);
        }

        return $this->getPendingApprovalsForApprover($approvalPerson->id);
    }

    /**
     * Check if a user can approve a specific bon commend.
     */
    public function canUserApproveBonCommend(int $userId, BonCommend $bonCommend): bool
    {
        $approvalPerson = ApprovalPerson::where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (! $approvalPerson) {
            return false;
        }

        return $approvalPerson->canApprove($bonCommend->total_amount);
    }

    /**
     * Get approval statistics for dashboard.
     */
    public function getApprovalStatistics(?int $approvalPersonId = null): array
    {
        $query = BonCommendApproval::query();

        if ($approvalPersonId) {
            $query->where('approval_person_id', $approvalPersonId);
        }

        return [
            'total' => $query->count(),
            'pending' => $query->clone()->where('status', 'pending')->count(),
            'approved' => $query->clone()->where('status', 'approved')->count(),
            'rejected' => $query->clone()->where('status', 'rejected')->count(),
            'total_amount_pending' => $query->clone()
                ->where('status', 'pending')
                ->sum('amount'),
            'total_amount_approved' => $query->clone()
                ->where('status', 'approved')
                ->sum('amount'),
        ];
    }

    /**
     * Check which products in the bon commend require approval.
     *
     * @return array Array of items with products requiring approval
     */
    public function checkProductsRequireApproval(BonCommend $bonCommend): array
    {
        $productsRequiringApproval = [];

        // Load items with their products
        $items = $bonCommend->items()->with('product')->get();

        foreach ($items as $item) {
            // Check both is_required_approval and is_request_approval fields
            if ($item->product && ($item->product->is_required_approval || $item->product->is_request_approval)) {
                $productsRequiringApproval[] = [
                    'item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? $item->product->product_name ?? 'Unknown Product',
                    'quantity' => $item->quantity_desired ?? $item->quantity,
                    'price' => $item->price ?? 0,
                ];
            }
        }

        return $productsRequiringApproval;
    }

    /**
     * Cancel/revoke an approval request (only if pending).
     */
    public function cancelApprovalRequest(BonCommendApproval $approval): bool
    {
        if (! $approval->isPending()) {
            return false;
        }

        try {
            DB::beginTransaction();

            $approval->delete();

            // Update bon commend status back to draft
            $bonCommend = $approval->bonCommend;
            $bonCommend->update(['status' => 'draft']);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error canceling approval request', [
                'error' => $e->getMessage(),
                'approval_id' => $approval->id,
            ]);

            return false;
        }
    }
}
