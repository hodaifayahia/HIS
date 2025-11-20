<?php

namespace App\Services\Reception;

use App\Models\B2B\Annex;
use App\Models\B2B\Avenant;
use App\Models\B2B\Convention;
use App\Models\B2B\ConventionDetail;
use App\Models\B2B\PrestationPricing;
use App\Models\CONFIGURATION\Prestation;
use App\Services\B2B\ConventionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ConventionPricingService
{
    protected $conventionService;

    public function __construct(ConventionService $conventionService)
    {
        $this->conventionService = $conventionService;
    }

    /**
     * Get prestations with pricing based on prise_en_charge_date AND contract_percentage_id
     */
    public function getPrestationsWithDateBasedPricing(array $conventionIds, $priseEnChargeDate = null, $contractPercentageId = null): array
    {
        $priseEnChargeDate = $priseEnChargeDate ? Carbon::parse($priseEnChargeDate) : Carbon::now();
        $prestationsWithPricing = [];

        foreach ($conventionIds as $conventionId) {
            $conventionPrestations = $this->getConventionPrestationsForDate($conventionId, $priseEnChargeDate, $contractPercentageId);
            $prestationsWithPricing = array_merge($prestationsWithPricing, $conventionPrestations);
        }

        return $this->removeDuplicatePrestations($prestationsWithPricing);
    }

    /**
     * Get prestations for a specific convention, date, and contract percentage
     */
    public function getConventionPrestationsForDate($conventionId, Carbon $priseEnChargeDate, $contractPercentageId = null): array
    {
        $prestationsWithPricing = [];

        // Step 1: Find the appropriate convention detail based on date
        $conventionDetail = $this->findConventionDetailForDate($conventionId, $priseEnChargeDate);

        if (! $conventionDetail) {
            Log::warning("No convention detail found for convention {$conventionId} and date {$priseEnChargeDate}");

            return [];
        }

        // Step 2: Check if this detail has an avenant_id
        if ($conventionDetail->avenant_id) {
            // Get prestations from Avenant's PrestationPricing table
            $avenantPrestations = $this->getPrestationsFromAvenant(
                $conventionDetail->avenant_id,
                $conventionId,
                $contractPercentageId
            );

            foreach ($avenantPrestations as $prestation) {
                $prestationsWithPricing[] = array_merge($prestation, [
                    'pricing_source' => 'avenant',
                    'priority' => 1,
                    'convention_detail_id' => $conventionDetail->id,
                    'avenant_id' => $conventionDetail->avenant_id,
                    'valid_date_range' => [
                        'start_date' => $conventionDetail->start_date,
                        'end_date' => $conventionDetail->end_date,
                    ],
                ]);
            }
        } else {
            // Get prestations from Annexes' PrestationPricing table
            $annexPrestations = $this->getPrestationsFromAnnexes(
                $conventionId,
                $contractPercentageId
            );

            foreach ($annexPrestations as $prestation) {
                $prestationsWithPricing[] = array_merge($prestation, [
                    'pricing_source' => 'annex',
                    'priority' => 2,
                    'convention_detail_id' => $conventionDetail->id,
                    'valid_date_range' => [
                        'start_date' => $conventionDetail->start_date,
                        'end_date' => $conventionDetail->end_date,
                    ],
                ]);
            }
        }

        // Step 3: If still nothing found, return empty (NO fallback to public pricing)
        if (empty($prestationsWithPricing)) {
            Log::warning("No pricing found in PrestationPricing for convention {$conventionId}, date {$priseEnChargeDate}, percentage {$contractPercentageId}");

            return [];
        }

        return $prestationsWithPricing;
    }

    /**
     * Get all prestations with public pricing as fallback
     */
    private function getAllPrestationsWithPublicPricing($conventionId): array
    {
        $prestations = [];

        try {
            $allPrestations = Prestation::with('specialization')->get();

            foreach ($allPrestations as $prestation) {
                $prestations[] = [
                    'prestation_id' => $prestation->id,
                    'prestation_name' => $prestation->name,
                    'prestation_code' => $prestation->internal_code,
                    'specialization_id' => $prestation->specialization_id,
                    'specialization_name' => $prestation->specialization->name ?? null,
                    'standard_price' => $prestation->price_with_vat_and_consumables_variant,
                    'convention_price' => $prestation->price_with_vat_and_consumables_variant, // Use public price as convention price
                    'patient_price' => $prestation->public_price,
                    // Provide a `price` field for frontend fallbacks (use patient share)
                    'price' => $prestation->public_price,
                    'need_an_appointment' => $prestation->need_an_appointment, // <-- ADD THIS LINE
                    'company_price' => 0,
                    'convention_id' => $conventionId,
                    'pricing_source' => 'public_pricing',
                    'priority' => 4,
                    'prestation' => $prestation,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error getting all prestations with public pricing: '.$e->getMessage());
        }

        return $prestations;
    }

    /**
     * Get prestations from annexes - fetch from PrestationPricing table with contract_percentage_id filter
     */
    private function getPrestationsFromAnnexes($conventionId, $contractPercentageId = null): array
    {
        $prestations = [];

        try {
            // Get all active annexes for this convention
            $annexes = Annex::where('convention_id', $conventionId)
                ->where('is_active', true)
                ->get();

            foreach ($annexes as $annex) {
                // Build query for PrestationPricing
                $query = PrestationPricing::with(['prestation.specialization'])
                    ->where('annex_id', $annex->id);

                // Add contract_percentage_id filter if provided
                if ($contractPercentageId !== null) {
                    $query->where('contract_percentage_id', $contractPercentageId);
                }

                $annexPrestations = $query->get();

                foreach ($annexPrestations as $pricing) {
                    if ($pricing->prestation) {
                        $prestations[] = [
                            'prestation_id' => $pricing->prestation->id,
                            'prestation_name' => $pricing->prestation->name,
                            'prestation_code' => $pricing->prestation->internal_code,
                            'specialization_id' => $pricing->prestation->specialization_id,
                            'specialization_name' => $pricing->prestation->specialization->name ?? null,
                            'need_an_appointment' => $pricing->prestation->need_an_appointment,
                            'standard_price' => $pricing->prix, // Use prix from PrestationPricing, NOT public_price
                            'convention_price' => $pricing->patient_price + $pricing->company_price,
                            'patient_price' => $pricing->patient_price,
                            // Frontend expects a `price` field for fallbacks — use the patient's share
                            'price' => $pricing->patient_price,
                            'company_price' => $pricing->company_price,
                            'prix' => $pricing->prix,
                            'convention_id' => $conventionId,
                            'annex_id' => $annex->id,
                            'pricing_id' => $pricing->id,
                            'contract_percentage_id' => $pricing->contract_percentage_id,
                            'prestation' => $pricing->prestation,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error getting prestations from annexes: '.$e->getMessage());
        }

        return $prestations;
    }

    /**
     * Find the appropriate convention detail for a given date
     */
    private function findConventionDetailForDate($conventionId, Carbon $priseEnChargeDate): ?ConventionDetail
    {
        return ConventionDetail::where('convention_id', $conventionId)
            ->where('start_date', '<=', $priseEnChargeDate)
            ->where('end_date', '>=', $priseEnChargeDate)
            ->orderBy('avenant_id', 'desc') // Prioritize avenants
            ->orderBy('created_at', 'desc') // Get the most recent if multiple
            ->first();
    }

    /**
     * Get prestations from a specific avenant with contract_percentage_id filter
     */
    private function getPrestationsFromAvenant($avenantId, $conventionId, $contractPercentageId = null): array
    {
        $prestations = [];

        try {
            // Build query for PrestationPricing
            $query = PrestationPricing::with(['prestation.specialization'])
                ->where('avenant_id', $avenantId);

            // Add contract_percentage_id filter if provided
            if ($contractPercentageId !== null) {
                $query->where('contract_percentage_id', $contractPercentageId);
            }

            $avenantPrestations = $query->get();

            foreach ($avenantPrestations as $pricing) {
                if ($pricing->prestation) {
                    $prestations[] = [
                        'prestation_id' => $pricing->prestation->id,
                        'prestation_name' => $pricing->prestation->name,
                        'prestation_code' => $pricing->prestation->internal_code,
                        'specialization_id' => $pricing->prestation->specialization_id,
                        'specialization_name' => $pricing->prestation->specialization->name ?? null,
                        'need_an_appointment' => $pricing->prestation->need_an_appointment,
                        'standard_price' => $pricing->prix, // Use prix from PrestationPricing
                        'convention_price' => $pricing->patient_price + $pricing->company_price,
                        'patient_price' => $pricing->patient_price,
                        // Provide `price` for frontend — use the patient share
                        'price' => $pricing->patient_price,
                        'company_price' => $pricing->company_price,
                        'prix' => $pricing->prix,
                        'convention_id' => $conventionId,
                        'avenant_id' => $avenantId,
                        'pricing_id' => $pricing->id,
                        'contract_percentage_id' => $pricing->contract_percentage_id,
                        'prestation' => $pricing->prestation,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Error getting prestations from avenant: '.$e->getMessage());
        }

        return $prestations;
    }

    /**
     * Remove duplicate prestations keeping highest priority
     */
    private function removeDuplicatePrestations(array $prestations): array
    {
        $uniquePrestations = [];
        $seenPrestationIds = [];

        foreach ($prestations as $prestation) {
            if (! in_array($prestation['prestation_id'], $seenPrestationIds)) {
                $uniquePrestations[] = $prestation;
                $seenPrestationIds[] = $prestation['prestation_id'];
            }
        }

        return $uniquePrestations;
    }

    /**
     * Get pricing for a specific prestation on a specific date with contract_percentage_id
     */
    public function getPrestationPricingForDate($prestationId, $conventionId, Carbon $priseEnChargeDate, $contractPercentageId = null): ?array
    {
        $conventionDetail = $this->findConventionDetailForDate($conventionId, $priseEnChargeDate);

        if (! $conventionDetail) {
            return null;
        }

        // Check avenant first (if this detail has an avenant)
        if ($conventionDetail->avenant_id) {
            $query = PrestationPricing::with('prestation')
                ->where('avenant_id', $conventionDetail->avenant_id)
                ->where('prestation_id', $prestationId);

            if ($contractPercentageId !== null) {
                $query->where('contract_percentage_id', $contractPercentageId);
            }

            $pricing = $query->first();

            if ($pricing) {
                return [
                    'pricing_source' => 'avenant',
                    'convention_price' => $pricing->patient_price + $pricing->company_price,
                    'patient_price' => $pricing->patient_price,
                    // Provide `price` for frontend fallback (patient share)
                    'price' => $pricing->patient_price,
                    'company_price' => $pricing->company_price,
                    'prix' => $pricing->prix,
                    'pricing_id' => $pricing->id,
                    'avenant_id' => $conventionDetail->avenant_id,
                    'contract_percentage_id' => $pricing->contract_percentage_id,
                    'uses_convention' => true,
                ];
            }
        } else {
            // Check annexes
            $annexes = Annex::where('convention_id', $conventionId)
                ->where('status', 'active')
                ->get();

            foreach ($annexes as $annex) {
                $query = PrestationPricing::with('prestation')
                    ->where('annex_id', $annex->id)
                    ->where('prestation_id', $prestationId);

                if ($contractPercentageId !== null) {
                    $query->where('contract_percentage_id', $contractPercentageId);
                }

                $pricing = $query->first();

                if ($pricing) {
                    return [
                        'pricing_source' => 'annex',
                        'convention_price' => $pricing->patient_price + $pricing->company_price,
                        'patient_price' => $pricing->patient_price,
                        // Provide `price` for frontend fallback (patient share)
                        'price' => $pricing->patient_price,
                        'company_price' => $pricing->company_price,
                        'prix' => $pricing->prix,
                        'pricing_id' => $pricing->id,
                        'annex_id' => $annex->id,
                        'contract_percentage_id' => $pricing->contract_percentage_id,
                        'uses_convention' => true,
                    ];
                }
            }
        }

        // No pricing found in PrestationPricing table
        return null;
    }
}
