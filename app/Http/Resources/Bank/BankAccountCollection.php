<?php
// app/Http/Resources/Bank/BankAccountCollection.php

namespace App\Http\Resources\Bank;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BankAccountCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->total(),
                'count' => $this->count(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'total_pages' => $this->lastPage(),
                'has_more_pages' => $this->hasMorePages(),
            ],
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
            'summary' => [
                'total_balance' => $this->collection->sum('current_balance'),
                'total_available_balance' => $this->collection->sum('available_balance'),
                'active_accounts' => $this->collection->where('is_active', true)->count(),
                'inactive_accounts' => $this->collection->where('is_active', false)->count(),
                'currencies' => $this->collection->pluck('currency')->unique()->values(),
                'banks' => $this->collection->pluck('bank.name')->unique()->filter()->values(),
                'by_currency' => $this->getByCurrency(),
                'by_bank' => $this->getByBank(),
            ],
        ];
    }

    /**
     * Get accounts grouped by currency
     */
    private function getByCurrency(): array
    {
        $grouped = [];
        
        foreach ($this->collection->groupBy('currency') as $currency => $accounts) {
            $grouped[$currency] = [
                'count' => $accounts->count(),
                'total_balance' => $accounts->sum('current_balance'),
                'active_count' => $accounts->where('is_active', true)->count(),
            ];
        }

        return $grouped;
    }

    /**
     * Get accounts grouped by bank
     */
    private function getByBank(): array
    {
        $grouped = [];
        
        foreach ($this->collection->groupBy('bank.name') as $bankName => $accounts) {
            if ($bankName) {
                $firstAccount = $accounts->first();
                $grouped[$bankName] = [
                    'bank_id' => $firstAccount->bank?->id,
                    'bank_code' => $firstAccount->bank?->code,
                    'count' => $accounts->count(),
                    'total_balance' => $accounts->sum('current_balance'),
                    'active_count' => $accounts->where('is_active', true)->count(),
                    'currencies' => $accounts->pluck('currency')->unique()->values(),
                ];
            }
        }

        return $grouped;
    }

    /**
     * Get additional information when the collection is used.
     */
    public function with(Request $request): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'version' => '1.0',
        ];
    }
}
