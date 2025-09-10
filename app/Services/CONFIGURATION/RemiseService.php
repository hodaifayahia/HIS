<?php

namespace App\Services\CONFIGURATION;

use App\Models\CONFIGURATION\Remise;
use App\Models\CONFIGURATION\RemiseUser;
use Illuminate\Support\Collection;
use Auth;


class RemiseService
{
    /**
     * Get all remises with their relationships
     */
    public function getAllRemises(array $params = [])
    {
        $query = Remise::with(['users', 'prestations']);

        // Apply search filter if provided
        if (isset($params['search']) && !empty($params['search'])) {
            $searchTerm = $params['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('code', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }
       
        // Only fetch remises for the authenticated user
        $query->whereHas('users', function ($q) {
            $q->where('user_id', Auth::id());
        });

        // Apply pagination if 'page' and 'size' are provided
        if (isset($params['page']) && isset($params['size'])) {
            $page = $params['page'];
            $size = $params['size'];
            return $query->paginate($size, ['*'], 'page', $page);
        }

        // Default to returning all if no pagination params
        return $query->get();
    }


public function getUserRemises(string $userId) {
    return Remise::with(['users', 'prestations'])
        ->whereHas('users', function($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->where('is_active', true)
        ->get(); // Add ->get() to execute the query
}

    /**
     * Create a new remise with users and prestations
     */
    public function createRemise(array $data): Remise
    {
        // Create the remise
        $remise = Remise::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'code' => $data['code'],
            'prestation_id' => $data['prestation_id'] ?? null,
            'amount' => $data['amount'] ?? null,
            'percentage' => $data['percentage'] ?? null,
            'type' => $data['type'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        // Attach users if provided
        if (isset($data['user_ids']) && is_array($data['user_ids'])) {
            $this->attachUsersToRemise($remise, $data['user_ids']);
        }

        // Attach prestations if provided
        if (isset($data['prestation_ids']) && is_array($data['prestation_ids'])) {
            $this->attachPrestationsToRemise($remise, $data['prestation_ids']);
        }

        return $remise->load(['users', 'prestations']);
    }

    /**
     * Update an existing remise
     */
    public function updateRemise(Remise $remise, array $data): Remise
    {
        // Update remise basic data
        $remise->update([
            'name' => $data['name'] ?? $remise->name,
            'description' => $data['description'] ?? $remise->description,
            'code' => $data['code'] ?? $remise->code,
            'prestation_id' => $data['prestation_id'] ?? $remise->prestation_id,
            'amount' => $data['amount'] ?? $remise->amount,
            'percentage' => $data['percentage'] ?? $remise->percentage,
            'type' => $data['type'] ?? $remise->type,
            'is_active' => $data['is_active'] ?? $remise->is_active,
        ]);

        // Update users if provided
        if (isset($data['user_ids'])) {
            $this->syncUsersToRemise($remise, $data['user_ids']);
        }

        // Update prestations if provided
        if (isset($data['prestation_ids'])) {
            $this->syncPrestationsToRemise($remise, $data['prestation_ids']);
        }

        return $remise->load(['users', 'prestations']);
    }

    /**
     * Delete a remise and its relationships
     */
    public function deleteRemise(Remise $remise): bool
    {
        // Delete pivot table records
        RemiseUser::where('remise_id', $remise->id)->delete();
        
        // Delete the remise
        return $remise->delete();
    }

    /**
     * Get remise by ID with relationships
     */
    public function getRemiseById(int $id): ?Remise
    {
        return Remise::with(['users', 'prestations'])->find($id);
    }

    /**
     * Attach users to remise
     */
    private function attachUsersToRemise(Remise $remise, array $userIds): void
    {
        foreach ($userIds as $userId) {
            RemiseUser::create([
                'remise_id' => $remise->id,
                'user_id' => $userId
            ]);
        }
    }

    /**
     * Sync users to remise (remove old, add new)
     */
    private function syncUsersToRemise(Remise $remise, array $userIds): void
    {
        // Remove existing users
        RemiseUser::where('remise_id', $remise->id)->delete();
        
        // Add new users
        $this->attachUsersToRemise($remise, $userIds);
    }

    /**
     * Attach prestations to remise
     */
    private function attachPrestationsToRemise(Remise $remise, array $prestationIds): void
    {
        $remise->prestations()->attach($prestationIds);
    }

    /**
     * Sync prestations to remise
     */
    private function syncPrestationsToRemise(Remise $remise, array $prestationIds): void
    {
        $remise->prestations()->sync($prestationIds);
    }
}