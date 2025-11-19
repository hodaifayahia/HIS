<?php

namespace App\Http\Controllers\Admission;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admission\Treatment\StoreAdmissionTreatmentRequest;
use App\Http\Requests\Admission\Treatment\UpdateAdmissionTreatmentRequest;
use App\Http\Resources\Admission\AdmissionTreatmentResource;
use App\Models\Admission;
use App\Models\AdmissionTreatment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AdmissionTreatmentController extends Controller
{
    /**
     * Display a listing of treatments for an admission.
     */
    public function index(Admission $admission): JsonResponse
    {
        $treatments = $admission->treatments()
            ->with(['doctor.user', 'prestation', 'creator'])
            ->orderBy('entered_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => AdmissionTreatmentResource::collection($treatments),
        ]);
    }

    /**
     * Store a newly created treatment.
     */
    public function store(StoreAdmissionTreatmentRequest $request, Admission $admission): JsonResponse
    {
        $validated = $request->validated();
        $validated['admission_id'] = $admission->id;
        $validated['created_by'] = Auth::id();

        $treatment = AdmissionTreatment::create($validated);
        $treatment->load(['doctor.user', 'prestation', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Treatment added successfully',
            'data' => new AdmissionTreatmentResource($treatment),
        ], 201);
    }

    /**
     * Display the specified treatment.
     */
    public function show(Admission $admission, AdmissionTreatment $treatment): JsonResponse
    {
        // Ensure treatment belongs to this admission
        if ($treatment->admission_id !== $admission->id) {
            return response()->json([
                'success' => false,
                'message' => 'Treatment not found for this admission',
            ], 404);
        }

        $treatment->load(['doctor.user', 'prestation', 'creator']);

        return response()->json([
            'success' => true,
            'data' => new AdmissionTreatmentResource($treatment),
        ]);
    }

    /**
     * Update the specified treatment.
     */
    public function update(UpdateAdmissionTreatmentRequest $request, Admission $admission, AdmissionTreatment $treatment): JsonResponse
    {
        // Ensure treatment belongs to this admission
        if ($treatment->admission_id !== $admission->id) {
            return response()->json([
                'success' => false,
                'message' => 'Treatment not found for this admission',
            ], 404);
        }

        $treatment->update($request->validated());
        $treatment->load(['doctor.user', 'prestation', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Treatment updated successfully',
            'data' => new AdmissionTreatmentResource($treatment),
        ]);
    }

    /**
     * Remove the specified treatment.
     */
    public function destroy(Admission $admission, AdmissionTreatment $treatment): JsonResponse
    {
        // Ensure treatment belongs to this admission
        if ($treatment->admission_id !== $admission->id) {
            return response()->json([
                'success' => false,
                'message' => 'Treatment not found for this admission',
            ], 404);
        }

        $treatment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Treatment deleted successfully',
        ]);
    }
}
