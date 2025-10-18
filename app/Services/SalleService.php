<?php

namespace App\Services;

use App\Models\Salle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SalleService
{
    /**
     * Get all salles with pagination or collection
     */
    public function getAll(?int $perPage = null): Collection|LengthAwarePaginator
    {
        $query = Salle::with(['defaultSpecialization', 'specializations']);

        if ($perPage) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    /**
     * Get a salle by ID with relationships
     */
    public function getById(int $id): ?Salle
    {
        return Salle::with(['defaultSpecialization', 'specializations'])->find($id);
    }

    /**
     * Create a new salle
     */
    public function create(array $data): Salle
    {
        DB::beginTransaction();

        try {
            $salle = Salle::create($data);

            // If specialization_ids are provided, attach them
            if (isset($data['specialization_ids']) && is_array($data['specialization_ids'])) {
                $salle->specializations()->sync($data['specialization_ids']);
            }

            DB::commit();

            return $salle->load(['defaultSpecialization', 'specializations']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing salle
     */
    public function update(Salle $salle, array $data): Salle
    {
        DB::beginTransaction();

        try {
            $salle->update($data);

            // If specialization_ids are provided, sync them
            if (isset($data['specialization_ids']) && is_array($data['specialization_ids'])) {
                $salle->specializations()->sync($data['specialization_ids']);
            }

            DB::commit();

            return $salle->fresh(['defaultSpecialization', 'specializations']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a salle
     */
    public function delete(Salle $salle): bool
    {
        DB::beginTransaction();

        try {
            // Detach all specializations first
            $salle->specializations()->detach();

            // Delete the salle
            $deleted = $salle->delete();

            DB::commit();

            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Assign specializations to a salle
     */
    public function assignSpecializations(Salle $salle, array $specializationIds): Salle
    {
        DB::beginTransaction();

        try {
            // Get current specializations and merge with new ones (no duplicates)
            $currentIds = $salle->specializations()->pluck('specializations.id')->toArray();
            $newIds = array_unique(array_merge($currentIds, $specializationIds));

            $salle->specializations()->sync($newIds);

            DB::commit();

            return $salle->fresh(['defaultSpecialization', 'specializations']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove specializations from a salle
     */
    public function removeSpecializations(Salle $salle, array $specializationIds): Salle
    {
        DB::beginTransaction();

        try {
            $salle->specializations()->detach($specializationIds);

            DB::commit();

            return $salle->fresh(['defaultSpecialization', 'specializations']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get salles by specialization
     */
    public function getBySpecialization(int $specializationId): Collection
    {
        return Salle::whereHas('specializations', function ($query) use ($specializationId) {
            $query->where('specializations.id', $specializationId);
        })->with(['defaultSpecialization', 'specializations'])->get();
    }

    /**
     * Search salles by name or number
     */
    public function search(string $query): Collection
    {
        return Salle::where('name', 'like', "%{$query}%")
            ->orWhere('number', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->with(['defaultSpecialization', 'specializations'])
            ->get();
    }
}
