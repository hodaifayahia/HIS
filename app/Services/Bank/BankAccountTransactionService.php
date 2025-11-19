<?php
// app/Services/Bank/BankAccountTransactionService.php

namespace App\Services\Bank;

use App\Models\Bank\BankAccountTransaction;
use App\Models\Bank\BankAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Coffre\CoffreTransaction;

class BankAccountTransactionService
{
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = BankAccountTransaction::with(['bankAccount.bank', 'acceptedBy', 'reconciledBy'])
                                      ->latest('transaction_date');

        // Apply filters
        if (!empty($filters['bank_account_id'])) {
            $query->byBankAccount($filters['bank_account_id']);
        }

        if (!empty($filters['transaction_type'])) {
            $query->byType($filters['transaction_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('reference', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): BankAccountTransaction
    {
        return BankAccountTransaction::with(['bankAccount.bank', 'acceptedBy', 'reconciledBy'])
                                    ->findOrFail($id);
    }

    public function create(array $data): BankAccountTransaction
    {
        return DB::transaction(function () use ($data) {
            // Handle file upload for Attachment if present
            if (isset($data['Attachment']) && $data['Attachment'] instanceof \Illuminate\Http\UploadedFile) {
                $file = $data['Attachment'];
                $fileName = time() . '_transaction_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('transaction_attachments', $fileName, 'public');
                $data['Attachment'] = $filePath;
            }

            // Generate reference if not provided
            if (!isset($data['reference'])) {
                $data['reference'] = BankAccountTransaction::generateReference();
            }

            // Set default status if not provided
            if (!isset($data['status'])) {
                $data['status'] = 'pending';
            }

            $transaction = BankAccountTransaction::create($data);

            // Update bank account balance if transaction is completed
            if ($transaction->status === 'completed') {
                $this->updateBankAccountBalance($transaction);
            }

            // If this bank transaction is intended to transfer funds to a coffre,
            // create a pending CoffreTransaction so the coffre side can be reconciled
            // by an operator. We expect the incoming payload to include
            // 'dest_coffre_id' when creating a transfer from bank -> coffre.
            if (!empty($data['coffre_id'])) {
                // Create the coffre transaction in pending state. Do NOT apply
                // the coffre balance here; the existing coffre update flow will
                // apply balances when the coffre transaction is marked completed.
                \App\Models\Coffre\CoffreTransaction::create([
                    'coffre_id' => $data['coffre_id'],
                    'user_id' => auth()->id(),
                    'bank_account_id' => $transaction->bank_account_id,
                    'transaction_type' => 'transfer_in',
                    'amount' => $transaction->amount,
                    'status' => 'complate',
                    'description' => $data['description'] ?? ('Bank transfer from account ' . ($transaction->bankAccount?->account_name ?? $transaction->bank_account_id)),
                ]);
            }

            return $transaction->load(['bankAccount.bank', 'acceptedBy']);
        });
    }

    public function update(BankAccountTransaction $transaction, array $data): BankAccountTransaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            $oldStatus = $transaction->status;
            $transaction->update($data);

            // Update bank account balance if status changed to completed
            if ($oldStatus !== 'completed' && $transaction->status === 'completed') {
                $this->updateBankAccountBalance($transaction);
            }

            return $transaction->refresh()->load(['bankAccount.bank', 'acceptedBy', 'reconciledBy']);
        });
    }

    public function delete(BankAccountTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            // Reverse balance update if transaction was completed
            if ($transaction->status === 'completed') {
                $this->reverseBankAccountBalance($transaction);
            }

            $transaction->delete();
        });
    }

    public function complete(BankAccountTransaction $transaction): BankAccountTransaction
    {
        return DB::transaction(function () use ($transaction) {
            if ($transaction->complete()) {
                $this->updateBankAccountBalance($transaction);
            }

            return $transaction->refresh();
        });
    }

    public function cancel(BankAccountTransaction $transaction): BankAccountTransaction
    {
        return DB::transaction(function () use ($transaction) {
            // Reverse balance if transaction was completed
            if ($transaction->status === 'completed') {
                $this->reverseBankAccountBalance($transaction);
            }

            $transaction->cancel();

            return $transaction->refresh();
        });
    }

    public function reconcile(BankAccountTransaction $transaction, int $userId): BankAccountTransaction
    {
        $transaction->reconcile($userId);
        return $transaction->refresh();
    }

    /**
     * Validate a bank transaction by changing status from pending to completed
     * and storing validation data
     */
    public function validate(BankAccountTransaction $transaction, array $validationData, int $validatedBy): BankAccountTransaction
    {
        return DB::transaction(function () use ($transaction, $validationData, $validatedBy) {
            // Only pending transactions can be validated
            if ($transaction->status !== 'pending') {
                throw new \Exception('Only pending transactions can be validated.');
            }

            // Update transaction with validation data
            $transaction->update([
                'status' => 'completed',
                'Payment_date' => $validationData['Payment_date'] ?? now()->format('Y-m-d'),
                'reference_validation' => $validationData['reference_validation'] ?? null,
                'Attachment_validation' => $validationData['Attachment_validation'] ?? null,
                'Reason_validation' => $validationData['Reason_validation'] ?? null,
                'reconciled_by_user_id' => $validatedBy,
                'reconciled_at' => now(),
            ]);

            // Update bank account balance if transaction is now completed
            if ($transaction->status === 'completed') {
                $this->updateBankAccountBalance($transaction);
            }

            return $transaction->refresh()->load(['bankAccount.bank', 'acceptedBy', 'reconciledBy']);
        });
    }

    public function getTransactionStats(int $bankAccountId = null): array
    {
        $query = BankAccountTransaction::query();
        
        if ($bankAccountId) {
            $query->byBankAccount($bankAccountId);
        }

        return [
            'total_transactions' => $query->count(),
            'pending_count' => $query->pending()->count(),
            'completed_count' => $query->completed()->count(),
            'reconciled_count' => $query->reconciled()->count(),
            'total_credit' => $query->byType('credit')->sum('amount'),
            'total_debit' => $query->byType('debit')->sum('amount'),
            'net_amount' => $query->byType('credit')->sum('amount') - $query->byType('debit')->sum('amount'),
        ];
    }

    private function updateBankAccountBalance(BankAccountTransaction $transaction): void
    {
        $bankAccount = $transaction->bankAccount;
    $amount = (float)$transaction->amount;

        if ($transaction->transaction_type === 'credit') {
            $bankAccount->increment('current_balance', $amount);
            $bankAccount->increment('available_balance', $amount);
        } else {
            $bankAccount->decrement('current_balance', $amount);
            $bankAccount->decrement('available_balance', $amount);
        }
    }

    private function reverseBankAccountBalance(BankAccountTransaction $transaction): void
    {
        $bankAccount = $transaction->bankAccount;
    $amount = (float)$transaction->amount;

        if ($transaction->transaction_type === 'credit') {
            $bankAccount->decrement('current_balance', $amount);
            $bankAccount->decrement('available_balance', $amount);
        } else {
            $bankAccount->increment('current_balance', $amount);
            $bankAccount->increment('available_balance', $amount);
        }
    }
}
