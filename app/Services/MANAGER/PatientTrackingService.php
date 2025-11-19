<?php

namespace App\Services\MANAGER;

use App\Models\MANAGER\PatientTracking;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Salle;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PatientTrackingService
{
    /**
     * Check in a patient to a salle
     */
    public function checkIn(array $data): PatientTracking
    {
        return DB::transaction(function () use ($data) {
            // Get the fiche navette item
            $ficheItem = ficheNavetteItem::with(['prestation', 'ficheNavette.patient'])
                ->findOrFail($data['fiche_navette_item_id']);

            // Create patient tracking record
            $tracking = PatientTracking::create([
                'fiche_navette_item_id' => $data['fiche_navette_item_id'],
                'patient_id' => $ficheItem->ficheNavette->patient_id,
                'salle_id' => $data['salle_id'] ,
                'specialization_id' => $ficheItem->prestation->specialization_id ?? null,
                'prestation_id' => $ficheItem->prestation_id,
                'check_in_time' => now(),
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Optionally update the fiche navette item status
            if (isset($data['update_item_status']) && $data['update_item_status']) {
                $ficheItem->update(['status' => 'working']);
            }

            return $tracking->load([
                'patient',
                'salle',
                'specialization',
                'prestation',
                'ficheNavetteItem',
                'creator',
            ]);
        });
    }

    /**
     * Check out a patient from a salle
     */
    public function checkOut(int $trackingId, array $data = []): PatientTracking
    {
        return DB::transaction(function () use ($trackingId, $data) {
            $tracking = PatientTracking::with(['ficheNavetteItem'])->findOrFail($trackingId);

            $tracking->update([
                'check_out_time' => now(),
                'status' => $data['status'] ?? 'completed',
                'notes' => $data['notes'] ?? $tracking->notes,
            ]);

            // Optionally update the fiche navette item status
            if (isset($data['update_item_status']) && $data['update_item_status']) {
                $tracking->ficheNavetteItem->update(['status' => 'done']);
            }

            return $tracking->fresh([
                'patient',
                'salle',
                'specialization',
                'prestation',
                'ficheNavetteItem',
                'creator',
            ]);
        });
    }

    /**
     * Get all current patient positions (active trackings)
     */
    public function getCurrentPositions(array $filters = []): Collection
    {
        $query = PatientTracking::with([
            'patient',
            'salle',
            'specialization',
            'prestation',
            'ficheNavetteItem.ficheNavette',
            'creator',
        ])->active();

        // Filter by salle
        if (! empty($filters['salle_id'])) {
            $query->where('salle_id', $filters['salle_id']);
        }

        // Filter by specialization
        if (! empty($filters['specialization_id'])) {
            $query->where('specialization_id', $filters['specialization_id']);
        }

        // Filter by status
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('check_in_time', 'desc')->get();
    }

    /**
     * Get patient tracking history
     */
    public function getHistory(array $filters = []): Collection
    {
        $query = PatientTracking::with([
            'patient',
            'salle',
            'specialization',
            'prestation',
            'ficheNavetteItem.ficheNavette',
            'creator',
        ]);

        // Filter by patient
        if (! empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        // Filter by date range
        if (! empty($filters['start_date'])) {
            $query->whereDate('check_in_time', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('check_in_time', '<=', $filters['end_date']);
        }

        // Filter by salle
        if (! empty($filters['salle_id'])) {
            $query->where('salle_id', $filters['salle_id']);
        }

        return $query->orderBy('check_in_time', 'desc')->get();
    }

    /**
     * Get available salles for a specific specialization
     */
    public function getAvailableSalles(int $specializationId): Collection
    {
        return Salle::with(['specializations', 'defaultSpecialization'])
            ->where(function ($query) use ($specializationId) {
                $query->where('defult_specialization_id', $specializationId)
                    ->orWhereHas('specializations', function ($q) use ($specializationId) {
                        $q->where('specializations.id', $specializationId);
                    });
            })
            ->get()
            ->map(function ($salle) {
                // Count current patients in this salle
                $currentPatients = PatientTracking::where('salle_id', $salle->id)
                    ->active()
                    ->count();

                return [
                    'id' => $salle->id,
                    'name' => $salle->name,
                    'number' => $salle->number,
                    'description' => $salle->description,
                    'current_patients' => $currentPatients,
                    'default_specialization' => $salle->defaultSpecialization,
                    'specializations' => $salle->specializations,
                ];
            });
    }

    /**
     * Get tracking by ID
     */
    public function getById(int $id): ?PatientTracking
    {
        return PatientTracking::with([
            'patient',
            'salle',
            'specialization',
            'prestation',
            'ficheNavetteItem.ficheNavette',
            'creator',
        ])->find($id);
    }

    /**
     * Get salle occupancy statistics
     */
    public function getSalleOccupancy(): array
    {
        $salles = Salle::with(['specializations', 'defaultSpecialization'])->get();

        return $salles->map(function ($salle) {
            $activeCount = PatientTracking::where('salle_id', $salle->id)
                ->active()
                ->count();

            $todayTotal = PatientTracking::where('salle_id', $salle->id)
                ->whereDate('check_in_time', Carbon::today())
                ->count();

            return [
                'salle_id' => $salle->id,
                'salle_name' => $salle->name,
                'salle_number' => $salle->number,
                'active_patients' => $activeCount,
                'today_total' => $todayTotal,
                'specialization' => $salle->defaultSpecialization,
            ];
        })->toArray();
    }
}
