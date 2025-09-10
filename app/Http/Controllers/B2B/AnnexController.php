<?php

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use App\Http\Requests\B2B\AnnexRequest;
use App\Http\Resources\B2B\AnnexResource;
use App\Models\B2B\Annex;
use App\Services\B2B\AnnexCreationService; // Import the new service
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// Removed direct DB import as transactions are now handled within the service

class AnnexController extends Controller
{
    protected $annexCreationService;

    /**
     * Constructor to inject the AnnexCreationService.
     *
     * @param AnnexCreationService $annexCreationService
     */
    public function __construct(AnnexCreationService $annexCreationService)
    {
        $this->annexCreationService = $annexCreationService;
    }

    /**
     * Get annexes for a specific contract.
     *
     * @param string $contractId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByContract(string $contractId)
    {
        try {
            $annexes = Annex::with(['service:id,name', 'creator:id,name'])
                ->where('convention_id', $contractId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => AnnexResource::collection($annexes)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch annexes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
public function checkRelations($itemToDelete)
{
    try {
        $annex = Annex::findOrFail($itemToDelete);
        
        $hasPrestationPricing = $annex->prestationPrices()->exists();
        
        return response()->json([
            'success' => true,
            'hasPrestationPricing' => $hasPrestationPricing
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error checking annex relations',
            'error' => $e->getMessage()
        ], 500);
    }
}
    /**
     * Store a newly created annex with contractId from route.
     * This method is used when creating an annex directly linked to a contract ID in the URL.
     *
     * @param AnnexRequest $request
     * @param string $contractId
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeWithContract(AnnexRequest $request, string $contractId)
    {
        try {
            $validatedData = $request->validated();
            // Delegate the creation and initialization logic to the service
            $annex = $this->annexCreationService->createAnnexAndInitializePrestations($validatedData, $contractId);

            // Load necessary relationships for the response
            $annex->load('service:id,name', 'convention:status');

            return response()->json([
                'success' => true,
                'data' => new AnnexResource($annex),
                'message' => 'Annex created successfully and prestations initialized'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating annex or initializing prestations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $annexes = Annex::with(['service:id,name', 'creator:id,name', 'convention:status'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => AnnexResource::collection($annexes)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch annexes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * This method is used when creating an annex without a contract ID in the URL (e.g., from a general annex creation form).
     *
     * @param AnnexRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(AnnexRequest $request)
    {
        try {
            $validatedData = $request->validated();
            // Delegate the creation and initialization logic to the service
            // The 'convention_id' is expected to be present in $validatedData in this case.
            $annex = $this->annexCreationService->createAnnexAndInitializePrestations($validatedData);

            // Load necessary relationships for the response
            $annex->load('service:id,name', 'creator:id,name', 'convention:status');

            return response()->json([
                'success' => true,
                'data' => new AnnexResource($annex),
                'message' => 'Annex created successfully and prestations initialized'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating annex or initializing prestations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        try {
            $annex = Annex::with(['service:id,name', 'creator:id,name', 'convention.conventionDetail'])
                ->find($id);

            if (!$annex) {
                return response()->json([
                    'success' => false,
                    'message' => 'Annex not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new AnnexResource($annex)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve annex',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param AnnexRequest $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(AnnexRequest $request, string $id)
    {
        try {
            $annex = Annex::find($id);

            if (!$annex) {
                return response()->json([
                    'success' => false,
                    'message' => 'Annex not found'
                ], 404);
            }

            $validatedData = $request->validated();

            $annex->update([
                'annex_name' => $validatedData['annex_name'],
                'service_id' => $validatedData['service_id'],
                'description' => $validatedData['description'] ?? $annex->description,
                'is_active' => $validatedData['is_active'] ?? $annex->is_active,
                'min_price' => $validatedData['min_price'] ?? $annex->min_price,
                'prestation_prix_status' => $validatedData['prestation_prix_status'],
                'updated_by' => Auth::id(),
            ]);

            $annex->load('service:id,name', 'creator:id,name', 'convention:status');

            return response()->json([
                'success' => true,
                'data' => new AnnexResource($annex),
                'message' => 'Annex updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating annex',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
   public function destroy(string $id)
{
    try {
        $annex = Annex::with('prestationPrices')->find($id);

        if (!$annex) {
            return response()->json([
                'success' => false,
                'message' => 'Annex not found'
            ], 404);
        }

        // Use a database transaction to ensure data integrity
        \DB::transaction(function () use ($annex) {
            // Delete related prestation pricing records first
            $annex->prestationPrices()->delete();
            
            // Then delete the annex
            $annex->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Annex deleted successfully'
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error deleting annex',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
