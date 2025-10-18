<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChronicDiseases; // Correct model name
use App\Models\Patient; // Make sure this is imported

class ChronicDiseaseController extends Controller
{
    /**
     * Display a listing of chronic diseases for a specific patient.
     * GET /api/patients/{patient}/chronic-diseases
     */
    public function index(Patient $patient)
    {
        $chronicDiseases = $patient->chronicDiseases()->orderBy('diagnosis_date', 'desc')->get();
        return response()->json($chronicDiseases);
    }

    /**
     * Store a newly created chronic disease for a specific patient.
     * POST /api/patients/{patient}/chronic-diseases
     */
    public function store(Request $request, Patient $patient)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'diagnosis_date' => 'nullable|date',
        ]);

        $chronicDisease = $patient->chronicDiseases()->create($validatedData);

        return response()->json($chronicDisease, 201); // 201 Created
    }

    /**
     * Update the specified chronic disease for a specific patient.
     * PUT/PATCH /api/patients/{patient}/chronic-diseases/{chronicDisease}
     */
    public function update(Request $request, Patient $patient, ChronicDiseases $chronicDisease)
    {
        // Ensure the chronic disease belongs to the patient
        if ($patient->id !== $chronicDisease->patient_id) {
            abort(404, 'Chronic disease not found for this patient.');
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'diagnosis_date' => 'nullable|date',
        ]);

        $chronicDisease->update($validatedData);

        return response()->json($chronicDisease); // 200 OK
    }

    /**
     * Remove the specified chronic disease from storage for a specific patient.
     * DELETE /api/patients/{patient}/chronic-diseases/{chronicDisease}
     */
    public function destroy(Patient $patient, ChronicDiseases $chronicDisease)
    {
        // Ensure the chronic disease belongs to the patient before deleting
        if ($patient->id !== $chronicDisease->patient_id) {
            abort(404, 'Chronic disease not found for this patient.');
        }

        $chronicDisease->delete();

        return response()->json(null, 204); // 204 No Content
    }
}