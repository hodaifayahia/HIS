<?php

namespace App\Http\Controllers\Admission;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admission\StoreAdmissionRequest;
use App\Http\Requests\Admission\UpdateAdmissionRequest;
use App\Http\Resources\Admission\AdmissionResource;
use App\Models\Admission;
use App\Services\Admission\AdmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdmissionController extends Controller
{
    protected $admissionService;

    public function __construct(AdmissionService $admissionService)
    {
        $this->admissionService = $admissionService;
    }

    /**
     * Display a listing of admissions
     */
    public function index(Request $request): JsonResponse
    {
        $admissions = Admission::select([
            'id',
            'patient_id',
            'doctor_id',
            'companion_id',
            'type',
            'status',
            'admitted_at',
            'discharged_at',
            'initial_prestation_id',
            'fiche_navette_id',
            'documents_verified',
            'created_by',
            'created_at',
            'updated_at',
        ])
            ->with([
                'patient:id,Firstname,Lastname,phone',
                'doctor:id,user_id',
                'doctor.user:id,name',
                'companion:id,Firstname,Lastname,phone',
                'initialPrestation:id,name,internal_code',
                'creator:id,name',
            ])
            ->when($request->type, function ($q) use ($request) {
                $q->where('type', $request->type);
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->search, function ($q) use ($request) {
                $q->whereHas('patient', function ($patientQuery) use ($request) {
                    $patientQuery->where('Firstname', 'like', "%{$request->search}%")
                        ->orWhere('Lastname', 'like', "%{$request->search}%");
                });
            })
            ->latest('created_at')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => AdmissionResource::collection($admissions),
            'meta' => [
                'current_page' => $admissions->currentPage(),
                'total' => $admissions->total(),
                'per_page' => $admissions->perPage(),
            ],
        ]);
    }

    /**
     * Store a new admission
     */
    public function store(StoreAdmissionRequest $request): JsonResponse
    {
        try {
            $admission = $this->admissionService->createAdmission($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Admission created successfully',
                'data' => new AdmissionResource($admission->load([
                    'patient',
                    'doctor.user',
                    'companion',
                    'initialPrestation',
                ])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create admission',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified admission
     */
    public function show($id): JsonResponse
    {
        $admission = Admission::with([
            'patient',
            'doctor.user',
            'companion',
            'initialPrestation',
            'procedures.prestation',
            'procedures.performedBy',
            'documents',
            'billingRecords',
            'creator',
            'company',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AdmissionResource($admission),
        ]);
    }

    /**
     * Update the specified admission
     */
    public function update(UpdateAdmissionRequest $request, $id): JsonResponse
    {
        try {
            $admission = $this->admissionService->updateAdmission($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Admission updated successfully',
                'data' => new AdmissionResource($admission),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update admission',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified admission
     */
    public function destroy($id): JsonResponse
    {
        try {
            $admission = Admission::findOrFail($id);

            // Check if admission can be deleted
            if ($admission->status === 'discharged') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete discharged admission',
                ], 422);
            }

            $admission->delete();

            return response()->json([
                'success' => true,
                'message' => 'Admission deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete admission',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Discharge a patient
     */
    public function discharge($id): JsonResponse
    {
        try {
            $admission = $this->admissionService->dischargePatient($id);

            return response()->json([
                'success' => true,
                'message' => 'Patient discharged successfully',
                'data' => new AdmissionResource($admission),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get active admissions
     */
    public function active(Request $request): JsonResponse
    {
        $admissions = Admission::with([
            'patient:id,Firstname,Lastname,phone',
            'doctor.user:id,name',
            'companion:id,Firstname,Lastname,phone',
        ])
            ->whereIn('status', ['admitted', 'in_service', 'document_pending'])
            ->orderBy('admitted_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => AdmissionResource::collection($admissions),
        ]);
    }

    /**
     * Get admission statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->admissionService->getStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get or create Fiche Navette for an admission
     * This endpoint is now mainly kept for backwards compatibility
     * The fiche navette is automatically created/linked during admission creation
     */
    public function getOrCreateFicheNavette($id): JsonResponse
    {
        try {
            $admission = Admission::with('patient', 'doctor', 'ficheNavette')->findOrFail($id);

            // Check if admission already has a fiche navette
            if ($admission->fiche_navette_id && $admission->ficheNavette) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fiche Navette already exists',
                    'fiche_navette_id' => $admission->fiche_navette_id,
                    'data' => [
                        'fiche_navette_id' => $admission->fiche_navette_id,
                        'admission_id' => $admission->id,
                        'fiche_navette' => $admission->ficheNavette,
                    ],
                ]);
            }

            // If no fiche navette exists, it means something went wrong during admission creation
            return response()->json([
                'success' => false,
                'message' => 'No Fiche Navette associated with this admission. Please check the admission record.',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get Fiche Navette',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Fiche Navette for an admission
     */
    public function getFicheNavette($id): JsonResponse
    {
        try {
            $admission = Admission::with('ficheNavette')->findOrFail($id);

            if (! $admission->fiche_navette_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No Fiche Navette found for this admission',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'fiche_navette_id' => $admission->fiche_navette_id,
                    'admission_id' => $admission->id,
                    'fiche_navette' => $admission->ficheNavette,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get Fiche Navette',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the next file number that will be generated
     */
    public function getNextFileNumber(): JsonResponse
    {
        try {
            $nextFileNumber = Admission::generateFileNumber();

            return response()->json([
                'success' => true,
                'data' => [
                    'next_file_number' => $nextFileNumber,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate next file number',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify file number for an admission
     */
    public function verifyFileNumber($id): JsonResponse
    {
        try {
            $admission = Admission::findOrFail($id);

            if ($admission->file_number_verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'File number is already verified',
                ], 422);
            }

            $admission->update(['file_number_verified' => true]);

            return response()->json([
                'success' => true,
                'message' => 'File number verified successfully',
                'data' => new AdmissionResource($admission->fresh(['patient', 'doctor', 'companion', 'company'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify file number',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
