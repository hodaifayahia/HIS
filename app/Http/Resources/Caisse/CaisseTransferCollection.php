<?php
// app/Http/Resources/Caisse/CaisseTransferCollection.php

namespace App\Http\Resources\Caisse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CaisseTransferCollection extends ResourceCollection
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
                'pending_count' => $this->collection->where('status', 'pending')->count(),
                'accepted_count' => $this->collection->where('status', 'accepted')->count(),
                'rejected_count' => $this->collection->where('status', 'rejected')->count(),
                'expired_count' => $this->collection->where('status', 'expired')->count(),
                'by_status' => $this->getByStatus(),
            ],
        ];
    }

    private function getByStatus(): array
    {
        $grouped = [];
        foreach ($this->collection->groupBy('status') as $status => $transfers) {
            $grouped[$status] = [
                'count' => $transfers->count(),
                'total_amount' => $transfers->sum('amount'),
            ];
        }
        return $grouped;
    }
}
