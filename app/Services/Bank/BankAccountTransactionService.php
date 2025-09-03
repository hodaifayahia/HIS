<?php
// app/Services/Bank/BankAccountTransactionService.php

namespace App\Services\Bank;

use App\Models\Bank\BankAccountTransaction;
use App\Models\Bank\BankAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
        $amount = $transaction->amount;

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
        $amount = $transaction->amount;

        if ($transaction->transaction_type === 'credit') {
            $bankAccount->decrement('current_balance', $amount);
            $bankAccount->decrement('available_balance', $amount);
        } else {
            $bankAccount->increment('current_balance', $amount);
            $bankAccount->increment('available_balance', $amount);
        }
    }
}
