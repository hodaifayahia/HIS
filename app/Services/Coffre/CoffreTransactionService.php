<?php

namespace App\Services\Coffre;

use App\Models\Coffre\Coffre;
use App\Models\Coffre\CoffreTransaction;
use App\Models\User;
use App\Models\Configuration\TransferApproval;
use App\Models\RequestTransactionApproval;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Services\Bank\BankAccountTransactionService;
use App\Models\Bank\BankAccountTransaction;

class CoffreTransactionService
{
    public function getAllPaginated(int $perPage = 15, $coffreId = null, $caisseSessionId = null, $search = null): LengthAwarePaginator
    {
    $query = CoffreTransaction::with([
        'coffre', 
        'user', 
        'destinationCoffre', 
        'approvalRequest',
        'sourceCaisseSession',
        'sourceCaisseSession.cashier',
        'destinationBanque',
        'patient',
        'prestation'
    ]);
        
        // Filter by coffre_id if provided
        if ($coffreId !== null) {
            $query->where('coffre_id', $coffreId);
        }
        
        // Filter by caisse_session_id if provided (including 0)
        if ($caisseSessionId !== null) {
            $query->where('source_caisse_session_id', $caisseSessionId);
        }
        
        // Filter by search term if provided
        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('transaction_type', 'like', "%{$search}%");
            });
        }
        
        return $query->latest('id')->paginate($perPage);
    }

    public function findById(int $id): CoffreTransaction
    {
        return CoffreTransaction::with(['coffre', 'user', 'destinationCoffre', 'sourceCaisseSession', 'destinationBanque'])
                                ->findOrFail($id);
    }

    public function create(array $data): CoffreTransaction
    {
        return DB::transaction(function () use ($data) {
            // Check if this is a bank transfer that needs approval
            $needsApproval = $this->needsApproval($data);
            
                $data['status'] = 'pending';
          

            $transaction = CoffreTransaction::create([
                'coffre_id' => $data['coffre_id'],
                'user_id' => $data['user_id'],
                'transaction_type' => $data['transaction_type'],
                'amount' => $data['amount'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
                'source_caisse_session_id' => $data['source_caisse_session_id'] ?? null,
                'destination_banque_id' => $data['bank_account_id'] ?? null,
                'dest_coffre_id' => $data['dest_coffre_id'] ?? null,
            ]);
            
            if ($needsApproval) {
                // Create approval request and find eligible approvers
               $this->createApprovalRequest($transaction , $data);
                // Don't update balance yet - wait for approval
            } else {
                // Update coffre balance immediately for non-approval transactions
                $this->updateCoffreBalance($transaction);
            }
            
            // Clear cache - use individual keys instead of tags
            Cache::forget('coffres_for_select');
            Cache::forget('users_for_select');
            
            return $transaction->load(['coffre', 'user', 'destinationCoffre', 'approvalRequest']);
        });
    }

    public function update(CoffreTransaction $transaction, array $data): CoffreTransaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            // Check if trying to change status from pending without approval
            if (isset($data['status']) && $transaction->status === 'pending' && $data['status'] !== 'pending') {
                $approvalRequest = $transaction->approvalRequest;
                if ($approvalRequest && $approvalRequest->status !== 'approved') {
                    throw new HttpException(403, 'Cannot change transaction status until approval request is approved.');
                }
            }
            
            // Store original amount and type for balance adjustment
            $originalAmount = $transaction->amount;
            $originalType = $transaction->transaction_type;
            $originalStatus = $transaction->status;
            
            $transaction->update($data);
            
            // If status changed from pending to completed/approved, update balance
            if ($originalStatus === 'pending' && isset($data['status']) && $data['status'] === 'completed') {
                $this->updateCoffreBalance($transaction->refresh());
            } 
            // Adjust coffre balance if amount or type changed for completed transactions
            else if ($transaction->status === 'completed' && ($originalAmount != $transaction->amount || $originalType != $transaction->transaction_type)) {
                $this->revertCoffreBalance($transaction->coffre, (float)$originalAmount, $originalType);
                $this->updateCoffreBalance($transaction->refresh());
            }
            
            Cache::forget('coffres_for_select');
            Cache::forget('users_for_select');
            
            return $transaction->load(['coffre', 'user', 'destinationCoffre', 'approvalRequest']);
        });
    }

    public function delete(CoffreTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            // Revert balance change
            $this->revertCoffreBalance($transaction->coffre, (float)$transaction->amount, $transaction->transaction_type);
            
            $transaction->delete();
            
            Cache::forget('coffres_for_select');
            Cache::forget('users_for_select');
        });
    }

    public function getTransactionTypes(): array
    {
        return [
            'deposit' => 'Deposit',
            'withdrawal' => 'Withdrawal', 
            'transfer_in' => 'Transfer In',
            'transfer_out' => 'Transfer Out',
            'adjustment' => 'Adjustment'
        ];
    }

    public function getCoffresForSelect(): Collection
    {
        return Cache::remember('coffres_for_select', 300, function () {
            return Coffre::select('id', 'name', 'location')
                         ->orderBy('name')
                         ->get();
        });
    }

    public function getUsersForSelect(): Collection
    {
        return Cache::remember('users_for_select', 300, function () {
            return User::select('id', 'name', 'email')
                       ->orderBy('name')
                       ->get();
        });
    }

    private function updateCoffreBalance(CoffreTransaction $transaction): void
    {
        $coffre = $transaction->coffre;
        $amount = (float)$transaction->amount;
        
        switch ($transaction->transaction_type) {
            case 'deposit':
            case 'transfer_in':
                $coffre->increment('current_balance', $amount);
                break;
                
            case 'withdrawal':
            case 'transfer_out':
                $coffre->decrement('current_balance', $amount);
                break;
                
            case 'adjustment':
                // For adjustments, the amount can be positive or negative
                $coffre->increment('current_balance', $amount);
                break;
        }
    }

    private function revertCoffreBalance(Coffre $coffre, float $amount, string $type): void
    {
        switch ($type) {
            case 'deposit':
            case 'transfer_in':
                $coffre->decrement('current_balance', $amount);
                break;
                
            case 'withdrawal':
            case 'transfer_out':
                $coffre->increment('current_balance', $amount);
                break;
                
            case 'adjustment':
                $coffre->decrement('current_balance', $amount);
                break;
        }
    }

    /**
     * Check if a transaction needs approval based on type and destination
     */
    private function needsApproval(array $data): bool
    {
        // Check if this is a transfer to bank (has destination_banque_id)
        return isset($data['bank_account_id']) && !empty($data['bank_account_id']);
    }

    /**
     * Create an approval request and find eligible approvers
     */
    private function createApprovalRequest(CoffreTransaction $transaction): void
    {
        // Find users who can approve this amount based on their maximum approval limit
        $candidateApprovers = TransferApproval::active()
            ->where('maximum', '>=', (float)$transaction->amount)
            ->orderBy('maximum', 'asc') // Start with users who have the lowest sufficient maximum
            ->pluck('user_id')
            ->toArray();

        // Create the approval request
        RequestTransactionApproval::create([
            'status' => 'pending',
            'requested_by' => Auth::id(),
            'request_transaction_id' => $transaction->id,
            'candidate_user_ids' => $candidateApprovers,
        ]);
        
    }

    /**
     * Approve a transaction and update balances
     */
  public function approveTransaction(CoffreTransaction $transaction, int $approvedBy, array $metadata = []): CoffreTransaction
{
    return DB::transaction(function () use ($transaction, $approvedBy, $metadata) {
        // Update the approval request
        $approvalRequest = $transaction->approvalRequest;
        if (!$approvalRequest) {
            throw new \Exception('No approval request found for this transaction.');
        }

        $approvalRequest->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        // Mark the transaction as completed
        $transaction->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Update coffre balance for the completed transaction
        $this->updateCoffreBalance($transaction->refresh());

        // If this transaction targets a bank account, create a corresponding BankAccountTransaction
        if (!empty($transaction->destination_banque_id)) {
            $bankService = app(BankAccountTransactionService::class);

            // Create bank transaction data
            $bankTransactionData = [
                'bank_account_id' => $transaction->destination_banque_id,
                'accepted_by_user_id' => $approvedBy,
                'transaction_type' => 'credit',
                'amount' => $transaction->amount,
                'transaction_date' => $metadata['Payment_date'] ?? now()->format('Y-m-d'),
                'description' => $transaction->description ?? 'Transfer from coffre #' . $transaction->coffre_id . ' (CoffreTransaction #' . $transaction->id . ')',
                'status' => 'completed', // Set as completed since it's approved
                
                // Metadata fields
                'Reason' => $metadata['Reason_validation'] ?? ('Transfer from coffre #' . $transaction->coffre_id),
                'Designation' => $transaction->description ?? 'Coffre to Bank Transfer',
                'Payer' => $transaction->user?->name ?? $transaction->coffre?->name ?? 'Coffre',
                'reference' => $metadata['reference_validation'] ?? BankAccountTransaction::generateReference(),
                
                // Validation fields from approval process
                'Payment_date' => $metadata['Payment_date'] ?? now()->format('Y-m-d'),
                'reference_validation' => $metadata['reference_validation'] ?? null,
                'Attachment_validation' => $metadata['Attachment_validation'] ?? null,
                'Reason_validation' => $metadata['Reason_validation'] ?? null,
            ];

            // Create the bank transaction
            $bankTransaction = $bankService->create($bankTransactionData);
            
            // Update transaction with bank transaction reference
            $transaction->update([
                'bank_transaction_id' => $bankTransaction->id ?? null
            ]);
        }

        return $transaction->refresh();
    });
}

}
