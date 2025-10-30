<?php

namespace App\Actions\Reception;

use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PreparePackageConversionData
 * 
 * Single Responsibility: Analyze a fiche's items and prepare conversion data if applicable.
 * 
 * Returns:
 * [
 *   'should_convert' => bool,
 *   'package_id' => ?int,
 *   'items_to_remove' => array of full item objects,
 *   'message' => string,
 *   'is_cascading' => bool,
 * ]
 * 
 * Handles both simple and cascading conversions:
 * - Simple: 2+ individual prestations match a package
 * - Cascading: existing package + new item = larger package
 */
class PreparePackageConversionData
{
    public function __construct(
        private DetectMatchingPackage $detector
    ) {}

    public function execute(
        int $ficheNavetteId,
        array $newPrestationIds,
        array $existingPrestationIds = []
    ): array {
        try {
            $fiche = ficheNavette::findOrFail($ficheNavetteId);

            // Gather all items in fiche
            $existingPackageItems = ficheNavetteItem::where('fiche_navette_id', $ficheNavetteId)
                ->whereNotNull('package_id')
                ->get();

            // Extract prestations from packages
            $packagePrestationIds = $this->extractPackagePrestations($existingPackageItems);

            // Combine all prestation IDs (unique)
            $allPrestationIds = array_unique(
                array_merge($existingPrestationIds, $packagePrestationIds, $newPrestationIds)
            );
            sort($allPrestationIds);

            Log::info('📊 Analyzing package conversion', [
                'fiche_id' => $ficheNavetteId,
                'new_prestations' => $newPrestationIds,
                'existing_prestations' => $existingPrestationIds,
                'package_prestations' => $packagePrestationIds,
                'combined_prestations' => $allPrestationIds,
                'existing_packages_count' => $existingPackageItems->count(),
            ]);

            // Check for matching package
            $matchingPackage = $this->detector->execute($allPrestationIds);

            if (!$matchingPackage) {
                Log::info('No package match found');
                return $this->noConversionResponse('No matching package found');
            }

            // Prepare items to remove (only those belonging to the target package)
            $targetPrestationIds = $matchingPackage->prestations->pluck('id')->toArray();
            $itemsToRemove = $this->identifyItemsToRemove(
                $ficheNavetteId,
                $targetPrestationIds,
                $existingPackageItems
            );

            // Check payment status
            if ($this->hasAllPaidItems($itemsToRemove)) {
                return $this->noConversionResponse('All existing items are paid - cannot convert');
            }

            // Check package status
            if (!$matchingPackage->is_active) {
                return $this->noConversionResponse('Matching package is not active');
            }

            Log::info('✅ Can convert', [
                'package_id' => $matchingPackage->id,
                'items_to_remove_count' => $itemsToRemove->count(),
                'is_cascading' => $existingPackageItems->isNotEmpty(),
            ]);

            return [
                'should_convert' => true,
                'package_id' => $matchingPackage->id,
                'package_name' => $matchingPackage->name,
                'items_to_remove' => $itemsToRemove->map(fn($item) => $item->toArray())->toArray(),
                'is_cascading' => $existingPackageItems->isNotEmpty(),
                'message' => $existingPackageItems->isNotEmpty()
                    ? 'Cascading conversion: replacing existing package with new package'
                    : 'Ready for auto-conversion',
            ];

        } catch (\Exception $e) {
            Log::error('Error preparing package conversion', [
                'fiche_id' => $ficheNavetteId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->noConversionResponse('Error: ' . $e->getMessage());
        }
    }

    /**
     * Extract prestation IDs from existing package items
     */
    private function extractPackagePrestations($packageItems): array
    {
        $prestationIds = [];

        foreach ($packageItems as $item) {
            if ($item->package_id) {
                $packagePrestations = DB::table('prestation_package_items')
                    ->where('prestation_package_id', $item->package_id)
                    ->pluck('prestation_id')
                    ->toArray();
                $prestationIds = array_merge($prestationIds, $packagePrestations);
            }
        }

        return $prestationIds;
    }

    /**
     * Identify which items should be removed (only those in target package)
     */
    private function identifyItemsToRemove(
        int $ficheNavetteId,
        array $targetPrestationIds,
        $existingPackageItems
    ) {
        // Filter package items whose prestations are all in target
        $packageItemsToRemove = $existingPackageItems->filter(function ($item) use ($targetPrestationIds) {
            if (!$item->package_id) {
                return false;
            }

            $packagePrestations = DB::table('prestation_package_items')
                ->where('prestation_package_id', $item->package_id)
                ->pluck('prestation_id')
                ->toArray();

            return empty(array_diff($packagePrestations, $targetPrestationIds));
        });

        // Add individual prestations that match the target package
        $individualItems = ficheNavetteItem::where('fiche_navette_id', $ficheNavetteId)
            ->whereNotNull('prestation_id')
            ->whereIn('prestation_id', $targetPrestationIds)
            ->get();

        return collect()
            ->merge($packageItemsToRemove)
            ->merge($individualItems)
            ->unique('id');
    }

    /**
     * Check if all items are paid
     */
    private function hasAllPaidItems($items): bool
    {
        $unpaidCount = $items->filter(fn($item) => 
            !$item->payment_status || $item->payment_status === 'unpaid'
        )->count();

        return $unpaidCount === 0 && $items->count() > 0;
    }

    /**
     * Standard no-conversion response
     */
    private function noConversionResponse(string $message): array
    {
        return [
            'should_convert' => false,
            'package_id' => null,
            'items_to_remove' => [],
            'is_cascading' => false,
            'message' => $message,
        ];
    }
}
