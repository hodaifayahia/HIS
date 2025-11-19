<?php

namespace App\Http\Controllers\MANAGER;

use App\Http\Controllers\Controller;
use App\Http\Requests\MANAGER\PatientTrackingCheckInRequest;
use App\Http\Requests\MANAGER\PatientTrackingCheckOutRequest;
use App\Http\Resources\MANAGER\PatientTrackingResource;
use App\Services\MANAGER\PatientTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientTrackingController extends Controller
{
    protected PatientTrackingService $service;

    public function __construct(PatientTrackingService $service)
    {
        $this->service = $service;
    }

    /**
     * Check in a patient to a salle
     */
    public function checkIn(PatientTrackingCheckInRequest $request): JsonResponse
    {
        try {
            $tracking = $this->service->checkIn($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Patient checked in successfully',
                'data' => new PatientTrackingResource($tracking),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check in patient',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check out a patient from a salle
     */
    public function checkOut(int $trackingId, PatientTrackingCheckOutRequest $request): JsonResponse
    {
        try {
            $tracking = $this->service->checkOut($trackingId, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Patient checked out successfully',
                'data' => new PatientTrackingResource($tracking),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check out patient',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all current patient positions
     */
    public function getCurrentPositions(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['salle_id', 'specialization_id', 'status']);
            $positions = $this->service->getCurrentPositions($filters);

            return response()->json([
                'success' => true,
                'data' => PatientTrackingResource::collection($positions),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve patient positions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get patient tracking history
     */
    public function getHistory(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['patient_id', 'start_date', 'end_date', 'salle_id']);
            $history = $this->service->getHistory($filters);

            return response()->json([
                'success' => true,
                'data' => PatientTrackingResource::collection($history),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tracking history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available salles for a specialization
     */
    public function getAvailableSalles(int $specializationId): JsonResponse
    {
        try {
            $salles = $this->service->getAvailableSalles($specializationId);

            return response()->json([
                'success' => true,
                'data' => $salles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve available salles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get salle occupancy statistics
     */
    public function getSalleOccupancy(): JsonResponse
    {
        try {
            $occupancy = $this->service->getSalleOccupancy();

            return response()->json([
                'success' => true,
                'data' => $occupancy,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve salle occupancy',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single tracking by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $tracking = $this->service->getById($id);

            if (! $tracking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tracking not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new PatientTrackingResource($tracking),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tracking',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
