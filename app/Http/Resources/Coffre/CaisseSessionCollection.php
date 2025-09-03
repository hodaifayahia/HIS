<?php
// app/Http/Resources/Coffre/CaisseSessionCollection.php

namespace App\Http\Resources\Coffre;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CaisseSessionCollection extends ResourceCollection
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
                'total_opening_amount' => $this->collection->sum('opening_amount'),
                'total_closing_amount' => $this->collection->whereNotNull('closing_amount')->sum('closing_amount'),
                'total_variance' => $this->collection->whereNotNull('variance')->sum('variance'),
                'open_count' => $this->collection->where('status', 'open')->count(),
                'closed_count' => $this->collection->where('status', 'closed')->count(),
            ],
        ];
    }
}
