<?php

namespace App\Services\B2B;

use App\Models\B2B\Convention;
use App\Models\B2B\ConventionDetail;
use App\Models\B2B\Annex;
use App\Models\CONFIGURATION\Prestation;
use Illuminate\Support\Facades\DB;
// Carbon
 use Illuminate\Support\Carbon;


class ConventionService
{
    public function createConvention(array $data): Convention
    {
        return DB::transaction(function () use ($data) {
            $convention = Convention::create([
                'organisme_id' => $data['organisme_id'],
                'name' => $data['name'],
                'status' => $data['status'],
            ]);

            $convention->conventionDetail()->create([
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'family_auth' => $data['family_auth'],
                'max_price' => $data['max_price'],
                'min_price' => $data['min_price'],
                'discount_percentage' => $data['discount_percentage'],
            ]);

            return $convention->load('conventionDetail');
        });
    }

    public function updateConvention(Convention $convention, array $data): Convention
    {
        return DB::transaction(function () use ($convention, $data) {
            // dd($data);
            // Update the convention
            $convention->update([
                'name' => $data['name'],
                'status' => $data['status'],
            ]);

            // Update or create convention details
            $convention->conventionDetail()->updateOrCreate(
                ['convention_id' => $convention->id],
                [
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'family_auth' => $data['family_auth'],
                    'max_price' => $data['max_price'],
                    'min_price' => $data['min_price'],
                    'discount_percentage' => $data['discount_percentage'],
                ]
            );

            return $convention->load('conventionDetail');
        });
    }
   public function calculatePrestationPricing($annexId)
    {
        $annex = Annex::find($annexId);
        
        if (!$annex) {
            throw new \Exception('Annex not found');
        }

        $convention = Convention::with(['conventionDetail', 'organisme'])->find($annex->convention_id);
        
        if (!$convention || !$convention->conventionDetail) {
            throw new \Exception('Convention or Convention Detail not found');
        }

        $serviceId = $annex->service_id;
        $prestationPrixStatus = $annex->prestation_prix_status;

        // Get all prestations for this service
        $prestations = Prestation::where('service_id', $serviceId)->get();

        $results = [];

        foreach ($prestations as $prestation) {
            $pricing = $this->calculatePrestationPrice($prestation, $convention, $prestationPrixStatus);
            
            // Create formatted identifier: organisme_abrv + service_id + prestation_id
            $organismeAbrv = $convention->organisme->abrv ?? 'N/A';
            $formattedId = $organismeAbrv . '_' . $serviceId . '_' . $prestation->id;
            
            $results[] = [
                'prestation_id' => $prestation->id,
                'prestation_name' => $prestation->name ?? 'N/A',
                'service_id' => $serviceId,
                'organisme_abrv' => $organismeAbrv,
                'formatted_id' => $formattedId,
                'prix_global' => $pricing['prix_global'],
                'prix_company' => $pricing['prix_company'],
                'prix_patient' => $pricing['prix_patient'],
                'max_price_exceeded' => $pricing['max_price_exceeded'],
                'original_company_share' => $pricing['original_company_share'],
                'original_patient_share' => $pricing['original_patient_share']
            ];
        }

        return $results;
    }

    private function calculatePrestationPrice($prestation, $convention, $prestationPrixStatus)
    {
        $conventionDetail = $convention->conventionDetail;
        $discountPercentage = $conventionDetail->discount_percentage;
        $maxPrice = $conventionDetail->max_price;
        
        // Determine the base price based on prestation_prix_status
        $basePrice = $this->getBasePrice($prestation, $prestationPrixStatus);
        // If base price is 0 or empty, everything is 0
        if ($basePrice <= 0) {
            return [
                'prix_global' => 0,
                'prix_company' => 0,
                'prix_patient' => 0,
                'max_price_exceeded' => false,
                'original_company_share' => 0,
                'original_patient_share' => 0
            ];
        }

        // Calculate company and patient shares
        $companySharePercentage = $discountPercentage / 100;
        $patientSharePercentage = 1 - $companySharePercentage;
        
        $originalCompanyShare = $basePrice * $companySharePercentage;
        $originalPatientShare = $basePrice * $patientSharePercentage;
        
        // Check if company share exceeds max price
        $maxPriceExceeded = false;
        $finalCompanyShare = $originalCompanyShare;
        $finalPatientShare = $originalPatientShare;
        
        if ($maxPrice > 0 && $originalCompanyShare > $maxPrice) {
            $maxPriceExceeded = true;
            $excess = $originalCompanyShare - $maxPrice;
            $finalCompanyShare = $maxPrice;
            $finalPatientShare = $originalPatientShare + $excess;
        }

        return [
            'prix_global' => $basePrice,
            'prix_company' => $finalCompanyShare,
            'prix_patient' => $finalPatientShare,
            'max_price_exceeded' => $maxPriceExceeded,
            'original_company_share' => $originalCompanyShare,
            'original_patient_share' => $originalPatientShare
        ];
    }

    private function getBasePrice($prestation, $prestationPrixStatus)
    {
        switch ($prestationPrixStatus) {
            case 'convenience_prix':
                return $prestation->convenience_prix ?? 0;
            case 'public_prix':
                return $prestation->public_price ?? 0;
            case 'empty':
            default:
                return $prestation->PrixGloble ?? 0;
        }
    }
     public function activateConventionById(int $conventionId, string $activationDate, bool $isDelayedActivation): array
    {
        return DB::transaction(function () use ($conventionId, $activationDate, $isDelayedActivation) {
            $convention = Convention::findOrFail($conventionId);
            $activationCarbon = Carbon::parse($activationDate);

            if ($isDelayedActivation) {
                // For delayed activation, just set the activation_at date and status (e.g., 'scheduled')
                $convention->update([
                    'activation_at' => $activationCarbon,
                    'status' => 'scheduled', // Or 'pending-activation' or similar
                ]);

                // You might have related models (like PrestationPricing or ConventionDetail from your avenant example)
                // that also need their activation_at or start_date updated for the convention.
                // Add similar logic here if applicable to your conventions.
                // Example:
                // ConventionDetail::where('convention_id', $conventionId)
                //                 ->update(['start_date' => $activationCarbon]);

                return [
                    'conventionId' => $conventionId,
                    'scheduledAt' => $activationDate,
                    'status' => $convention->status,
                ];
            } else {
                // Immediate activation:
                // Deactivate any currently active conventions if your business logic requires only one active at a time
                // Convention::where('status', 'active')
                //           ->where('id', '!=', $conventionId) // Exclude the current one if it was already active
                //           ->update(['status' => 'archived', 'inactive_at' => Carbon::now()]);

                $convention->update([
                    'status' => 'active',
                    'activation_at' => $activationCarbon,
                ]);

                // Update related models if they need immediate activation
                // Example:
                // ConventionDetail::where('convention_id', $conventionId)
                //                 ->update(['start_date' => $activationCarbon]);

                return [
                    'conventionId' => $conventionId,
                    'activatedAt' => $activationDate,
                    'status' => 'active',
                ];
            }
        });
    }

    public function expireConventionById(int $conventionId): array
    {
        return DB::transaction(function () use ($conventionId) {
            $convention = Convention::findOrFail($conventionId);
            $convention->status = 'terminated';
            $convention->save();

            // You might want to also update related models to reflect termination
            // e.g., ConventionDetail::where('convention_id', $conventionId)->update(['end_date' => Carbon::now()]);

            return [
                'conventionId' => $conventionId,
                'status' => 'terminated',
            ];
        });
    }
}