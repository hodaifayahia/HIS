<?php

namespace App\Services\B2B;

use App\Models\B2B\Annex;
use App\Models\B2B\Avenant;
use App\Models\B2B\Convention; // Ensure this is correctly imported
use App\Models\B2B\ConventionDetail;
use App\Models\B2B\PrestationPricing;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AvenantService
{
    /**
     * Duplicates all latest prestations and agreement details for a convention
     * and creates a new Avenant with 'head' = true (first avenant for the convention).
     *
     * @param  int|null  $creatorId  User ID of the creator (optional, but good for tracking)
     *
     * @throws \Exception
     */
    public function duplicateAllPrestationsWithNewAvenant(int $conventionId, ?int $creatorId = null): array
    {
        return DB::transaction(function () use ($conventionId, $creatorId) {
            // Create a new Avenant with head = true and status = 'pending'
            $newAvenant = Avenant::create([
                'convention_id' => $conventionId,
                'name' => 'New Avenant',
                'description' => 'Avenant created with duplicated prestations',
                'status' => 'pending',
                'head' => true,
                'creator_id' => $creatorId,
                'start_date' => now(),
            ]);

            $newAvenantId = $newAvenant->id;

            // Get all annexes for the convention
            $annexes = Annex::where('convention_id', $conventionId)->get();

            if ($annexes->isEmpty()) {
                throw new \Exception('No annexes found for convention. Cannot duplicate prestations.');
            }

            $newPrestationIds = [];

            // Duplicate prestations from all annexes
            foreach ($annexes as $annex) {
                $annexPrestations = PrestationPricing::where('annex_id', $annex->id)->get();

                foreach ($annexPrestations as $annexPrestation) {
                    // Create a copy in the avenant
                    $newPrestation = PrestationPricing::create([
                        'avenant_id' => $newAvenantId,
                        'prestation_id' => $annexPrestation->prestation_id,
                        'contract_percentage_id' => $annexPrestation->contract_percentage_id,
                        'discount_percentage' => $annexPrestation->discount_percentage,
                        'max_price' => $annexPrestation->max_price,
                        'company_price' => $annexPrestation->company_price,
                        'patient_price' => $annexPrestation->patient_price,
                        'head' => true, // Mark as head for the avenant
                    ]);

                    $newPrestationIds[] = ['oldId' => $annexPrestation->id, 'newId' => $newPrestation->id];
                }
            }

            // Duplicate ConventionDetail if it exists
            $conventionDetail = ConventionDetail::where('convention_id', $conventionId)
                ->whereNull('avenant_id') // Get the base convention detail
                ->first();

            if ($conventionDetail) {
                ConventionDetail::create([
                    'convention_id' => $conventionId,
                    'avenant_id' => $newAvenantId,
                    'head' => true,
                    'start_date' => $conventionDetail->start_date,
                    'end_date' => $conventionDetail->end_date,
                    'family_auth' => $conventionDetail->family_auth,
                    'max_price' => $conventionDetail->max_price,
                    'min_price' => $conventionDetail->min_price,
                    'discount_percentage' => $conventionDetail->discount_percentage,
                ]);
            }

            return ['avenantId' => $newAvenantId, 'prestations' => $newPrestationIds];
        });
    }

    /**
     * Duplicates all latest prestations and agreement details for a convention
     * and creates a new Avenant linked to an existing, latest Avenant.
     *
     * @param  int|null  $creatorId  User ID of the creator (optional)
     *
     * @throws \Exception
     */
    public function duplicateAllPrestationsWithExistingAvenant(int $conventionId, ?int $creatorId = null): array
    {
        return DB::transaction(function () use ($conventionId, $creatorId) {
            // 1. Find the latest active avenant for this convention
            $oldAvenant = Avenant::where('convention_id', $conventionId)
                ->where('status', 'active')
                ->latest('id')
                ->first();

            if (! $oldAvenant) {
                throw new \Exception('No existing active avenant found for convention to duplicate from. Please activate an avenant first or create a new one.');
            }

            // 2. Create a new avenant (head = false, status = 'pending')
            $newAvenant = Avenant::create([
                'convention_id' => $conventionId,
                'name' => 'Additional Avenant',
                'description' => 'Avenant created with duplicated prestations from existing avenant',
                'status' => 'pending',
                'head' => false,
                'creator_id' => $creatorId,
                'start_date' => now(),
            ]);
            $newAvenantId = $newAvenant->id;

            // 3. Mark the old avenant as no longer head
            if ($oldAvenant->head === true) {
                $oldAvenant->update(['head' => false]);
            }

            $newPrestationIds = [];

            // 4. Get all prestations from the old avenant
            $prestations = PrestationPricing::where('avenant_id', $oldAvenant->id)->get();

            // Duplicate Prestations
            foreach ($prestations as $oldPrestation) {
                // Duplicate prestation pricing for the new avenant
                $newPrestation = PrestationPricing::create([
                    'prestation_id' => $oldPrestation->prestation_id,
                    'contract_percentage_id' => $oldPrestation->contract_percentage_id,
                    'discount_percentage' => $oldPrestation->discount_percentage,
                    'max_price' => $oldPrestation->max_price,
                    'company_price' => $oldPrestation->company_price,
                    'patient_price' => $oldPrestation->patient_price,
                    'avenant_id' => $newAvenantId,
                    'head' => true, // Mark as head for the new avenant
                ]);

                $newPrestationIds[] = ['oldId' => $oldPrestation->id, 'newId' => $newPrestation->id];
            }

            // 7. Duplicate ConventionDetail
            $conventionDetail = ConventionDetail::where('convention_id', $conventionId)
                ->where('avenant_id', $oldAvenant->id)
                ->first();

            if ($conventionDetail) {
                ConventionDetail::create([
                    'convention_id' => $conventionId,
                    'avenant_id' => $newAvenantId,
                    'head' => true,
                    'start_date' => $conventionDetail->start_date,
                    'end_date' => $conventionDetail->end_date,
                    'family_auth' => $conventionDetail->family_auth,
                    'max_price' => $conventionDetail->max_price,
                    'min_price' => $conventionDetail->min_price,
                    'discount_percentage' => $conventionDetail->discount_percentage,
                ]);
            }

            return ['avenantId' => $newAvenantId, 'prestations' => $newPrestationIds];
        });
    }

    /**
     * Activates an avenant by its ID, handling delayed activation and deactivating predecessors.
     *
     * @param  string  $activationDate  Date string (YYYY-MM-DD)
     * @param  int|null  $approverId  User ID of the approver (optional)
     *
     * @throws \Exception
     */
    public function activateAvenantById(int $avenantId, string $activationDate, bool $isDelayedActivation, ?int $approverId = null): array
    {
        return DB::transaction(function () use ($avenantId, $activationDate, $isDelayedActivation, $approverId) {
            $avenant = Avenant::findOrFail($avenantId);
            $activationCarbon = Carbon::parse($activationDate);

            if ($isDelayedActivation) {
                // Only set the activation_at date without changing status
                $avenant->update([
                    'activation_at' => $activationCarbon,
                    'status' => 'scheduled', // Or 'scheduled' if you have that status
                    'approver_id' => $approverId,
                ]);

                // Update activation_at for related prestation prices (assuming this column exists)
                PrestationPricing::where('avenant_id', $avenantId)
                    ->update(['activation_at' => $activationCarbon]);

                // Update activation_at for related agreement details (assuming this column exists)
                ConventionDetail::where('avenant_id', $avenantId)
                    ->update(['start_date' => $activationCarbon]); // Assuming start_date is the activation date for agreement details

                return [
                    'avenantId' => $avenantId,
                    'scheduledAt' => $activationDate,
                    'status' => $avenant->status,
                ];
            } else {
                // Deactivate any currently active avenants for this convention
                // Set their status to 'archived' or 'inactive' and set inactive_at
                Avenant::where('convention_id', $avenant->convention_id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'archived',
                        'inactive_at' => $activationCarbon,
                        'head' => false, // The old active avenant is no longer the head
                    ]);

                // Now activate the new avenant
                $avenant->update([
                    'status' => 'active',
                    'activation_at' => $activationCarbon,
                    'approver_id' => $approverId,
                    'head' => true, // This new avenant becomes the active head
                ]);

                // Update activation_at for related prestation prices
                PrestationPricing::where('avenant_id', $avenantId)
                    ->update(['activation_at' => $activationCarbon]);

                // Update activation_at for related agreement details
                ConventionDetail::where('avenant_id', $avenantId)
                    ->update(['start_date' => $activationCarbon]);

                return [
                    'avenantId' => $avenantId,
                    'activatedAt' => $activationDate,
                    'status' => 'active',
                ];
            }
        });
    }

    /**
     * Get an avenant by its ID.
     */
    public function getAvenantById(int $avenantId): ?Avenant
    {
        return Avenant::with('convention')->find($avenantId);
    }

    /**
     * Find a pending avenant for a given convention ID.
     */
    public function findPendingAvenantByConventionId(int $conventionId): ?Avenant
    {
        return Avenant::where('convention_id', $conventionId)
            ->where('status', 'pending')
            ->first();
    }

    /**
     * Get all avenants for a given convention ID.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvenantsByConventionId(int $conventionId)
    {
        return Avenant::where('convention_id', $conventionId)
            ->orderBy('id', 'desc')
            ->get();
    }
}
