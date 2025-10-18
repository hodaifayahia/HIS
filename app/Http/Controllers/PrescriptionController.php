<?php

namespace App\Http\Controllers;

use App\Models\Prescriptiontemplate;
use App\Models\Prescription; // Changed from 'prescription' to 'Prescription' for consistency
use App\Models\PrescriptionMedication;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\User;
use Milon\Barcode\DNS1D;
use App\Models\Doctor;
use App\Models\PatientDocement; // Assuming this is the model for patient documents

// use PDF;

use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf; // Use the correct facade


use App\Http\Resources\PrescriptionResource;
use App\Http\Resources\PrescriptionTemplateResource; // Added if you plan to use it consistently
use App\Http\Resources\PrescriptionMedicationResource; // Added if you plan to use it consistently
use App\Http\Resources\MedicationResource; // Added if you plan to use it consistently
use Carbon\Carbon; // <-- IMPORTANT: Make sure this line is present
use Log;
class PrescriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     * Optionally filter by patient_id.
     */
    public function index(Request $request)
    {
        $prescriptions = Prescription::with(['medications', 'patient', 'doctor.user'])
            ->when($request->patient_id, function ($query, $patientId) {
                return $query->where('patient_id', $patientId);
            })
            ->latest()
            ->get();

        return PrescriptionResource::collection($prescriptions);
    }

    /**
     * Store a newly created resource in storage.
     */
public function store(Request $request)
{
     $validated = $request->validate([
        'patient_id' => 'required|exists:patients,id',
        'patient_age' => 'nullable|numeric|min:0',
        'patient_weight' => 'nullable|numeric|min:0',
        'patientDob' => 'nullable|date',
        'prescription_date' => 'nullable|date',
        'ageUnit' => 'nullable|string',
        'medications' => 'required|array|min:1',
        'medications.*.medication_id' => 'required|exists:medications,id',
        'medications.*.form' => 'required|string|max:255',
        'medications.*.num_times' => 'required|string',
        'medications.*.frequency' => 'required|string',
        'medications.*.start_date' => 'nullable|date',
        'medications.*.end_date' => 'nullable|date|after_or_equal:medications.*.start_date',
        'medications.*.description' => 'nullable|string',
        'medications.*.period_intakes' => 'nullable|string',
        'medications.*.timing_preference' => 'nullable|string',
        'medications.*.frequency_period' => 'nullable|string',
        // Add validation for custom pill counts
        'medications.*.pills_matin' => 'nullable|integer|min:0',
        'medications.*.pills_apres_midi' => 'nullable|integer|min:0',
        'medications.*.pills_midi' => 'nullable|integer|min:0',
        'medications.*.pills_soir' => 'nullable|integer|min:0',
        'appointment_id' => 'nullable',
        'with_date' => 'boolean'
    ]);


    DB::beginTransaction();

    try {
        // Get the patient data from database
        $patient = Patient::findOrFail($validated['patient_id']);
       $patient->update([
            'age' => $validated['patient_age'] ?? $patient->age,
            'weight' => $validated['patient_weight'] ?? $patient->weight,
            'dateOfBirth'=>$validated['patientDob'] ?? $patient->dateOfBirth
        ]);
        
        // Create the prescription with patient's age and weight from DB
        $prescription = Prescription::create([
            'patient_id' => $validated['patient_id'],
            'doctor_id' => auth()->user()->doctor->id ?? 1,
            'appointment_id' => $validated['appointment_id'],
            'signature_status' => 'confirmed',
            'prescription_date' => $validated['prescription_date'],
            'pdf_path' => null
        ]);
        // get the the consulaiton codebar 
        $consultation = Consultation::where('appointment_id', $validated['appointment_id'])
            ->latest()
            ->first();

            $barcodeBase64 = null; // Initialize to null
            if ($consultation->codebash) { // Check if codebash exists and is not empty
                try {
                    $barcodeBase64 = (new DNS1D)->getBarcodePNG($consultation->codebash, 'C128');
                } catch (\Exception $e) {
                    // Log the barcode generation error but don't stop the main process
                    \Log::error("Barcode generation failed for consultation ID {$consultationId}: " . $e->getMessage());
                    // Optionally, set a default or indicate an error in the response
                    $barcodeBase64 = null; // Or some error string
                }
            }
        
        // Create medications
        foreach ($validated['medications'] as $medicationData) {
          foreach ($validated['medications'] as $medicationData) {
                $prescription->medications()->create([
                    'medication_id' => $medicationData['medication_id'],
                    'form' => $medicationData['form'],
                    'num_times' => $medicationData['num_times'],
                    'frequency' => $medicationData['frequency'],
                    'start_date' => $medicationData['start_date'],
                    'period_intakes' => $medicationData['period_intakes'],
                    'frequency_period' => $medicationData['frequency_period'],
                    'timing_preference' => $medicationData['timing_preference'],
                    'end_date' => $medicationData['end_date'],
                    'description' => $medicationData['description'] ?? null,
                    // Add custom pill counts
                    'pills_matin' => $medicationData['pills_matin'] ?? null,
                    'pills_apres_midi' => $medicationData['pills_apres_midi'] ?? null,
                    'pills_midi' => $medicationData['pills_midi'] ?? null,
                    'pills_soir' => $medicationData['pills_soir'] ?? null,
                ]);
            }
        }

        // Generate and save PDF
        $pdfPath = $this->generateAndSavePrescriptionPdf($prescription, $request->with_date , $request->ageUnit , $barcodeBase64);
        $prescription->pdf_path = $pdfPath;
        $prescription->save(); // Save the PDF path

        // Create record in patient_documents table
        $documentName = "Prescription_{$prescription->id}_" . now()->format('Y-m-d');
        $patientDocument = PatientDocement::create([
            'patient_id' => $validated['patient_id'],
            'document_name' => $documentName,
            'document_path' => $pdfPath,
            'doctor_id' => auth()->user()->doctor->id,
            'document_type' => 'prescription',
            'created_by' => auth()->id() ?? $doctor->user->id,
            'appointment_id' => $validated['appointment_id'] ?? null,
            'folder_id' => null,
            'document_size' => Storage::disk('public')->exists($pdfPath) ? Storage::disk('public')->size($pdfPath) : 0,
        ]);
        
        DB::commit();

        return response()->json([
            'message' => 'Prescription created successfully',
            'prescription' => new PrescriptionResource($prescription)
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Prescription creation failed: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'request_data' => $request->all()
        ]);
        
        return response()->json([
            'message' => 'Failed to create prescription',
            'details' => $e->getMessage()
        ], 500);
    }
}
    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $prescription = Prescription::with(['medications', 'patient', 'doctor.user'])->findOrFail($id);
        return response()->json(new PrescriptionResource($prescription)); // Use resource
    }

    public function prescriptiontemplates(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => 'nullable|exists:doctors,id', // Made nullable for flexibility, assuming authenticated doctor otherwise
            'name' => 'required|string|max:255', // Made name required for a template
            'prescription_id' => 'nullable|exists:prescriptions,id',
            'description' => 'nullable|string',
        ]);

        $doctorId = $validated['doctor_id'] ?? (auth()->user()->doctor->id ?? null);
        if (!$doctorId) {
            return response()->json(['error' => 'Doctor ID is required or user is not authenticated as a doctor.'], 400);
        }

        // It seems like prescription_id is optional here, implying a template can be created without being linked to an existing prescription
        Prescriptiontemplate::create([
            'doctor_id' => $doctorId,
            'name' => $validated['name'],
            'prescription_id' => $validated['prescription_id'] ?? null, // Use null if not provided
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Prescription template created successfully.'
        ], 201);
    }

    public function getPrescriptionTemplates(Request $request)
    {
        $doctorId = $request->input('doctor_id');

        // Fallback to authenticated user's ID if not provided in the request
      

        // Handle cases where doctor_id is still not found
        // if (empty($doctorId)) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Doctor ID not found. Please provide doctor_id in the request or ensure user is authenticated.'
        //     ], 400); // Bad Request status
        // }

        try {
            // Remove dd($prescriptiontemplates);
            $prescriptiontemplates = PrescriptionTemplate::where('doctor_id', $doctorId)->get();
            
            // Use the PrescriptionTemplateResource to transform the collection
            return response()->json([
                'success' => true,
                'message' => 'Prescription templates fetched successfully',
                // Return a collection of resources
                'templates' => PrescriptionTemplateResource::collection($prescriptiontemplates)
            ], 200); // OK status
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch prescription templates: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch prescription templates: An unexpected error occurred.' // More generic error message for production
            ], 500); // Internal Server Error status
        }
    }

    public function prescriptiontemplatesmedication(Request $request)
    {
        $validated = $request->validate([
            'prescription_id' => 'required|exists:prescriptions,id', // Made required to fetch specific medications
        ]);

        // Assuming this method should return medications associated with a *specific* prescription,
        // not a template. The naming is a bit confusing.
        // If it's about template medications, then it should be template_id.
        // Let's assume it's for a *prescription* and its medications based on the validation.
        $prescription = Prescription::with('medications.medication')->findOrFail($validated['prescription_id']);

        return response()->json([
            'success' => true,
            'message' => 'Medications for the prescription fetched successfully.',
            'medications' => MedicationResource::collection($prescription->medications->map->medication) // Assuming you want the medication details themselves
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
  public function update(Request $request, Prescription $prescription)
{
    $validated = $request->validate([
        'patient_id' => 'sometimes|required|exists:patients,id',
        'patient_age' => 'nullable|numeric|min:0',
        'patient_weight' => 'nullable|numeric|min:0',
        'medications' => 'sometimes|required|array|min:1',
        'medications.*.id' => 'nullable|exists:prescription_medications,id',
        'medications.*.medication_id' => 'required|exists:medications,id',
        'medications.*.form' => 'required|string|max:255',
        'medications.*.num_times' => 'required|string',
        'medications.*.frequency' => 'required|string',
        'medications.*.start_date' => 'nullable|date',
        'medications.*.end_date' => 'nullable|date|after_or_equal:medications.*.start_date',
        'medications.*.description' => 'nullable|string',
        'medications.*.period_intakes' => 'nullable|string',
        'medications.*.timing_preference' => 'nullable|string',
        'medications.*.frequency_period' => 'nullable|string',
        'medications.*.pills_matin' => 'nullable|integer|min:0',
        'medications.*.pills_apres_midi' => 'nullable|integer|min:0',
        'medications.*.pills_midi' => 'nullable|integer|min:0',
        'medications.*.pills_soir' => 'nullable|integer|min:0',
        'appointment_id' => 'nullable|exists:appointments,id'
    ]);

    DB::beginTransaction();

    try {
        // Update patient details if provided
        $patient = $prescription->patient;
        if ($patient) {
            if (isset($validated['patient_age'])) {
                $patient->age = $validated['patient_age'];
            }
            if (isset($validated['patient_weight'])) {
                $patient->weight = $validated['patient_weight'];
            }
            if ($patient->isDirty('age') || $patient->isDirty('weight')) {
                $patient->save();
            }
        }

        // Update appointment/consultation if provided
        if (isset($validated['appointment_id'])) {
            $consultation = Consultation::where('appointment_id', $validated['appointment_id'])->first();
            $prescription->consultation_id = $consultation ? $consultation->id : null;
        }

        $prescription->save();

        // Handle Medications update
        if (isset($validated['medications'])) {
            $currentMedicationIds = $prescription->medications->pluck('id')->toArray();
            $incomingMedicationIds = [];

            foreach ($validated['medications'] as $medicationData) {
                $medicationUpdateData = [
                    'medication_id' => $medicationData['medication_id'],
                    'form' => $medicationData['form'],
                    'num_times' => $medicationData['num_times'],
                    'frequency' => $medicationData['frequency'],
                    'start_date' => $medicationData['start_date'] ?? null,
                    'end_date' => $medicationData['end_date'] ?? null,
                    'period_intakes' => $medicationData['period_intakes'] ?? null,
                    'timing_preference' => $medicationData['timing_preference'] ?? null,
                    'frequency_period' => $medicationData['frequency_period'] ?? null,
                    'description' => $medicationData['description'] ?? null,
                    'pills_matin' => $medicationData['pills_matin'] ?? null,
                    'pills_apres_midi' => $medicationData['pills_apres_midi'] ?? null,
                    'pills_midi' => $medicationData['pills_midi'] ?? null,
                    'pills_soir' => $medicationData['pills_soir'] ?? null
                ];

                if (isset($medicationData['id'])) {
                    $prescriptionMedication = PrescriptionMedication::find($medicationData['id']);
                    if ($prescriptionMedication && $prescriptionMedication->prescription_id === $prescription->id) {
                        $prescriptionMedication->update($medicationUpdateData);
                        $incomingMedicationIds[] = $medicationData['id'];
                    }
                } else {
                    $newMedication = $prescription->medications()->create($medicationUpdateData);
                    $incomingMedicationIds[] = $newMedication->id;
                }
            }

            // Delete medications not in incoming list
            $medicationsToDelete = array_diff($currentMedicationIds, $incomingMedicationIds);
            PrescriptionMedication::destroy($medicationsToDelete);
        }

        // Regenerate PDF
        if ($prescription->pdf_path && Storage::disk('public')->exists($prescription->pdf_path)) {
            Storage::disk('public')->delete($prescription->pdf_path);
        }

        $prescription->load('medications.medication', 'patient', 'doctor.user');
        $newPdfPath = $this->generateAndSavePrescriptionPdf($prescription);
        $prescription->pdf_path = $newPdfPath;
        $prescription->save();

        // Update patient document
        $patientDocument = PatientDocement::where('document_path', $prescription->getOriginal('pdf_path'))
                                        ->first();
        if ($patientDocument) {
            $patientDocument->update([
                'document_name' => "Prescription_{$prescription->id}_" . now()->format('Y-m-d'),
                'document_path' => $newPdfPath,
                'document_size' => Storage::disk('public')->exists($newPdfPath) ? Storage::disk('public')->size($newPdfPath) : 0,
                'updated_at' => now(),
            ]);
        } else {
            PatientDocement::create([
                'patient_id' => $prescription->patient->id,
                'document_name' => "Prescription_{$prescription->id}_" . now()->format('Y-m-d'),
                'document_path' => $newPdfPath,
                'doctor_id' => $prescription->doctor->id,
                'document_type' => 'prescription',
                'created_by' => auth()->id() ?? $prescription->doctor->user->id,
                'appointment_id' => $validated['appointment_id'] ?? null,
                'folder_id' => null,
                'document_size' => Storage::disk('public')->exists($newPdfPath) ? Storage::disk('public')->size($newPdfPath) : 0,
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Prescription updated successfully',
            'prescription' => new PrescriptionResource($prescription)
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Prescription update failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json([
            'error' => 'Failed to update prescription.',
            'details' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Prescription $prescription)
    {
        DB::beginTransaction();
        try {
            // Delete associated PDF file
            if ($prescription->pdf_path && Storage::disk('public')->exists($prescription->pdf_path)) {
                Storage::disk('public')->delete($prescription->pdf_path);
            }

            // Delete associated patient document record
            PatientDocement::where('document_path', $prescription->pdf_path)->delete();

            // Medications will be cascade deleted if setup in migration, otherwise delete them first
            $prescription->medications()->delete();
            $prescription->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Prescription deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Prescription deletion failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Failed to delete prescription.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Generates a PDF for a given prescription and saves it to storage.
     *
     * @param Prescription $prescription
     * @return string The path to the saved PDF file.
     */

public function generateAndSavePrescriptionPdf(Prescription $prescription, $with_date , $ageUnit ,$barcodeBase64): string
{
    // Load relationships
    $prescription->loadMissing('medications.medication', 'patient', 'doctor.user');
    // Prepare data
    $data = [
        'prescription' => $prescription,
        'doctor_name' => $prescription->doctor->user->name ?? 'Médecin Inconnu',
        'patient_first_name' => $prescription->patient->Firstname,
        'patient_last_name' => $prescription->patient->Lastname,
        'age_unit' => $ageUnit, // Add age unit to data
        'patient_age' => $prescription->patient->age, 
        'codebar' => $barcodeBase64, // Add barcode to data
        'medications' => $prescription->medications,
    ];

    if ($with_date) {
        $data['current_date'] = $prescription->prescription_date;
    }

    // Ensure directory exists
    Storage::makeDirectory('public/prescriptions/pdfs');

    // Generate PDF
    $pdf = PDF::loadView('prescriptions.pdf', $data, [
        'mode'          => 'utf-8',
        'format'        => [200, 250], // 20cm x 25cm in mm
        'orientation'   => 'P', // Portrait
        'margin_left'   => 15,
        'margin_right'  => 5,
        'margin_top'    => 15,
        'margin_bottom' => 15,
        'margin_header' => 0,
        'margin_footer' => 0,
        'tempDir'       => storage_path('temp'), // Important for Windows
    ]);

    $filename = "prescriptions/pdfs/ordonnance_{$prescription->id}_{$prescription->patient->last_name}_".time().".pdf";
    $filePath = storage_path('app/public/'.$filename);

    // Save the PDF
    $pdf->save($filePath);

    return $filename;
}

    /**
     * Generates and downloads a PDF for a given prescription.
     */
    public function downloadPrescriptionPdf($id)
    {
        $prescription = Prescription::with(['medications.medication', 'patient', 'doctor.user'])->findOrFail($id);

        if ($prescription->pdf_path && Storage::disk('public')->exists($prescription->pdf_path)) {
            $filename = "ordonnance_{$prescription->id}_{$prescription->patient->last_name}.pdf";
            return Storage::disk('public')->download($prescription->pdf_path, $filename);
        }

        // If PDF not found or not generated, generate and stream it
        $data = [
            'prescription' => $prescription,
            'doctor_name' => $prescription->doctor->user->name ?? 'Médecin Inconnu',
            'patient_first_name' => $prescription->patient->first_name,
            'patient_last_name' => $prescription->patient->last_name,
            'medications' => $prescription->medications,
        ];
        if ($with_date) {
              $data['current_date'] = Carbon::now()->format('d/m/Y');

        }
        $pdf = PDF::loadView('prescriptions.pdf', $data);

        $filename = "ordonnance_{$prescription->id}_{$prescription->patient->last_name}.pdf";
        return $pdf->stream($filename);
    }

    /**
     * Generates and streams a PDF for a given prescription (alias for downloadPrescriptionPdf if just streaming).
     * Consider merging with downloadPrescriptionPdf if functionality is identical.
     */
    public function printPrescription(Request $request)
    {
        try {
            $path = $request->query('path');

            if (!$path || !Storage::disk('public')->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'PDF document not found'
                ], 404);
            }

            // Return the file with inline disposition for printing
            return response()->file(
                Storage::disk('public')->path($path),
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="prescription.pdf"'
                ]
            );

        } catch (\Exception $e) {
            \Log::error('PDF print failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to print PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadPdf(Request $request)
    {
        try {
            $path = $request->query('path');

            if (!$path || !Storage::disk('public')->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'PDF document not found'
                ], 404);
            }

            return response()->download(
                Storage::disk('public')->path($path),
                "prescription.pdf"
            );

        } catch (\Exception $e) {
            \Log::error('PDF download failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to download PDF: ' . $e->getMessage()
            ], 500);
        }
    }

  
// Add this new method to generate preview PDF
public function generatePreviewPdf(Prescription $prescription): string 
{
    // Load relationships
    $prescription->loadMissing('medications.medication', 'patient', 'doctor.user');
    
    // Prepare data for preview (you can customize this differently from the main PDF)
    $data = [
        'prescription' => $prescription,
        'doctor_name' => $prescription->doctor->user->name ?? 'Médecin Inconnu',
        'patient_first_name' => $prescription->patient->Firstname,
        'patient_last_name' => $prescription->patient->Lastname,
        'patient_age' => $prescription->patient->age,
        'medications' => $prescription->medications,
        'is_preview' => true, // Flag to indicate this is a preview
        'current_date' => Carbon::now()->format('d/m/Y'),
    ];

    // Ensure directory exists
    Storage::makeDirectory('public/prescriptions/previews');

    // Generate Preview PDF (you might want to use a different view template)
    $pdf = PDF::loadView('prescriptions.preview', $data, [
        'mode'          => 'utf-8',
        'format'        => [200, 250], // 20cm x 25cm in mm
        'orientation'   => 'P', // Portrait
        'margin_left'   => 15,
        'margin_right'  => 15,
        'margin_top'    => 15,
        'margin_bottom' => 15,
        'margin_header' => 0,
        'margin_footer' => 0,
        'tempDir'       => storage_path('temp'),
    ]);

    $filename = "prescriptions/previews/preview_ordonnance_{$prescription->id}_{$prescription->patient->last_name}_".time().".pdf";
    $filePath = storage_path('app/public/'.$filename);

    // Save the preview PDF
    $pdf->save($filePath);

    return $filename;
}

// Updated viewPdf method
public function viewPdfOnTheFly (Request $request, $appointment_id)
{
    try {
        // Find the prescription by appointment_id
        $prescription = Prescription::where('appointment_id', $appointment_id)->first();
        
        if (!$prescription) {
            return response()->json([
                'success' => false,
                'message' => 'Prescription not found'
            ], 404);
        }

        // Generate a new preview PDF each time
        $previewPath = $this->generatePreviewPdf($prescription);
        
        // Return the preview PDF
        return response()->file(
            Storage::disk('public')->path($previewPath),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="prescription_preview.pdf"'
            ]
        );

    } catch (\Exception $e) {
        \Log::error('PDF view failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to view PDF: ' . $e->getMessage()
        ], 500);
    }
}

// Alternative approach: Generate preview PDF on-the-fly without saving
public function viewPdf(Request $request, $appointment_id)
{
    try {
        // Find the prescription by appointment_id
        $prescription = Prescription::where('appointment_id', $appointment_id)->first();
        
        if (!$prescription) {
            return response()->json([
                'success' => false,
                'message' => 'Prescription not found'
            ], 404);
        }

        // Load relationships
        $prescription->loadMissing('medications.medication', 'patient', 'doctor.user');
        
        // Prepare data for preview
        $data = [
            'prescription' => $prescription,
            'doctor_name' => $prescription->doctor->user->name ?? 'Médecin Inconnu',
            'patient_first_name' => $prescription->patient->Firstname,
            'patient_last_name' => $prescription->patient->Lastname,
            'patient_age' => $prescription->patient->age,
            'medications' => $prescription->medications,
            'is_preview' => true,
            'current_date' => Carbon::now()->format('d/m/Y'),
        ];

        // Generate PDF directly without saving
        $pdf = PDF::loadView('prescriptions.preview', $data, [
            'mode'          => 'utf-8',
            'format'        => [200, 250],
            'orientation'   => 'P',
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 15,
            'margin_bottom' => 15,
            'margin_header' => 0,
            'margin_footer' => 0,
            'tempDir'       => storage_path('temp'),
        ]);

        // Return PDF directly
        return $pdf->stream('prescription_preview.pdf');

    } catch (\Exception $e) {
        \Log::error('PDF view failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to view PDF: ' . $e->getMessage()
        ], 500);
    }
}
 public function getTemplateMedications($templateId, Request $request)
    {
        try {
            // Eager load the 'medications' relationship (which now uses hasManyThrough)
            // and then further eager load the 'medication' relationship for each PrescriptionMedication
            $template = PrescriptionTemplate::with(['medications.medication'])->findOrFail($templateId);

            return response()->json([
                'success' => true,
                'data' => [
                    // The PrescriptionTemplateResource will now correctly handle the transformation
                    // of PrescriptionMedication to Medication objects.
                    'template' => new PrescriptionTemplateResource($template),
                    // For the direct 'medications' array in the response, we still need to map
                    // from PrescriptionMedication instances to their actual Medication details.
                    'medications' => MedicationResource::collection($template->medications->map->medication)
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the specific exception for debugging
            Log::warning('Prescription template not found: ' . $templateId . ' - ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Prescription template not found.'
            ], 404);
        } catch (\Exception $e) {
            // Log general errors with a full trace for detailed debugging
            Log::error('Failed to fetch template medications: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch template medications: An unexpected error occurred.'
            ], 500);
        }
    }

    private function calculateWeightBasedDose($baseDose, $weight)
    {
        // Add your weight-based dosing calculation logic here
        return $baseDose;
    }
}