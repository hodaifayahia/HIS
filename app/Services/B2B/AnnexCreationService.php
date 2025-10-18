<?php

namespace App\Services\B2B;

use App\Models\B2B\Annex;
use App\Models\B2B\Convention;
use App\Models\CONFIGURATION\Prestation;
use App\Models\B2B\PrestationPricing;
use App\Models\ContractPercentage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AnnexCreationService
{
    protected $conventionService;

    /**
     * Constructor to inject ConventionService.
     *
     * @param ConventionService $conventionService
     */
    public function __construct(ConventionService $conventionService)
    {
        $this->conventionService = $conventionService;
    }

    /**
     * Create a new annex and initialize its associated prestation pricings.
     *
     * @param array $data Validated data for the annex.
     * @param string|null $contractId Optional contract ID if coming from storeWithContract.
     * @return Annex
     * @throws \Exception
     */
    public function createAnnexAndInitializePrestations(array $data, ?string $contractId = null): Annex
    {
        // Start a database transaction to ensure atomicity
        return DB::transaction(function () use ($data, $contractId) {
            $annexData = [
                'annex_name' => $data['annex_name'],
                'convention_id' => $contractId ?? $data['convention_id'],
                'service_id' => $data['service_id'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'min_price' => $data['min_price'] ?? null,
                'prestation_prix_status' => $data['prestation_prix_status'],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ];

            // Create the new Annex record
            $annex = Annex::create($annexData);

            // Fetch the associated Convention and its details to get discount_percentage and max_price
            $convention = Convention::with('conventionDetail', 'contractPercentages')->find($annex->convention_id);

            if (!$convention || !$convention->conventionDetail) {
                throw new \Exception('Associated Convention or Convention Detail not found for annex initialization.');
            }

            $contractPercentages = $convention->contractPercentages;
            if ($contractPercentages->isEmpty()) {
                // Fallback to old single percentage if no percentages set
                $contractPercentages = collect([['id' => null, 'percentage' => $convention->conventionDetail->discount_percentage ?? 0]]);
            }
            $maxPrice = $convention->conventionDetail->max_price;

            // Fetch all prestations associated with the service chosen for this new annex
            $prestations = Prestation::where('service_id', $annex->service_id)->get();
            $prestationPrixStatus = $annex->prestation_prix_status;

            // Loop through each fetched prestation and create an entry in the prestation_pricing table
            foreach ($prestations as $prestation) {
                // For each prestation, create entries for each percentage
                foreach ($contractPercentages as $contractPercentage) {
                    $discountPercentage = $contractPercentage['percentage'] ?? $contractPercentage->percentage;

                    // Determine the initial base price based on the annex's prestation_prix_status
                    // Use the calculated price with VAT from the prestation model
                    $initialBasePrice = 0.00;
                    switch ($prestationPrixStatus) {
                        case 'convenience_prix':
                            // Use convenience price with VAT calculation
                            $conventionPrice = is_null($prestation->convenience_prix) ? 0.0 : (float) $prestation->convenience_prix;
                            $consumables = is_null($prestation->consumables_cost) ? 0.0 : (float) $prestation->consumables_cost;
                            $vat = is_null($prestation->vat_rate) ? 0.0 : (float) $prestation->vat_rate;

                            // If prestation has a special VAT for consumables, apply separately
                            $consumablesVat = is_null($prestation->tva_const_prestation ?? null) ? null : (float) $prestation->tva_const_prestation;
                            if ($consumablesVat !== null && $consumablesVat > 0) {
                                $ttcBase = $conventionPrice * (1 + $vat / 100);
                                $ttcConsumables = $consumables * (1 + $consumablesVat / 100);
                                $initialBasePrice = round($ttcBase + $ttcConsumables, 2);
                            } else {
                                $base = $conventionPrice + $consumables;
                                $initialBasePrice = round($base * (1 + $vat / 100), 2);
                            }
                            break;
                        case 'public_prix':
                            // Use public price with VAT calculation (from prestation's getPriceWithVatAttribute)
                            // Prefer prestation's accessor that now applies separate consumables VAT when present
                            $initialBasePrice = $prestation->price_with_vat ?? 0.00;
                            break;
                        case 'empty':
                        default:
                            // Use public price with VAT as default
                            $initialBasePrice = $prestation->price_with_vat ?? 0.00;
                            break;
                    }

                    // Calculate company and patient shares based on the convention's discount and max_price
                    $companyShareFactor = $discountPercentage / 100;
                  
                    $originalCompanyShare = $initialBasePrice * $companyShareFactor;
                    $originalPatientShare = $initialBasePrice - $originalCompanyShare; // Patient share before capping

                    // Check if company share exceeds max price
                    $maxPriceExceeded = false;
                    $finalCompanyPrice = $originalCompanyShare; // This will be the value stored in company_price
                    $finalPatientPrice = $originalPatientShare; // This will be the value stored in patient_price

                    if ($maxPrice > 0 && $originalCompanyShare > $maxPrice) {
                        $maxPriceExceeded = true;
                        $excess = $originalCompanyShare - $maxPrice;
                        $finalCompanyPrice = $maxPrice;
                        $finalPatientPrice = $originalPatientShare + $excess; // Patient absorbs the excess
                    }

                    // Create the PrestationPricing entry for the current prestation and new annex
                    PrestationPricing::create([
                        'prestation_id' => $prestation->id,
                        'annex_id' => $annex->id,
                        'contract_percentage_id' => $contractPercentage['id'] ?? $contractPercentage->id,
                        'prix' => $initialBasePrice, // Storing the base price in the 'prix' column
                        'company_price' => $finalCompanyPrice,    // Storing absolute company price
                        'patient_price' => $finalPatientPrice,    // Storing absolute patient price
                        'max_price_exceeded' => $maxPriceExceeded,
                        'original_company_share' => $originalCompanyShare,
                        'original_patient_share' => $originalPatientShare,
                    ]);
                }
            }

            // Return the created annex (loaded with relationships if needed for the response)
            return $annex;
        });
    }
}
