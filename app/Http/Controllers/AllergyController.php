<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Allergy;
use App\Models\Patient; // Make sure this is imported

class AllergyController extends Controller
{
    /**
     * Display a listing of allergies for a specific patient.
     * GET /api/patients/{patient}/allergies
     */
    public function index(Patient $patient)
    {
        $allergies = $patient->allergies()->orderBy('date', 'desc')->get();
        
        return response()->json($allergies);
    }

    /**
     * Store a newly created allergy for a specific patient.
     * POST /api/patients/{patient}/allergies
     */
    public function store(Request $request, Patient $patient)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'severity' => 'required|string|in:mild,moderate,severe',
            'date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        $allergy = $patient->allergies()->create($validatedData);

        return response()->json($allergy, 201); // 201 Created
    }

    /**
     * Update the specified allergy for a specific patient.
     * PUT/PATCH /api/patients/{patient}/allergies/{allergy}
     */
    public function update(Request $request, Patient $patient, Allergy $allergy)
    {
        // Ensure the allergy belongs to the patient
        if ($patient->id !== $allergy->patient_id) {
            abort(404, 'Allergy not found for this patient.');
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255', // 'sometimes' if not all fields are required on update
            'severity' => 'sometimes|required|string|in:mild,moderate,severe',
            'date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        $allergy->update($validatedData);

        return response()->json($allergy); // 200 OK
    }

    /**
     * Remove the specified allergy from storage for a specific patient.
     * DELETE /api/patients/{patient}/allergies/{allergy}
     */
    public function destroy(Patient $patient, Allergy $allergy)
    {
        // Ensure the allergy belongs to the patient before deleting
        if ($patient->id !== $allergy->patient_id) {
            abort(404, 'Allergy not found for this patient.');
        }

        $allergy->delete();

        return response()->json(null, 204); // 204 No Content
    }
}