<?php

namespace App\Services\Coffre;

use App\Models\Coffre\Coffre;
use App\Models\Coffre\CoffreTransaction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CoffreTransactionService
{
    public function getAllPaginated(int $perPage = 15, $coffreId = null): LengthAwarePaginator
    {
        $query = CoffreTransaction::with(['coffre', 'user', 'destinationCoffre']);
        
        // Filter by coffre_id if provided
        if ($coffreId) {
            $query->where('coffre_id', $coffreId);
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
            $transaction = CoffreTransaction::create($data);
            
            // Update coffre balance based on transaction type
            $this->updateCoffreBalance($transaction);
            
            // Clear cache - use individual keys instead of tags
            Cache::forget('coffres_for_select');
            Cache::forget('users_for_select');
            
            return $transaction->load(['coffre', 'user', 'destinationCoffre']);
        });
    }

    public function update(CoffreTransaction $transaction, array $data): CoffreTransaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            // Store original amount and type for balance adjustment
            $originalAmount = $transaction->amount;
            $originalType = $transaction->transaction_type;
            
            $transaction->update($data);
            
            // Adjust coffre balance if amount or type changed
            if ($originalAmount != $transaction->amount || $originalType != $transaction->transaction_type) {
                $this->revertCoffreBalance($transaction->coffre, $originalAmount, $originalType);
                $this->updateCoffreBalance($transaction->refresh());
            }
            
            Cache::forget('coffres_for_select');
            Cache::forget('users_for_select');
            
            return $transaction->load(['coffre', 'user', 'destinationCoffre']);
        });
    }

    public function delete(CoffreTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            // Revert balance change
            $this->revertCoffreBalance($transaction->coffre, $transaction->amount, $transaction->transaction_type);
            
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
        
        switch ($transaction->transaction_type) {
            case 'deposit':
            case 'transfer_in':
                $coffre->increment('current_balance', $transaction->amount);
                break;
                
            case 'withdrawal':
            case 'transfer_out':
                $coffre->decrement('current_balance', $transaction->amount);
                break;
                
            case 'adjustment':
                // For adjustments, the amount can be positive or negative
                $coffre->increment('current_balance', $transaction->amount);
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
}
