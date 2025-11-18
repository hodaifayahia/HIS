<?php

namespace App\Services\Reception;

use App\Models\Patient;
use App\Models\Reception\ficheNavette;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FicheNavetteSearchService
{
    /**
     * Get today's fiche navette for a patient or create one if it doesn't exist
     * Also creates initial prestation for Surgery (Upfront) admission type
     */
    public function getOrCreateFicheNavetteForToday(
        int $patientId,
        string $admissionType = 'nursing'
    ): ficheNavette {
        return DB::transaction(function () use ($patientId) {
            // Check if patient has a fiche navette for today
            $ficheNavette = ficheNavette::where('patient_id', $patientId)
                ->whereDate('fiche_date', today())
                ->first();

            if ($ficheNavette) {
                // Return existing fiche navette
                return $ficheNavette->fresh(['items.prestation', 'items.package', 'patient']);
            }

            // Create new fiche navette for today
            $ficheNavette = ficheNavette::create([
                'patient_id' => $patientId,
                'creator_id' => Auth::id(),
                'fiche_date' => now(),
                'status' => 'pending',
                'total_amount' => 0,
            ]);

            // Load with relationships
            return $ficheNavette->fresh(['items.prestation', 'items.package', 'patient']);
        });
    }

    /**
     * Search for today's fiche navette for a patient
     */
    public function findTodaysFicheNavette(int $patientId): ?ficheNavette
    {
        return ficheNavette::where('patient_id', $patientId)
            ->whereDate('fiche_date', today())
            ->first();
    }

    /**
     * Check if patient has today's fiche navette
     */
    public function hasTodaysFicheNavette(int $patientId): bool
    {
        return ficheNavette::where('patient_id', $patientId)
            ->whereDate('fiche_date', today())
            ->exists();
    }
}
