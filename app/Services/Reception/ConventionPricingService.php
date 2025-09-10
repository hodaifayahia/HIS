<?php

namespace App\Services\Reception;

use App\Models\B2B\Convention;
use App\Models\B2B\ConventionDetail;
use App\Models\B2B\PrestationPricing;
use App\Models\B2B\Avenant;
use App\Models\B2B\Annex;
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
     * Get prestations with pricing based on prise_en_charge_date
     */
    public function getPrestationsWithDateBasedPricing(array $conventionIds, $priseEnChargeDate = null): array
    {
        $priseEnChargeDate = $priseEnChargeDate ? Carbon::parse($priseEnChargeDate) : Carbon::now();
        $prestationsWithPricing = [];

        foreach ($conventionIds as $conventionId) {
            $conventionPrestations = $this->getConventionPrestationsForDate($conventionId, $priseEnChargeDate);
            $prestationsWithPricing = array_merge($prestationsWithPricing, $conventionPrestations);
        }

        // Remove duplicates (keep the first occurrence - highest priority)
        return $this->removeDuplicatePrestations($prestationsWithPricing);
    }

    /**
     * Get prestations for a specific convention and date
     */
    public function getConventionPrestationsForDate($conventionId, Carbon $priseEnChargeDate): array
    {
        $prestationsWithPricing = [];
        
        // Step 1: Find the appropriate convention detail based on date
        $conventionDetail = $this->findConventionDetailForDate($conventionId, $priseEnChargeDate);
        // dd($conventionDetail);
        
        if (!$conventionDetail) {
            Log::warning("No convention detail found for convention {$conventionId} and date {$priseEnChargeDate}");
            // Fallback to all prestations with public pricing
            return [];
        }

        // Step 2: Check if this detail has an avenant_id
        if ($conventionDetail->avenant_id) {
            // Get prestations from Avenant (HIGHEST PRIORITY)
            $avenantPrestations = $this->getPrestationsFromAvenant($conventionDetail->avenant_id, $conventionId);
            foreach ($avenantPrestations as $prestation) {
                $prestationsWithPricing[] = array_merge($prestation, [
                    'pricing_source' => 'avenant',
                    'priority' => 1,
                    'convention_detail_id' => $conventionDetail->id,
                    'avenant_id' => $conventionDetail->avenant_id,
                    'valid_date_range' => [
                        'start_date' => $conventionDetail->start_date,
                        'end_date' => $conventionDetail->end_date
                    ]
                ]);
            }
        } else {
            // Get prestations from Annexes (MEDIUM PRIORITY)
            $annexPrestations = $this->getPrestationsFromAnnexes($conventionId);
            foreach ($annexPrestations as $prestation) {
                $prestationsWithPricing[] = array_merge($prestation, [
                    'pricing_source' => 'annex',
                    'priority' => 2,
                    'convention_detail_id' => $conventionDetail->id,
                    'valid_date_range' => [
                        'start_date' => $conventionDetail->start_date,
                        'end_date' => $conventionDetail->end_date
                    ]
                ]);
            }
        }

        // Step 3: Fallback to Prestation Pricing (LOWEST PRIORITY)
        if (empty($prestationsWithPricing)) {
            $pricingPrestations = $this->getPrestationsFromPrestationPricing($conventionId);
            foreach ($pricingPrestations as $prestation) {
                $prestationsWithPricing[] = array_merge($prestation, [
                    'pricing_source' => 'prestation_pricing',
                    'priority' => 3,
                    'convention_detail_id' => $conventionDetail->id,
                    'valid_date_range' => [
                        'start_date' => $conventionDetail->start_date,
                        'end_date' => $conventionDetail->end_date
                    ]
                ]);
            }
        }

        // Step 4: Final fallback to public pricing if nothing found
        if (empty($prestationsWithPricing)) {
            return $this->getAllPrestationsWithPublicPricing($conventionId);
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
                    'standard_price' => $prestation->public_price,
                    'convention_price' => $prestation->public_price, // Use public price as convention price
                    'patient_price' => $prestation->public_price,
                    'need_an_appointment' => $prestation->need_an_appointment, // <-- ADD THIS LINE
                    'company_price' => 0,
                    'convention_id' => $conventionId,
                    'pricing_source' => 'public_pricing',
                    'priority' => 4,
                    'prestation' => $prestation
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error getting all prestations with public pricing: ' . $e->getMessage());
        }

        return $prestations;
    }

    /**
     * Get prestations from annexes (get ALL prestations from ALL annexes)
     */
   private function getPrestationsFromAnnexes($conventionId): array
{
    $prestations = [];

    try {
        $annexes = Annex::where('convention_id', $conventionId)
            ->where('status', 'active')
            ->get();

        foreach ($annexes as $annex) {
            $annexPricingData = $this->conventionService->calculatePrestationPricing($annex->id);

            foreach ($annexPricingData as $pricingData) {
                $prestation = Prestation::with('specialization')->find($pricingData['prestation_id']);
                
                if ($prestation) {
                    $prestations[] = [
                        'prestation_id' => $prestation->id,
                        'prestation_name' => $prestation->name,
                        'prestation_code' => $prestation->internal_code,
                        'specialization_id' => $prestation->specialization_id,
                        'specialization_name' => $prestation->specialization->name ?? null,
                        'need_an_appointment' => $prestation->need_an_appointment, // <-- ADD THIS
                        'standard_price' => $pricingData['prix_global'],
                        'convention_price' => $pricingData['prix_global'],
                        'patient_price' => $pricingData['prix_patient'],
                        'company_price' => $pricingData['prix_company'],
                        'convention_id' => $conventionId,
                        'annex_id' => $annex->id,
                        'max_price_exceeded' => $pricingData['max_price_exceeded'],
                        'prestation' => $prestation
                    ];
                }
            }
        }
    } catch (\Exception $e) {
        Log::error('Error getting prestations from annexes: ' . $e->getMessage());
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
     * Get prestations from a specific avenant
     */
  private function getPrestationsFromAvenant($avenantId, $conventionId): array
{
    $prestations = [];

    try {
        $avenantPrestations = PrestationPricing::with(['prestation.specialization'])
            ->where('avenant_id', $avenantId)
            ->get();

        foreach ($avenantPrestations as $pricing) {
            if ($pricing->prestation) {
                $prestations[] = [
                    'prestation_id' => $pricing->prestation->id,
                    'prestation_name' => $pricing->prestation->name,
                    'prestation_code' => $pricing->prestation->internal_code,
                    'specialization_id' => $pricing->prestation->specialization_id,
                    'specialization_name' => $pricing->prestation->specialization->name ?? null,
                    'need_an_appointment' => $pricing->prestation->need_an_appointment, // <-- ADD THIS
                    'standard_price' => $pricing->prestation->public_price,
                    'convention_price' => $pricing->prix_patient + $pricing->prix_company,
                    'patient_price' => $pricing->prix_patient,
                    'company_price' => $pricing->prix_company,
                    'convention_id' => $conventionId,
                    'pricing_id' => $pricing->id,
                    'prestation' => $pricing->prestation
                ];
            }
        }
    } catch (\Exception $e) {
        Log::error('Error getting prestations from avenant: ' . $e->getMessage());
    }

    return $prestations;
}

    /**
     * Get prestations from prestation pricing table
     */
  private function getPrestationsFromPrestationPricing($conventionId): array
{
    $prestations = [];

    try {
        $pricingData = PrestationPricing::with(['prestation.specialization'])
            ->where('convention_id', $conventionId)
            ->get();

        foreach ($pricingData as $pricing) {
            if ($pricing->prestation) {
                $prestations[] = [
                    'prestation_id' => $pricing->prestation->id,
                    'prestation_name' => $pricing->prestation->name,
                    'prestation_code' => $pricing->prestation->internal_code,
                    'specialization_id' => $pricing->prestation->specialization_id,
                    'specialization_name' => $pricing->prestation->specialization->name ?? null,
                    'need_an_appointment' => $pricing->prestation->need_an_appointment, // <-- ADD THIS
                    'standard_price' => $pricing->prestation->public_price,
                    'convention_price' => $pricing->prix_patient + $pricing->prix_company,
                    'patient_price' => $pricing->prix_patient,
                    'company_price' => $pricing->prix_company,
                    'convention_id' => $conventionId,
                    'pricing_id' => $pricing->id,
                    'prestation' => $pricing->prestation
                ];
            }
        }
    } catch (\Exception $e) {
        Log::error('Error getting prestations from prestation pricing: ' . $e->getMessage());
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
            if (!in_array($prestation['prestation_id'], $seenPrestationIds)) {
                $uniquePrestations[] = $prestation;
                $seenPrestationIds[] = $prestation['prestation_id'];
            }
        }

        return $uniquePrestations;
    }

    /**
     * Get pricing for a specific prestation on a specific date
     */
    public function getPrestationPricingForDate($prestationId, $conventionId, Carbon $priseEnChargeDate): ?array
    {
        $conventionDetail = $this->findConventionDetailForDate($conventionId, $priseEnChargeDate);
        
        if (!$conventionDetail) {
            // Return public pricing as fallback
            $prestation = Prestation::find($prestationId);
            if ($prestation) {
                return [
                    'pricing_source' => 'public_pricing',
                    'convention_price' => $prestation->public_price,
                    'patient_price' => $prestation->public_price,
                    'company_price' => 0,
                    'uses_convention' => false
                ];
            }
            return null;
        }

        // Check avenant first
        if ($conventionDetail->avenant_id) {
            $pricing = PrestationPricing::with('prestation')
                ->where('avenant_id', $conventionDetail->avenant_id)
                ->where('prestation_id', $prestationId)
                ->first();

            if ($pricing) {
                return [
                    'pricing_source' => 'avenant',
                    'convention_price' => $pricing->prix_patient + $pricing->prix_company,
                    'patient_price' => $pricing->prix_patient,
                    'company_price' => $pricing->prix_company,
                    'pricing_id' => $pricing->id,
                    'avenant_id' => $conventionDetail->avenant_id,
                    'uses_convention' => true
                ];
            }
        } else {
            // Check annexes
            $annexes = Annex::where('convention_id', $conventionId)
                ->where('status', 'active')
                ->get();

            foreach ($annexes as $annex) {
                $annexPricingData = $this->conventionService->calculatePrestationPricing($annex->id);
                $prestationPricing = collect($annexPricingData)->firstWhere('prestation_id', $prestationId);

                if ($prestationPricing) {
                    return [
                        'pricing_source' => 'annex',
                        'convention_price' => $prestationPricing['prix_global'],
                        'patient_price' => $prestationPricing['prix_patient'],
                        'company_price' => $prestationPricing['prix_company'],
                        'annex_id' => $annex->id,
                        'uses_convention' => true
                    ];
                }
            }
        }

        // Fallback to prestation pricing
        $pricing = PrestationPricing::with('prestation')
            ->where('convention_id', $conventionId)
            ->where('prestation_id', $prestationId)
            ->first();

        if ($pricing) {
            return [
                'pricing_source' => 'prestation_pricing',
                'convention_price' => $pricing->prix_patient + $pricing->prix_company,
                'patient_price' => $pricing->prix_patient,
                'company_price' => $pricing->prix_company,
                'pricing_id' => $pricing->id,
                'uses_convention' => true
            ];
        }

        // Final fallback to public pricing
        $prestation = Prestation::find($prestationId);
        if ($prestation) {
            return [
                'pricing_source' => 'public_pricing',
                'convention_price' => $prestation->public_price,
                'patient_price' => $prestation->public_price,
                'company_price' => 0,
                'uses_convention' => false
            ];
        }

        return null;
    }
}
