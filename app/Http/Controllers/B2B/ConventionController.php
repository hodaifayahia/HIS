<?php

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use App\Http\Requests\B2B\StoreConventionRequest;
use App\Http\Requests\B2B\UpdateConventionRequest;
use App\Models\B2B\Convention;
use App\Services\B2B\ConventionService;
use App\Http\Resources\B2B\ConventionResource;
use App\Http\Resources\B2B\PrestationPricingResource;

use App\Models\B2B\ConventionDetail;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConventionController extends Controller
{
    public function __construct(
        private ConventionService $conventionService
    ) {}

    /**
     * Display a listing of the resource.
     * GET /conventions
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $status = $request->get('status');
            $organismeId = $request->get('organisme_id');
            $organismeIds = $request->get('organisme_ids'); // Accept multiple ids

            $query = Convention::with(['conventionDetail', 'organisme', 'annexes']);

            if ($search) {
                $query->where('contract_name', 'like', '%' . $search . '%');
            }

            if ($status) {
                $query->where('status', $status);
            }

            // Support single or multiple organisme IDs
            if ($organismeIds) {
                // Accept comma-separated or array
                if (is_string($organismeIds)) {
                    $ids = array_filter(explode(',', $organismeIds));
                } else {
                    $ids = (array)$organismeIds;
                }
                $query->whereIn('organisme_id', $ids);
            } elseif ($organismeId) {
                $query->where('organisme_id', $organismeId);
            }

            // If per_page is -1, return all results without pagination
            if ($perPage == -1) {
                $conventions = $query->get();
                return response()->json([
                    'success' => true,
                    'data' => ConventionResource::collection($conventions)
                ]);
            }

            $conventions = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => ConventionResource::collection($conventions)->response()->getData(true)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve conventions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     * GET /conventions/create
     */
    public function create(): JsonResponse
    {
        try {
            $formData = [
                'statuses' => ['active', 'inactive', 'pending'],
                'family_auth_options' => [
                    ['label' => 'Ascendant', 'value' => 'ascendant'],
                    ['label' => 'Descendant', 'value' => 'descendant'],
                    ['label' => 'Conjoint', 'value' => 'conjoint'],
                    ['label' => 'Adherent', 'value' => 'adherent'],
                    ['label' => 'Autre', 'value' => 'autre']
                ],
                'organismes' => []
            ];

            return response()->json([
                'success' => true,
                'data' => $formData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load create form data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
 
public function getFamilyAuthorization(Request $request)
{
    $validatedData = $request->validate([
        'prise_en_charge_date' => 'required|date',
    ]);

    try {
        Log::info('Fetching family authorization for date: ' . $validatedData['prise_en_charge_date']);

        // Query convention_details where the date is between start_date and end_date
        $conventionDetail = ConventionDetail::whereDate('start_date', '<=', $validatedData['prise_en_charge_date'])
            ->whereDate('end_date', '>=', $validatedData['prise_en_charge_date'])
            ->first();

        if (!$conventionDetail) {
            Log::info('No convention detail found for date: ' . $validatedData['prise_en_charge_date']);
            
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No family authorization available for ' . Carbon::parse($validatedData['prise_en_charge_date'])->format('d/m/Y'),
            ]);
        }

        // Parse the family_auth - handle different formats
        $familyAuthRaw = trim($conventionDetail->family_auth);
        
        if (empty($familyAuthRaw)) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No family authorization configured for this period.',
            ]);
        }

        // Split by comma and clean up
        $familyAuthOptions = array_filter(
            array_map('trim', explode(',', $familyAuthRaw)),
            fn($item) => !empty($item)
        );

        if (empty($familyAuthOptions)) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No valid family authorization options found.',
            ]);
        }

        // Format the options for the frontend with proper labels
        $formattedOptions = array_map(function ($auth) {
            return [
                'value' => $auth,
                'label' => ucfirst($auth),
            ];
        }, $familyAuthOptions);

        Log::info('Found family authorization options: ' . json_encode($formattedOptions));

        return response()->json([
            'success' => true,
            'data' => $formattedOptions,
            'message' => count($formattedOptions) . ' authorization option(s) available',
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to fetch family authorization: ' . $e->getMessage(), [
            'date' => $validatedData['prise_en_charge_date'],
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch family authorization options',
            'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
        ], 500);
    }
}

/**
 * Format authorization label for better display
 */
private function formatAuthorizationLabel(string $auth): string
{
    // Handle common authorization types
    $authMap = [
        'auth' => 'Authorized',
        'asendent' => 'Ascending',
        'descendent' => 'Descending',
        'partial' => 'Partial Authorization',
        'full' => 'Full Authorization',
    ];

    $lower = strtolower(trim($auth));
    
    if (isset($authMap[$lower])) {
        return $authMap[$lower];
    }

    // Capitalize first letter and replace underscores/dashes with spaces
    return ucfirst(str_replace(['_', '-'], ' ', $auth));
}

    /**
     * Store a newly created resource in storage.
     * POST /conventions
     */
    public function store(StoreConventionRequest $request ): JsonResponse
    {
        try {
            $convention = $this->conventionService->createConvention(
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Convention created successfully',
                'data' => new ConventionResource($convention)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create convention',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /conventions/{convention}
     */
    public function show(Convention $convention): JsonResponse
    {
        try {
            $convention->load(['conventionDetail', 'organisme']);

            return response()->json([
                'success' => true,
                'data' => new ConventionResource($convention)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve convention',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /conventions/{convention}
     */
    public function update(UpdateConventionRequest $request, Convention $convention): JsonResponse
    {
        try {
            $updatedConvention = $this->conventionService->updateConvention(
                $convention,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Convention updated successfully',
                'data' => new ConventionResource($updatedConvention)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update convention',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function calculatePrestationPricing(Request $request): JsonResponse
    {
        try {
            $pricingData = $this->conventionService->calculatePrestationPricing($request->annex_id);

            return response()->json([
                'success' => true,
                'data' => PrestationPricingResource::collection(collect($pricingData)),
                'message' => 'Pricing calculated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Activate the specified convention.
     * PATCH /conventions/{conventionId}/activate
     */
    public function activate(Request $request, int $conventionId): JsonResponse // <--- This is line 197 (or near it)
    {
        try {
            // Validate the incoming request data
            $request->validate([
                'activationDate' => 'nullable|date_format:Y-m-d',
            ]);

            $activationDate = $request->input('activationDate') ?? Carbon::now()->format('Y-m-d');
            $isDelayedActivation = $request->query('activate_later') === 'yes';

            // Use the ConventionService to handle the activation logic
            $result = $this->conventionService->activateConventionById(
                $conventionId,
                $activationDate,
                $isDelayedActivation
            );

            $message = $isDelayedActivation
                ? 'Convention scheduled for activation successfully'
                : 'Convention activated successfully';

            $updatedConvention = Convention::findOrFail($conventionId);

            return response()->json([
                'message' => $message,
                'data' => new ConventionResource($updatedConvention),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Convention not found',
                'details' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to activate convention',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Terminate the specified convention.
     * PATCH /conventions/{conventionId}/expire
     */
    public function expire($conventionId): JsonResponse
    {
        try {
            $convention = Convention::findOrFail($conventionId);
            $convention->status = 'Terminated'; // Assuming 'Terminated' is the status for expired
            $convention->save();

            return response()->json([
                'success' => true,
                'message' => 'Convention terminated successfully.',
                'data' => new ConventionResource($convention)
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Convention not found.',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to terminate convention.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /conventions/{convention}
     */
    public function destroy(Convention $convention): JsonResponse
    {
        try {
            DB::beginTransaction();

            $convention->conventionDetail()->delete();

            $convention->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Convention deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete convention',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}