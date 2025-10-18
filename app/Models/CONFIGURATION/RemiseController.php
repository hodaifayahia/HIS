<?php

namespace App\Http\Controllers\CONFIGURATION;

use App\Http\Controllers\Controller;
use App\Models\CONFIGURATION\Remise;
use App\Services\CONFIGURATION\RemiseService;
use App\Models\Reception\ItemDependency;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Reception\ficheNavette;

use App\Http\Requests\CONFIGURATION\RemiseRequest;
use Illuminate\Http\Request;
use App\Http\Resources\CONFIGURATION\RemiseResource;
use Illuminate\Http\JsonResponse;
//DB
use DB;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RemiseController extends Controller
{
    protected $remiseService;

    public function __construct(RemiseService $remiseService)
    {
        $this->remiseService = $remiseService;
    }

    /**
     * Display a listing of the resource.
     */
   public function index(Request $request): JsonResponse
    {
        try {
            // Get all request parameters, including 'search', 'page', 'size'
            $params = $request->all();

            // Pass the parameters to the service method
            $remises = $this->remiseService->getAllRemises($params);

            // If using pagination, the resource collection will automatically handle it
            if ($remises instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
                return response()->json([
                    'success' => true,
                    'data' => RemiseResource::collection($remises),
                    'meta' => [ // Include pagination meta data
                        'current_page' => $remises->currentPage(),
                        'from' => $remises->firstItem(),
                        'last_page' => $remises->lastPage(),
                        'per_page' => $remises->perPage(),
                        'to' => $remises->lastItem(),
                        'total' => $remises->total(),
                    ],
                    'message' => 'Remises retrieved successfully'
                ], 200);
            }

            // For non-paginated results (e.g., if 'all' param is always true)
            return response()->json([
                'success' => true,
                'data' => RemiseResource::collection($remises),
                'message' => 'Remises retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error retrieving remises: ' . $e->getMessage(), ['exception' => $e]); // Log the error
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving remises: ' . $e->getMessage()
            ], 500);
        }
    }

    public function userRemise(Request $request)
    {
        try {
            $userId = $request->input('user_id');
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID is required'
                ], 400);
            }
            
            $remises = $this->remiseService->getUserRemises($userId);
            // Remove the dd() statement that was causing the hang
            
            return response()->json([
                'success' => true,
                'data' => RemiseResource::collection($remises),
                'message' => 'User remises retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error retrieving user remises: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving user remises: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RemiseRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $remise = $this->remiseService->createRemise($validatedData);

            return response()->json([
                'success' => true,
                'data' => new RemiseResource($remise),
                'message' => 'Remise created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating remise: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $remise = $this->remiseService->getRemiseById($id);

            if (!$remise) {
                return response()->json([
                    'success' => false,
                    'message' => 'Remise not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new RemiseResource($remise),
                'message' => 'Remise retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving remise: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RemiseRequest $request, Remise $remise): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $updatedRemise = $this->remiseService->updateRemise($remise, $validatedData);

            return response()->json([
                'success' => true,
                'data' => new RemiseResource($updatedRemise),
                'message' => 'Remise updated successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating remise: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Remise $remise)
    {
        try {
            $deleted = $this->remiseService->deleteRemise($remise);

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Remise deleted successfully'
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete remise'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting remise: ' . $e->getMessage()
            ], 500);
        }
    }

    public function applyRemise(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'remise_id' => 'nullable|integer',
                'is_custom' => 'boolean',
                'custom_user_balance' => 'nullable|array',
                'custom_doctor_balance' => 'nullable|array',
                'prestations' => 'nullable|array',
                'affected_items' => 'required|array'
            ]);

            $affectedItems = $validatedData['affected_items'] ?? [];

            DB::beginTransaction();

            $updatedFicheIds = [];

            foreach ($affectedItems as $itemData) {
                $ficheItemId = $itemData['fiche_item_id'] ?? null;
                $discountedPrice = isset($itemData['discounted_price']) ? (float) $itemData['discounted_price'] : null;

                if (!$ficheItemId) {
                    continue;
                }

                // If this id is an ItemDependency
                $dependency = ItemDependency::find($ficheItemId);
                if ($dependency) {
                    if (!is_null($discountedPrice)) {
                        $dependency->final_price = $discountedPrice;
                        $dependency->save();
                    }

                    // collect fiche_navette id from parent fiche item if available
                    $parentItem = ficheNavetteItem::find($dependency->parent_item_id);
                    if ($parentItem) {
                        $updatedFicheIds[] = $parentItem->fiche_navette_id;
                    }

                    continue;
                }

                // Otherwise try to update fiche navette item
                $ficheItem = ficheNavetteItem::find($ficheItemId);
                if ($ficheItem) {
                    if (!is_null($discountedPrice)) {
                        $ficheItem->final_price = $discountedPrice;
                        $ficheItem->save();
                    }
                    $updatedFicheIds[] = $ficheItem->fiche_navette_id;
                }
            }

            // Recalculate totals for affected fiches
            $updatedFicheIds = array_unique($updatedFicheIds);
            foreach ($updatedFicheIds as $ficheId) {
                // sum fiche items final_price
                $items = ficheNavetteItem::where('fiche_navette_id', $ficheId)->get();
                $itemIds = $items->pluck('id')->toArray();
                $sumItems = $items->sum(function ($i) {
                    return (float) ($i->final_price ?? 0);
                });

                if (!empty($itemIds)) {
                    $sumDeps = ItemDependency::whereIn('parent_item_id', $itemIds)
                        ->sum(DB::raw('COALESCE(final_price,0)'));
                }

            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Remise applied successfully',
                'data' => [
                    'remise_id' => $validatedData['remise_id'] ?? null,
                    'affected_items' => $affectedItems,
                    'updated_fiche_ids' => $updatedFicheIds
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error applying remise: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Error applying remise: ' . $e->getMessage()
            ], 500);
        }
    }
}
