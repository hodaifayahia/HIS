<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Surgical;
use App\Models\Patient;

class SurgicalController extends Controller
{
    public function index(Patient $patient)
    {
        $surgicalHistory = $patient->surgical()->orderBy('created_at', 'desc')->get();
        return response()->json($surgicalHistory);
    }

    public function store(Request $request, Patient $patient)
    {
        $validatedData = $request->validate([
            'surgery_name' => 'required|string|max:255',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $surgicalEntry = Surgical::create([
            'procedure_name' => $validatedData['surgery_name'],
            'surgery_date' => $validatedData['date'],
            'description' => $validatedData['notes'],
            'patient_id' => $patient->id,
        ]);

        return response()->json($surgicalEntry, 201);
    }

  public function update(Request $request, $patientId, $id)
    {
        $surgicalHistory = Surgical::where('patient_id', $patientId)->find($id);
        if (!$surgicalHistory) {
            throw new NotFoundHttpException('Surgical entry not found for this patient.');
        }

        $validatedData = $request->validate([
            'surgery_name' => 'required|string|max:255',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $surgicalHistory->update([
            'procedure_name' => $validatedData['surgery_name'],
            'surgery_date' => $validatedData['date'],
            'description' => $validatedData['notes'],
        ]);

        return response()->json($surgicalHistory);
    }
   public function destroy($patientId, $id)
    {
        $surgicalHistory = Surgical::where('patient_id', $patientId)->find($id);
        if (!$surgicalHistory) {
            throw new NotFoundHttpException('Surgical entry not found for this patient.');
        }

        $surgicalHistory->delete();
        return response()->json(null, 204);
    }
}