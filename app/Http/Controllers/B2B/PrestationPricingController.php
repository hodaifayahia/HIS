<?php

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use App\Services\B2B\PrestationPricingService;
use App\Http\Requests\B2B\PrestationPricingRequest;
use App\Http\Resources\B2B\PrestationPricingResource;
use App\Http\Resources\PrestationResource;
use App\Http\Resources\CONFIGURATION\ServiceResource;
use App\Models\B2B\PrestationPricing;
use App\Models\B2B\Convention;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException; // Import ModelNotFoundException

class PrestationPricingController extends Controller
{
    protected $prestationPricingService;

    public function __construct(PrestationPricingService $prestationPricingService)
    {
        $this->prestationPricingService = $prestationPricingService;
    }

    /**
     * Display a listing of prestation pricing entries for a specific avenant.
     * GET /api/prestation-pricings/avenant/{avenantId}
     *
     * @param string $avenantId
     * @return JsonResponse
     */
    public function getPrestationsByAvenantId(string $avenantId): JsonResponse
    {
        try {
            // Eager load prestation.service for table display if needed
            $pricings = PrestationPricing::with('prestation.service')
                ->where('avenant_id', $avenantId)
                ->get();

            return response()->json([
                'success' => true,
                'data' => PrestationPricingResource::collection($pricings),
                'message' => 'Prestation pricings fetched successfully.'
            ]);
        } catch (\Exception $e) {
            \Log::error("Error fetching prestations for avenant ID {$avenantId}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch prestation pricings.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
     public function index(Request $request): JsonResponse
    {
        $request->validate([
            'annex_id' => 'required|exists:annexes,id',
        ]);

        try {
            $pricings = $this->prestationPricingService->getPrestationPricingsByAnnexId($request->annex_id);

            // Use the PrestationPricingResource to transform the collection
            return response()->json([
                'success' => true,
                'data' => PrestationPricingResource::collection($pricings),
                'message' => 'Prestation pricings fetched successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch prestation pricings.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created prestation pricing entry in storage.
     * POST /api/prestation-pricings
     *
     * @param PrestationPricingRequest $request
     * @return JsonResponse
     */
    public function store(PrestationPricingRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $newPricing = null;
            $message = '';

            if (isset($validatedData['avenant_id'])) {
                $newPricing = $this->prestationPricingService->createPrestationPricing($validatedData);
                $message = 'Prestation pricing created successfully for avenant.';
            } elseif (isset($validatedData['annex_id'])) {
                $newPricing = $this->prestationPricingService->createPrestationPricingForAnnex($validatedData);
                $message = 'Prestation pricing created successfully for annex.';
            } else {
                // This scenario should ideally be prevented by PrestationPricingRequest rules,
                // but as a fallback, explicitly state if neither ID is present.
                return response()->json([
                    'success' => false,
                    'message' => 'Neither avenant_id nor annex_id provided for creation.',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => new PrestationPricingResource($newPricing),
                'message' => $message
            ], 201);

        } catch (ModelNotFoundException $e) {
            \Log::error("Resource not found during creation: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Associated resource (Avenant/Annex/Prestation) not found.',
                'error' => $e->getMessage()
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error("Error creating prestation pricing: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create prestation pricing.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
      public function bulkDelete(Request $request)
{
    $request->validate([
        'ids' => 'required|array',
        'ids.*' => 'exists:prestation_pricing,id', // Ensures all IDs exist
    ]);

    try {
        PrestationPricing::whereIn('id', $request->input('ids'))->delete();
        return response()->json(['message' => 'Prestations deleted successfully.'], 200);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Failed to delete prestations.', 'error' => $e->getMessage()], 500);
    }
}

    /**
     * Update the specified prestation pricing entry.
     * It intelligently determines if it's for an Avenant or an Annex.
     * PUT/PATCH /api/prestation-pricings/{id}
     *
     * @param PrestationPricingRequest $request
     * @param string $id The ID of the PrestationPricing record.
     * @return JsonResponse
     */
    public function update(PrestationPricingRequest $request, string $id): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $updatedPricing = null;
            $message = '';

            // Retrieve the existing PrestationPricing model to determine its type
            $prestationPricing = PrestationPricing::findOrFail($id);

            if ($prestationPricing->avenant_id) {
                $updatedPricing = $this->prestationPricingService->updatePrestationPricing($id, $validatedData);
                $message = 'Prestation pricing updated successfully for avenant.';
            } elseif ($prestationPricing->annex_id) {
                $updatedPricing = $this->prestationPricingService->updatePrestationPricingForAnnex($id, $validatedData);
                $message = 'Prestation pricing updated successfully for annex.';
            } else {
                // This case should ideally not happen if every PrestationPricing has either an avenant_id or annex_id
                return response()->json([
                    'success' => false,
                    'message' => 'Prestation pricing record is not linked to an Avenant or an Annex.',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => new PrestationPricingResource($updatedPricing),
                'message' => $message
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Prestation pricing not found.',
                'error' => $e->getMessage()
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error("Error updating prestation pricing: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update prestation pricing.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified prestation pricing entry from storage.
     * DELETE /api/prestation-pricings/{id}
     *
     * @param string $id The ID of the PrestationPricing record.
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $deleted = $this->prestationPricingService->deletePrestationPricing($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prestation pricing not found or could not be deleted.',
                    
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Prestation pricing deleted successfully.'
            ]);
        } catch (\Exception $e) {
            \Log::error("Error deleting prestation pricing: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete prestation pricing.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all available services.
     * GET /api/services/all
     *
     * @return JsonResponse
     */
    public function getAllServices(): JsonResponse
    {
        try {
            $services = $this->prestationPricingService->getAllServices();
            return response()->json([
                'success' => true,
                'data' => ServiceResource::collection($services),
                'message' => 'All services fetched successfully.'
            ]);
        } catch (\Exception $e) {
            \Log::error("Error fetching all services: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch services.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get prestations for a specific service ID that are not yet priced for a specific avenant.
     * GET /api/prestations/available-for-service-avenant/{serviceId}/{avenantId}
     *
     * @param string $serviceId
     * @param string $avenantId
     * @return JsonResponse
     */
    public function getAvailablePrestationsForServiceAndAvenant(string $serviceId, string $avenantId): JsonResponse
    {
        try {
            $prestations = $this->prestationPricingService->getAvailablePrestationsForServiceAndAvenant($serviceId, $avenantId);
            return response()->json([
                'success' => true,
                'data' => PrestationResource::collection($prestations),
                'message' => 'Available prestations for service and avenant fetched successfully.'
            ]);
        } catch (\Exception $e) {
            \Log::error("Error fetching available prestations for service {$serviceId} and avenant {$avenantId}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available prestations.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
       public function getAvailablePrestationsForServiceAndAnnex(string $serviceId, string $annexId): JsonResponse
    {
        try {
            $prestations = $this->prestationPricingService->getAvailablePrestationsForServiceAndAnnex($serviceId, $annexId);

            return response()->json([
                'success' => true,
                'data' => PrestationResource::collection($prestations),
                'message' => 'Available prestations for service and annex fetched successfully.'
            ]);
        } catch (\Exception $e) {
            \Log::error("Error fetching available prestations for service {$serviceId} and annex {$annexId}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available prestations.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
       public function getallAvailablePrestationsForServiceAndAnnex(string $serviceId, string $annexId): JsonResponse
    {
        try {
            $prestations = $this->prestationPricingService->getallAvailablePrestationsForServiceAndAnnex($serviceId, $annexId);

            return response()->json([
                'success' => true,
                'data' => PrestationResource::collection($prestations),
                'message' => 'Available prestations for service and annex fetched successfully.'
            ]);
        } catch (\Exception $e) {
            \Log::error("Error fetching available prestations for service {$serviceId} and annex {$annexId}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available prestations.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get prestations by convention ID
     * This is a simplified version that only looks at prestation_pricing table
     */
    public function getPrestationsByConvention($conventionId): JsonResponse
    {
        try {
            $prestations = PrestationPricing::with(['prestation.specialization'])
                ->where('convention_id', $conventionId)
                ->get()
                ->map(function ($pricing) use ($conventionId) {
                    return [
                        'prestation_id' => $pricing->prestation->id,
                        'prestation_name' => $pricing->prestation->name,
                        'prestation_code' => $pricing->prestation->internal_code,
                        'specialization_id' => $pricing->prestation->specialization_id,
                        'specialization_name' => $pricing->prestation->specialization->name ?? null,
                        'standard_price' => $pricing->prestation->public_price,
                        'convention_price' => $pricing->prix_patient + $pricing->prix_company,
                        'patient_price' => $pricing->prix_patient,
                        'company_price' => $pricing->prix_company,
                        'convention_id' => $conventionId,
                        'pricing_id' => $pricing->id,
                        'pricing_source' => 'prestation_pricing',
                        'prestation' => $pricing->prestation
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $prestations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch prestations by convention',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}