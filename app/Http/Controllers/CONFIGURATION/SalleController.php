<?php

namespace App\Http\Controllers\CONFIGURATION;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalleStoreRequest;
use App\Http\Requests\SalleUpdateRequest;
use App\Http\Resources\SalleResource;
use App\Models\Salle;
use App\Models\Specialization;
use App\Services\SalleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalleController extends Controller
{
    public function __construct(
        private SalleService $salleService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $salles = $this->salleService->getAll($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Salles retrieved successfully',
                'data' => SalleResource::collection($salles),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve salles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SalleStoreRequest $request): JsonResponse
    {
        try {
            $salle = $this->salleService->create($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Salle created successfully',
                'data' => new SalleResource($salle),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create salle',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Salle $salle): JsonResponse
    {
        try {
            $salle = $this->salleService->getById($salle->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Salle retrieved successfully',
                'data' => new SalleResource($salle),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve salle',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SalleUpdateRequest $request, Salle $salle): JsonResponse
    {
        try {
            $updatedSalle = $this->salleService->update($salle, $request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Salle updated successfully',
                'data' => new SalleResource($updatedSalle),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update salle',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Salle $salle): JsonResponse
    {
        try {
            $this->salleService->delete($salle);

            return response()->json([
                'status' => 'success',
                'message' => 'Salle deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete salle',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign specializations to a salle
     */
    public function assignSpecializations(Request $request, Salle $salle): JsonResponse
    {
        $request->validate([
            'specialization_ids' => 'required|array',
            'specialization_ids.*' => 'exists:specializations,id',
        ]);

        try {
            $updatedSalle = $this->salleService->assignSpecializations($salle, $request->specialization_ids);

            return response()->json([
                'status' => 'success',
                'message' => 'Specializations assigned successfully',
                'data' => new SalleResource($updatedSalle),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to assign specializations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove specializations from a salle
     */
    public function removeSpecializations(Request $request, Salle $salle): JsonResponse
    {
        $request->validate([
            'specialization_ids' => 'required|array',
            'specialization_ids.*' => 'exists:specializations,id',
        ]);

        try {
            $updatedSalle = $this->salleService->removeSpecializations($salle, $request->specialization_ids);

            return response()->json([
                'status' => 'success',
                'message' => 'Specializations removed successfully',
                'data' => new SalleResource($updatedSalle),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove specializations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available specializations for assignment
     */
    public function getAvailableSpecializations(): JsonResponse
    {
        try {
            $specializations = Specialization::all(['id', 'name']);

            return response()->json([
                'status' => 'success',
                'message' => 'Specializations retrieved successfully',
                'data' => $specializations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve specializations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
