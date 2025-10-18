<?php

namespace App\Http\Controllers;

use App\Models\FamilyDiseases;
use App\Models\Patient;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FamilyHistoryController extends Controller
{
    public function index($patientId)
    {
        $patient = Patient::findOrFail($patientId);
        $familyHistory = FamilyDiseases::where('patient_id', $patientId)->get();
        return response()->json($familyHistory);
    }

    public function store(Request $request, $patientId)
    {
        $patient = Patient::findOrFail($patientId);
        $validated = $request->validate([
            'disease_name' => 'required|string|max:255',
            'relation' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $familyHistory = FamilyDiseases::create(array_merge(
            $validated,
            ['patient_id' => $patientId]
        ));

        return response()->json($familyHistory, 201);
    }

    public function show($patientId, $id)
    {
        $familyHistory = FamilyDiseases::where('patient_id', $patientId)->find($id);
        if (!$familyHistory) {
            throw new NotFoundHttpException('Family history entry not found for this patient.');
        }
        return response()->json($familyHistory);
    }

    public function update(Request $request, $patientId, $id)
    {
        $familyHistory = FamilyDiseases::where('patient_id', $patientId)->find($id);
        if (!$familyHistory) {
            throw new NotFoundHttpException('Family history entry not found for this patient.');
        }

        $validated = $request->validate([
            'disease_name' => 'required|string|max:255',
            'relation' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $familyHistory->update($validated);
        return response()->json($familyHistory);
    }

    public function destroy($patientId, $id)
    {
        $familyHistory = FamilyDiseases::where('patient_id', $patientId)->find($id);
        if (!$familyHistory) {
            throw new NotFoundHttpException('Family history entry not found for this patient.');
        }

        $familyHistory->delete();
        return response()->json(null, 204);
    }
}