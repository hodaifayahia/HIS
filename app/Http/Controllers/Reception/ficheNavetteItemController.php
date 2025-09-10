<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Resources\Reception\ficheNavetteResource;
use App\Http\Resources\Reception\ficheNavetteItemResource;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Reception\ItemDependency;
use App\Models\Reception\ficheNavette;
use App\Services\Reception\ReceptionService;
use App\Models\CONFIGURATION\PrestationPackageitem;

use App\Services\Reception\ConventionPricingService;
use App\Services\Reception\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response; // Add this import
use Illuminate\Support\Facades\Storage; // Add this import
use App\Http\Resources\Reception\ConventionOrganismeResource;

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
    public function store(Request $request, $ficheNavetteId): JsonResponse
    {
        if (!$ficheNavetteId) {
            return response()->json([
                'success' => false,
                'message' => 'Fiche Navette ID is required',
                'error' => 'Missing ficheNavetteId parameter'
            ], 400);
        }

        $validatedData = $request->validate([
            'type' => 'required|string|in:prestation,custom',
            'prestations' => 'sometimes|array',
            'prestations.*.id' => 'required|exists:prestations,id',
            'prestations.*.doctor_id' => 'nullable|exists:users,id',
            'prestations.*.quantity' => 'nullable|integer|min:1',
            'prestations.*.isPackage' => 'nullable|boolean',
            'prestations.*.convention_id' => 'nullable|exists:conventions,id',
            'prestations.*.convention_price' => 'nullable|numeric',
            'prestations.*.uses_convention' => 'nullable|boolean',
            'dependencies' => 'sometimes|array',
            'dependencies.*.id' => 'required|exists:prestations,id',
            'dependencies.*.doctor_id' => 'nullable|exists:users,id',
            'dependencies.*.convention_id' => 'nullable|exists:conventions,id',
            'dependencies.*.convention_price' => 'nullable|numeric',
            'dependencies.*.uses_convention' => 'nullable|boolean',
            'customPrestations' => 'sometimes|array',
            'customPrestations.*.id' => 'nullable|exists:prestations,id',
            'customPrestations.*.doctor_id' => 'nullable|exists:users,id',
            'customPrestations.*.selected_doctor_id' => 'nullable|exists:users,id',
            'customPrestations.*.quantity' => 'nullable|integer|min:1',
            'customPrestations.*.display_name' => 'nullable|string',
            'customPrestations.*.type' => 'nullable|string|in:predefined,custom',
            'customPrestations.*.convention_id' => 'nullable|exists:conventions,id',
            'customPrestations.*.convention_price' => 'nullable|numeric',
            'customPrestations.*.uses_convention' => 'nullable|boolean',
            'selectedSpecialization' => 'nullable|exists:specializations,id',
            'selectedDoctor' => 'nullable|exists:users,id',
            'selectedConventions' => 'sometimes|array',
            'selectedConventions.*' => 'exists:conventions,id',
            'selectedOrganismes' => 'sometimes|array',
            'selectedOrganismes.*' => 'exists:companies,id',
            'enableConventionMode' => 'nullable|boolean',
            'prise_en_charge_date' => 'nullable|date',
            'familyAuth' => 'nullable|array',
            'familyAuth.*' => 'string|in:ascendant,descendant,Conjoint,adherent,autre',
            'notes' => 'nullable|string',
           'packages.*.id' => 'required|exists:prestation_packages,id',
            'packages.*.prestations' => 'sometimes|array',
            'packages.*.prestations.*.id' => 'required|exists:prestations,id',
            // File upload validation
            'uploadedFiles' => 'sometimes|array',
            'uploadedFiles.*.name' => 'sometimes|string',
            'uploadedFiles.*.size' => 'sometimes|integer',
            'uploadedFiles.*.type' => 'sometimes|string',
            'uploadedFiles.*.file' => 'sometimes|file|mimes:pdf,doc,docx|max:10240',
        ]);

        try {
            $ficheNavette = ficheNavette::findOrFail($ficheNavetteId);
            $updatedFiche = $this->receptionService->addItemsToFicheNavette($ficheNavette, $validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Items added to Fiche Navette successfully',
                'data' => new ficheNavetteResource($updatedFiche->load([
                    'items.prestation', 
                    'items.dependencies.dependencyPrestation',
                    'items.convention',
                    'patient', 
                    'creator'
                ]))
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
public function storeConventionPrescription(Request $request, $ficheNavetteId): JsonResponse
{
    $validatedData = $request->validate([
        'conventions' => 'required|string', // JSON string
        'prise_en_charge_date' => 'required|date',
        'familyAuth' => 'required|string',
        'adherentPatient_id' => 'nullable|exists:patients,id',
        'uploadedFiles' => 'sometimes|array',
        'uploadedFiles.*' => 'file|mimes:pdf,doc,docx|max:10240',
    ]);

    try {
        // Decode the JSON string
        $validatedData['conventions'] = json_decode($validatedData['conventions'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid conventions data format');
        }

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
    public function uploadConventionFiles(Request $request): JsonResponse
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|mimes:pdf,doc,docx|max:10240', // 10MB max
        ]);

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
 public function getPrestationsByPackage($packageId) {
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
    public function downloadFile($fileId): Response
    {
        dd($fileId);
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
public function getPatientConventions(Request $request, $patientId): JsonResponse
{
    try {
        // Get convention IDs and prestation IDs from fiche navette items
        $ficheNavetteItems = ficheNavetteItem::where('fiche_navette_id', $request->fiche_navette_id)
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
                'organism_color' => $organisme->organism_color ?? null, // CHANGE: Use organism_color instead of organisme_color
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
                        ->filter() // Remove null/empty files
                        ->unique('id') // Remove duplicates by file id
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
// In the index method, make sure dependencies are loaded with proper parent_item_id
public function index(Request $request, $ficheNavetteId): JsonResponse
{
    try {
        $ficheNavette = ficheNavette::with([
            'items.prestation.service', 
            'items.prestation.specialization',
            'items.package.items.prestation.service',
            'items.package.items.prestation.specialization',
            'items.dependencies.dependencyPrestation.service', // FIXED: Load dependencies with full prestation data
            'items.dependencies.dependencyPrestation.specialization',
            'items.convention.organisme',
            'items.doctor',
            'items.insuredPatient',
            'patient',
            'creator'
        ])->findOrFail($ficheNavetteId);

        // FIXED: Ensure each item has its own dependencies properly linked
        $ficheNavette->items->each(function($item) {
            if ($item->dependencies) {
                $item->dependencies->each(function($dependency) use ($item) {
                    // Ensure the dependency knows its parent
                    $dependency->parent_item_id = $item->id;
                });
            }
        });

        return response()->json([
            'success' => true,
            'data' => ficheNavetteItemResource::collection($ficheNavette->items),
            'meta' => [
                'fiche_navette_id' => $ficheNavetteId,
                'total_items' => $ficheNavette->items->count(),
                'total_amount' => $ficheNavette->items->sum('final_price'),
                'patient_name' => $ficheNavette->patient_name ?? 'Unknown',
                'fiche_date' => $ficheNavette->fiche_date
            ]
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
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Update a specific item in a fiche navette
     */
    public function update(Request $request, $ficheNavetteId, $itemId): JsonResponse
    {
        $validatedData = $request->validate([
            'doctor_id' => 'nullable|exists:users,id',
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled,required,dependency',
            'custom_name' => 'nullable|string',
            'convention_id' => 'nullable|exists:conventions,id',
            'family_authorization' => 'nullable|array',
            'family_authorization.*' => 'string|in:ascendant,descendant,Conjoint,adherent,autre',
            'notes' => 'nullable|string'
        ]);

        try {
            $updatedFiche = $this->receptionService->updateFicheNavetteItem($ficheNavetteId, $itemId, $validatedData);

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
    public function getPrestationsWithConventionPricing(Request $request): JsonResponse
    {
        try {
            $conventionIds = $request->get('convention_ids');
            $priseEnChargeDate = $request->get('prise_en_charge_date');
            // dd($conventionIds, $priseEnChargeDate);
            
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
    public function getPrestationsByConvention(Request $request, $conventionId): JsonResponse
    {
        try {
            $priseEnChargeDate = $request->get('prise_en_charge_date');
            
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
    public function getGroupedByInsured(Request $request, $ficheNavetteId): JsonResponse
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
}