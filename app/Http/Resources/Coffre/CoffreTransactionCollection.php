<?php

namespace App\Http\Resources\Coffre;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CoffreTransactionCollection extends ResourceCollection
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
                'total_deposits' => $this->collection->where('transaction_type', 'deposit')->sum('amount'),
                'total_withdrawals' => $this->collection->where('transaction_type', 'withdrawal')->sum('amount'),
                'net_amount' => $this->collection->where('transaction_type', 'deposit')->sum('amount') - 
                              $this->collection->where('transaction_type', 'withdrawal')->sum('amount'),
            ],
        ];
    }
}