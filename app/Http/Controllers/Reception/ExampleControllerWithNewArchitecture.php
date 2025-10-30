<?php

namespace App\Http\Controllers\Reception;

use App\Services\Reception\PackageConversionFacade;
use App\Http\Requests\Reception\StoreFicheNavetteItemsRequest;
use App\Http\Resources\Reception\FicheNavetteResource;
use App\Models\Reception\ficheNavette;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Example: How to Use PackageConversionFacade in Controller
 * 
 * This shows the SIMPLIFIED approach using the new Facade.
 * Replace the old checkAndPreparePackageConversion calls with this.
 */
class ExampleFicheNavetteItemControllerWithFacade
{
    public function __construct(
        private PackageConversionFacade $conversionService
    ) {}

    /**
     * Store items to an existing fiche navette - WITH AUTO-CONVERSION
     * 
     * New approach using PackageConversionFacade:
     * - Cleaner code
     * - Uses new architecture (Actions, Observers, Events, Jobs)
     * - Async processing (non-blocking)
     */
    public function storeWithNewArchitecture(
        StoreFicheNavetteItemsRequest $request,
        $ficheNavetteId
    ): JsonResponse {
        try {
            $fiche = ficheNavette::findOrFail($ficheNavetteId);

            // Step 1: Extract existing and new prestation IDs
            $existingPrestationIds = $fiche->items()
                ->whereNotNull('prestation_id')
                ->whereNull('package_id')
                ->pluck('prestation_id')
                ->toArray();

            $newPrestationIds = $this->extractPrestationIds($request->validated());

            Log::info('📝 Adding items to fiche', [
                'fiche_id' => $ficheNavetteId,
                'existing_prestations' => $existingPrestationIds,
                'new_prestations' => $newPrestationIds,
            ]);

            // Step 2: Check if conversion is needed (using new Facade)
            $conversionResult = $this->conversionService->checkAndPrepare(
                $ficheNavetteId,
                $newPrestationIds,
                $existingPrestationIds
            );

            $conversion = [
                'converted' => false,
                'package_name' => null,
                'is_cascading' => false,
            ];

            // Step 3: If conversion needed, execute it (using new Facade)
            if ($conversionResult['should_convert']) {
                Log::info('✅ Auto-conversion triggered', [
                    'package_id' => $conversionResult['package_id'],
                    'package_name' => $conversionResult['package_name'],
                    'is_cascading' => $conversionResult['is_cascading'],
                ]);

                // Extract item IDs to remove
                $itemIdsToRemove = array_map(
                    fn($item) => $item['id'],
                    $conversionResult['items_to_remove']
                );

                // Execute conversion (using new Facade)
                $updatedFiche = $this->conversionService->execute(
                    $ficheNavetteId,
                    $conversionResult['package_id'],
                    $itemIdsToRemove,
                    $newPrestationIds
                );

                // Set conversion response data
                $conversion = [
                    'converted' => true,
                    'package_name' => $conversionResult['package_name'],
                    'is_cascading' => $conversionResult['is_cascading'],
                ];

                Log::info('✨ Conversion completed successfully', [
                    'fiche_id' => $ficheNavetteId,
                    'package_id' => $conversionResult['package_id'],
                ]);
            } else {
                Log::info('➡️ No conversion - adding items normally', [
                    'reason' => $conversionResult['message'] ?? 'Unknown',
                ]);

                // No conversion - add items normally (existing logic)
                // $updatedFiche = $this->receptionService->addItemsToFicheNavette($fiche, $request->validated());
            }

            // Step 4: Load relationships and return response
            $fiche->refresh();
            $fiche->loadMissing([
                'items.prestation',
                'items.package',
                'items.dependencies.dependencyPrestation',
                'items.convention',
                'patient',
                'creator',
            ]);

            return response()->json([
                'success' => true,
                'message' => $conversion['converted']
                    ? "Auto-converted to package: {$conversion['package_name']}"
                    : 'Items added successfully',
                'data' => new FicheNavetteResource($fiche),
                'conversion' => $conversion,
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Error adding items to fiche', [
                'fiche_id' => $ficheNavetteId,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Just detect package (no conversion)
     * Useful for UI showing "Will be converted to X package"
     */
    public function detectPackageForPrestations($ficheNavetteId): JsonResponse
    {
        try {
            $fiche = ficheNavette::findOrFail($ficheNavetteId);

            $prestationIds = $fiche->items()
                ->whereNotNull('prestation_id')
                ->pluck('prestation_id')
                ->toArray();

            // Use Facade to detect
            $package = $this->conversionService->detectPackage($prestationIds);

            if ($package) {
                return response()->json([
                    'success' => true,
                    'message' => "Found matching package",
                    'package' => [
                        'id' => $package->id,
                        'name' => $package->name,
                        'description' => $package->description,
                        'price' => $package->price,
                    ],
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'No matching package found',
                'package' => null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: Extract prestation IDs from request
     */
    private function extractPrestationIds(array $validated): array
    {
        $ids = [];

        if (isset($validated['prestations'])) {
            foreach ($validated['prestations'] as $prestation) {
                if (isset($prestation['prestation_id'])) {
                    $ids[] = $prestation['prestation_id'];
                } elseif (isset($prestation['id'])) {
                    $ids[] = $prestation['id'];
                }
            }
        }

        return $ids;
    }
}
