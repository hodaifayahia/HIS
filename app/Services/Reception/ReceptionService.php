<?php
// filepath: d:\Projects\AppointmentSystem\AppointmentSystem-main\app\Services\Reception\ReceptionService.php

namespace App\Services\Reception;

use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;

use App\Models\Reception\ItemDependency;
use App\Models\CONFIGURATION\Prestation;
use App\Models\B2B\Convention;
use App\Models\CONFIGURATION\PrestationPackage;
use App\Models\CONFIGURATION\PrestationPackageitem;
use App\Services\Reception\FileUploadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * Class ReceptionService
 * @package App\Services\Reception
 */
class ReceptionService
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Create a new fiche navette
     */
    public function createFicheNavette(array $data): ficheNavette
    {
        DB::beginTransaction();

        try {
            $fiche = ficheNavette::create([
                'patient_id' => $data['patient_id'],
                'creator_id' => Auth::id(),
                'fiche_date' => $data['fiche_date'],
                'status' => $data['status'] ?? 'pending'
            ]);

            DB::commit();
            return $fiche->fresh(['items', 'patient', 'creator']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing fiche navette
     */
    public function updateFicheNavette(ficheNavette $ficheNavette, array $data): ficheNavette
    {
        DB::beginTransaction();

        try {
            $ficheNavette->update($data);

            DB::commit();
            return $ficheNavette->fresh(['items', 'patient', 'creator']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a fiche navette
     */
    public function deleteFicheNavette(ficheNavette $ficheNavette): bool
    {
        DB::beginTransaction();

        try {
            // Delete all associated items first
            $ficheNavette->items()->delete();
            
            // Delete the fiche navette
            $ficheNavette->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Change the status of a fiche navette
     */
    public function changeFicheNavetteStatus(ficheNavette $ficheNavette, string $newStatus): ficheNavette
    {
        $ficheNavette->update(['status' => $newStatus]);
        return $ficheNavette->fresh(['items', 'patient', 'creator']);
    }

    /**
     * Create a new fiche navette with items and dependencies
     */
    public function createFicheNavetteWithItems(array $data): ficheNavette
    {
        DB::beginTransaction();

        try {
            // 1. Create the main Fiche Navette entry
            $fiche = ficheNavette::create([
                'patient_id' => $data['patient_id'],
                'creator_id' => Auth::id(),
                'reference' => $this->generateReference(),
                'status' => 'pending',
                'fiche_date' => now(),
                'total_amount' => 0,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id()
            ]);

            $totalAmount = 0;

            // Extract convention data from the request
            $conventionData = $this->extractConventionData($data);

            // 2. Handle different types of prestations
            if (isset($data['type']) && $data['type'] === 'prestation') {
                if (isset($data['prestations']) && !empty($data['prestations'])) {
                    // Add main prestation ONLY to fiche_navette_items
                    $mainPrestation = $data['prestations'][0];
                    $mainItem = $this->addPrestationToFiche($fiche, $mainPrestation, null, $conventionData);
                    $totalAmount += $mainItem->final_price;

                    // Add dependencies ONLY to item_dependencies table
                    if (isset($data['dependencies']) && !empty($data['dependencies'])) {
                        $this->storeDependenciesOnly($mainItem, $data['dependencies'], $conventionData);
                    }
                } elseif (isset($data['packages']) && !empty($data['packages'])) {
                    // Add package
                    $packageData = $data['packages'][0];
                    $packageItems = $this->addPackageToFiche($fiche, $packageData, $conventionData);
                    foreach ($packageItems as $item) {
                        $totalAmount += $item->final_price;
                    }
                }
            } 
            // 3. Handle custom prestations (first one is main, rest are dependencies)
            elseif (isset($data['type']) && $data['type'] === 'custom' && isset($data['customPrestations'])) {
                $customItems = $this->addCustomPrestationsToFiche($fiche, $data['customPrestations'], $conventionData);
                // Only add the first (main) prestation price to total
                if (!empty($customItems)) {
                    $totalAmount += $customItems[0]->final_price;
                }
            }

            // 4. Update the total amount
            $fiche->update(['total_amount' => $totalAmount]);

            DB::commit();
            return $fiche->fresh(['items.prestation', 'items.dependencies.dependentItem.prestation', 'items.convention', 'patient', 'creator']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

   public function getPrestationsByPackage($packageId): array
{
    try {
        // Get package with its items and prestations
        $package = PrestationPackage::with([
            'items.prestation.service',
            'items.prestation.specialization'
        ])->findOrFail($packageId);

        $prestations = $package->items->map(function($packageItem) {
            return [
                'id' => $packageItem->prestation->id,
                'name' => $packageItem->prestation->name,
                'internal_code' => $packageItem->prestation->internal_code,
                'public_price' => $packageItem->prestation->public_price,
                'service_name' => $packageItem->prestation->service->name ?? null,
                'specialization_name' => $packageItem->prestation->specialization->name ?? null,
                'specialization_id' => $packageItem->prestation->specialization_id,
                'need_an_appointment' => $packageItem->prestation->need_an_appointment ?? false,
                'package_item_id' => $packageItem->id,
                'prestation_package_id' => $packageItem->prestation_package_id
            ];
        });

        return [
            'success' => true,
            'data' => $prestations
        ];
    } catch (\Exception $e) {
        \Log::error('Error fetching prestations by package:', [
            'package_id' => $packageId,
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ]);
        return [
            'success' => false,
            'message' => 'Failed to fetch prestations',
            'error' => $e->getMessage()
        ];
    }
}

    /**
     * Add items to an existing fiche navette
     */
    public function addItemsToFicheNavette(ficheNavette $ficheNavette, array $data): ficheNavette
    {
        DB::beginTransaction();

        try {
            $totalAmount = $ficheNavette->total_amount ?? 0;

            // Extract convention data from the request
            $conventionData = $this->extractConventionData($data);

            // Handle different types of prestations
            if (isset($data['type']) && $data['type'] === 'prestation') {
                if (isset($data['prestations']) && !empty($data['prestations'])) {
                    // Add main prestation ONLY to fiche_navette_items
                    $mainPrestation = $data['prestations'][0];
                    
                    // Fix: Ensure prestation_id is properly mapped
                    $prestationData = [
                        'prestation_id' => $mainPrestation['id'], // Map 'id' to 'prestation_id'
                        'doctor_id' => $mainPrestation['doctor_id'] ?? null,
                        'convention_id' => $mainPrestation['convention_id'] ?? null,
                        'convention_price' => $mainPrestation['convention_price'] ?? null,
                        'notes' => $mainPrestation['notes'] ?? null,
                    ];
                    
                    $mainItem = $this->addPrestationToFiche($ficheNavette, $prestationData, null, $conventionData);
                    $totalAmount += $mainItem->final_price;

                    // Add dependencies ONLY to item_dependencies table
                    if (isset($data['dependencies']) && !empty($data['dependencies'])) {
                        $formattedDependencies = [];
                        foreach ($data['dependencies'] as $dependency) {
                            $formattedDependencies[] = [
                                'prestation_id' => $dependency['id'], // Map 'id' to 'prestation_id'
                                'doctor_id' => $dependency['doctor_id'] ?? $mainPrestation['doctor_id'] ?? null,
                                'convention_id' => $dependency['convention_id'] ?? $mainPrestation['convention_id'] ?? null,
                                'convention_price' => $dependency['convention_price'] ?? null,
                                'notes' => $dependency['notes'] ?? null,
                            ];
                        }
                        $this->storeDependenciesOnly($mainItem, $formattedDependencies, $conventionData);
                    }
                } elseif (isset($data['packages']) && !empty($data['packages'])) {
                    // Add package
                    $packageData = $data['packages'][0];
                    
                    // Fix: Ensure package data is properly formatted
                    $formattedPackageData = [
                        'package_id' => $packageData['id'], // Map 'id' to 'package_id'
                        'doctor_id' => $packageData['doctor_id'] ?? null,
                        'convention_id' => $packageData['convention_id'] ?? null,
                        'convention_prices' => $packageData['convention_prices'] ?? [],
                    ];
                    
                    $packageItems = $this->addPackageToFiche($ficheNavette, $formattedPackageData, $conventionData);
                    foreach ($packageItems as $item) {
                        $totalAmount += $item->final_price;
                    }
                }
            } 
            // Handle custom prestations (first one is main, rest are dependencies)
            elseif (isset($data['type']) && $data['type'] === 'custom' && isset($data['customPrestations'])) {
                $formattedCustomPrestations = [];
                foreach ($data['customPrestations'] as $customPrestation) {
                    $formattedCustomPrestations[] = [
                        'prestation_id' => $customPrestation['id'], // Map 'id' to 'prestation_id'
                        'doctor_id' => $customPrestation['selected_doctor_id'] ?? $customPrestation['doctor_id'] ?? null,
                        'convention_id' => $customPrestation['convention_id'] ?? null,
                        'convention_price' => $customPrestation['convention_price'] ?? null,
                        'notes' => $customPrestation['notes'] ?? null,
                        'custom_name' => $customPrestation['display_name'] ?? null,
                    ];
                }
                
                $customItems = $this->addCustomPrestationsToFiche($ficheNavette, $formattedCustomPrestations, $conventionData);
                // Only add the first (main) prestation price to total
                if (!empty($customItems)) {
                    $totalAmount += $customItems[0]->final_price;
                }
            }

            // Update the total amount
            $ficheNavette->update(['total_amount' => $totalAmount]);

            DB::commit();
            return $ficheNavette->fresh([
                'items.prestation', 
                'items.dependencies.dependencyPrestation',
                'items.convention',
                'patient', 
                'creator'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error adding items to fiche navette:', [
                'error' => $e->getMessage(),
                'data' => $data,
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            throw $e;
        }
    }

    /**
     * Create convention prescription items
     */
    public function createConventionPrescriptionItems(array $data , $ficheNavetteId): array
    {
        DB::beginTransaction();

        try {
            $createdItems = [];
            $totalAmount = 0;
            
            // Get the fiche navette
            $ficheNavette = ficheNavette::findOrFail($ficheNavetteId);

            // Extract global convention data (same for all items)
            $globalConventionData = $this->extractConventionDataForPrescription($data);
          
            
            // Create items for each convention group
            foreach ($data['conventions'] as $conventionIndex => $conventionGroup) {
                \Log::info("Processing convention group {$conventionIndex}", [
                    'convention_id' => $conventionGroup['convention_id'],
                    // 'specialization_id' => $conventionGroup['specialization_id'],
                    'doctor_id' => $conventionGroup['doctor_id'],
                    'prestations_count' => count($conventionGroup['prestations'])
                ]);
                
                // Create an item for each prestation in this convention group
                foreach ($conventionGroup['prestations'] as $prestationIndex => $prestationData) {
                    \Log::info("Creating item for prestation {$prestationIndex}", [
                        'prestation_id' => $prestationData['prestation_id'],
                        'convention_price' => $prestationData['convention_price'] ?? 'null',
                        'doctor_id' => $prestationData['doctor_id'],
                        // 'specialization_id' => $prestationData['specialization_id']
                    ]);
                    
                    $item = $this->createConventionPrescriptionItem(
                        $ficheNavette,
                        $conventionGroup,
                        $prestationData,
                        $globalConventionData
                    );

                    
                    $createdItems[] = $item;
                    $totalAmount += $item->final_price;
                    
                    \Log::info("Successfully created ficheNavetteItem", [
                        'item_id' => $item->id,
                        'prestation_id' => $item->prestation_id,
                        'convention_id' => $item->convention_id,
                        'doctor_id' => $item->doctor_id,
                        'final_price' => $item->final_price
                    ]);
                }
            }

            // Update the fiche navette total amount
            $ficheNavette->increment('total_amount', $totalAmount);

            \Log::info('Convention prescription creation completed successfully', [
                'total_items_created' => count($createdItems),
                'total_amount' => $totalAmount,
                'fiche_navette_new_total' => $ficheNavette->fresh()->total_amount
            ]);

            DB::commit();
            
            return [
                'items' => $createdItems,
                'items_created' => count($createdItems),
                'total_amount' => $totalAmount
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating convention prescription items', [
                'fiche_navette_id' => $data['fiche_navette_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Create a single convention prescription item
     */
   private function createConventionPrescriptionItem(
    ficheNavette $ficheNavette, 
    array $conventionGroup, 
    array $prestationData, 
    array $globalConventionData
): ficheNavetteItem {
    $prestation = Prestation::findOrFail($prestationData['prestation_id']);
    $finalPrice = $prestationData['convention_price'] ?? $prestation->public_price;

    // Handle multiple file uploads
    $fileMetas = [];
    if (!empty($globalConventionData['uploaded_files']) && is_array($globalConventionData['uploaded_files'])) {
        foreach ($globalConventionData['uploaded_files'] as $file) {
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                if ($this->fileUploadService->validateConventionFile($file)) {
                    $fileMetas[] = $this->fileUploadService->uploadSingleFile($file);
                }
            }
        }
    }

    return ficheNavetteItem::create([
        'fiche_navette_id' => $ficheNavette->id,
        'prestation_id' => $prestation->id,
        'convention_id' => $conventionGroup['convention_id'],
        'insured_id' => $globalConventionData['adherent_patient_id'] ?? $ficheNavette->patient_id,
        'doctor_id' => $prestationData['doctor_id'],
        'status' => 'pending',
        'base_price' => $prestation->public_price,
        'final_price' => $finalPrice,
        'patient_share' => $finalPrice,
        'prise_en_charge_date' => $globalConventionData['prise_en_charge_date'],
        'family_authorization' => json_encode($globalConventionData['family_authorization']),
        'uploaded_file' => !empty($fileMetas) ? json_encode($fileMetas) : null,
        'notes' => 'Convention prescription',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

    /**
     * Extract convention data from request for prescription
     */
 private function extractConventionDataForPrescription(array $data): array
{
    // Handle adherent patient ID safely
    $adherentPatientId = null;
    
    if (isset($data['adherentPatient_id'])) {
        $adherentPatientId = $data['adherentPatient_id'];
    }

    \Log::info('Final adherent patient ID:', ['adherent_patient_id' => $adherentPatientId]);

    return [
        'prise_en_charge_date' => isset($data['prise_en_charge_date']) ? Carbon::parse($data['prise_en_charge_date']) : now(),
        'family_authorization' => $data['familyAuth'] ?? '',
        'uploaded_files' => $data['uploadedFiles'] ?? [], // Array of files
        'adherent_patient_id' => $adherentPatientId,
    ];
}
    /**
     * Extract convention data from request - Updated for new structure
     */
    private function extractConventionData(array $data): array
    {
        // Handle file uploads
        $uploadedFiles = [];
        if (isset($data['uploadedFiles']) && is_array($data['uploadedFiles'])) {
            foreach ($data['uploadedFiles'] as $fileData) {
                if (isset($fileData['file'])) {
                    if ($this->fileUploadService->validateConventionFile($fileData['file'])) {
                        $uploadedFiles[] = $this->fileUploadService->uploadSingleFile($fileData['file']);
                    }
                } else {
                    $uploadedFiles[] = $fileData;
                }
            }
        }

       

        return [
            'prise_en_charge_date' => isset($data['prise_en_charge_date']) ? Carbon::parse($data['prise_en_charge_date']) : now(),
            'family_authorization' => $data['familyAuth'] ?? [],
            'uploaded_file' => $uploadedFiles,
            'adherent_patient_id' => $data['adherentPatient_id'] ?? null,
        ];
    }

    /**
     * Get items grouped by insured patient for frontend display
     * Now handles both regular and convention items differently
     */
    public function getItemsGroupedByInsured(int $ficheNavetteId): array
    {
        $items = ficheNavetteItem::where('fiche_navette_id', $ficheNavetteId)
            ->with(['insuredPatient', 'convention', 'prestation', 'doctor'])
            ->orderBy('created_at')
            ->get();

        // Separate convention items from regular items
        $conventionItems = $items->filter(function ($item) {
            return !is_null($item->convention_id);
        });
        
        $regularItems = $items->filter(function ($item) {
            return is_null($item->convention_id);
        });

        $groupedItems = [];

        // Group convention items by insured_id + convention_id
        $conventionGroups = $conventionItems->groupBy(function ($item) {
            return $item->insured_id . '_' . $item->convention_id;
        });

        foreach ($conventionGroups as $groupKey => $groupItems) {
            $firstItem = $groupItems->first();
            $insuredId = $firstItem->insured_id;
            
            $groupedItems[] = [
                'type' => 'convention',
                'group_key' => $groupKey,
                'insured_patient' => $firstItem->insuredPatient,
                'patient_id' => $firstItem->patient_id,
                'convention' => $firstItem->convention,
                'convention_id' => $firstItem->convention_id,
                'items' => $groupItems->values()->toArray(),
                'conventions_count' => 1, // Always 1 for convention groups
                'prestations_count' => $groupItems->count(),
                'total_amount' => $groupItems->sum('final_price'),
                'prise_en_charge_date' => $firstItem->prise_en_charge_date,
                'family_authorization' => $firstItem->family_authorization,
                'created_at' => $firstItem->created_at,
            ];
        }

        // Group regular items by insured_id only
        $regularGroups = $regularItems->groupBy('insured_id');
        
        foreach ($regularGroups as $insuredId => $groupItems) {
            if ($groupItems->isEmpty()) continue;
            
            $firstItem = $groupItems->first();
            
            $groupedItems[] = [
                'type' => 'regular',
                'group_key' => 'regular_' . $insuredId,
                'insured_patient' => $firstItem->insuredPatient,
                'patient_id' => $firstItem->patient_id,
                'convention' => null,
                'convention_id' => null,
                'items' => $groupItems->values()->toArray(),
                'conventions_count' => 0,
                'prestations_count' => $groupItems->count(),
                'total_amount' => $groupItems->sum('final_price'),
                'prise_en_charge_date' => null,
                'family_authorization' => null,
                'created_at' => $groupItems->min('created_at'),
            ];
        }

        // Sort by creation date
        usort($groupedItems, function ($a, $b) {
            return $a['created_at'] <=> $b['created_at'];
        });

        return $groupedItems;
    }

    /**
     * Update a specific fiche navette item
     */
    public function updateFicheNavetteItem(int $ficheNavetteId, int $itemId, array $data): ficheNavette
    {
        DB::beginTransaction();

        try {
            $ficheNavette = ficheNavette::findOrFail($ficheNavetteId);
            $item = ficheNavetteItem::where('fiche_navette_id', $ficheNavetteId)
                ->where('id', $itemId)
                ->firstOrFail();

            // Handle file uploads if present
            if (isset($data['uploaded_files']) && is_array($data['uploaded_files'])) {
                // Delete old files if replacing
                if ($item->uploaded_file) {
                    $this->fileUploadService->deleteFiles($item->uploaded_file);
                }
            }

            $item->update($data);

            DB::commit();
            return $ficheNavette->fresh(['items.prestation', 'items.dependencies', 'items.convention', 'patient', 'creator']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove a fiche navette item and its dependencies
     */
    public function removeFicheNavetteItem(int $ficheNavetteId, int $itemId): ficheNavette
    {
        DB::beginTransaction();

        try {
            $ficheNavette = ficheNavette::findOrFail($ficheNavetteId);
            $item = ficheNavetteItem::where('fiche_navette_id', $ficheNavetteId)
                ->where('id', $itemId)
                ->firstOrFail();

            // // Delete uploaded files
            // if ($item->uploaded_file) {
            //     $this->fileUploadService->deleteFiles($item->uploaded_file);
            // }

            // Remove all dependency relationships for this item
            ItemDependency::where('parent_item_id', $itemId)->delete();

            // Store the price to subtract from total
            $priceToSubtract = $item->final_price;

            // Delete the main item
            $item->delete();

            // Update the total amount
            $newTotal = max(0, ($ficheNavette->total_amount ?? 0) - $priceToSubtract);
            $ficheNavette->update(['total_amount' => $newTotal]);

            DB::commit();
            return $ficheNavette->fresh(['items.prestation', 'items.dependencies', 'items.convention', 'patient', 'creator']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove a dependency from an item
     */
    public function removeDependency(int $dependencyId): bool
    {
        DB::beginTransaction();

        try {
            $dependency = ItemDependency::findOrFail($dependencyId);
            
            \Log::info('Removing dependency:', [
                'dependency_id' => $dependencyId,
                'parent_item_id' => $dependency->parent_item_id,
                'dependent_prestation_id' => $dependency->dependent_prestation_id
            ]);
            
            // Delete the dependency
            $dependency->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error removing dependency:', [
                'dependency_id' => $dependencyId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate a unique reference number
     */
    private function generateReference(): string
    {
        $prefix = 'FN';
        $date = now()->format('Ymd');
        $sequence = FicheNavette::whereDate('created_at', today())->count() + 1;
        return $prefix . $date . sprintf('%04d', $sequence);
    }

    /**
     * Add a prestation to fiche navette
     */
    private function addPrestationToFiche(
        ficheNavette $ficheNavette, 
        array $prestationData, 
        ?PrestationPackage $package = null, 
        array $conventionData = []
    ): ficheNavetteItem {
        
        $prestation = Prestation::findOrFail($prestationData['prestation_id']);
        
        // Calculate pricing
        $basePrice = $prestation->public_price;
        $finalPrice = $basePrice;
        
        // Apply convention pricing if available
        if (!empty($conventionData) && isset($prestationData['convention_price'])) {
            $finalPrice = $prestationData['convention_price'];
        }
        
        // Handle file uploads for this specific item
        $itemFiles = [];
        if (!empty($conventionData['uploaded_files'])) {
            foreach ($conventionData['uploaded_files'] as $file) {
                if (isset($file['file'])) {
                    if ($this->fileUploadService->validateConventionFile($file['file'])) {
                        $itemFiles[] = $this->fileUploadService->uploadSingleFile($file['file']);
                    }
                } else {
                    $itemFiles[] = $file;
                }
            }
        }
        
        // Prepare item data
        $itemData = [
            'fiche_navette_id' => $ficheNavette->id,
            'prestation_id' => $prestation->id,
            'package_id' => $package ? $package->id : null,
            'doctor_id' => $prestationData['doctor_id'] ?? null,
            'status' => 'pending',
            'base_price' => $basePrice,
            'final_price' => $finalPrice,
            'patient_share' => $finalPrice,
            'notes' => $prestationData['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
        // Add convention-specific data if available
        if (!empty($conventionData)) {
            $itemData = array_merge($itemData, [
                'convention_id' => $prestationData['convention_id'] ?? null,
                'insured_id' => $conventionData['adherent_patient_id'] ?? $ficheNavette->patient_id,
                'prise_en_charge_date' => $conventionData['prise_en_charge_date'] ?? now(),
                'family_authorization' => json_encode($conventionData['family_authorization']),
                'uploaded_file' => json_encode($itemFiles),
            ]);
        } else {
            // For regular items, use the main patient as insured
            $itemData['insured_id'] = $ficheNavette->patient_id;
        }
        
        return ficheNavetteItem::create($itemData);
    }

    /**
     * Add a package to fiche navette
     */
private function addPackageToFiche(
    ficheNavette $ficheNavette, 
    array $packageData, 
    array $conventionData = []
): array {
    
    $package = PrestationPackage::findOrFail($packageData['package_id']);
    
    // Handle file uploads for this specific item
    $itemFiles = [];
    if (!empty($conventionData['uploaded_files'])) {
        foreach ($conventionData['uploaded_files'] as $file) {
            if (isset($file['file'])) {
                if ($this->fileUploadService->validateConventionFile($file['file'])) {
                    $itemFiles[] = $this->fileUploadService->uploadSingleFile($file['file']);
                }
            } else {
                $itemFiles[] = $file;
            }
        }
    }
    
    // FIXED: Use the package price directly from prestation_packages table
    // This is the special deal price, not the sum of individual prestations
    $packagePrice = $package->price;
    
    // Prepare item data - store package_id, not prestation_id
    $itemData = [
        'fiche_navette_id' => $ficheNavette->id,
        'prestation_id' => null, // NULL for packages
        'package_id' => $package->id, // Store the package ID
        'doctor_id' => $packageData['doctor_id'] ?? null,
        'status' => 'pending',
        'base_price' => $packagePrice, // Use package special price
        'final_price' => $packageData['total_price'] ?? $packagePrice,
        'patient_share' => $packageData['total_price'] ?? $packagePrice,
        'notes' => $packageData['notes'] ?? 'Package: ' . $package->name,
        'created_at' => now(),
        'updated_at' => now(),
    ];
    
    // Add convention-specific data if available
    if (!empty($conventionData)) {
        $itemData = array_merge($itemData, [
            'convention_id' => $packageData['convention_id'] ?? null,
            'insured_id' => $conventionData['adherent_patient_id'] ?? $ficheNavette->patient_id,
            'prise_en_charge_date' => $conventionData['prise_en_charge_date'] ?? now(),
            'family_authorization' => json_encode($conventionData['family_authorization']),
            'uploaded_file' => json_encode($itemFiles),
        ]);
    } else {
        // For regular items, use the main patient as insured
        $itemData['insured_id'] = $ficheNavette->patient_id;
    }
    
    // Create single fiche navette item for the entire package
    $createdItem = ficheNavetteItem::create($itemData);
    
    return [$createdItem]; // Return array with single item
}
    /**
     * Add custom prestations to fiche navette
     */
    private function addCustomPrestationsToFiche(
        ficheNavette $ficheNavette, 
        array $customPrestations, 
        array $conventionData = []
    ): array {

        $createdItems = [];
        $isFirst = true;

        foreach ($customPrestations as $index => $customPrestation) {
            if ($isFirst) {
                // First item is the main prestation - add to fiche_navette_items
                $item = $this->addPrestationToFiche($ficheNavette, $customPrestation, null, $conventionData);
                $createdItems[] = $item;
                
                // Store dependencies for the main item
                if (count($customPrestations) > 1) {
                    $dependencies = array_slice($customPrestations, 1); // All except first
                    
                    // Make sure each dependency has the correct custom_name
                    foreach ($dependencies as &$dependency) {
                        // If display_name is set and different from prestation name, use it as custom_name
                        if (isset($dependency['display_name'])) {
                            $prestation = \App\Models\CONFIGURATION\Prestation::find($dependency['prestation_id']);
                            if ($prestation && $dependency['display_name'] !== $prestation->name) {
                                $dependency['custom_name'] = $dependency['display_name'];
                            }
                        }
                    }
                    
                    $this->storeDependenciesOnly($item, $dependencies, $conventionData);
                }
                
                $isFirst = false;
            }
            // Skip remaining items as they are stored as dependencies
        }
        
        return $createdItems;
    }

    /**
     * Store dependencies only (not as main items)
     */
    private function storeDependenciesOnly(
        ficheNavetteItem $parentItem, 
        array $dependencies, 
        array $conventionData = []
    ): void {

        foreach ($dependencies as $dependencyData) {
            // Validate that prestation_id exists
            if (!isset($dependencyData['prestation_id'])) {
                \Log::warning('Skipping dependency due to missing prestation_id', $dependencyData);
                continue;
            }
            
            $prestation = Prestation::findOrFail($dependencyData['prestation_id']);
            
            // Calculate pricing for dependency
            $basePrice = $prestation->public_price;
            $finalPrice = $basePrice;
            
            if (!empty($conventionData) && isset($dependencyData['convention_price']) && $dependencyData['convention_price'] !== null) {
                $finalPrice = $dependencyData['convention_price'];
            }
            
            // Create dependency record
            ItemDependency::create([
                'parent_item_id' => $parentItem->id,
                'dependent_prestation_id' => $prestation->id,
                // 'dependency_type' => 'custom', // Add this field to identify custom dependencies
                'doctor_id' => $dependencyData['doctor_id'] ?? $parentItem->doctor_id,
                'base_price' => $basePrice,
                'final_price' => $finalPrice,
                'status' => 'pending',
                'notes' => $dependencyData['notes'] ?? 'Custom dependency item',
                'custom_name' => $dependencyData['custom_name'] ?? $dependencyData['display_name'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Get prestations with convention pricing
     */
    public function getPrestationsWithConventionPricing(array $conventionIds, string $date): array
    {
        try {
            // Get all prestations
            $prestations = Prestation::with(['service', 'specialization'])
                ->where('is_active', true)
                ->get();
            
            $prestationsWithPricing = [];
            
            foreach ($prestations as $prestation) {
                foreach ($conventionIds as $conventionId) {
                    // Calculate convention price (you may need to implement your pricing logic here)
                    $conventionPrice = $this->calculateConventionPrice($prestation, $conventionId, $date);
                    
                    $prestationsWithPricing[] = [
                        'prestation_id' => $prestation->id,
                        'prestation_name' => $prestation->name,
                        'prestation_code' => $prestation->internal_code,
                        'service_name' => $prestation->service->name ?? null,
                        'specialization_id' => $prestation->specialization_id,
                        'base_price' => $prestation->public_price,
                        'convention_price' => $conventionPrice,
                        'convention_id' => $conventionId,
                    ];
                }
            }
            
            return $prestationsWithPricing;
            
        } catch (\Exception $e) {
            \Log::error('Error getting prestations with convention pricing:', [
                'error' => $e->getMessage(),
                'convention_ids' => $conventionIds,
                'date' => $date
            ]);
            throw $e;
        }
    }

    /**
     * Calculate convention price for a prestation
     */
    private function calculateConventionPrice(Prestation $prestation, int $conventionId, string $date): float
    {
        // Implement your convention pricing logic here
        // This is a basic example - you may need to adjust based on your business rules
        
        // For now, return the public price (you can modify this logic)
        // You might want to check convention_pricing table or apply discounts
        
        return $prestation->public_price;
        
        // Example of more complex pricing logic:
        /*
        $convention = Convention::find($conventionId);
        if ($convention && $convention->discount_percentage) {
            $discount = ($prestation->public_price * $convention->discount_percentage) / 100;
            return $prestation->public_price - $discount;
        }
        
        return $prestation->public_price;
        */
    }

    /**
     * Get doctors by specialization
     */
    public function getDoctorsBySpecialization(int $specializationId): array
    {
        try {
            // Assuming you have a User model with doctor role and specialization relationship
            // Adjust this query based on your actual doctor/user model structure
            
            $doctors = \App\Models\User::where('role', 'doctor')
                ->where('specialization_id', $specializationId)
                ->where('is_active', true)
                ->select('id', 'name', 'email', 'specialization_id')
                ->get()
                ->toArray();
            
            return $doctors;
            
        } catch (\Exception $e) {
            \Log::error('Error getting doctors by specialization:', [
                'error' => $e->getMessage(),
                'specialization_id' => $specializationId
            ]);
            throw $e;
        }
    }

    /**
     * Get all specializations
     */
    public function getSpecializations(): array
    {
        try {
            $specializations = \App\Models\CONFIGURATION\Specialization::where('is_active', true)
                ->select('id', 'name', 'description')
                ->get()
                ->toArray();
            
            return $specializations;
            
        } catch (\Exception $e) {
            \Log::error('Error getting specializations:', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get all conventions
     */
    public function getConventions(): array
    {
        try {
            $conventions = \App\Models\CONFIGURATION\Convention::where('is_active', true)
                ->select('id', 'contract_name', 'company_name', 'is_active')
                ->get()
                ->toArray();
            
            return $conventions;
            
        } catch (\Exception $e) {
            \Log::error('Error getting conventions:', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Search patients for convention selection
     */
    public function searchPatientsForConvention(string $query): array
    {
        try {
            $patients = \App\Models\Patient::where(function($q) use ($query) {
                    $q->where('first_name', 'LIKE', "%{$query}%")
                      ->orWhere('last_name', 'LIKE', "%{$query}%")
                      ->orWhere('id', 'LIKE', "%{$query}%")
                      ->orWhere('phone', 'LIKE', "%{$query}%");
                })
                ->where('is_active', true)
                ->select('id', 'first_name', 'last_name', 'phone', 'email')
                ->limit(20)
                ->get()
                ->toArray();
            
            return $patients;
            
        } catch (\Exception $e) {
            \Log::error('Error searching patients:', [
                'error' => $e->getMessage(),
                'query' => $query
            ]);
            throw $e;
        }
    }
}
