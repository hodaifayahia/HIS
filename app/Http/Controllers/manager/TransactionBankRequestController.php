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
     * 3. Does NOT update item amounts (happens only after approval)
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
            'notes' => 'nullable|string|max:1000'
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

            // Create approval request linked to the transaction
            $approvalRequest = TransactionBankRequest::create([
                'transaction_id' => $transaction->id,
                'requested_by' => Auth::id(),
                'approved_by' => $request->approved_by ?? null,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
                'status' => 'pending',
                'requested_at' => now()
            ]);

            DB::commit();

            return (new TransactionBankRequestResource($approvalRequest->load(['requester', 'approver', 'transaction'])))
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
            'notes' => 'nullable|string|max:1000'
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
                'notes' => $request->notes ?? $transactionBankRequest->notes
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
     * Get pending requests for the authenticated user to approve.
     */
    public function getPendingApprovals()
    {
        $requests = TransactionBankRequest::with(['requester', 'transaction.ficheNavetteItem.prestation'])
            ->where('approved_by', Auth::id())
            ->where('status', 'pending')
            ->orderBy('requested_at', 'desc')
            ->get();

        // return resource collection so frontend receives consistent shape (data + meta when paginated)
        return TransactionBankRequestResource::collection($requests);
    }
}
