<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use App\Models\RequestTransactionApproval;
use App\Services\Coffre\CoffreTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestTransactionApprovalController extends Controller
{
    protected CoffreTransactionService $transactionService;

    public function __construct(CoffreTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Display a listing of pending approval requests for the authenticated user.
     */
    public function index(): JsonResponse
    {
        try {
            $userId = Auth::id();

            // Get approval requests where user is a candidate approver
            $approvals = RequestTransactionApproval::with(['transaction.coffre', 'requested', 'approved'])
                ->where('status', 'pending')
                ->whereJsonContains('candidate_user_ids', $userId)
                ->latest()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $approvals,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch approval requests: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve a transaction approval request.
     */
    public function approve(Request $request, RequestTransactionApproval $requestTransactionApproval): JsonResponse
    {
        try {
            $userId = Auth::id();

            // Check if user is eligible to approve this request
            if (! in_array($userId, $requestTransactionApproval->candidate_user_ids ?? [])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to approve this request.',
                ], 403);
            }

            // Check if request is still pending
            if ($requestTransactionApproval->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This request has already been processed.',
                ], 400);
            }

            // Validate the request data
            $validatedData = $request->validate([
                'Payment_date' => 'required|date',
                'reference_validation' => 'required|string|max:255',
                'Attachment_validation' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240', // 10MB max
                'Reason_validation' => 'nullable|string|max:1000',
            ]);

            // Handle file upload for Attachment_validation
            $attachmentPath = null;
            if ($request->hasFile('Attachment_validation')) {
                $file = $request->file('Attachment_validation');
                $fileName = time().'_validation_'.$file->getClientOriginalName();
                $attachmentPath = $file->storeAs('validation_attachments', $fileName, 'public');
                $validatedData['Attachment_validation'] = $attachmentPath;
            }

            // Approve the transaction through the service
            $transaction = $this->transactionService->approveTransaction(
                $requestTransactionApproval->transaction,
                $userId,
                $validatedData
            );

            return response()->json([
                'success' => true,
                'message' => 'Transaction approved successfully.',
                'data' => [
                    'transaction' => $transaction->load(['coffre', 'user', 'destinationBanque', 'approvalRequest']),
                    'validation_data' => $validatedData,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Transaction approval error: '.$e->getMessage(), [
                'transaction_id' => $requestTransactionApproval->transaction_id,
                'user_id' => $userId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve transaction: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a transaction approval request.
     */
    public function reject(RequestTransactionApproval $requestTransactionApproval): JsonResponse
    {
        try {
            $userId = Auth::id();

            // Check if user is eligible to reject this request
            if (! in_array($userId, $requestTransactionApproval->candidate_user_ids ?? [])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to reject this request.',
                ], 403);
            }

            // Check if request is still pending
            if ($requestTransactionApproval->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This request has already been processed.',
                ], 400);
            }

            // Update approval request to rejected
            $requestTransactionApproval->update([
                'status' => 'rejected',
                'approved_by' => $userId,
            ]);

            // Update transaction status to rejected
            $requestTransactionApproval->transaction->update(['status' => 'rejected']);

            return response()->json([
                'success' => true,
                'message' => 'Transaction rejected successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject transaction: '.$e->getMessage(),
            ], 500);
        }
    }
}
