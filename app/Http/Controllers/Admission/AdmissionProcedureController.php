<?php

namespace App\Http\Controllers\Admission;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admission\StoreAdmissionProcedureRequest;
use App\Http\Requests\Admission\UpdateAdmissionProcedureRequest;
use App\Http\Resources\Admission\AdmissionProcedureResource;
use App\Models\AdmissionProcedure;
use App\Services\Admission\AdmissionProcedureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdmissionProcedureController extends Controller
{
    protected $procedureService;

    public function __construct(AdmissionProcedureService $procedureService)
    {
        $this->procedureService = $procedureService;
    }

    /**
     * Display procedures for an admission
     */
    public function index(Request $request, $admissionId): JsonResponse
    {
        $procedures = AdmissionProcedure::where('admission_id', $admissionId)
            ->with(['prestation', 'performedBy', 'creator'])
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => AdmissionProcedureResource::collection($procedures),
        ]);
    }

    /**
     * Store a new procedure
     */
    public function store(StoreAdmissionProcedureRequest $request, $admissionId): JsonResponse
    {
        try {
            $data = array_merge($request->validated(), ['admission_id' => $admissionId]);
            $procedure = $this->procedureService->createProcedure($data);

            return response()->json([
                'success' => true,
                'message' => 'Procedure created successfully',
                'data' => new AdmissionProcedureResource($procedure->load([
                    'prestation',
                    'performedBy',
                ])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create procedure',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified procedure
     */
    public function show($admissionId, $id): JsonResponse
    {
        $procedure = AdmissionProcedure::where('admission_id', $admissionId)
            ->with(['prestation', 'performedBy', 'creator'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AdmissionProcedureResource($procedure),
        ]);
    }

    /**
     * Update the specified procedure
     */
    public function update(UpdateAdmissionProcedureRequest $request, $admissionId, $id): JsonResponse
    {
        try {
            $procedure = $this->procedureService->updateProcedure($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Procedure updated successfully',
                'data' => new AdmissionProcedureResource($procedure),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update procedure',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete a procedure
     */
    public function complete($admissionId, $id): JsonResponse
    {
        try {
            $procedure = $this->procedureService->completeProcedure($id);

            return response()->json([
                'success' => true,
                'message' => 'Procedure marked as completed',
                'data' => new AdmissionProcedureResource($procedure),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel a procedure
     */
    public function cancel($admissionId, $id): JsonResponse
    {
        try {
            $procedure = $this->procedureService->cancelProcedure($id);

            return response()->json([
                'success' => true,
                'message' => 'Procedure cancelled',
                'data' => new AdmissionProcedureResource($procedure),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
