<?php

namespace App\Services\Stock;

use App\Models\Stock\Reserve;

class ReserveService
{
    public function paginate(int $perPage = 15)
    {
        return Reserve::with('creator')->paginate($perPage);
    }

    public function store(array $data): Reserve
    {
        // Ensure we record the creator server-side if not provided
        if (!isset($data['created_by']) || !$data['created_by']) {
            $data['created_by'] = auth()->id();
        }
        return Reserve::create($data);
    }

    public function update(Reserve $reserve, array $data): Reserve
    {
        $reserve->update($data);
        return $reserve->refresh();
    }

    public function delete(Reserve $reserve): void
    {
        $reserve->delete();
    }
}