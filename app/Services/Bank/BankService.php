<?php
// app/Services/Bank/BankService.php

namespace App\Services\Bank;

use App\Models\Bank\Bank;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BankService
{
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Bank::ordered();

        // Apply filters
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (!empty($filters['currency'])) {
            $query->supportsCurrency($filters['currency']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): Bank
    {
        return Bank::findOrFail($id);
    }

    public function create(array $data): Bank
    {
        return DB::transaction(function () use ($data) {
            $bank = Bank::create($data);
            
            // Clear all cache entries
            $this->clearBankCache();
            
            return $bank;
        });
    }

    public function update(Bank $bank, array $data): Bank
    {
        return DB::transaction(function () use ($bank, $data) {
            $bank->update($data);
            
            // Clear all cache entries
            $this->clearBankCache();
            
            return $bank->refresh();
        });
    }

    public function delete(Bank $bank): void
    {
        DB::transaction(function () use ($bank) {
            // Check if bank is used in any banque records
            if ($bank->banques()->exists()) {
                throw new \Exception('Cannot delete bank that is being used by bank accounts.');
            }
            
            $bank->delete();
            // Clear all cache entries
            $this->clearBankCache();
        });
    }

    public function toggleStatus(Bank $bank): Bank
    {
        return DB::transaction(function () use ($bank) {
            $bank->update(['is_active' => !$bank->is_active]);
            
            // Clear all cache entries
            $this->clearBankCache();
            
            return $bank->refresh();
        });
    }

    public function getActiveBanks(): Collection
    {
        return Cache::remember('active_banks', 300, function () {
            return Bank::active()->ordered()->get();
        });
    }

    public function getBanksByCurrency(string $currency): Collection
    {
        return Cache::remember("banks_currency_{$currency}", 300, function () use ($currency) {
            return Bank::active()
                       ->supportsCurrency($currency)
                       ->ordered()
                       ->get();
        });
    }

    public function getBankOptions(): Collection
    {
        return Cache::remember('bank_options', 300, function () {
            return Bank::active()
                       ->ordered()
                       ->get(['id', 'name', 'code'])
                       ->map(function ($bank) {
                           return [
                               'value' => $bank->name,
                               'label' => $bank->code ? "{$bank->name} ({$bank->code})" : $bank->name,
                               'code' => $bank->code,
                               'id' => $bank->id
                           ];
                       });
        });
    }

    public function getBankStats(): array
    {
        return Cache::remember('bank_stats', 300, function () {
            return [
                'total_banks' => Bank::count(),
                'active_banks' => Bank::active()->count(),
                'inactive_banks' => Bank::where('is_active', false)->count(),
                'by_currency' => $this->getBanksByCurrencyStats(),
            ];
        });
    }

    public function reorderBanks(array $bankIds): void
    {
        DB::transaction(function () use ($bankIds) {
            foreach ($bankIds as $index => $bankId) {
                Bank::where('id', $bankId)->update(['sort_order' => $index + 1]);
            }
            
            // Clear all cache entries
            $this->clearBankCache();
        });
    }

    public function seedDefaultBanks(): void
    {
        $defaultBanks = [
            [
                'name' => 'Banque d\'Algérie',
                'code' => 'BA',
                'supported_currencies' => ['DZD'],
                'is_active' => true,
                'sort_order' => 1
            ],
            [
                'name' => 'BNP Paribas El Djazair',
                'code' => 'BNPD',
                'supported_currencies' => ['DZD', 'EUR', 'USD'],
                'is_active' => true,
                'sort_order' => 2
            ],
            [
                'name' => 'Société Générale Algérie',
                'code' => 'SGA',
                'supported_currencies' => ['DZD', 'EUR', 'USD'],
                'is_active' => true,
                'sort_order' => 3
            ],
            [
                'name' => 'Crédit Populaire d\'Algérie',
                'code' => 'CPA',
                'supported_currencies' => ['DZD'],
                'is_active' => true,
                'sort_order' => 4
            ],
            [
                'name' => 'Banque Extérieure d\'Algérie',
                'code' => 'BEA',
                'supported_currencies' => ['DZD', 'EUR', 'USD'],
                'is_active' => true,
                'sort_order' => 5
            ],
            [
                'name' => 'CNEP-Banque',
                'code' => 'CNEP',
                'supported_currencies' => ['DZD'],
                'is_active' => true,
                'sort_order' => 6
            ],
            [
                'name' => 'Banque de Développement Local',
                'code' => 'BDL',
                'supported_currencies' => ['DZD'],
                'is_active' => true,
                'sort_order' => 7
            ],
            [
                'name' => 'Trust Bank Algeria',
                'code' => 'TBA',
                'supported_currencies' => ['DZD', 'USD'],
                'is_active' => true,
                'sort_order' => 8
            ],
            [
                'name' => 'Al Baraka Bank Algeria',
                'code' => 'ABBA',
                'supported_currencies' => ['DZD', 'USD'],
                'is_active' => true,
                'sort_order' => 9
            ],
            [
                'name' => 'Banque Al-Salam',
                'code' => 'BAS',
                'supported_currencies' => ['DZD'],
                'is_active' => true,
                'sort_order' => 10
            ]
        ];

        foreach ($defaultBanks as $bankData) {
            Bank::updateOrCreate(
                ['name' => $bankData['name']],
                $bankData
            );
        }

        // Clear all cache entries
        $this->clearBankCache();
    }

    private function getBanksByCurrencyStats(): array
    {
        $stats = [];
        $currencies = ['DZD', 'EUR', 'USD', 'GBP'];

        foreach ($currencies as $currency) {
            $count = Bank::active()->supportsCurrency($currency)->count();
            if ($count > 0) {
                $stats[$currency] = $count;
            }
        }

        return $stats;
    }

    /**
     * Clear all bank-related cache entries
     */
    private function clearBankCache(): void
    {
        // Remove specific cache keys
        Cache::forget('active_banks');
        Cache::forget('bank_stats');
        Cache::forget('bank_options');
        
        // Clear all keys that start with "banks_currency_"
        $allKeys = Cache::get('bank_cache_keys', []);
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