<?php

namespace App\Services\Reception;

use App\Actions\Reception\DetectMatchingPackage;
use App\Actions\Reception\PreparePackageConversionData;
use App\Actions\Reception\ExecutePackageConversion;
use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use App\Models\CONFIGURATION\PrestationPackage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PackageConversionFacade
 * 
 * Simplified API for package conversion workflows.
 * Acts as a Facade to the Action classes - cleaner public interface.
 * 
 * All heavy lifting is delegated to Actions for testability and clarity.
 */
class PackageConversionFacade
{
    public function __construct(
        private DetectMatchingPackage $detector,
        private PreparePackageConversionData $preparer,
        private ExecutePackageConversion $executor,
    ) {}

    /**
     * Main entry point: Check if conversion is needed and provide preparation data
     */
    public function checkAndPrepare(
        int $ficheNavetteId,
        array $newPrestationIds = [],
        array $existingPrestationIds = []
    ): array {
        return $this->preparer->execute($ficheNavetteId, $newPrestationIds, $existingPrestationIds);
    }

    /**
     * Execute a conversion that was previously prepared
     */
    public function execute(
        int $ficheNavetteId,
        int $packageId,
        array $itemIdsToRemove,
        array $newPrestationIds = []
    ): ficheNavette {
        return $this->executor->execute($ficheNavetteId, $packageId, $itemIdsToRemove, $newPrestationIds);
    }

    /**
     * Detect which package matches the given prestations
     */
    public function detectPackage(array $prestationIds): ?PrestationPackage
    {
        return $this->detector->execute($prestationIds);
    }
}
