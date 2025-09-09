<?php

namespace App\Http\Controllers\manager;

use App\Http\Controllers\Controller;

use App\Models\manager\TransactionBankRequest;
use App\Models\Caisse\FinancialTransaction;
use App\Models\Reception\FicheNavetteItem;
use App\Models\Reception\ItemDependency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\manager\TransactionBankRequestResource;

class TransactionBankRequestController extends Controller
{
    /**
     * Display a listing of pending requests for the authenticated user.
     */
    public function index(Request $request)
    {
        $query = TransactionBankRequest::with(['requester', 'transaction.ficheNavetteItem.prestation'])
            ->where('status', 'pending');

        // If user is an approver, show requests assigned to them
        if ($request->has('for_approval')) {
            $query->where('approved_by', Auth::id());
        }

        $requests = $query->orderBy('requested_at', 'desc')->paginate(20);
        return TransactionBankRequestResource::collection($requests);
    }

    /**
     * Store a newly created approval request.
     * 
     * This method:
     * 1. Creates a FinancialTransaction with status 'pending'
     * 2. Creates TransactionBankRequest linked to that transaction
     * 3. Handles file upload and storage
     * 4. Does NOT update item amounts (happens only after approval)
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'fiche_navette_item_id' => 'nullable|exists:fiche_navette_items,id',
            'item_dependency_id' => 'nullable|integer',
            'amount' => 'required|numeric|min:0.01',
            'patient_id' => 'nullable|exists:patients,id',
            'payment_method' => 'required|in:card,cheque',
            'approved_by' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:5120' // Max 5MB
        ]);

        try {
            DB::beginTransaction();

            // Create FinancialTransaction first (with pending status - no amount updates)
            $transaction = FinancialTransaction::create([
                'fiche_navette_item_id' => $request->fiche_navette_item_id,
                'item_dependency_id' => $request->item_dependency_id,
                'amount' => $request->amount,
                'patient_id' => $request->patient_id,
                'payment_method' => $request->payment_method,
                'transaction_type' => 'payment',
                'cashier_id' => Auth::id(),
                'notes' => $request->notes,
                'status' => 'pending' // Important: pending status, no item amount updates
            ]);

            // Handle file upload if present
            $attachmentPath = null;
            $attachmentData = null;

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $originalName = $file->getClientOriginalName();
                $mimeType = $file->getMimeType();
                $size = $file->getSize();

                // Store file in public disk under approvals folder
                $path = $file->store('approvals', 'public');

                $attachmentData = [
                    'original_name' => $originalName,
                    'mime_type' => $mimeType,
                    'size' => $size,
                    'path' => $path,
                    'url' => asset('storage/' . $path)
                ];
            }

            // Create approval request linked to the transaction
            $approvalRequest = TransactionBankRequest::create([
                'transaction_id' => $transaction->id,
                'requested_by' => Auth::id(),
                'approved_by' => $request->approved_by ?? null,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
                'status' => 'pending',
                'requested_at' => now(),
                'attachment_path' => $attachmentData['path'] ?? null,
                'attachment_original_name' => $attachmentData['original_name'] ?? null,
                'attachment_mime_type' => $attachmentData['mime_type'] ?? null,
                'attachment_size' => $attachmentData['size'] ?? null
            ]);

            DB::commit();

            // Prepare response data with attachment info
            $responseData = $approvalRequest->load(['requester', 'approver', 'transaction']);
            if ($attachmentData) {
                $responseData->attachment = $attachmentData;
            }

            return (new TransactionBankRequestResource($responseData))
                ->additional(['success' => true, 'message' => 'Demande d\'approbation envoyée avec succès']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la demande: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve or reject a payment request.
     */
    public function updateStatus(Request $request, TransactionBankRequest $transactionBankRequest)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string|max:1000',
            'approval_file' => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:5120'
        ]);

        // Check if user is authorized to approve this request
        if ($transactionBankRequest->approved_by !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à approuver cette demande'
            ], 403);
        }


        if ($transactionBankRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cette demande a déjà été traitée'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $isApproved = $request->status === 'approved';
                      

            $transactionBankRequest->update([
                'status' => $request->status,
                'is_approved' => $isApproved,
                'approved_at' => now(),
                'notes' => $request->notes ?? $transactionBankRequest->notes,
                'approval_document' => $request->hasFile('approval_file') ? $request->file('approval_file')->store('approvals', 'public') : $transactionBankRequest->approval_document
            ]);
            // If approved, process the payment
            if ($isApproved) {
                $this->processApprovedPayment($transactionBankRequest);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $isApproved ? 'Demande approuvée et paiement traité avec succès' : 'Demande rejetée',
                'request' => new TransactionBankRequestResource($transactionBankRequest->fresh(['requester', 'approver', 'transaction']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process the approved payment by updating FinancialTransaction status and item amounts.
     * 
     * This method:
     * 1. Updates the existing FinancialTransaction from 'pending' to 'completed'
     * 2. Updates paid_amount and remaining_amount on the item/dependency
     * 3. Updates payment_status based on remaining amount
     */
    private function processApprovedPayment(TransactionBankRequest $approvalRequest)
    {
        // Get the existing FinancialTransaction and update its status
        $transaction = $approvalRequest->transaction;
        if (!$transaction) {
            throw new \Exception('Transaction not found for approval request');
        }

        // Update transaction status to completed and add approval info
        $transaction->update([
            'status' => 'completed',
            'approved_by' => $approvalRequest->approved_by,
            'notes' => $approvalRequest->notes ?? $transaction->notes
        ]);

        // Now update the fiche navette item or dependency amounts
        if ($transaction->item_dependency_id) {
            // Handle dependency payment
            $dependency = ItemDependency::find($transaction->item_dependency_id);
            if ($dependency) {
                $newPaidAmount = ($dependency->paid_amount ?? 0) + $approvalRequest->amount;
                $newRemainingAmount = max(0, ($dependency->final_price ?? $dependency->base_price ?? 0) - $newPaidAmount);
                
                $dependency->update([
                    'paid_amount' => $newPaidAmount,
                    'remaining_amount' => $newRemainingAmount,
                    'payment_status' => $newRemainingAmount <= 0 ? 'paid' : 'partial'
                ]);
            }
        } else {
            // Handle main item payment
            $ficheItem = FicheNavetteItem::find($transaction->fiche_navette_item_id);
            if ($ficheItem) {
                $newPaidAmount = ($ficheItem->paid_amount ?? 0) + $approvalRequest->amount;
                $newRemainingAmount = max(0, ($ficheItem->final_price ?? 0) - $newPaidAmount);
                
                $ficheItem->update([
                    'paid_amount' => $newPaidAmount,
                    'remaining_amount' => $newRemainingAmount,
                    'payment_status' => $newRemainingAmount <= 0 ? 'paid' : 'partial'
                ]);
            }
        }
    }

    /**
     * Update the attachment for a transaction bank request.
     */
    public function updateAttachment(Request $request, TransactionBankRequest $transactionBankRequest)
    {
        $request->validate([
            'attachment' => 'required|file|mimes:pdf,jpeg,jpg,png|max:5120' // Max 5MB
        ]);

        // Check if user is authorized to update this request
        if ($transactionBankRequest->approved_by !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier cette demande'
            ], 403);
        }

        if ($transactionBankRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cette demande ne peut plus être modifiée'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $file = $request->file('attachment');
            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
            $size = $file->getSize();

            // Store new file
            $path = $file->store('approvals', 'public');

            // Delete old file if exists
            if ($transactionBankRequest->attachment_path && \Storage::disk('public')->exists($transactionBankRequest->attachment_path)) {
                \Storage::disk('public')->delete($transactionBankRequest->attachment_path);
            }

            // Update request with new attachment
            $transactionBankRequest->update([
                'attachment_path' => $path,
                'attachment_original_name' => $originalName,
                'attachment_mime_type' => $mimeType,
                'attachment_size' => $size
            ]);

            DB::commit();

            $attachmentData = [
                'original_name' => $originalName,
                'mime_type' => $mimeType,
                'size' => $size,
                'path' => $path,
                'url' => asset('storage/' . $path)
            ];

            return response()->json([
                'success' => true,
                'message' => 'Attachment updated successfully',
                'attachment' => $attachmentData
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du fichier: ' . $e->getMessage()
            ], 500);
        }
    }
}
