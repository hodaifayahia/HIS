<?php
// app/Http/Resources/Coffre/CaisseCollection.php

namespace App\Http\Resources\Coffre;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CaisseCollection extends ResourceCollection
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
            'stats' => [
                'total_active' => $this->collection->where('is_active', true)->count(),
                'total_inactive' => $this->collection->where('is_active', false)->count(),
            ],
        ];
    }
}
