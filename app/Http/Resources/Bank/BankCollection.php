<?php
// app/Http/Resources/Bank/BankCollection.php

namespace App\Http\Resources\Bank;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BankCollection extends ResourceCollection
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
                'active_banks' => $this->collection->where('is_active', true)->count(),
                'inactive_banks' => $this->collection->where('is_active', false)->count(),
            ],
        ];
    }
}
