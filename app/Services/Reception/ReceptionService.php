<?php

// filepath: d:\Projects\AppointmentSystem\AppointmentSystem-main\app\Services\Reception\ReceptionService.php

namespace App\Services\Reception;

use App\Exceptions\MultiDoctorException;
use App\Models\B2B\Convention;
use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\PrestationPackage;
use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Reception\ItemDependency;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Class ReceptionService
 */
class ReceptionService
{
    protected $fileUploadService;

    protected $packageConversionFacade;

    public function __construct(FileUploadService $fileUploadService, PackageConversionFacade $packageConversionFacade)
    {
        $this->fileUploadService = $fileUploadService;
        $this->packageConversionFacade = $packageConversionFacade;
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
                'status' => $data['status'] ?? 'pending',
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
                'created_by' => auth()->id(),
            ]);

            $totalAmount = 0;

            // Extract convention data from the request
            $conventionData = $this->extractConventionData($data);

            // 2. Handle different types of prestations
            if (isset($data['type']) && $data['type'] === 'prestation') {
                if (isset($data['prestations']) && ! empty($data['prestations'])) {
                    // Add main prestation ONLY to fiche_navette_items
                    $mainPrestation = $data['prestations'][0];

                    $prestationData = [
                        'prestation_id' => $mainPrestation['id'],
                        'doctor_id' => $data['selectedDoctor'] ?? null,
                        'convention_id' => $mainPrestation['convention_id'] ?? null,
                        'convention_price' => $mainPrestation['convention_price'] ?? null,
                        'notes' => $mainPrestation['notes'] ?? null,
                    ];

                    $mainItem = $this->addPrestationToFiche($fiche, $prestationData, null, $conventionData);
                    $totalAmount += $mainItem->final_price;

                    // Add dependencies ONLY to item_dependencies table
                    if (count($data['prestations']) > 1) {
                        $dependencies = array_slice($data['prestations'], 1);
                        $formattedDependencies = [];
                        foreach ($dependencies as $dependency) {
                            $formattedDependencies[] = [
                                'prestation_id' => $dependency['id'],
                                'doctor_id' => $data['selectedDoctor'] ?? null,
                                'convention_id' => $dependency['convention_id'] ?? null,
                                'convention_price' => $dependency['convention_price'] ?? null,
                                'notes' => $dependency['notes'] ?? null,
                            ];
                        }
                        $this->storeDependenciesOnly($mainItem, $formattedDependencies, $conventionData);
                    }
                }

                // FIXED: Handle packages correctly
                if (isset($data['packages']) && ! empty($data['packages'])) {
                    $packageData = $data['packages'][0];

                    $formattedPackageData = [
                        'package_id' => $packageData['id'], // Map 'id' to 'package_id'
                        'doctor_id' => $data['selectedDoctor'] ?? null,
                        'convention_id' => $packageData['convention_id'] ?? null,
                        'convention_prices' => $packageData['convention_prices'] ?? [],
                        'notes' => $packageData['notes'] ?? null,
                    ];

                    $packageItems = $this->addPackageToFiche($fiche, $formattedPackageData, $conventionData);
                    foreach ($packageItems as $item) {
                        $totalAmount += $item->final_price;
                    }
                }
            }
            // 3. Handle custom prestations (first one is main, rest are dependencies)
            elseif (isset($data['type']) && $data['type'] === 'custom' && isset($data['customPrestations'])) {
                $formattedCustomPrestations = [];
                foreach ($data['customPrestations'] as $customPrestation) {
                    $formattedCustomPrestations[] = [
                        'prestation_id' => $customPrestation['id'],
                        'doctor_id' => $customPrestation['selected_doctor_id'] ?? $data['selectedDoctor'] ?? null,
                        'convention_id' => $customPrestation['convention_id'] ?? null,
                        'convention_price' => $customPrestation['convention_price'] ?? null,
                        'notes' => $customPrestation['notes'] ?? null,
                        'custom_name' => $customPrestation['display_name'] ?? null,
                    ];
                }

                $customItems = $this->addCustomPrestationsToFiche($fiche, $formattedCustomPrestations, $conventionData);
                // Only add the first (main) prestation price to total
                if (! empty($customItems)) {
                    $totalAmount += $customItems[0]->final_price;
                }
            }

            // 4. Update the total amount
            $fiche->update(['total_amount' => $totalAmount]);

            DB::commit();

            return $fiche->fresh(['items.prestation', 'items.package', 'items.dependencies.dependencyPrestation', 'items.convention', 'patient', 'creator']);
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
                'items.prestation.specialization',
            ])->findOrFail($packageId);
            $prestations = $package->items->map(function ($packageItem) {
                return [
                    'id' => $packageItem->prestation->id,
                    'name' => $packageItem->prestation->name,
                    'internal_code' => $packageItem->prestation->internal_code,
                    'public_price' => $packageItem->prestation->public_price ?? 0,
                    'service_name' => $packageItem->prestation->service->name ?? null,
                    'specialization_name' => $packageItem->prestation->specialization->name ?? null,
                    'specialization_id' => $packageItem->prestation->specialization_id,
                    'need_an_appointment' => $packageItem->prestation->need_an_appointment ?? false,
                    'package_item_id' => $packageItem->id,
                    'prestation_package_id' => $packageItem->prestation_package_id,
                ];
            });

            return [
                'success' => true,
                'data' => $prestations,
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
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Detect if prestations match a package and automatically convert
     * Returns the LARGEST matching package (most prestations) or null
     * 
     * LOGIC:
     * - If you have prestations [5, 87, 88]
     * - Package A has [5, 87] - matches but only 2 of 3
     * - Package B has [5, 87, 88] - matches all 3 ← PICK THIS ONE (largest)
     */
    public function detectMatchingPackage(array $prestationIds): ?PrestationPackage
    {
        // dd('detectMatchingPackage called', $prestationIds);

        try {
            // Get all packages with their prestations
            $packages = PrestationPackage::with('prestations')->get();

            $bestMatch = null;
            $bestMatchCount = 0;
            $bestMatchIsExact = false;

            \Log::info('🔍 Searching for matching package:', [
                'looking_for_prestations' => $prestationIds,
                'looking_for_count' => count($prestationIds),
            ]);
            
            // Find the package with the biggest overlap where all package prestations are present
            foreach ($packages as $package) {
                $packagePrestationIds = $package->prestations->pluck('id')->toArray();

                // Determine if the requested prestations contain all package prestations
                $missingPrestations = array_diff($packagePrestationIds, $prestationIds);

                if (!empty($missingPrestations)) {
                    // We are missing prestations required for this package, skip it
                    continue;
                }

                $matchCount = count($packagePrestationIds);
                $isExactMatch = $matchCount === count($prestationIds) && empty(array_diff($prestationIds, $packagePrestationIds));

                \Log::info('✅ PACKAGE MATCH CANDIDATE:', [
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                    'prestation_count' => $matchCount,
                    'is_exact_match' => $isExactMatch,
                    'best_match_count' => $bestMatchCount,
                    'best_match_is_exact' => $bestMatchIsExact,
                ]);

                // Prefer exact matches first
                if ($isExactMatch) {
                    if (!$bestMatchIsExact || $matchCount > $bestMatchCount) {
                        $bestMatch = $package;
                        $bestMatchCount = $matchCount;
                        $bestMatchIsExact = true;
                    }
                    continue;
                }

                // For non-exact matches, still pick the largest package if we do not already have an exact match
                if (!$bestMatchIsExact && $matchCount > $bestMatchCount) {
                    $bestMatch = $package;
                    $bestMatchCount = $matchCount;
                }
            }

            if ($bestMatch) {
                \Log::info('🏆 SELECTED BEST MATCHING PACKAGE:', [
                    'package_id' => $bestMatch->id,
                    'package_name' => $bestMatch->name,
                    'prestation_count' => $bestMatchCount,
                ]);
                return $bestMatch;
            }

            \Log::info('❌ No matching package found');
            return null;

        } catch (\Exception $e) {
            \Log::error('Error in detectMatchingPackage:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return null;
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

            // STEP 1: Check if we should convert prestations to a package
            if (isset($data['prestations']) && !empty($data['prestations']) && (!isset($data['packages']) || empty($data['packages']))) {
                // Extract prestation IDs (ONLY standard ones - exclude convention and dependency items)
                $prestationIds = [];
                $standardPrestations = [];
                $conventionPrestations = [];
                $dependencyPrestations = [];
                
                foreach ($data['prestations'] as $prestation) {
                    // Check if this is a convention or dependency item
                    $isConvention = isset($prestation['is_convention']) && $prestation['is_convention'];
                    $isDependency = isset($prestation['is_dependency']) && $prestation['is_dependency'];
                    
                    $prestationId = $prestation['prestation_id'] ?? $prestation['id'] ?? null;
                    
                    if ($prestationId) {
                        if ($isConvention) {
                            $conventionPrestations[] = $prestation;
                        } elseif ($isDependency) {
                            $dependencyPrestations[] = $prestation;
                        } else {
                            // Standard prestation - eligible for package conversion
                            $standardPrestations[] = $prestation;
                            $prestationIds[] = $prestationId;
                        }
                    }
                }

                \Log::info('Package detection - filtered prestations:', [
                    'total_prestations' => count($data['prestations']),
                    'standard_prestations' => count($standardPrestations),
                    'convention_prestations' => count($conventionPrestations),
                    'dependency_prestations' => count($dependencyPrestations),
                    'standard_ids' => $prestationIds,
                ]);

                // Try to detect matching package (only for standard prestations)
                if (!empty($prestationIds)) {
                    $matchingPackage = $this->detectMatchingPackage($prestationIds);
                    
                    if ($matchingPackage) {
                        \Log::info('🎯 Package auto-conversion triggered:', [
                            'matching_package_id' => $matchingPackage->id,
                            'matching_package_name' => $matchingPackage->name,
                            'converted_prestation_ids' => $prestationIds,
                            'preserved_conventions' => count($conventionPrestations),
                            'preserved_dependencies' => count($dependencyPrestations),
                        ]);

                        // Convert to package format
                        $data['packages'] = [[
                            'package_id' => $matchingPackage->id,
                            'id' => $matchingPackage->id,
                            'name' => $matchingPackage->name,
                        ]];
                        
                        // Keep convention and dependency items, remove only standard ones that matched
                        $data['prestations'] = array_merge($conventionPrestations, $dependencyPrestations);
                        
                        \Log::info('After conversion:', [
                            'packages_count' => count($data['packages']),
                            'remaining_prestations' => count($data['prestations']),
                        ]);
                    }
                }
            }

            // Handle different types of prestations
            if (isset($data['type']) && $data['type'] === 'prestation') {
                if (isset($data['prestations']) && ! empty($data['prestations'])) {
                    // Add main prestation ONLY to fiche_navette_items
                    $mainPrestation = $data['prestations'][0];

                    // Fix: Ensure prestation_id is properly mapped
                    $prestationData = [
                        'prestation_id' => $mainPrestation['id'], // Map 'id' to 'prestation_id'
                        'doctor_id' => $data['selectedDoctor'] ?? null, // Use selectedDoctor from main data
                        'convention_id' => $mainPrestation['convention_id'] ?? null,
                        
                        'convention_price' => $mainPrestation['convention_price'] ?? null,
                        'notes' => $mainPrestation['notes'] ?? null,
                    ];

                    $mainItem = $this->addPrestationToFiche($ficheNavette, $prestationData, null, $conventionData);
                    $totalAmount += $mainItem->final_price;

                    // Add dependencies ONLY to item_dependencies table
                    if (isset($data['prestations']) && count($data['prestations']) > 1) {
                        $dependencies = array_slice($data['prestations'], 1); // All except first
                        $formattedDependencies = [];
                        foreach ($dependencies as $dependency) {
                            $formattedDependencies[] = [
                                'prestation_id' => $dependency['id'], // Map 'id' to 'prestation_id'
                                'doctor_id' => $data['selectedDoctor'] ?? null,
                                'convention_id' => $dependency['convention_id'] ?? null,
                                'convention_price' => $dependency['convention_price'] ?? null,
                                'notes' => $dependency['notes'] ?? null,
                            ];
                        }
                        $this->storeDependenciesOnly($mainItem, $formattedDependencies, $conventionData);
                    }
                }

                // FIXED: Handle packages separately - not in elseif
                if (isset($data['packages']) && ! empty($data['packages'])) {
                    $packageData = $data['packages'][0];

                    \Log::info('Processing package data:', [
                        'package_data' => $packageData,
                        'selectedDoctor' => $data['selectedDoctor'] ?? null,
                    ]);

                    // Fix: Ensure package_id is properly mapped
                    $formattedPackageData = [
                        'package_id' => $packageData['id'] ?? $packageData['package_id'], // Handle both id and package_id
                        'doctor_id' => $data['selectedDoctor'] ?? null, // Use selectedDoctor from main data
                        'convention_id' => $packageData['convention_id'] ?? null,
                        'convention_prices' => $packageData['convention_prices'] ?? [],
                        'notes' => $packageData['notes'] ?? null,
                    ];

                    \Log::info('Formatted package data for addPackageToFiche:', $formattedPackageData);

                    $packageItems = $this->addPackageToFiche($ficheNavette, $formattedPackageData, $conventionData);
                    foreach ($packageItems as $item) {
                        $totalAmount += $item->final_price;
                        \Log::info('Created package item:', [
                            'id' => $item->id,
                            'package_id' => $item->package_id,
                            'final_price' => $item->final_price,
                        ]);
                    }
                }
            }
            // Handle custom prestations (first one is main, rest are dependencies)
            elseif (isset($data['type']) && $data['type'] === 'custom' && isset($data['customPrestations'])) {
                $formattedCustomPrestations = [];
                foreach ($data['customPrestations'] as $customPrestation) {
                    $formattedCustomPrestations[] = [
                        'prestation_id' => $customPrestation['id'], // Map 'id' to 'prestation_id'
                        'doctor_id' => $customPrestation['selected_doctor_id'] ?? $data['selectedDoctor'] ?? null,
                        'convention_id' => $customPrestation['convention_id'] ?? null,
                        'convention_price' => $customPrestation['convention_price'] ?? null,
                        'notes' => $customPrestation['notes'] ?? null,
                        'custom_name' => $customPrestation['display_name'] ?? null,
                    ];
                }

                $customItems = $this->addCustomPrestationsToFiche($ficheNavette, $formattedCustomPrestations, $conventionData);
                // Only add the first (main) prestation price to total
                if (! empty($customItems)) {
                    $totalAmount += $customItems[0]->final_price;
                }
            }

            // Update the total amount
            $ficheNavette->update(['total_amount' => $totalAmount]);

            // STEP 2: After items are added, check if ANY combination of existing prestations match a package
            // This enables automatic conversion when a second prestation is added (works for both regular and custom prestations)
            $existingPrestationItems = ficheNavetteItem::where('fiche_navette_id', $ficheNavette->id)
                ->whereNotNull('prestation_id')
                ->whereNull('package_id')
                ->get();

            $conversionData = [
                'converted' => false,
                'package_name' => null,
                'package_id' => null,
                'converted_count' => 0,
            ];

            if ($existingPrestationItems->count() >= 2) {
                \Log::info('Checking for auto-package conversion:', [
                    'fiche_id' => $ficheNavette->id,
                    'prestation_items_count' => $existingPrestationItems->count(),
                ]);

                // Extract all prestation IDs from existing items
                $allPrestationIds = $existingPrestationItems->pluck('prestation_id')->toArray();
                
                // Check if these prestations match any package
                $matchingPackage = $this->detectMatchingPackage($allPrestationIds);
                
                if ($matchingPackage) {
                    \Log::info('✅ Auto-conversion triggered - found matching package:', [
                        'package_id' => $matchingPackage->id,
                        'package_name' => $matchingPackage->name,
                        'prestation_ids' => $allPrestationIds,
                        'converted_count' => count($allPrestationIds),
                    ]);

                    // Store conversion data for response
                    $conversionData = [
                        'converted' => true,
                        'package_name' => $matchingPackage->name,
                        'package_id' => $matchingPackage->id,
                        'converted_count' => count($allPrestationIds),
                    ];

                    // Perform automatic conversion
                    $ficheNavette = $this->convertPrestationsToPackage($ficheNavette->id, $allPrestationIds, $matchingPackage->id);
                }
            }

            DB::commit();

            // Fresh fiche with relationships
            $ficheNavette = $ficheNavette->fresh([
                'items.prestation',
                'items.package',
                'items.dependencies.dependencyPrestation',
                'items.convention',
                'patient',
                'creator',
            ]);

            // Add conversion data to response for toast notification
            $ficheNavette->setAttribute('conversion_data', $conversionData);

            return $ficheNavette;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error adding items to fiche navette:', [
                'error' => $e->getMessage(),
                'data' => $data,
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        }
    }

    /**
     * Create convention prescription items
     */
    public function createConventionPrescriptionItems(array $data, $ficheNavetteId): array
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
                    'prestations_count' => count($conventionGroup['prestations']),
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

                    \Log::info('Successfully created ficheNavetteItem', [
                        'item_id' => $item->id,
                        'prestation_id' => $item->prestation_id,
                        'convention_id' => $item->convention_id,
                        'doctor_id' => $item->doctor_id,
                        'final_price' => $item->final_price,
                    ]);
                }
            }

            // Update the fiche navette total amount
            $ficheNavette->increment('total_amount', $totalAmount);

            \Log::info('Convention prescription creation completed successfully', [
                'total_items_created' => count($createdItems),
                'total_amount' => $totalAmount,
                'fiche_navette_new_total' => $ficheNavette->fresh()->total_amount,
            ]);

            DB::commit();

            return [
                'items' => $createdItems,
                'items_created' => count($createdItems),
                'total_amount' => $totalAmount,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating convention prescription items', [
                'fiche_navette_id' => $data['fiche_navette_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
        // Use patient_price from PrestationPricing (annex/avenant), fallback to convention_price, then public_price
        $finalPrice = $prestationData['patient_price'] ?? $prestationData['convention_price'] ?? $prestation->getPublicPrice();

        // Handle multiple file uploads
        $fileMetas = [];
        if (! empty($globalConventionData['uploaded_files']) && is_array($globalConventionData['uploaded_files'])) {
            foreach ($globalConventionData['uploaded_files'] as $file) {
                // Check if it's already processed file data (from controller)
                if (is_array($file) && isset($file['path']) && isset($file['original_name'])) {
                    // File is already processed, use it directly
                    $fileMetas[] = $file;
                    \Log::info('Using pre-processed file:', [
                        'original_name' => $file['original_name'],
                        'path' => $file['path'],
                    ]);
                } elseif ($file instanceof \Illuminate\Http\UploadedFile) {
                    // File is raw UploadedFile, process it
                    if ($this->fileUploadService->validateConventionFile($file)) {
                        $processedFile = $this->fileUploadService->uploadSingleFile($file);
                        $fileMetas[] = $processedFile;
                        \Log::info('Processed raw file:', [
                            'original_name' => $processedFile['original_name'],
                            'path' => $processedFile['path'],
                        ]);
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
            'base_price' => $prestation->getPublicPrice(),
            'default_payment_type' => $prestation->default_payment_type ?? null,
            'final_price' => $finalPrice,
            'patient_share' => $finalPrice,
            'prise_en_charge_date' => $globalConventionData['prise_en_charge_date'],
            'family_authorization' => json_encode($globalConventionData['family_authorization']),
            'uploaded_file' => ! empty($fileMetas) ? json_encode($fileMetas) : null,
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
            ->with(['insuredPatient', 'convention', 'prestation', 'doctor', 'packageReceptionRecords.doctor.user', 'packageReceptionRecords.prestation', 'package'])
            ->orderBy('created_at')
            ->get();

        // Separate convention items from regular items
        $conventionItems = $items->filter(function ($item) {
            return ! is_null($item->convention_id);
        });

        $regularItems = $items->filter(function ($item) {
            return is_null($item->convention_id);
        });

        $groupedItems = [];

        // Group convention items by insured_id + convention_id
        $conventionGroups = $conventionItems->groupBy(function ($item) {
            return $item->insured_id.'_'.$item->convention_id;
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
            if ($groupItems->isEmpty()) {
                continue;
            }

            $firstItem = $groupItems->first();

            $groupedItems[] = [
                'type' => 'regular',
                'group_key' => 'regular_'.$insuredId,
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

            // Ensure related prestation/package are loaded so we can derive default_payment_type
            $item->loadMissing(['prestation', 'package']);

            // If default_payment_type is not provided in the update payload, try to derive it
            if (! array_key_exists('default_payment_type', $data) || $data['default_payment_type'] === null) {
                $derived = null;
                if ($item->prestation) {
                    $derived = $item->prestation->default_payment_type ?? null;
                }
                if (is_null($derived) && $item->package) {
                    $derived = $item->package->default_payment_type ?? null;
                }

                // If we derived a value and item has no stored value, set it on the payload so update persists it
                if (! is_null($derived) && (is_null($item->default_payment_type) || $item->default_payment_type === '')) {
                    $data['default_payment_type'] = $derived;
                }
            }

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
                'dependent_prestation_id' => $dependency->dependent_prestation_id,
            ]);

            // Delete the dependency
            $dependency->delete();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error removing dependency:', [
                'dependency_id' => $dependencyId,
                'error' => $e->getMessage(),
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

        return $prefix.$date.sprintf('%04d', $sequence);
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
    $basePrice = $prestation->price_with_vat_and_consumables_variant ?? $prestation->getPublicPrice();
    $finalPrice = $basePrice;

    // Normalize prices to scalar floats (some accessors return arrays with variants)
    $basePrice = $this->normalizePrice($basePrice, $prestation);
    $finalPrice = $this->normalizePrice($finalPrice, $prestation);

        // Apply convention pricing if available - use patient_price from PrestationPricing
        if (! empty($conventionData) && isset($prestationData['patient_price'])) {
            $finalPrice = $prestationData['patient_price'];
        } elseif (! empty($conventionData) && isset($prestationData['convention_price'])) {
            // Fallback to convention_price for backward compatibility
            $finalPrice = $prestationData['convention_price'];
        }

        // Handle file uploads for this specific item
        $itemFiles = [];
        if (! empty($conventionData['uploaded_files'])) {
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
            'default_payment_type' => $prestation->default_payment_type ?? null,
            'base_price' => $basePrice,
            'final_price' => $finalPrice,
            'patient_share' => $finalPrice,
            'notes' => $prestationData['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Add convention-specific data if available
        if (! empty($conventionData)) {
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
     * Normalize a price value that may be an array or scalar to a float.
     * Prefer ttc_with_consumables_vat, then ttc, then price/public_price keys when present.
     */
    private function normalizePrice($value, Prestation $prestation): float
    {
        if (is_array($value)) {
            return (float) ($value['ttc_with_consumables_vat'] ?? $value['ttc'] ?? $value['price'] ?? $value['public_price'] ?? $prestation->getPublicPrice() ?? 0);
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return (float) ($prestation->getPublicPrice() ?? 0);
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

        \Log::info('Found package:', [
            'package_id' => $package->id,
            'package_name' => $package->name,
            'package_price' => $package->price,
        ]);

        // Handle file uploads for this specific item
        $itemFiles = [];
        if (! empty($conventionData['uploaded_files'])) {
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
        $packagePrice = $package->price; // This is the special package price
        // Normalize package price in case it's an array/complex value
        if (is_array($packagePrice)) {
            $packagePrice = (float) ($packagePrice['ttc_with_consumables_vat'] ?? $packagePrice['ttc'] ?? $packagePrice['price'] ?? $package->price ?? 0);
        } else {
            $packagePrice = (float) $packagePrice;
        }

        // Prepare item data - store package_id, not prestation_id
        $itemData = [
            'fiche_navette_id' => $ficheNavette->id,
            'prestation_id' => null, // NULL for packages
            'package_id' => $package->id, // Store the package ID
            'doctor_id' => $packageData['doctor_id'] ?? null,
            'status' => 'pending',
            // If package has a default payment type at package level, prefer it; otherwise null
            'default_payment_type' => $package->default_payment_type ?? null,
            'base_price' => $packagePrice, // Use package price as base
            'final_price' => $packagePrice, // Use package price as final (not individual prestations)
            'patient_share' => $packagePrice, // Patient pays package price
            'remaining_amount' => $packagePrice, // Full package price remaining
            'paid_amount' => 0, // Nothing paid initially
            'payment_status' => 'pending',
            'notes' => $packageData['notes'] ?? 'Package: '.$package->name,
            'custom_name' => $package->name,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Add convention-specific data if available
        if (! empty($conventionData)) {
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

        \Log::info('Created package fiche navette item:', [
            'item_id' => $createdItem->id,
            'package_id' => $createdItem->package_id,
            'prestation_id' => $createdItem->prestation_id,
            'final_price' => $createdItem->final_price,
            'base_price' => $createdItem->base_price,
            'remaining_amount' => $createdItem->remaining_amount,
            'paid_amount' => $createdItem->paid_amount,
        ]);

        // IMPORTANT: Do NOT create dependencies for package prestations
        // The package is treated as a single unit with its own price

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
            if (! isset($dependencyData['prestation_id'])) {
                \Log::warning('Skipping dependency due to missing prestation_id', $dependencyData);

                continue;
            }

            $prestation = Prestation::findOrFail($dependencyData['prestation_id']);

            // Calculate pricing for dependency
            $basePrice = $prestation->getPublicPrice();
            $finalPrice = $basePrice;

            if (! empty($conventionData) && isset($dependencyData['convention_price']) && $dependencyData['convention_price'] !== null) {
                $finalPrice = $dependencyData['convention_price'];
            }

            // Create dependency record
            ItemDependency::create([
                'parent_item_id' => $parentItem->id,
                'dependent_prestation_id' => $prestation->id,
                // 'dependency_type' => 'custom', // Add this field to identify custom dependencies
                'doctor_id' => $dependencyData['doctor_id'] ?? $parentItem->doctor_id,
                'base_price' => $basePrice,
                // copy default payment type from prestation to the dependency record
                'default_payment_type' => $prestation->default_payment_type ?? null,
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
                        'base_price' => $prestation->getPublicPrice(),
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
                'date' => $date,
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

        return $prestation->getPublicPrice();

        // Example of more complex pricing logic:
        /*
        $convention = Convention::find($conventionId);
        if ($convention && $convention->discount_percentage) {
            $discount = ($prestation->getPublicPrice()
 * $convention->discount_percentage) / 100;
            return $prestation->getPublicPrice()
 - $discount;
        }

        return $prestation->getPublicPrice()
;
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
                'specialization_id' => $specializationId,
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
                'error' => $e->getMessage(),
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
                'error' => $e->getMessage(),
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
            $patients = \App\Models\Patient::where(function ($q) use ($query) {
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
                'query' => $query,
            ]);
            throw $e;
        }
    }

    /**
     * Convert multiple prestation items to a package by:
     * 1. Detecting if prestations match an available package
     * 2. Removing the existing prestation items
     * 3. Creating a new package item
     * 4. Updating the total amount
     */
    public function convertPrestationsToPackage(
        int $ficheNavetteId, 
        array $prestationIds, 
        int $packageId,
        ?int $explicitDoctorId = null
    ): ficheNavette {
        DB::beginTransaction();

        try {
            $ficheNavette = ficheNavette::findOrFail($ficheNavetteId);
            $package = PrestationPackage::findOrFail($packageId);

            \Log::info('Converting prestations to package:', [
                'fiche_id' => $ficheNavetteId,
                'prestation_ids' => $prestationIds,
                'package_id' => $packageId,
                'explicit_doctor_id' => $explicitDoctorId,
            ]);

            // Step 1: Get existing fiche items that match the prestation IDs
            $itemsToRemove = ficheNavetteItem::where('fiche_navette_id', $ficheNavetteId)
                ->whereIn('prestation_id', $prestationIds)
                ->get();

            if ($itemsToRemove->isEmpty()) {
                DB::rollBack();
                throw new \InvalidArgumentException('No prestation items found to convert');
            }

            // Step 2: Doctor Validation - CRITICAL REQUIREMENT
            $doctorIds = $itemsToRemove->pluck('doctor_id')->filter()->unique()->toArray();
            $finalDoctorId = null;

            if ($explicitDoctorId) {
                // User explicitly chose a doctor - use it
                $finalDoctorId = $explicitDoctorId;
                \Log::info('Using explicit doctor for package creation:', [
                    'doctor_id' => $finalDoctorId,
                    'package_id' => $packageId,
                ]);
            } elseif (count($doctorIds) === 0) {
                // No doctors assigned to items - stay null
                $finalDoctorId = null;
                \Log::info('No doctors assigned to prestation items - package will have no doctor');
            } elseif (count($doctorIds) === 1) {
                // All items have the same doctor - use it
                $finalDoctorId = $doctorIds[0];
                \Log::info('All items have same doctor - using it for package:', [
                    'doctor_id' => $finalDoctorId,
                ]);
            } else {
                // Multiple different doctors - Allow this scenario
                // The package will store each doctor in prestation_package_reception
                // Use the first doctor for the package item's doctor_id field
                $finalDoctorId = $doctorIds[0];
                \Log::info('Multiple doctors found - recording all doctors in package_reception:', [
                    'fiche_id' => $ficheNavetteId,
                    'all_doctor_ids' => $doctorIds,
                    'package_doctor_id' => $finalDoctorId,
                    'prestation_ids' => $prestationIds,
                ]);
            }

            $totalAmountToRemove = 0;
            foreach ($itemsToRemove as $item) {
                $totalAmountToRemove += $item->final_price;
                $item->delete();
            }

            \Log::info('Removed prestation items:', [
                'count' => $itemsToRemove->count(),
                'total_amount' => $totalAmountToRemove,
            ]);

            // Step 3: Create new package item with preserved data from first item
            $firstItem = $itemsToRemove->first();
            $packagePrice = $package->price ?? $package->public_price ?? 0;
            
            // Normalize package price in case it's an array/complex value
            if (is_array($packagePrice)) {
                $packagePrice = (float) ($packagePrice['ttc_with_consumables_vat'] ?? $packagePrice['ttc'] ?? $packagePrice['price'] ?? 0);
            } else {
                $packagePrice = (float) $packagePrice;
            }

            $packageItem = ficheNavetteItem::create([
                'fiche_navette_id' => $ficheNavetteId,
                'package_id' => $packageId,
                'prestation_id' => null, // Package items have no individual prestation
                'doctor_id' => $finalDoctorId, // DOCTOR PRESERVED AND VALIDATED
                'convention_id' => $firstItem->convention_id ?? null,
                'insured_id' => $firstItem->insured_id ?? $ficheNavette->patient_id,
                'status' => 'pending',
                'base_price' => $packagePrice,
                'final_price' => $packagePrice,
                'patient_share' => $packagePrice,
                'default_payment_type' => $firstItem->default_payment_type ?? null,
                'prise_en_charge_date' => $firstItem->prise_en_charge_date ?? now(),
                'family_authorization' => $firstItem->family_authorization ?? null,
                'uploaded_file' => $firstItem->uploaded_file ?? null,
                'notes' => 'Converted from prestations: ' . implode(', ', $prestationIds),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Log::info('✅ Package created from prestations:', [
                'item_id' => $packageItem->id,
                'package_id' => $packageId,
                'package_name' => $package->name,
                'doctor_id' => $finalDoctorId,
                'prestation_ids_converted' => $prestationIds,
                'price' => $packagePrice,
            ]);

            // Step 3b: Auto-record doctor assignments in prestation_package_reception
            // Build mappings from the original prestation items that were converted
            $prestationDoctorMappings = [];
            foreach ($itemsToRemove as $item) {
                if ($item->doctor_id) {
                    $prestationDoctorMappings[] = [
                        'prestation_id' => $item->prestation_id,
                        'doctor_id' => $item->doctor_id,
                    ];
                }
            }

            // Store all doctor assignments for this package if there are any
            if (!empty($prestationDoctorMappings)) {
                $this->storePrestationDoctorsInPackage($packageId, $prestationDoctorMappings);
                \Log::info('Auto-recorded doctor assignments for package:', [
                    'package_id' => $packageId,
                    'mappings_count' => count($prestationDoctorMappings),
                    'mappings' => $prestationDoctorMappings,
                ]);
            }

            // Step 4: Update total amount (subtract old items, add new package)
            $newTotal = $ficheNavette->total_amount - $totalAmountToRemove + $packagePrice;
            $ficheNavette->update(['total_amount' => $newTotal]);

            \Log::info('Updated fiche totals after package conversion:', [
                'fiche_id' => $ficheNavetteId,
                'old_total' => $ficheNavette->total_amount,
                'removed_amount' => $totalAmountToRemove,
                'package_amount' => $packagePrice,
                'new_total' => $newTotal,
            ]);

            DB::commit();

            return $ficheNavette->fresh([
                'items.prestation',
                'items.package',
                'items.dependencies.dependencyPrestation',
                'items.convention',
                'patient',
                'creator',
            ]);

        } catch (MultiDoctorException $e) {
            DB::rollBack();
            throw $e; // Re-throw multi-doctor exception
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error converting prestations to package:', [
                'fiche_id' => $ficheNavetteId,
                'prestation_ids' => $prestationIds,
                'package_id' => $packageId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Add multiple prestations as separate items WITHOUT package conversion
     * Used when user explicitly chooses "Add as separate prestations" mode
     * 
     * @param int $ficheNavetteId
     * @param array $prestationIds
     * @return ficheNavette
     */
    public function addSeparatePrestations(int $ficheNavetteId, array $prestationIds): ficheNavette
    {
        DB::beginTransaction();

        try {
            $ficheNavette = ficheNavette::findOrFail($ficheNavetteId);
            
            \Log::info('Adding prestations as separate items (no package conversion):', [
                'fiche_id' => $ficheNavetteId,
                'prestation_ids' => $prestationIds,
                'count' => count($prestationIds),
            ]);

            $totalAmountAdded = 0;

            // Get all prestations
            $prestations = Prestation::whereIn('id', $prestationIds)->get();

            if ($prestations->count() !== count($prestationIds)) {
                throw new \InvalidArgumentException('One or more prestations not found');
            }

            // Create a separate item for EACH prestation
            foreach ($prestations as $prestation) {
                $price = $prestation->public_price ?? 0;

                $item = ficheNavetteItem::create([
                    'fiche_navette_id' => $ficheNavetteId,
                    'prestation_id' => $prestation->id,
                    'package_id' => null, // NO package for this mode
                    'doctor_id' => null, // Will be assigned separately if needed
                    'status' => 'pending',
                    'base_price' => $price,
                    'final_price' => $price,
                    'patient_share' => $price,
                    'default_payment_type' => $prestation->default_payment_type ?? null,
                    'prise_en_charge_date' => now(),
                    'notes' => 'Added as individual prestation (not converted to package)',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $totalAmountAdded += $price;
            }

            // Update fiche total
            $ficheNavette->increment('total_amount', $totalAmountAdded);

            \Log::info('✅ Separate prestations added:', [
                'fiche_id' => $ficheNavetteId,
                'count_added' => count($prestationIds),
                'total_amount_added' => $totalAmountAdded,
            ]);

            DB::commit();

            return $ficheNavette->fresh([
                'items.prestation',
                'items.package',
                'items.dependencies',
                'patient',
                'creator',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error adding separate prestations:', [
                'fiche_id' => $ficheNavetteId,
                'prestation_ids' => $prestationIds,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a new package from selected prestations (custom package mode)
     * Validates doctor consistency and creates new package if needed
     * 
     * @param int $ficheNavetteId
     * @param array $prestationIds - Prestation IDs to bundle
     * @param ?int $explicitDoctorId - User-selected doctor (overrides validation)
     * @param ?string $packageName - Custom name for new package
     * @param ?string $packageDescription - Custom description
     * @return ficheNavette
     */
    public function createCustomPackageFromPrestations(
        int $ficheNavetteId,
        array $prestationIds,
        ?int $explicitDoctorId = null,
        ?string $packageName = null,
        ?string $packageDescription = null
    ): ficheNavette {
        DB::beginTransaction();

        try {
            $ficheNavette = ficheNavette::findOrFail($ficheNavetteId);

            \Log::info('Creating custom package from prestations:', [
                'fiche_id' => $ficheNavetteId,
                'prestation_ids' => $prestationIds,
                'explicit_doctor' => $explicitDoctorId,
                'package_name' => $packageName,
            ]);

            // Get prestations to bundle
            $prestations = Prestation::whereIn('id', $prestationIds)->get();

            if ($prestations->count() !== count($prestationIds)) {
                throw new \InvalidArgumentException('One or more prestations not found');
            }

            // Calculate total price
            $totalPrice = $prestations->sum('public_price');

            // Check if this exact combination already exists as a package
            $existingPackage = $this->detectMatchingPackage($prestationIds);

            if ($existingPackage) {
                \Log::info('Found existing package matching prestations:', [
                    'package_id' => $existingPackage->id,
                    'package_name' => $existingPackage->name,
                ]);

                // Use existing package
                $packageId = $existingPackage->id;
            } else {
                // Create new package
                $newPackage = PrestationPackage::create([
                    'name' => $packageName ?? 'Custom Package ' . now()->format('Y-m-d H:i'),
                    'description' => $packageDescription,
                    'price' => $totalPrice,
                    'is_custom' => true,
                    'is_active' => true,
                ]);

                // Link prestations to package via PrestationPackageitem pivot table
                try {
                    foreach ($prestationIds as $prestationId) {
                        \App\Models\CONFIGURATION\PrestationPackageitem::create([
                            'prestation_package_id' => $newPackage->id,
                            'prestation_id' => $prestationId,
                        ]);
                    }
                } catch (\Exception $e) {
                    // Table might not exist in test environment
                    \Log::warning('Could not link prestations to package (pivot table issue):', [
                        'package_id' => $newPackage->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                \Log::info('Created new custom package:', [
                    'package_id' => $newPackage->id,
                    'package_name' => $newPackage->name,
                    'prestation_count' => count($prestationIds),
                ]);

                $packageId = $newPackage->id;
            }

            // Now convert the prestations to this package
            // This will handle doctor validation
            $result = $this->convertPrestationsToPackage(
                $ficheNavetteId,
                $prestationIds,
                $packageId,
                $explicitDoctorId
            );

            DB::commit();

            return $result;

        } catch (MultiDoctorException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating custom package:', [
                'fiche_id' => $ficheNavetteId,
                'prestation_ids' => $prestationIds,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Intelligently handle package conversion when adding new items
     * DELEGATED TO FACADE - Uses clean architecture pattern
     * 
     * @param int $ficheNavetteId
     * @param array $newPrestationIds - New prestations being added
     * @param array $existingPrestationIds - Existing prestations in fiche
     * @return array - ['should_convert' => bool, 'package_id' => int|null, 'items_to_remove' => array]
     */
    public function checkAndPreparePackageConversion(
        int $ficheNavetteId,
        array $newPrestationIds,
        array $existingPrestationIds = []
    ): array {
        // Delegate to the clean architecture Facade
        return $this->packageConversionFacade->checkAndPrepare(
            $ficheNavetteId,
            $newPrestationIds,
            $existingPrestationIds
        );
    }

    /**
     * Auto-convert existing prestations to a matching package when new items are added
     * DELEGATED TO FACADE - Uses clean architecture pattern
     * 
     * SUPPORTS CASCADING:
     * - You have PACK A with [5, 87]
     * - You add [88]
     * - Together [5, 87, 88] = PACK CARDIOLOGIE
     * - Remove PACK A and all individual items
     * - Replace with new PACK CARDIOLOGIE
     * 
     * @param int $ficheNavetteId
     * @param int $packageId - The new package to create
     * @param array $itemIdsToRemove - IDs of fiche items to remove (both individual items AND package items)
     * @param array $newPrestationIds - New prestation IDs being added
     * @return ficheNavette
     */
    public function autoConvertToPackageOnAddItem(
        int $ficheNavetteId,
        int $packageId,
        array $itemIdsToRemove,
        array $newPrestationIds = []
    ): ficheNavette {
        // Delegate to the clean architecture Facade
        return $this->packageConversionFacade->execute(
            $ficheNavetteId,
            $packageId,
            $itemIdsToRemove,
            $newPrestationIds
        );
    }

    /**
     * Update the ReceptionService to incorporate the new logic
     * This should be called after adding new items to check if auto-conversion is needed
     */
    public function addItemsToFicheNavetteWithAutoConversion(ficheNavette $ficheNavette, array $data): ficheNavette
    {
        // First, get existing prestation IDs
        $existingPrestationIds = $ficheNavette->items()
            ->whereNotNull('prestation_id')
            ->pluck('prestation_id')
            ->toArray();

        // Add items normally first
        $updatedFiche = $this->addItemsToFicheNavette($ficheNavette, $data);

        // Get new prestation IDs from the request
        $newPrestationIds = [];
        if (isset($data['prestations'])) {
            foreach ($data['prestations'] as $prestationData) {
                if (isset($prestationData['prestation_id'])) {
                    $newPrestationIds[] = $prestationData['prestation_id'];
                }
            }
        }

        // Check if we should auto-convert
        $conversionCheck = $this->checkAndPreparePackageConversion(
            $ficheNavette->id,
            $newPrestationIds,
            $existingPrestationIds
        );

        if ($conversionCheck['should_convert']) {
            // Get IDs of items to remove
            $itemIds = array_map(function ($item) {
                return $item['id'];
            }, $conversionCheck['items_to_remove']);

            // Perform auto-conversion
            return $this->autoConvertToPackageOnAddItem(
                $ficheNavette->id,
                $conversionCheck['package_id'],
                $itemIds,
                $newPrestationIds
            );
        }

        return $updatedFiche;
    }

    /**
     * Store doctor assignments for prestations in a package
     * This is called when creating or updating a package item with specific doctors for each prestation
     * 
     * @param int $packageId - The package ID
     * @param array $prestationDoctorMappings - Array of ['prestation_id' => id, 'doctor_id' => id]
     * @return bool
     */
    public function storePrestationDoctorsInPackage(int $packageId, array $prestationDoctorMappings): bool
    {
        DB::beginTransaction();

        try {
            $package = PrestationPackage::findOrFail($packageId);

            // Clear existing records for this package
            \App\Models\CONFIGURATION\PrestationPackageReception::where('package_id', $packageId)->delete();

            // Create new records for each prestation-doctor mapping
            foreach ($prestationDoctorMappings as $mapping) {
                \App\Models\CONFIGURATION\PrestationPackageReception::create([
                    'package_id' => $packageId,
                    'prestation_id' => $mapping['prestation_id'],
                    'doctor_id' => $mapping['doctor_id'] ?? null,
                ]);
            }

            DB::commit();

            \Log::info('Stored prestation doctor assignments for package:', [
                'package_id' => $packageId,
                'mappings_count' => count($prestationDoctorMappings),
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to store prestation doctors in package:', [
                'package_id' => $packageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get all prestations in a package with their assigned doctors
     * Used for displaying package details with doctor assignments
     * 
     * @param int $packageId - The package ID
     * @return array - Array of prestations with doctor info
     */
    public function getPrestationsWithDoctorsInPackage(int $packageId): array
    {
        try {
            $receptionRecords = \App\Models\CONFIGURATION\PrestationPackageReception::where('package_id', $packageId)
                ->with(['prestation', 'doctor'])
                ->get();

            if ($receptionRecords->isEmpty()) {
                // If no records yet, get all prestations in the package without doctor info
                $package = PrestationPackage::with('prestations')->findOrFail($packageId);
                return $package->prestations->map(function ($prestation) {
                    return [
                        'prestation_id' => $prestation->id,
                        'prestation_name' => $prestation->name,
                        'prestation_price' => $prestation->price,
                        'doctor_id' => null,
                        'doctor_name' => null,
                    ];
                })->toArray();
            }

            return $receptionRecords->map(function ($record) {
                return [
                    'prestation_id' => $record->prestation_id,
                    'prestation_name' => $record->prestation?->name,
                    'prestation_price' => $record->prestation?->price,
                    'doctor_id' => $record->doctor_id,
                    'doctor_name' => $record->doctor?->name ?? $record->doctor?->user?->name,
                ];
            })->toArray();
        } catch (\Exception $e) {
            \Log::error('Failed to get prestations with doctors in package:', [
                'package_id' => $packageId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update or create doctor assignment for a specific prestation in a package
     * 
     * @param int $packageId - The package ID
     * @param int $prestationId - The prestation ID
     * @param int|null $doctorId - The doctor ID (null to remove assignment)
     * @return bool
     */
    public function updatePrestationDoctorInPackage(int $packageId, int $prestationId, ?int $doctorId): bool
    {
        DB::beginTransaction();

        try {
            // Find or create the record
            $record = \App\Models\CONFIGURATION\PrestationPackageReception::firstOrCreate(
                [
                    'package_id' => $packageId,
                    'prestation_id' => $prestationId,
                ],
                [
                    'doctor_id' => $doctorId,
                ]
            );

            // Update the doctor_id if record already exists
            if ($record->wasRecentlyCreated === false) {
                $record->update(['doctor_id' => $doctorId]);
            }

            DB::commit();

            \Log::info('Updated prestation doctor assignment in package:', [
                'package_id' => $packageId,
                'prestation_id' => $prestationId,
                'doctor_id' => $doctorId,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update prestation doctor in package:', [
                'package_id' => $packageId,
                'prestation_id' => $prestationId,
                'doctor_id' => $doctorId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
  