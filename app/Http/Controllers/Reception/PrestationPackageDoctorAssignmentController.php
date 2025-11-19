<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\StorePrestationDoctorAssignmentsRequest;
use App\Models\CONFIGURATION\PrestationPackage;
use App\Models\CONFIGURATION\PrestationPackageReception;
use App\Models\Reception\ficheNavetteItem;
use App\Services\Reception\ReceptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for managing doctor assignments in prestation packages
 * Handles storing and retrieving doctor-prestation mappings for packages
 */
class PrestationPackageDoctorAssignmentController extends Controller
{
    protected $receptionService;

    public function __construct(ReceptionService $receptionService)
    {
        $this->receptionService = $receptionService;
    }

    /**
     * Get all prestations in a package with their assigned doctors
     * 
     * GET /api/reception/packages/{packageId}/prestations-with-doctors
     */
    public function getPrestationsWithDoctors($packageId): JsonResponse
    {
        try {
            // Verify package exists
            $package = PrestationPackage::findOrFail($packageId);

            // Get prestations with doctors
            $prestations = $this->receptionService->getPrestationsWithDoctorsInPackage($packageId);

            return response()->json([
                'success' => true,
                'package' => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'price' => $package->price,
                ],
                'prestations' => $prestations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch prestations with doctors',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store doctor assignments for all prestations in a package
     * 
     * POST /api/reception/packages/{packageId}/doctor-assignments
     * 
     * Body:
     * {
     *   "prestations": [
     *     {"prestation_id": 1, "doctor_id": 2},
     *     {"prestation_id": 2, "doctor_id": 3},
     *     {"prestation_id": 3, "doctor_id": null}
     *   ]
     * }
     */
    public function storeDoctorAssignments(Request $request, $packageId): JsonResponse
    {
        try {
            // Verify package exists
            $package = PrestationPackage::findOrFail($packageId);

            // Validate input
            $validated = $request->validate([
                'prestations' => 'required|array|min:1',
                'prestations.*.prestation_id' => 'required|integer|exists:prestations,id',
                'prestations.*.doctor_id' => 'nullable|integer|exists:doctors,id',
            ]);

            // Store doctor assignments
            $this->receptionService->storePrestationDoctorsInPackage(
                $packageId,
                $validated['prestations']
            );

            // Fetch and return updated data
            $prestations = $this->receptionService->getPrestationsWithDoctorsInPackage($packageId);

            return response()->json([
                'success' => true,
                'message' => 'Doctor assignments stored successfully',
                'count' => count($validated['prestations']),
                'prestations' => $prestations,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to store doctor assignments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update doctor assignment for a single prestation in a package
     * 
     * PUT /api/reception/packages/{packageId}/prestations/{prestationId}/doctor
     * 
     * Body:
     * {
     *   "doctor_id": 2  // or null to remove assignment
     * }
     */
    public function updateDoctorAssignment(Request $request, $packageId, $prestationId): JsonResponse
    {
        try {
            // Verify package and prestation exist
            $package = PrestationPackage::findOrFail($packageId);
            
            // Verify prestation is in package
            $exists = PrestationPackageReception::where('package_id', $packageId)
                ->where('prestation_id', $prestationId)
                ->exists();

            if (!$exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prestation not found in package',
                ], 404);
            }

            // Validate input
            $validated = $request->validate([
                'doctor_id' => 'nullable|integer|exists:doctors,id',
            ]);

            // Update doctor assignment
            $this->receptionService->updatePrestationDoctorInPackage(
                $packageId,
                $prestationId,
                $validated['doctor_id'] ?? null
            );

            // Fetch updated record
            $record = PrestationPackageReception::where('package_id', $packageId)
                ->where('prestation_id', $prestationId)
                ->with(['prestation', 'doctor'])
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Doctor assignment updated successfully',
                'data' => [
                    'prestation_id' => $record->prestation_id,
                    'prestation_name' => $record->prestation->name,
                    'doctor_id' => $record->doctor_id,
                    'doctor_name' => $record->doctor?->name ?? null,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update doctor assignment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove doctor assignment for a prestation
     * 
     * DELETE /api/reception/packages/{packageId}/prestations/{prestationId}/doctor
     */
    public function removeDoctorAssignment($packageId, $prestationId): JsonResponse
    {
        try {
            // Verify package exists
            PrestationPackage::findOrFail($packageId);

            // Remove doctor assignment (set to null)
            $this->receptionService->updatePrestationDoctorInPackage(
                $packageId,
                $prestationId,
                null
            );

            return response()->json([
                'success' => true,
                'message' => 'Doctor assignment removed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove doctor assignment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get doctor assignments for a fiche navette item (if it's a package)
     * 
     * GET /api/reception/fiche-navette-items/{itemId}/package-doctors
     */
    public function getPackageItemDoctors($itemId): JsonResponse
    {
        try {
            // Get the fiche navette item
            $item = ficheNavetteItem::findOrFail($itemId);

            // Check if it's a package
            if (!$item->isPackage()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This item is not a package',
                ], 422);
            }

            // Get prestations with doctors
            $prestations = $this->receptionService->getPrestationsWithDoctorsInPackage($item->package_id);

            return response()->json([
                'success' => true,
                'item' => [
                    'id' => $item->id,
                    'package_id' => $item->package_id,
                    'package_name' => $item->package->name,
                    'final_price' => $item->final_price,
                ],
                'prestations' => $prestations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch package doctors',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update doctor assignments for a package
     * Replaces all existing assignments with new ones
     * 
     * PATCH /api/reception/packages/{packageId}/doctor-assignments/bulk
     */
    public function bulkUpdateDoctorAssignments(Request $request, $packageId): JsonResponse
    {
        try {
            // Verify package exists
            $package = PrestationPackage::findOrFail($packageId);

            // Validate input
            $validated = $request->validate([
                'prestations' => 'required|array|min:1',
                'prestations.*.prestation_id' => 'required|integer|exists:prestations,id',
                'prestations.*.doctor_id' => 'nullable|integer|exists:doctors,id',
            ]);

            // Store doctor assignments (this will clear old ones)
            $this->receptionService->storePrestationDoctorsInPackage(
                $packageId,
                $validated['prestations']
            );

            // Fetch and return updated data
            $prestations = $this->receptionService->getPrestationsWithDoctorsInPackage($packageId);

            return response()->json([
                'success' => true,
                'message' => 'Doctor assignments updated successfully',
                'count' => count($validated['prestations']),
                'prestations' => $prestations,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk update doctor assignments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
