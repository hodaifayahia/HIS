<?php

namespace App\Actions\Reception;

use App\Events\Reception\PrestationsConvertedToPackage;
use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use App\Models\CONFIGURATION\PrestationPackage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ExecutePackageConversion
 * 
 * Single Responsibility: Execute the actual conversion - remove old items, create new package item.
 * 
 * Handles:
 * - Removing old prestation and package items
 * - Extracting and preserving doctor assignments
 * - Creating new package item with proper pricing and metadata
 * - Firing event for downstream listeners
 */
class ExecutePackageConversion
{
    public function execute(
        int $ficheNavetteId,
        int $packageId,
        array $itemIdsToRemove,
        array $newPrestationIds = []
    ): ficheNavette {
        return DB::transaction(function () use ($ficheNavetteId, $packageId, $itemIdsToRemove, $newPrestationIds) {
            $fiche = ficheNavette::findOrFail($ficheNavetteId);
            $package = PrestationPackage::findOrFail($packageId);

            Log::info('🔄 Executing package conversion', [
                'fiche_id' => $ficheNavetteId,
                'package_id' => $packageId,
                'items_to_remove_count' => count($itemIdsToRemove),
            ]);

            // Retrieve items to remove
            $itemsToRemove = ficheNavetteItem::whereIn('id', $itemIdsToRemove)
                ->where('fiche_navette_id', $ficheNavetteId)
                ->get();

            if ($itemsToRemove->isEmpty()) {
                throw new \InvalidArgumentException('No items found to remove for conversion');
            }

            // Extract data before deletion
            $totalAmountToRemove = 0;
            $doctorIds = [];
            $oldPackageIds = [];

            foreach ($itemsToRemove as $item) {
                $totalAmountToRemove += $item->final_price;
                if ($item->doctor_id) {
                    $doctorIds[] = $item->doctor_id;
                }
                if ($item->package_id) {
                    $oldPackageIds[] = $item->package_id;
                }
                $item->delete();
            }

            $oldPackageIds = array_unique($oldPackageIds);
            $uniqueDoctorIds = array_unique($doctorIds);
            $finalDoctorId = count($uniqueDoctorIds) === 1 ? $uniqueDoctorIds[0] : null;

            Log::info('Items removed', [
                'count' => $itemsToRemove->count(),
                'total_amount' => $totalAmountToRemove,
                'doctors_extracted' => count($uniqueDoctorIds),
                'old_packages_replaced' => $oldPackageIds,
            ]);

            // Get first item for convention/metadata
            $firstItem = $itemsToRemove->first();

            // Calculate package price
            $packagePrice = $this->normalizePackagePrice($package);

            // Create new package item
            $newPackageItem = ficheNavetteItem::create([
                'fiche_navette_id' => $ficheNavetteId,
                'package_id' => $packageId,
                'prestation_id' => null,
                'doctor_id' => $finalDoctorId,
                'convention_id' => $firstItem->convention_id ?? null,
                'insured_id' => $firstItem->insured_id ?? $fiche->patient_id,
                'status' => 'pending',
                'base_price' => $packagePrice,
                'final_price' => $packagePrice,
                'patient_share' => $packagePrice,
                'default_payment_type' => $firstItem->default_payment_type ?? null,
                'prise_en_charge_date' => $firstItem->prise_en_charge_date ?? now(),
                'family_authorization' => $firstItem->family_authorization ?? null,
                'uploaded_file' => $firstItem->uploaded_file ?? null,
                'notes' => !empty($oldPackageIds)
                    ? 'Auto-converted from packages ' . implode(', ', $oldPackageIds) . ' to: ' . $package->name
                    : 'Auto-converted to: ' . $package->name,
            ]);

            Log::info('✅ Package item created', [
                'new_item_id' => $newPackageItem->id,
                'package_id' => $packageId,
                'price' => $packagePrice,
            ]);

            // Update fiche total
            $fiche->update(['total_amount' => ($fiche->total_amount ?? 0) - $totalAmountToRemove + $packagePrice]);

            // Fire event for listeners
            PrestationsConvertedToPackage::dispatch(
                fiche: $fiche,
                newPackage: $package,
                removedItemIds: $itemsToRemove->pluck('id')->toArray(),
                isCascading: !empty($oldPackageIds),
                oldPackageIds: $oldPackageIds,
            );

            return $fiche->fresh([
                'items.prestation',
                'items.package',
                'items.dependencies.dependencyPrestation',
                'patient',
                'creator',
            ]);
        });
    }

    /**
     * Normalize package price (handle arrays and variants)
     */
    private function normalizePackagePrice(PrestationPackage $package): float
    {
        $price = $package->price ?? 0;

        if (is_array($price)) {
            return (float) ($price['ttc_with_consumables_vat'] ?? $price['ttc'] ?? $price['price'] ?? 0);
        }

        return (float) $price;
    }
}
