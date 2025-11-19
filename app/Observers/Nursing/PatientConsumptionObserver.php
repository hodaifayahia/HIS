<?php

namespace App\Observers\Nursing;

use App\Models\Nursing\PatientConsumption;
use App\Models\Reception\ficheNavetteItem;
use Illuminate\Support\Facades\Log;

class PatientConsumptionObserver
{
    /**
     * Handle the PatientConsumption "created" event.
     */
    public function created(PatientConsumption $patientConsumption): void
    {
        Log::info('PatientConsumption created observer triggered', [
            'consumption_id' => $patientConsumption->id,
            'fiche_id' => $patientConsumption->fiche_id,
            'product_id' => $patientConsumption->product_id,
        ]);

        $this->createFicheNavetteItemIfNeeded($patientConsumption);
    }

    /**
     * Handle the PatientConsumption "updated" event.
     */
    public function updated(PatientConsumption $patientConsumption): void
    {
        // Only create/update if fiche_id was added or changed
        if ($patientConsumption->wasChanged('fiche_id') && $patientConsumption->fiche_id) {
            $this->createFicheNavetteItemIfNeeded($patientConsumption);
        }
    }

    /**
     * Handle the PatientConsumption "deleted" event.
     */
    public function deleted(PatientConsumption $patientConsumption): void
    {
        $this->deleteRelatedFicheNavetteItem($patientConsumption);
    }

    /**
     * Create ficheNavetteItem if it doesn't exist
     */
    private function createFicheNavetteItemIfNeeded(PatientConsumption $consumption): void
    {
        // Only proceed if we have fiche_id and product_id
        if (! $consumption->fiche_id || ! $consumption->product_id) {
            return;
        }

        // Load relationships if not already loaded
        if (! $consumption->relationLoaded('product')) {
            $consumption->load('product');
        }
        if (! $consumption->relationLoaded('pharmacy')) {
            $consumption->load('pharmacy');
        }
        if (! $consumption->relationLoaded('ficheNavette')) {
            $consumption->load('ficheNavette');
        }

        try {
            Log::info('Creating ficheNavetteItem for consumption', [
                'consumption_id' => $consumption->id,
                'fiche_id' => $consumption->fiche_id,
                'product_id' => $consumption->product_id,
            ]);

            // For nursing items, we'll create a new ficheNavetteItem for each consumption
            // This allows for better tracking of individual consumptions
            $item = $this->createNewFicheNavetteItem($consumption);

            Log::info('Successfully created ficheNavetteItem', [
                'item_id' => $item->id,
                'consumption_id' => $consumption->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create/update ficheNavetteItem from PatientConsumption', [
                'consumption_id' => $consumption->id,
                'fiche_id' => $consumption->fiche_id,
                'product_id' => $consumption->product_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create new ficheNavetteItem record
     */
    private function createNewFicheNavetteItem(PatientConsumption $consumption): ficheNavetteItem
    {
        // Calculate prices
        $basePrice = $this->calculateBasePrice($consumption);
        $finalPrice = $this->calculateFinalPrice($consumption);

        return ficheNavetteItem::create([
            'fiche_navette_id' => $consumption->fiche_id,
            'prestation_id' => null, // Nursing products don't have prestations
            'status' => 'pending',
            'base_price' => 0,
            'final_price' => 0,
            'patient_share' => 0,
            'organisme_share' => 0,
            'primary_clinician_id' => $consumption->consumed_by,
            'patient_id' => $consumption->ficheNavette->patient_id ?? null,
            'package_id' => null,
            'convention_id' => null,
            'insured_id' => null,
            'remaining_amount' => $finalPrice,
            'paid_amount' => 0,
            'payment_status' => 'pending',
            'payment_method' => null,
            'prise_en_charge_date' => null,
            'family_authorization' => null,
            'uploaded_file' => "nursing_product_id:{$consumption->product_id}|consumption_id:{$consumption->id}|qty:{$consumption->quantity}|".$this->getProductName($consumption),
        ]);
    }

    /**
     * Delete related ficheNavetteItem when consumption is deleted
     */
    private function deleteRelatedFicheNavetteItem(PatientConsumption $consumption): void
    {
        if (! $consumption->fiche_id || ! $consumption->product_id) {
            return;
        }

        try {
            // Find ficheNavetteItem that was created from this consumption
            ficheNavetteItem::where('fiche_navette_id', $consumption->fiche_id)
                ->whereNull('prestation_id') // Nursing items have no prestation_id
                ->where('uploaded_file', 'LIKE', "%consumption_id:{$consumption->id}%")
                ->delete();

        } catch (\Exception $e) {
            Log::error('Failed to delete related ficheNavetteItem', [
                'consumption_id' => $consumption->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate base price from consumption
     */
    private function calculateBasePrice(PatientConsumption $consumption): float
    {
        // If consumption has base_price, use it
        if (isset($consumption->base_price) && $consumption->base_price > 0) {
            return $consumption->base_price * $consumption->quantity;
        }

        // Try to get price from product relationship
        if ($consumption->product && isset($consumption->product->price) && $consumption->product->price > 0) {
            return $consumption->product->price * $consumption->quantity;
        }

        // Try to get price from pharmacy product relationship
        if ($consumption->pharmacy && isset($consumption->pharmacy->price) && $consumption->pharmacy->price > 0) {
            return $consumption->pharmacy->price * $consumption->quantity;
        }

        // Default price for nursing items without price (can be configured)
        return 10.0 * $consumption->quantity; // Default 10 units per item
    }

    /**
     * Calculate final price from consumption
     */
    private function calculateFinalPrice(PatientConsumption $consumption): float
    {
        // If consumption has final_price, use it
        if (isset($consumption->final_price) && $consumption->final_price > 0) {
            return $consumption->final_price * $consumption->quantity;
        }

        // Fallback to base price calculation
        return $this->calculateBasePrice($consumption);
    }

    /**
     * Get product name for custom_name field
     */
    private function getProductName(PatientConsumption $consumption): ?string
    {
        // Try to get name from product relationship
        if ($consumption->product && $consumption->product->name) {
            return $consumption->product->name;
        }

        // Try to get name from pharmacy product relationship
        if ($consumption->pharmacy && $consumption->pharmacy->name) {
            return $consumption->pharmacy->name;
        }

        // Fallback to generic name
        return "Nursing Item - Product ID: {$consumption->product_id}";
    }
}
