<?php

namespace App\Services\B2B;

use App\Models\B2B\Avenant;
use App\Models\B2B\Annex;
use App\Models\B2B\PrestationPricing; // Ensure this is correctly imported
use App\Models\B2B\ConventionDetail;
use App\Models\B2B\Convention;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AvenantService
{
    /**
     * Duplicates all latest prestations and agreement details for a convention
     * and creates a new Avenant with 'head' = 'yes' (first avenant for the convention).
     *
     * @param int $conventionId
     * @param int|null $creatorId User ID of the creator (optional, but good for tracking)
     * @return array
     * @throws \Exception
     */
    public function duplicateAllPrestationsWithNewAvenant(int $conventionId, ?int $creatorId = null): array
    {
        return DB::transaction(function () use ($conventionId, $creatorId) {
            // Create a new Avenant with head = 'yes' and status = 'Pending'
            $newAvenant = Avenant::create([
                'convention_id' => $conventionId,
                'status' => 'pending',
                'head' => 'yes',
                'creator_id' => $creatorId,
            ]);

            $newAvenantId = $newAvenant->id;

            // Get annexes for the convention
            $annexIds = Annex::where('convention_id', $conventionId)->pluck('id')->toArray();

            if (empty($annexIds)) {
                // Consider if this should throw an error or just proceed without duplicating prestations
                // For now, I'll allow it to proceed, but log/warn if no annexes.
            }

            // Get all latest prestations linked to these annexes (where updated_by_id is NULL)
            $prestations = PrestationPricing::whereIn('annex_id', $annexIds)
                                            ->whereNull('avenant_id') // Filter for base prestations not yet part of an avenant
                                            ->whereNull('updated_by_id') // Assuming this marks the latest original
                                            ->get();
            $newPrestationIds = [];

            // Duplicate Prestations
            foreach ($prestations as $oldPrestation) {
                // Insert duplicated prestation linked to the new Avenant with head = 'no'
                $newPrestation = PrestationPricing::create([
                    'prestation_id' => $oldPrestation->prestation_id, // CHANGED FROM prestation_list_id
                    'prix' => $oldPrestation->prix, // Consistent with model
                    'patient_price' => $oldPrestation->patient_price, // Consistent with model
                    'company_price' => $oldPrestation->company_price, // Consistent with model
                    'annex_id' => $oldPrestation->annex_id,
                    'avenant_id' => $newAvenantId,
                    'head' => 'no',
                    // 'activation_at' will be set when the avenant is activated
                ]);

                // Update the old prestation to point to the new duplicated prestation
                $oldPrestation->update(['updated_by_id' => $newPrestation->id]);

                $newPrestationIds[] = ['oldId' => $oldPrestation->id, 'newId' => $newPrestation->id];
            }

            // Duplicate ConventionDetail (only the latest where updated_by_id is NULL)
            $ConventionDetail = ConventionDetail::where('convention_id', $conventionId)
                                                ->whereNull('updated_by_id') // Assuming this marks the latest
                                                ->first();

            if ($ConventionDetail) {
                // Insert the new ConventionDetail with head = 'no' (duplicate with new avenant_id)
                $newAgreementDetail = ConventionDetail::create([
                    'convention_id' => $conventionId,
                    'avenant_id' => $newAvenantId,
                    'head' => 'no',
                    'start_date' => $ConventionDetail->start_date,
                    'end_date' => $ConventionDetail->end_date,
                    'family_auth' => $ConventionDetail->family_auth,
                    'max_price' => $ConventionDetail->max_price,
                    'min_price' => $ConventionDetail->min_price,
                    'discount_percentage' => $ConventionDetail->discount_percentage,
                ]);

                // Update the old ConventionDetail to point to the new one
                $ConventionDetail->update(['updated_by_id' => $newAgreementDetail->id]);
            }

            return ['avenantId' => $newAvenantId, 'prestations' => $newPrestationIds];
        });
    }

    /**
     * Duplicates all latest prestations and agreement details for a convention
     * and creates a new Avenant linked to an existing, latest Avenant.
     *
     * @param int $conventionId
     * @param int|null $creatorId User ID of the creator (optional)
     * @return array
     * @throws \Exception
     */
    public function duplicateAllPrestationsWithExistingAvenant(int $conventionId, ?int $creatorId = null): array
    {
        return DB::transaction(function () use ($conventionId, $creatorId) {
            // 1. Find the latest active avenant for this convention
            $oldAvenant = Avenant::where('convention_id', $conventionId)
                                    ->where('status', 'active')
                                    ->whereNull('updated_by_id')
                                    ->latest('id')
                                    ->first();

            if (!$oldAvenant) {
                throw new \Exception('No existing active avenant found for convention to duplicate from. Please activate an avenant first or create a new one.');
            }

            // 2. Create a new avenant (head = 'no', status = 'pending')
            $newAvenant = Avenant::create([
                'convention_id' => $conventionId,
                'status' => 'pending',
                'head' => 'no',
                'creator_id' => $creatorId,
            ]);
            $newAvenantId = $newAvenant->id;

            // 3. Mark the old avenant as having been updated (by the new avenant's creation implicitly, not by user)
            if ($oldAvenant->head === 'yes') {
                $oldAvenant->update(['head' => 'no']);
            }

            $newPrestationIds = [];

            // 4. Get annexes for the convention
            $annexIds = Annex::where('convention_id', $conventionId)->pluck('id')->toArray();

            if (empty($annexIds)) {
                throw new \Exception('No annexes found for convention.');
            }

            // 5. Get all latest prestations linked to old avenant
            $prestations = PrestationPricing::whereIn('annex_id', $annexIds)
                                            ->where('avenant_id', $oldAvenant->id)
                                            ->whereNull('updated_by_id') // Assuming this marks the latest
                                            ->get();

            // 6. Duplicate Prestations
            foreach ($prestations as $oldPrestation) {
                $newPrestation = PrestationPricing::create([
                    'prestation_id' => $oldPrestation->prestation_id, // CHANGED FROM prestation_list_id
                    'prix' => $oldPrestation->prix, // Consistent with model
                    'patient_price' => $oldPrestation->patient_price, // Consistent with model
                    'company_price' => $oldPrestation->company_price, // Consistent with model
                    'annex_id' => $oldPrestation->annex_id,
                    'avenant_id' => $newAvenantId,
                    'head' => 'no',
                    // 'activation_at' will be set when the avenant is activated
                ]);

                // Update the old prestation to point to the new one
                $oldPrestation->update(['updated_by_id' => $newPrestation->id]);

                $newPrestationIds[] = ['oldId' => $oldPrestation->id, 'newId' => $newPrestation->id];
            }

            // 7. Duplicate ConventionDetail
            $ConventionDetail = ConventionDetail::where('convention_id', $conventionId)
                                                ->where('avenant_id', $oldAvenant->id)
                                                ->whereNull('updated_by_id') // Assuming this marks the latest
                                                ->first();

            if ($ConventionDetail) {
                $newAgreementDetail = ConventionDetail::create([
                    'convention_id' => $conventionId,
                    'avenant_id' => $newAvenantId,
                    'head' => 'no',
                    'start_date' => $ConventionDetail->start_date,
                    'end_date' => $ConventionDetail->end_date,
                    'family_auth' => $ConventionDetail->family_auth,
                    'max_price' => $ConventionDetail->max_price,
                    'min_price' => $ConventionDetail->min_price,
                    'discount_percentage' => $ConventionDetail->discount_percentage,
                ]);

                // Update old agreement detail to point to new one
                $ConventionDetail->update(['updated_by_id' => $newAgreementDetail->id]);
            }

            return ['avenantId' => $newAvenantId, 'prestations' => $newPrestationIds];
        });
    }

    /**
     * Activates an avenant by its ID, handling delayed activation and deactivating predecessors.
     *
     * @param int $avenantId
     * @param string $activationDate Date string (YYYY-MM-DD)
     * @param bool $isDelayedActivation
     * @param int|null $approverId User ID of the approver (optional)
     * @return array
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
                           'head' => 'no' // The old active avenant is no longer the head
                       ]);

                // Now activate the new avenant
                $avenant->update([
                    'status' => 'active',
                    'activation_at' => $activationCarbon,
                    'approver_id' => $approverId,
                    'head' => 'yes' // This new avenant becomes the active head
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
     *
     * @param int $avenantId
     * @return Avenant|null
     */
    public function getAvenantById(int $avenantId): ?Avenant
    {
        return Avenant::with('convention')->find($avenantId);
    }

    /**
     * Find a pending avenant for a given convention ID.
     *
     * @param int $conventionId
     * @return Avenant|null
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
     * @param int $conventionId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvenantsByConventionId(int $conventionId)
    {
        return Avenant::where('convention_id', $conventionId)
                      ->orderBy('id', 'desc')
                      ->get();
    }
}