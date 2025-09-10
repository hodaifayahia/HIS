<?php

namespace App\Services\CONFIGURATION;

use App\Models\CONFIGURATION\PrestationPackage;
use App\Models\CONFIGURATION\PrestationPackageitem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Service class to handle the business logic for Prestation Packages.
 * This class encapsulates the data manipulation from the controller.
 */
class PrestationPackageService
{
    /**
     * Create a new prestation package and its associated items.
     *
     * @param array $data The validated request data.
     * @return PrestationPackage The newly created PrestationPackage model instance.
     */
    public function createPackageWithItems(array $data): PrestationPackage
    {
        return DB::transaction(function () use ($data) {
            // Create the main PrestationPackage record.
            // Using a simple array merge to handle nullable fields and
            // setting the creator.
            $package = PrestationPackage::create([
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'price' => $data['price'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Create the associated items for the package.
            $this->syncPrestationItems($package, $data['prestations']);

            return $package;
        });
    }

    /**
     * Update an existing prestation package and sync its associated items.
     *
     * @param PrestationPackage $package The package to update.
     * @param array $data The validated request data.
     * @return PrestationPackage The updated PrestationPackage model instance.
     */
    public function updatePackageWithItems(PrestationPackage $package, array $data): PrestationPackage
    {
        return DB::transaction(function () use ($package, $data) {
            // Fill the package with the new data, including the updater ID.
            // Only update fields present in the request to respect nullable nature.
            $package->fill([
                'name' => $data['name'] ?? $package->name,
                'description' => $data['description'] ?? $package->description,
                'price' => $data['price'] ?? $package->price,
                'updated_by' => Auth::id(),
            ])->save();

            // Sync the prestation items if the 'prestations' key exists in the request.
            // This is how you can add items based on the package ID.
            if (isset($data['prestations'])) {
                $this->syncPrestationItems($package, $data['prestations']);
            }

            return $package;
        });
    }

    /**
     * Clone an existing prestation package with new name and price.
     *
     * @param PrestationPackage $originalPackage
     * @param array $data
     * @return PrestationPackage
     */
    public function clonePackage(PrestationPackage $originalPackage, array $data): PrestationPackage
    {
        return DB::transaction(function () use ($originalPackage, $data) {
            // Load the original package with its items
            $originalPackage->load('items');

            // Create the cloned package
            $clonedPackage = PrestationPackage::create([
                'name' => $data['name'],
                'description' => $originalPackage->description,
                'price' => $data['price'],
                'is_active' => $originalPackage->is_active,
                'created_by' => Auth::id(),
            ]);

            // Clone all the prestation items
            $prestationIds = $originalPackage->items->pluck('prestation_id')->toArray();
            $this->syncPrestationItems($clonedPackage, $prestationIds);

            // Load the relationships for the response
            $clonedPackage->load('items.prestation');

            return $clonedPackage;
        });
    }

    /**
     * Sync the prestation items for a given package.
     * This method removes all existing items and creates new ones.
     *
     * @param PrestationPackage $package
     * @param array $prestationIds
     * @return void
     */
    protected function syncPrestationItems(PrestationPackage $package, array $prestationIds): void
    {
        // First, delete all existing items for this package.
        $package->items()->delete();

        // Then, create new items for each prestation ID provided.
        $items = collect($prestationIds)->map(function ($prestationId) {
            return [
                'prestation_id' => $prestationId,
                'created_by' => Auth::id(),
            ];
        })->toArray();

        // Use insert for efficiency when adding multiple records.
        $package->items()->createMany($items);
    }
}