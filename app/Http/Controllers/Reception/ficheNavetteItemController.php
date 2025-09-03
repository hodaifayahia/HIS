<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Resources\Reception\ficheNavetteResource;
use App\Http\Resources\Reception\ficheNavetteItemResource;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Reception\ItemDependency;
use App\Models\Reception\ficheNavette;
use App\Models\manager\RefundAuthorization;
use App\Services\Reception\ReceptionService;
use App\Models\CONFIGURATION\PrestationPackageitem;
use App\Services\Reception\ConventionPricingService;
use App\Services\Reception\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Reception\ConventionOrganismeResource;
use App\Http\Requests\Reception\StoreFicheNavetteItemsRequest;
use App\Http\Requests\Reception\StoreConventionPrescriptionRequest;
use App\Http\Requests\Reception\UploadConventionFilesRequest;
use App\Http\Requests\Reception\GetPrestationsByPackageRequest;
use App\Http\Requests\Reception\GetPatientConventionsRequest;
use App\Http\Requests\Reception\UpdateFicheNavetteItemRequest;
use App\Http\Requests\Reception\GetPrestationsWithConventionPricingRequest;
use App\Http\Requests\Reception\GetPrestationsByConventionRequest;
use App\Http\Requests\Reception\GetGroupedByInsuredRequest;

class ficheNavetteItemController extends Controller
{
    protected $receptionService;
    protected $conventionPricingService;
    protected $fileUploadService;

    public function __construct(
        ReceptionService $receptionService, 
        ConventionPricingService $conventionPricingService,
        FileUploadService $fileUploadService
    ) {
        $this->receptionService = $receptionService;
        $this->conventionPricingService = $conventionPricingService;
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Store items to an existing fiche navette
     */
    public function store(StoreFicheNavetteItemsRequest $request, $ficheNavetteId): JsonResponse
    {
        // Remove or comment out the dd() statement
        // dd($request->validated());
        
        if (!$ficheNavetteId) {
            return response()->json([
                'success' => false,
                'message' => 'Fiche Navette ID is required',
                'error' => 'Missing ficheNavetteId parameter'
            ], 400);
        }

        try {
            $ficheNavette = ficheNavette::findOrFail($ficheNavetteId);
            $updatedFiche = $this->receptionService->addItemsToFicheNavette($ficheNavette, $request->validated());

            // Optimize relationship loading
            $updatedFiche->loadMissing([
                'items' => function($query) {
                    $query->select(['id', 'fiche_navette_id', 'prestation_id', 'package_id', 'payment_status', 'status', 'final_price']);
                },
                'items.prestation:id,name,internal_code,public_price,default_payment_type,min_versement_amount,need_an_appointment',
                'items.package:id,name,description,price',
                'items.dependencies' => function($query) {
                    $query->select(['id', 'parent_item_id', 'dependent_prestation_id', 'custom_name']);
                },
                'items.dependencies.dependencyPrestation:id,name,internal_code,public_price',
                'items.convention:id,name,contract_name',
                'patient:id,first_name,last_name',
                'creator:id,name'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Items added to Fiche Navette successfully',
                'data' => new ficheNavetteResource($updatedFiche)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add items to Fiche Navette',
                'error' => $e->getMessage(),
                'debug' => [
                    'line' => $e->getLine(),
                    'file' => $e->getFile()
                ]
            ], 500);
        }
    }

    /**
     * Store convention prescription items with specialization and doctor
     */
    public function storeConventionPrescription(StoreConventionPrescriptionRequest $request, $ficheNavetteId): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $validatedData['fiche_navette_id'] = $ficheNavetteId;
            $result = $this->receptionService->createConventionPrescriptionItems($validatedData, $ficheNavetteId);

            return response()->json([
                'success' => true,
                'message' => 'Convention prescription items created successfully',
                'data' => [
                    'items_created' => $result['items_created'],
                    'total_amount' => $result['total_amount'],
                    'items' => ficheNavetteItemResource::collection($result['items'])
                ]
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Convention prescription creation failed:', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create convention prescription items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload convention files
     */
    public function uploadConventionFiles(UploadConventionFilesRequest $request): JsonResponse
    {
        try {
            $uploadedFiles = [];
            
            foreach ($request->file('files') as $file) {
                if ($this->fileUploadService->validateConventionFile($file)) {
                    $uploadedFiles[] = $this->fileUploadService->uploadSingleFile($file);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid file type. Only PDF and Word documents are allowed.',
                    ], 400);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Files uploaded successfully',
                'data' => $uploadedFiles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload files',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPrestationsByPackage(GetPrestationsByPackageRequest $request, $packageId) {
        try {
            $prestations = $this->receptionService->getPrestationsByPackage($packageId);
            return response()->json($prestations);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch prestations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download convention file
     */
    public function downloadConventionFile(Request $request, $fileId): JsonResponse
    {
        try {
            // Find the fiche navette item that contains this file
            $item = ficheNavetteItem::whereJsonContains('uploaded_file', ['id' => $fileId])->first();
            
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            $files = $item->uploaded_file ?? [];
            $file = collect($files)->firstWhere('id', $fileId);

            if (!$file || !isset($file['path'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            if (!\Storage::disk('public')->exists($file['path'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'File does not exist on storage'
                ], 404);
            }

            return response()->download(
                storage_path('app/public/' . $file['path']),
                $file['original_name']
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get items for a specific fiche navette
     */
    public function index(GetGroupedByInsuredRequest $request, $ficheNavetteId)
    {
        try {
            // Try to get from cache
            $cacheKey = "fiche_navette_items_{$ficheNavetteId}";
            if ($cachedResponse = cache()->get($cacheKey)) {
                return response()->json($cachedResponse);
            }

            // Optimize the main query by selecting only needed fields and chunking relations
            $ficheNavette = ficheNavette::select(['id', 'patient_id', 'creator_id', 'fiche_date'])
                ->with([
                    'items' => function($query) {
                        $query->select([
                            'id', 'fiche_navette_id', 'prestation_id', 'package_id',
                            'convention_id', 'doctor_id', 'insured_id', 'final_price'
                        ]);
                    },
                    'items.prestation' => function($query) {
                        $query->select(['id', 'name', 'service_id', 'specialization_id']);
                    },
                    'items.prestation.service:id,name',
                    'items.prestation.specialization:id,name',
                    'items.package' => function($query) {
                        $query->select(['id', 'name']);
                    },
                    'items.package.items' => function($query) {
                        $query->select(['id', 'prestation_package_id', 'prestation_id'])
                            ->with(['prestation' => function($q) {
                                $q->select(['id', 'name', 'service_id', 'specialization_id']);
                            }]);
                    },
                    'items.dependencies' => function($query) {
                        $query->select(['id', 'parent_item_id', 'dependent_prestation_id'])
                            ->with(['dependencyPrestation' => function($q) {
                                $q->select(['id', 'name', 'service_id']);
                            }]);
                    },
                    'items.convention' => function($query) {
                        $query->select(['id', 'name', 'organisme_id'])
                            ->with(['organisme:id,name']);
                    },
                    'items.doctor:id,name',
                    'items.insuredPatient:id,Firstname,Lastname',
                    'patient:id,Firstname,Lastname',
                    'creator:id,name'
                ])
                ->findOrFail($ficheNavetteId);

            // Process items collection efficiently
            $items = $ficheNavette->items;
            
            // Set parent IDs for dependencies in a more efficient way
            $items->each(function($item) {
                $item->dependencies?->each(fn($dep) => $dep->parent_item_id = $item->id);
            });

            // Optimize refund authorizations query
            $itemIds = $items->pluck('id')->all();
            $refundAuthorizations = empty($itemIds) ? [] : 
                RefundAuthorization::select(['id', 'fiche_navette_item_id'])
                    ->whereIn('fiche_navette_item_id', $itemIds)
                    ->get()
                    ->groupBy('fiche_navette_item_id')
                    ->map(fn($group) => $group->map->toArray()->values())
                    ->toArray();

            // Prepare the response
            $response = [
                'success' => true,
                'data' => ficheNavetteItemResource::collection($items),
                'refund_authorizations' => $refundAuthorizations,
                'meta' => [
                    'fiche_navette_id' => $ficheNavetteId,
                    'total_items' => $items->count(),
                    'total_amount' => $items->sum('final_price'),
                    'patient_name' => $ficheNavette->patient ? 
                        "{$ficheNavette->patient->Firstname} {$ficheNavette->patient->Lastname}" : 
                        'Unknown',
                    'fiche_date' => $ficheNavette->fiche_date
                ]
            ];

            // Cache the response for 5 minutes
            cache()->put($cacheKey, $response, now()->addMinutes(5));

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::error('Error fetching fiche navette items:', [
                'fiche_navette_id' => $ficheNavetteId,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch fiche navette items',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a specific item in a fiche navette
     */
    public function update(UpdateFicheNavetteItemRequest $request, $ficheNavetteId, $itemId): JsonResponse
    {
        try {
            $updatedFiche = $this->receptionService->updateFicheNavetteItem($ficheNavetteId, $itemId, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Item updated successfully',
                'data' => new ficheNavetteResource($updatedFiche)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get prestations with convention pricing based on date
     */
    public function getPrestationsWithConventionPricing(GetPrestationsWithConventionPricingRequest $request): JsonResponse
    {
        try {
            $conventionIds = $request->validated()['convention_ids'];
            $priseEnChargeDate = $request->validated()['prise_en_charge_date'];
            
            if (!$conventionIds) {
                return response()->json([
                    'success' => false,
                    'message' => 'Convention IDs are required'
                ], 400);
            }

            // Convert string to array if needed
            if (is_string($conventionIds)) {
                $conventionIds = array_filter(explode(',', $conventionIds));
            }

            $prestationsWithPricing = $this->conventionPricingService
                ->getPrestationsWithDateBasedPricing($conventionIds, $priseEnChargeDate);

            return response()->json([
                'success' => true,
                'data' => $prestationsWithPricing,
                'meta' => [
                    'prise_en_charge_date' => $priseEnChargeDate,
                    'convention_count' => count($conventionIds),
                    'prestation_count' => count($prestationsWithPricing)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch prestations with convention pricing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get prestations by a specific convention and date
     */
    public function getPrestationsByConvention(GetPrestationsByConventionRequest $request, $conventionId): JsonResponse
    {
        try {
            $priseEnChargeDate = $request->validated()['prise_en_charge_date'];
            
            $prestations = $this->conventionPricingService
                ->getConventionPrestationsForDate($conventionId, $priseEnChargeDate ? \Carbon\Carbon::parse($priseEnChargeDate) : \Carbon\Carbon::now());

            return response()->json([
                'success' => true,
                'data' => $prestations,
                'meta' => [
                    'convention_id' => $conventionId,
                    'prise_en_charge_date' => $priseEnChargeDate,
                    'prestation_count' => count($prestations)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch prestations by convention',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove an item from a fiche navette
     */
    public function destroy($ficheNavetteId, $itemId): JsonResponse
    {
        try {
            $updatedFiche = $this->receptionService->removeFicheNavetteItem($ficheNavetteId, $itemId);

            return response()->json([
                'success' => true,
                'message' => 'Item removed successfully',
                'data' => new ficheNavetteResource($updatedFiche)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get items grouped by insured patient
     */
    public function getGroupedByInsured(GetGroupedByInsuredRequest $request, $ficheNavetteId): JsonResponse
    {
        try {
            $groupedItems = $this->receptionService->getItemsGroupedByInsured($ficheNavetteId);

            return response()->json([
                'success' => true,
                'data' => $groupedItems,
                'meta' => [
                    'groups_count' => count($groupedItems),
                    'total_items' => collect($groupedItems)->sum('prestations_count')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch grouped items',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get patient conventions
     */
    public function getPatientConventions(GetPatientConventionsRequest $request, $patientId): JsonResponse
    {
        try {
            // Get convention IDs and prestation IDs from fiche navette items
            $ficheNavetteItems = ficheNavetteItem::where('fiche_navette_id', $request->validated()['fiche_navette_id'])
                ->get(['convention_id', 'prestation_id', 'uploaded_file'])
                ->filter(function ($item) {
                    return $item->convention_id && $item->prestation_id;
                });

            $conventionIds = $ficheNavetteItems->pluck('convention_id')->unique()->values();
            $prestationIds = $ficheNavetteItems->pluck('prestation_id')->unique()->values();

            // Fetch conventions directly from the database
            $conventions = \DB::table('conventions')
                ->whereIn('id', $conventionIds)
                ->get();

            // Fetch prestations directly from the database
            $prestations = \DB::table('prestations')
                ->whereIn('id', $prestationIds)
                ->get();

            // Fetch organismes directly from the database
            $organismes = \DB::table('organismes')
                ->whereIn('id', $conventions->pluck('organisme_id')->unique())
                ->get();

            // Group conventions by organisme
            $groupedData = $organismes->map(function ($organisme) use ($conventions, $ficheNavetteItems, $prestations) {
                $organismeConventions = $conventions->filter(function ($convention) use ($organisme) {
                    return $convention->organisme_id == $organisme->id;
                });

                return [
                    'id' => $organisme->id,
                    'organisme_name' => $organisme->name,
                    'organism_color' => $organisme->organism_color ?? null,
                    'description' => $organisme->description,
                    'industry' => $organisme->industry,
                    'address' => $organisme->address,
                    'phone' => $organisme->phone,
                    'email' => $organisme->email,
                    'website' => $organisme->website,
                    'conventions_count' => $organismeConventions->count(),
                    'conventions' => $organismeConventions->map(function ($convention) use ($ficheNavetteItems, $prestations) {
                        // Get prestation_ids for this convention from ficheNavetteItems
                        $prestationIdsForConvention = $ficheNavetteItems
                            ->where('convention_id', $convention->id)
                            ->pluck('prestation_id')
                            ->unique()
                            ->values();

                        // Get prestation details for this convention
                        $conventionPrestations = $prestations->whereIn('id', $prestationIdsForConvention);

                        // Collect all uploaded files for this convention from ficheNavetteItems
                        $uploadedFiles = $ficheNavetteItems
                            ->where('convention_id', $convention->id)
                            ->pluck('uploaded_file')
                            ->filter()
                            ->flatMap(function ($file) {
                                if (is_array($file)) return $file;
                                if (is_string($file)) return json_decode($file, true) ?: [];
                                return [];
                            })
                            ->filter()
                            ->unique('id')
                            ->values();

                        return [
                            'id' => $convention->id,
                            'convention_name' => $convention->name,
                            'status' => $convention->status,
                            'uploaded_files' => $uploadedFiles,
                            'prestations' => $conventionPrestations->map(function ($prestation) {
                                return [
                                    'id' => $prestation->id,
                                    'name' => $prestation->name,
                                    'specialization' => $prestation->specialization ?? 'Unknown',
                                ];
                            })->values()->all(),
                        ];
                    })->values()->all(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => ConventionOrganismeResource::collection($groupedData->values()),
                'meta' => [
                    'patient_id' => $patientId,
                    'organismes_count' => $groupedData->count(),
                    'total_conventions' => $groupedData->sum('conventions_count'),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching patient conventions:', [
                'patient_id' => $patientId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch patient conventions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * View file in browser
     */
    public function viewFile($fileId): Response
    {
        try {
            // Find the fiche navette item that contains this file
            $item = ficheNavetteItem::whereJsonContains('uploaded_file', ['id' => $fileId])->first();
            
            if (!$item) {
                abort(404, 'File not found');
            }

            $fileData = json_decode($item->uploaded_file, true);
            
            if (!$fileData || !isset($fileData['path'])) {
                abort(404, 'File path not found');
            }

            if (!Storage::disk('public')->exists($fileData['path'])) {
                abort(404, 'File does not exist on storage');
            }

            // Return file for viewing in browser
            return response()->file(
                storage_path('app/public/' . $fileData['path'])
            );
        } catch (\Exception $e) {
            \Log::error('Error viewing file:', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Failed to load file');
        }
    }

    /**
     * Download file
     */
    public function downloadFile($fileId): Response
    {
        try {
            // Find the fiche navette item that contains this file
            $item = ficheNavetteItem::whereJsonContains('uploaded_file', ['id' => $fileId])->first();
            
            if (!$item) {
                abort(404, 'File not found');
            }

            $fileData = json_decode($item->uploaded_file, true);
            
            if (!$fileData || !isset($fileData['path'])) {
                abort(404, 'File path not found');
            }

            if (!Storage::disk('public')->exists($fileData['path'])) {
                abort(404, 'File does not exist on storage');
            }

            // Return file for download
            return response()->download(
                storage_path('app/public/' . $fileData['path']),
                $fileData['original_name'] ?? 'download'
            );
        } catch (\Exception $e) {
            \Log::error('Error downloading file:', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Failed to download file');
        }
    }

    /**
     * Remove dependency
     */
    public function removeDependency($dependencyId): JsonResponse
    {
        try {
            $result = $this->receptionService->removeDependency($dependencyId);
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dependency removed successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to remove dependency'
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Error removing dependency:', [
                'dependency_id' => $dependencyId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove dependency',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}