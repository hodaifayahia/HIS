<?php

namespace App\Actions\Reception;

use App\Models\Reception\ficheNavette;
use App\Models\CONFIGURATION\PrestationPackage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DetectMatchingPackage
 * 
 * Single Responsibility: Given a set of prestation IDs, find the best matching package.
 * 
 * Returns the LARGEST matching package (most prestations) where:
 * - All package prestations exist in the provided list
 * - Exact matches are preferred over subset matches
 * 
 * Example:
 *   Input prestations: [5, 87, 88]
 *   Package A: [5, 87] ✓ matches but only 2 of 3
 *   Package B: [5, 87, 88] ✓ BEST - matches all 3
 *   Result: Package B
 */
class DetectMatchingPackage
{
    public function execute(array $prestationIds): ?PrestationPackage
    {
        if (empty($prestationIds)) {
            Log::info('DetectMatchingPackage: Empty prestation list');
            return null;
        }

        try {
            $packages = PrestationPackage::with('prestations')->get();

            $bestMatch = null;
            $bestMatchCount = 0;
            $bestMatchIsExact = false;

            Log::info('🔍 Searching for matching package', [
                'prestations' => $prestationIds,
                'count' => count($prestationIds),
            ]);

            foreach ($packages as $package) {
                $packagePrestationIds = $package->prestations->pluck('id')->toArray();
                $missingPrestations = array_diff($packagePrestationIds, $prestationIds);

                // Skip if package requires prestations we don't have
                if (!empty($missingPrestations)) {
                    continue;
                }

                $matchCount = count($packagePrestationIds);
                $isExactMatch = $matchCount === count($prestationIds) && 
                                empty(array_diff($prestationIds, $packagePrestationIds));

                Log::info('Package match candidate', [
                    'package_id' => $package->id,
                    'name' => $package->name,
                    'prestation_count' => $matchCount,
                    'is_exact' => $isExactMatch,
                ]);

                // Prefer exact matches; for non-exact, pick the largest
                if ($isExactMatch) {
                    if (!$bestMatchIsExact || $matchCount > $bestMatchCount) {
                        $bestMatch = $package;
                        $bestMatchCount = $matchCount;
                        $bestMatchIsExact = true;
                    }
                } elseif (!$bestMatchIsExact && $matchCount > $bestMatchCount) {
                    $bestMatch = $package;
                    $bestMatchCount = $matchCount;
                }
            }

            if ($bestMatch) {
                Log::info('🏆 Best matching package selected', [
                    'package_id' => $bestMatch->id,
                    'name' => $bestMatch->name,
                    'prestation_count' => $bestMatchCount,
                ]);
                return $bestMatch;
            }

            Log::info('❌ No matching package found for prestations', ['count' => count($prestationIds)]);
            return null;

        } catch (\Exception $e) {
            Log::error('Error in DetectMatchingPackage', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return null;
        }
    }
}
