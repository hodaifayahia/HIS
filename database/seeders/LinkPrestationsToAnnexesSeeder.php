<?php

namespace Database\Seeders;

use App\Models\B2B\Annex;
use App\Models\B2B\PrestationPricing;
use App\Models\CONFIGURATION\Prestation;
use Illuminate\Database\Seeder;

class LinkPrestationsToAnnexesSeeder extends Seeder
{
    public function run()
    {
        // Step 1: Get all prestations
        $allPrestations = Prestation::where('is_active', true)->get();

        if ($allPrestations->isEmpty()) {
            $this->command->error('❌ No active prestations found in database!');

            return;
        }

        $this->command->info('📍 Found '.$allPrestations->count().' prestations');

        // Step 2: Get all services
        $allServices = \App\Models\CONFIGURATION\Service::where('is_active', true)->get();

        if ($allServices->isEmpty()) {
            $this->command->error('❌ No active services found!');

            return;
        }

        $this->command->info('📍 Found '.$allServices->count().' services');

        // Step 3: Distribute prestations across services
        $this->command->info('🔄 Distributing prestations across services...');
        $serviceIndex = 0;
        foreach ($allPrestations as $prestation) {
            $selectedService = $allServices->get($serviceIndex % $allServices->count());
            $prestation->service_id = $selectedService->id;
            $prestation->save();
            $serviceIndex++;
        }
        $this->command->info('✅ Prestations distributed');

        // Step 4: For each annex, create PrestationPricing entries
        $this->command->info('🔗 Linking prestations to annexes...');

        $annexCount = 0;
        $prestationPricingCount = 0;

        foreach (Annex::with('convention.contractPercentages')->get() as $annex) {
            $annexCount++;

            // Get prestations for this annex's service
            $servicePrestations = Prestation::where('service_id', $annex->service_id)
                ->where('is_active', true)
                ->get();

            if ($servicePrestations->isEmpty()) {
                continue;
            }

            // Get contract percentages for this annex's convention
            $contractPercentages = $annex->convention->contractPercentages;
            if ($contractPercentages->isEmpty()) {
                $this->command->warn("⚠️ Convention {$annex->convention->id} has no contract percentages!");

                continue;
            }

            // For each prestation in this service
            foreach ($servicePrestations as $prestation) {
                // For each contract percentage
                foreach ($contractPercentages as $cp) {
                    // Check if pricing already exists
                    $exists = PrestationPricing::where('annex_id', $annex->id)
                        ->where('prestation_id', $prestation->id)
                        ->where('contract_percentage_id', $cp->id)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    // Calculate prices based on prestation_prix_status
                    $initialBasePrice = 0.00;
                    $prestationPrixStatus = $annex->prestation_prix_status;

                    switch ($prestationPrixStatus) {
                        case 'convenience_prix':
                            $conventionPrice = is_null($prestation->convenience_prix) ? 0.0 : (float) $prestation->convenience_prix;
                            $consumables = is_null($prestation->consumables_cost) ? 0.0 : (float) $prestation->consumables_cost;
                            $vat = is_null($prestation->vat_rate) ? 0.0 : (float) $prestation->vat_rate;
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
                            $publicPrice = is_null($prestation->public_price) ? 0.0 : (float) $prestation->public_price;
                            $consumables = is_null($prestation->consumables_cost) ? 0.0 : (float) $prestation->consumables_cost;
                            $vat = is_null($prestation->vat_rate) ? 0.0 : (float) $prestation->vat_rate;
                            $consumablesVat = is_null($prestation->tva_const_prestation ?? null) ? null : (float) $prestation->tva_const_prestation;

                            if ($consumablesVat !== null && $consumablesVat > 0) {
                                $ttcBase = $publicPrice * (1 + $vat / 100);
                                $ttcConsumables = $consumables * (1 + $consumablesVat / 100);
                                $initialBasePrice = round($ttcBase + $ttcConsumables, 2);
                            } else {
                                $base = $publicPrice + $consumables;
                                $initialBasePrice = round($base * (1 + $vat / 100), 2);
                            }
                            break;

                        case 'empty':
                        default:
                            $initialBasePrice = is_null($prestation->public_price) ? 0.0 : (float) $prestation->public_price;
                            break;
                    }

                    // Calculate company/patient split
                    $discountPercentage = $cp->percentage ?? 50;
                    $companyShareFactor = $discountPercentage / 100;
                    $originalCompanyShare = $initialBasePrice * $companyShareFactor;
                    $originalPatientShare = $initialBasePrice - $originalCompanyShare;

                    $maxPrice = $annex->convention->conventionDetail->max_price ?? 0;
                    $maxPriceExceeded = false;
                    $finalCompanyPrice = $originalCompanyShare;
                    $finalPatientPrice = $originalPatientShare;

                    if ($maxPrice > 0 && $originalCompanyShare > $maxPrice) {
                        $maxPriceExceeded = true;
                        $excess = $originalCompanyShare - $maxPrice;
                        $finalCompanyPrice = $maxPrice;
                        $finalPatientPrice = $originalPatientShare + $excess;
                    }

                    // Create PrestationPricing
                    PrestationPricing::create([
                        'prestation_id' => $prestation->id,
                        'annex_id' => $annex->id,
                        'contract_percentage_id' => $cp->id,
                        'prix' => $initialBasePrice,
                        'company_price' => $finalCompanyPrice,
                        'patient_price' => $finalPatientPrice,
                        'max_price_exceeded' => $maxPriceExceeded,
                        'original_company_share' => $originalCompanyShare,
                        'original_patient_share' => $originalPatientShare,
                        'activation_at' => now(),
                    ]);

                    $prestationPricingCount++;
                }
            }
        }

        $this->command->info("✅ Linked $prestationPricingCount prestation pricing entries across $annexCount annexes!");
    }
}
