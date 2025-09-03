<?php
// app/Services/Bank/BankAccountService.php

namespace App\Services\Bank;

use App\Models\Bank\BankAccount;
use App\Models\Bank\Bank;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BankAccountService
{
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = BankAccount::with(['bank'])
                           ->latest('id');

        // Apply filters
        if (!empty($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (!empty($filters['bank_id'])) {
            $query->byBank($filters['bank_id']);
        }

        if (!empty($filters['bank_name'])) {
            $query->byBankName($filters['bank_name']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): BankAccount
    {
        return BankAccount::with(['bank'])->findOrFail($id);
    }

    public function create(array $data): BankAccount
    {
        return DB::transaction(function () use ($data) {
            // Validate that bank exists and supports currency
            if (isset($data['bank_id'])) {
                $bank = Bank::findOrFail($data['bank_id']);
                
                if (!$bank->is_active) {
                    throw new \Exception("Selected bank is not active.");
                }
                
                if (isset($data['currency']) && !$bank->supportsCurrency($data['currency'])) {
                    throw new \Exception("Selected bank does not support {$data['currency']} currency.");
                }
            }

            // Set available_balance to current_balance if not provided
            if (!isset($data['available_balance'])) {
                $data['available_balance'] = $data['current_balance'] ?? 0;
            }

            $bankAccount = BankAccount::create($data);
            
            // Clear all cache entries
            $this->clearBankAccountCache();
            
            return $bankAccount->load(['bank']);
        });
    }

    public function update(BankAccount $bankAccount, array $data): BankAccount
    {
        return DB::transaction(function () use ($bankAccount, $data) {
            // Validate that bank exists and supports currency if changed
            if (isset($data['bank_id']) || isset($data['currency'])) {
                $bankId = $data['bank_id'] ?? $bankAccount->bank_id;
                $currency = $data['currency'] ?? $bankAccount->currency;
                
                $bank = Bank::findOrFail($bankId);
                
                if (!$bank->is_active) {
                    throw new \Exception("Selected bank is not active.");
                }
                
                if (!$bank->supportsCurrency($currency)) {
                    throw new \Exception("Selected bank does not support {$currency} currency.");
                }
            }

            $bankAccount->update($data);
            
            // Clear all cache entries
            $this->clearBankAccountCache();
            
            return $bankAccount->refresh()->load(['bank']);
        });
    }

    public function delete(BankAccount $bankAccount): void
    {
        DB::transaction(function () use ($bankAccount) {
            $bankAccount->delete();
            // Clear all cache entries
            $this->clearBankAccountCache();
        });
    }

    public function toggleStatus(BankAccount $bankAccount): BankAccount
    {
        return DB::transaction(function () use ($bankAccount) {
            $bankAccount->update(['is_active' => !$bankAccount->is_active]);
            
            // Clear all cache entries
            $this->clearBankAccountCache();
            
            return $bankAccount->refresh();
        });
    }

    public function updateBalance(BankAccount $bankAccount, float $amount, string $type = 'credit'): BankAccount
    {
        return DB::transaction(function () use ($bankAccount, $amount, $type) {
            $bankAccount->updateBalance($amount, $type);
            
            return $bankAccount->refresh();
        });
    }

    public function getActiveAccounts(): Collection
    {
        return Cache::remember('active_bank_accounts', 300, function () {
            return BankAccount::with(['bank'])
                             ->active()
                             ->orderBy('bank_id')
                             ->get();
        });
    }

    public function getAccountsByBank(int $bankId): Collection
    {
        return BankAccount::with(['bank'])
                         ->where('bank_id', $bankId)
                         ->active()
                         ->get();
    }

    public function getAccountsByCurrency(string $currency): Collection
    {
        return Cache::remember("bank_accounts_currency_{$currency}", 300, function () use ($currency) {
            return BankAccount::with(['bank'])
                             ->active()
                             ->byCurrency($currency)
                             ->orderBy('bank_id')
                             ->get();
        });
    }

    public function getBankAccountStats(): array
    {
        return Cache::remember('bank_account_stats', 300, function () {
            $stats = [
                'total_accounts' => BankAccount::count(),
                'active_accounts' => BankAccount::active()->count(),
                'inactive_accounts' => BankAccount::where('is_active', false)->count(),
                'total_balance' => BankAccount::active()->sum('current_balance'),
                'by_currency' => [],
                'by_bank' => [],
            ];

            // Group by currency
            $currencyStats = BankAccount::active()
                                      ->selectRaw('currency, COUNT(*) as count, SUM(current_balance) as total_balance')
                                      ->groupBy('currency')
                                      ->get();

            foreach ($currencyStats as $stat) {
                $stats['by_currency'][$stat->currency] = [
                    'count' => $stat->count,
                    'total_balance' => $stat->total_balance
                ];
            }

            // Group by bank
            $bankStats = BankAccount::with(['bank'])
                                   ->active()
                                   ->selectRaw('bank_id, COUNT(*) as count, SUM(current_balance) as total_balance')
                                   ->groupBy('bank_id')
                                   ->get();

            foreach ($bankStats as $stat) {
                $stats['by_bank'][$stat->bank?->name ?? 'Unknown Bank'] = [
                    'count' => $stat->count,
                    'total_balance' => $stat->total_balance,
                    'bank_code' => $stat->bank?->code,
                    'bank_id' => $stat->bank_id
                ];
            }

            return $stats;
        });
    }

    public function getCurrencies(): array
    {
        return ['DZD', 'EUR', 'USD', 'GBP', 'CHF', 'JPY'];
    }

    public function validateBankAccountData(array $data): array
    {
        $errors = [];

        // Check if bank exists
        if (isset($data['bank_id'])) {
            $bank = Bank::find($data['bank_id']);
            if (!$bank) {
                $errors['bank_id'] = 'Selected bank does not exist.';
            } elseif (!$bank->is_active) {
                $errors['bank_id'] = 'Selected bank is not active.';
            } elseif (isset($data['currency']) && !$bank->supportsCurrency($data['currency'])) {
                $errors['currency'] = "Selected bank does not support {$data['currency']} currency.";
            }
        }

        return $errors;
    }

    public function syncAccountBalances(): int
    {
        $updated = 0;
        
        BankAccount::whereNull('available_balance')
                  ->orWhere('available_balance', 0)
                  ->chunk(100, function ($accounts) use (&$updated) {
                      foreach ($accounts as $account) {
                          $account->syncAvailableBalance();
                          $updated++;
                      }
                  });

        return $updated;
    }

    /**
     * Clear all bank account-related cache entries
     */
    private function clearBankAccountCache(): void
    {
        // Remove specific cache keys
        Cache::forget('active_bank_accounts');
        Cache::forget('bank_account_stats');
        
        // Clear all keys that start with "bank_accounts_"
        $allKeys = Cache::get('bank_account_cache_keys', []);
        foreach ($allKeys as $key) {
            Cache::forget($key);
        }
        
        // Clear all cache (fallback for drivers that don't support key patterns)
        try {
            Cache::flush();
        } catch (\Exception $e) {
            // Silently ignore if flush fails
        }
    }
}