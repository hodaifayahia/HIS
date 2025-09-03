<?php
// app/Http/Resources/Bank/BankAccountTransactionCollection.php

namespace App\Http\Resources\Bank;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BankAccountTransactionCollection extends ResourceCollection
{
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
            'summary' => [
                'total_amount' => $this->collection->sum('amount'),
                'credit_amount' => $this->collection->where('transaction_type', 'credit')->sum('amount'),
                'debit_amount' => $this->collection->where('transaction_type', 'debit')->sum('amount'),
                'pending_count' => $this->collection->where('status', 'pending')->count(),
                'completed_count' => $this->collection->where('status', 'completed')->count(),
                'reconciled_count' => $this->collection->where('status', 'reconciled')->count(),
                'by_status' => $this->getByStatus(),
                'by_type' => $this->getByType(),
            ],
        ];
    }

    private function getByStatus(): array
    {
        $grouped = [];
        foreach ($this->collection->groupBy('status') as $status => $transactions) {
            $grouped[$status] = [
                'count' => $transactions->count(),
                'total_amount' => $transactions->sum('amount'),
            ];
        }
        return $grouped;
    }

    private function getByType(): array
    {
        $grouped = [];
        foreach ($this->collection->groupBy('transaction_type') as $type => $transactions) {
            $grouped[$type] = [
                'count' => $transactions->count(),
                'total_amount' => $transactions->sum('amount'),
            ];
        }
        return $grouped;
    }
}
