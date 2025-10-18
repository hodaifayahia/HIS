<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\GetGroupedByInsuredRequest;
use App\Http\Requests\Reception\GetPatientConventionsRequest;
use App\Http\Requests\Reception\GetPrestationsByConventionRequest;
use App\Http\Requests\Reception\GetPrestationsByPackageRequest;
use App\Http\Requests\Reception\GetPrestationsWithConventionPricingRequest;
use App\Http\Requests\Reception\StoreConventionPrescriptionRequest;
use App\Http\Requests\Reception\StoreFicheNavetteItemsRequest;
use App\Http\Requests\Reception\UpdateFicheNavetteItemRequest;
use App\Http\Requests\Reception\UploadConventionFilesRequest;
use App\Http\Resources\Reception\ConventionOrganismeResource;
use App\Http\Resources\Reception\ficheNavetteItemResource;
use App\Http\Resources\Reception\FicheNavetteResource;
use App\Models\manager\RefundAuthorization;
use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use App\Services\Reception\ConventionPricingService;
use App\Services\Reception\FileUploadService;
use App\Services\Reception\ReceptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

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

        if (! $ficheNavetteId) {
            return response()->json([
                'success' => false,
                'message' => 'Fiche Navette ID is required',
                'error' => 'Missing ficheNavetteId parameter',
            ], 400);
        }

        try {
            $ficheNavette = ficheNavette::findOrFail($ficheNavetteId);
            $updatedFiche = $this->receptionService->addItemsToFicheNavette($ficheNavette, $request->validated());

            // Optimize relationship loading
            $updatedFiche->loadMissing([
                'items' => function ($query) {
                    $query->select(['id', 'fiche_navette_id', 'prestation_id', 'package_id', 'payment_status', 'status', 'final_price']);
                },
                'items.prestation:id,name,internal_code,public_price,default_payment_type,min_versement_amount,get_min_versement_amount_attribute,need_an_appointment',
                'items.package:id,name,description,price',
                'items.dependencies' => function ($query) {
                    $query->select(['id', 'parent_item_id', 'dependent_prestation_id', 'custom_name']);
                },
                'items.dependencies.dependencyPrestation:id,name,internal_code,public_price',
                'items.convention:id,name,contract_name',
                'patient:id,first_name,last_name',
                'creator:id,name',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Items added to Fiche Navette successfully',
                'data' => new FicheNavetteResource($updatedFiche),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add items to Fiche Navette',
                'error' => $e->getMessage(),
                'debug' => [
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ],
            ], 500);
        }
    }

    /**
     * Store convention prescription items with specialization and doctor
     */
    public function storeConventionPrescription(StoreConventionPrescriptionRequest $request, $ficheNavetteId): JsonResponse
    {
        // Debug: Log what we're receiving
        \Log::info('Convention prescription request received:', [
            'all_data' => $request->all(),
            'conventions_type' => gettype($request->input('conventions')),
            'conventions_value' => $request->input('conventions'),
            'is_array' => is_array($request->input('conventions')),
            'has_files' => $request->hasFile('uploadedFiles'),
            'files_count' => $request->hasFile('uploadedFiles') ? count($request->file('uploadedFiles')) : 0,
        ]);

        try {
            $validatedData = $request->validated();
            $validatedData['fiche_navette_id'] = $ficheNavetteId;

            // Process uploaded files if they exist
            if ($request->hasFile('uploadedFiles')) {
                $uploadedFiles = [];

                foreach ($request->file('uploadedFiles') as $index => $file) {
                    if ($file && $file->isValid()) {
                        try {
                            // Validate file using the file upload service
                            if ($this->fileUploadService->validateConventionFile($file)) {
                                $uploadedFileData = $this->fileUploadService->uploadSingleFile($file);
                                $uploadedFiles[] = $uploadedFileData;

                                \Log::info("File {$index} uploaded successfully:", [
                                    'original_name' => $uploadedFileData['original_name'],
                                    'path' => $uploadedFileData['path'],
                                    'size' => $uploadedFileData['size'],
                                ]);
                            } else {
                                \Log::warning("File {$index} validation failed:", [
                                    'name' => $file->getClientOriginalName(),
                                    'size' => $file->getSize(),
                                    'mime' => $file->getMimeType(),
                                ]);
                            }
                        } catch (\Exception $fileException) {
                            \Log::error("Error processing file {$index}:", [
                                'error' => $fileException->getMessage(),
                                'file_name' => $file->getClientOriginalName(),
                            ]);
                            // Continue with other files even if one fails
                        }
                    }
                }

                // Add processed files to validated data
                $validatedData['uploadedFiles'] = $uploadedFiles;

                \Log::info('Files processed for convention prescription:', [
                    'total_files_processed' => count($uploadedFiles),
                    'files_data' => $uploadedFiles,
                ]);
            }

            $result = $this->receptionService->createConventionPrescriptionItems($validatedData, $ficheNavetteId);

            return response()->json([
                'success' => true,
                'message' => 'Convention prescription items created successfully',
                'data' => [
                    'items_created' => $result['items_created'],
                    'total_amount' => $result['total_amount'],
                    'items' => ficheNavetteItemResource::collection($result['items']),
                    'files_uploaded' => isset($validatedData['uploadedFiles']) ? count($validatedData['uploadedFiles']) : 0,
                ],
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Convention prescription creation failed:', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
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
                'data' => $uploadedFiles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload files',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getPrestationsByPackage(GetPrestationsByPackageRequest $request, $packageId)
    {
        try {
            $prestations = $this->receptionService->getPrestationsByPackage($packageId);

            return response()->json($prestations);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch prestations',
                'error' => $e->getMessage(),
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

            if (! $item) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found',
                ], 404);
            }

            $files = $item->uploaded_file ?? [];
            $file = collect($files)->firstWhere('id', $fileId);

            if (! $file || ! isset($file['path'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found',
                ], 404);
            }

            if (! \Storage::disk('public')->exists($file['path'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'File does not exist on storage',
                ], 404);
            }

            return response()->download(
                storage_path('app/public/'.$file['path']),
                $file['original_name']
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get items for a specific fiche navette
     */
    public function index(GetGroupedByInsuredRequest $request, $ficheNavetteId)
    {
        try {
            $ficheNavette = ficheNavette::with([
                'items.prestation.service',
                'items.prestation.specialization',
                'items.package.items.prestation.service',
                'items.package.items.prestation.specialization',
                'items.dependencies.dependencyPrestation.service',
                'items.dependencies.dependencyPrestation.specialization',
                'items.nursingConsumptions.product',
                'items.nursingConsumptions.pharmacy',
                'items.convention.organisme',
                'items.doctor',
                'items.insuredPatient',
                'patient',
                'creator',
            ])->findOrFail($ficheNavetteId);

            // Ensure each item has its own dependencies properly linked
            $ficheNavette->items->each(function ($item) {
                if ($item->dependencies) {
                    $item->dependencies->each(function ($dependency) use ($item) {
                        // Ensure the dependency knows its parent
                        $dependency->parent_item_id = $item->id;
                    });
                }
            });
            // Collect fiche navette item ids to fetch related refund authorizations
            $itemIds = $ficheNavette->items->pluck('id')->filter()->unique()->values()->all();

            // Load refund authorizations that belong to these fiche navette items and group by item id
            $refundAuthorizations = [];
            if (! empty($itemIds)) {
                $refundAuthorizations = RefundAuthorization::whereIn('fiche_navette_item_id', $itemIds)
                    ->get()
                    ->groupBy('fiche_navette_item_id')
                    ->map(function ($group) {
                        return $group->map(function ($auth) {
                            return $auth->toArray();
                        })->values();
                    })->toArray();
            }

            return response()->json([
                'success' => true,
                'data' => ficheNavetteItemResource::collection($ficheNavette->items),
                'refund_authorizations' => $refundAuthorizations,
                'meta' => [
                    'fiche_navette_id' => $ficheNavetteId,
                    'total_items' => $ficheNavette->items->count(),
                    'total_amount' => $ficheNavette->items->sum('final_price'),
                    'patient_name' => $ficheNavette->patient_name ?? 'Unknown',
                    'fiche_date' => $ficheNavette->fiche_date,
                ],
            ]);
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
                'error' => $e->getMessage(),
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
                'data' => new FicheNavetteResource($updatedFiche),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a specific item dependency (only default_payment_type for now)
     */
    public function updateDependency(Request $request, $dependencyId): JsonResponse
    {
        try {
            $dependency = \App\Models\Reception\ItemDependency::findOrFail($dependencyId);

            $allowed = ['Pré-paiement', 'Post-paiement', 'Versement'];
            $value = $request->input('default_payment_type');

            if (! in_array($value, $allowed) && $value !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment type',
                ], 422);
            }

            $dependency->default_payment_type = $value;
            $dependency->save();

            // Return the parent fiche navette refreshed items so frontend can re-render
            $parentItem = $dependency->parentItem;
            $fiche = $parentItem ? $parentItem->ficheNavette : null;

            if ($fiche) {
                $fiche->loadMissing(['items.prestation', 'items.dependencies.dependencyPrestation', 'items.convention', 'patient', 'creator']);
                return response()->json([
                    'success' => true,
                    'message' => 'Dependency updated',
                    'data' => new FicheNavetteResource($fiche),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Dependency updated',
                'data' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update dependency',
                'error' => $e->getMessage(),
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

            if (! $conventionIds) {
                return response()->json([
                    'success' => false,
                    'message' => 'Convention IDs are required',
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
                    'prestation_count' => count($prestationsWithPricing),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch prestations with convention pricing',
                'error' => $e->getMessage(),
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
                    'prestation_count' => count($prestations),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch prestations by convention',
                'error' => $e->getMessage(),
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
                'data' => new FicheNavetteResource($updatedFiche),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item',
                'error' => $e->getMessage(),
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
                    'total_items' => collect($groupedItems)->sum('prestations_count'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch grouped items',
                'error' => $e->getMessage(),
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
                                if (is_array($file)) {
                                    return $file;
                                }
                                if (is_string($file)) {
                                    return json_decode($file, true) ?: [];
                                }

                                return [];
                            })
                            ->filter()
                            ->unique('id')
                            ->values();

                        return [
                            'id' => $convention->id,
                            'convention_name' => $convention->name,
                            'convention_percentage' => $convention->conventionDetail->discount_percentage ?? null,
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

            if (! $item) {
                abort(404, 'File not found');
            }

            $fileData = json_decode($item->uploaded_file, true);

            if (! $fileData || ! isset($fileData['path'])) {
                abort(404, 'File path not found');
            }

            if (! Storage::disk('public')->exists($fileData['path'])) {
                abort(404, 'File does not exist on storage');
            }

            // Return file for viewing in browser
            return response()->file(
                storage_path('app/public/'.$fileData['path'])
            );
        } catch (\Exception $e) {
            \Log::error('Error viewing file:', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
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

            if (! $item) {
                abort(404, 'File not found');
            }

            $fileData = json_decode($item->uploaded_file, true);

            if (! $fileData || ! isset($fileData['path'])) {
                abort(404, 'File path not found');
            }

            if (! Storage::disk('public')->exists($fileData['path'])) {
                abort(404, 'File does not exist on storage');
            }

            // Return file for download
            return response()->download(
                storage_path('app/public/'.$fileData['path']),
                $fileData['original_name'] ?? 'download'
            );
        } catch (\Exception $e) {
            \Log::error('Error downloading file:', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
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
                    'message' => 'Dependency removed successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to remove dependency',
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Error removing dependency:', [
                'dependency_id' => $dependencyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove dependency',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
