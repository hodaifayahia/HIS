<?php

namespace App\Http\Controllers\B2B; // Corrected namespace

use App\Http\Controllers\Controller; // Ensure this is correct for your project structure
use App\Services\B2B\AvenantService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\B2B\AvenantResource; // Corrected import for B2B namespace
use App\Models\B2B\Avenant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon; // Import Carbon for date handling
use Illuminate\Support\Facades\Auth; // For getting authenticated user ID

class AvenantController extends Controller
{
    protected AvenantService $avenantService;

    public function __construct(AvenantService $avenantService)
    {
        $this->avenantService = $avenantService;
    }

    /**
     * Create a new avenant and duplicate prestations based on convention's existing avenants.
     *
     * @param Request $request
     * @param int $conventionId
     * @return JsonResponse
     */
    public function createAvenantAndDuplicatePrestations(Request $request, int $conventionId): JsonResponse
    {
        try {
            // Get the authenticated user's ID for creator_id
            $creatorId = Auth::id(); // Assuming user is authenticated

            // Check if the convention already has any avenants
            $hasExistingAvenants = Avenant::where('convention_id', $conventionId)->exists();

            $result = $hasExistingAvenants
                ? $this->avenantService->duplicateAllPrestationsWithExistingAvenant($conventionId, $creatorId)
                : $this->avenantService->duplicateAllPrestationsWithNewAvenant($conventionId, $creatorId);

            return response()->json([
                'message' => 'Avenant created and prestations duplicated successfully',
                'data' => $result,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create avenant and duplicate prestations',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activate an avenant.
     *
     * @param Request $request
     * @param int $avenantId
     * @return JsonResponse
     */
    public function activateAvenant(Request $request, int $avenantId): JsonResponse
    {
        try {
            $request->validate([
                'activationDate' => 'nullable|date_format:Y-m-d',
            ]);

            $activationDate = $request->input('activationDate') ?? Carbon::now()->format('Y-m-d');
            $isDelayedActivation = $request->query('activate_later') === 'yes';
            $approverId = Auth::id(); // User who is activating/approving

            $result = $this->avenantService->activateAvenantById($avenantId, $activationDate, $isDelayedActivation, $approverId);

            $message = $isDelayedActivation
                ? 'Avenant scheduled for activation successfully'
                : 'Avenant activated successfully';

            return response()->json([
                'message' => $message,
                'data' => $result,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Avenant not found',
                'details' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to activate avenant',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get an avenant by ID.
     *
     * @param int $avenantId
     * @return JsonResponse
     */
    public function getAvenantById(int $avenantId): JsonResponse
    {
        try {
            $avenant = $this->avenantService->getAvenantById($avenantId);

            if (!$avenant) {
                return response()->json(['message' => 'Avenant not found'], 404);
            }
            // Use AvenantResource to format the output
            return response()->json(new AvenantResource($avenant));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if a convention has a pending avenant.
     *
     * @param int $conventionId
     * @return JsonResponse
     */
    public function checkPendingAvenantByConventionId(int $conventionId): JsonResponse
    {
        try {
            $pendingAvenant = $this->avenantService->findPendingAvenantByConventionId($conventionId);
            return response()->json(['hasPending' => !is_null($pendingAvenant)]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all avenants for a given convention ID.
     *
     * @param int $conventionId
     * @return JsonResponse
     */
    public function getAvenantsByConventionId(int $conventionId): JsonResponse
    {
        try {
            $avenants = $this->avenantService->getAvenantsByConventionId($conventionId);
            // Use AvenantResource::collection to format the output
            return response()->json(AvenantResource::collection($avenants));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}