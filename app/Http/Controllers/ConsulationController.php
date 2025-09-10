<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Template;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PatientDocement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Events\PdfGeneratedEvent;
use App\Http\Resources\patientConsulationResource;
use App\Models\ConsultationPlaceholderAttributes;
use Spatie\Browsershot\Browsershot;
use App\Http\Resources\ConsulationResource;
use App\Http\Resources\PrescriptionResource;
use Spatie\Browsershot\Exceptions\CouldNotTakeBrowsershot;
use Milon\Barcode\DNS1D;

use App\Models\Doctor;
use App\Models\Patient;
use App\Http\Resources\DoctorResource;
use App\Http\Resources\PatientResource;
use App\Http\Resources\TemplateResource;
use App\Http\Resources\ConsultationPlaceholderAttributesResource;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Barryvdh\DomPDF\Options;
use Dompdf\Dompdf;

use PhpOffice\PhpWord\Shared\Html;
use App\Models\Consultation;
use ZipArchive;
//-import log
use Log;

class ConsulationController extends Controller
{
  public function index($patientid, Request $request)
{   

    try {
        $query = Consultation::with([
                'patient', 
                'doctor', 
                'template', 
                'placeholderAttributes.placeholder', 
                'placeholderAttributes.attribute'
            ])
            ->where('patient_id', $patientid)
            ->whereHas('appointment', function ($q) {
                $q->where('status', 'DONE')
                  ->orWhere('status', 4);
            })
            ->orderBy('created_at', 'desc');

        if ($request->consultationId) {
            $query->where('id', $request->consultationId);
        }

        $consultations = $query->get();
        return ConsulationResource::collection($consultations);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve consultations: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Generate a PDF from HTML content.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
 public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'nullable|numeric',
            'doctor_id' => 'nullable|numeric',
            'appointment_id' => 'nullable|numeric',
        ]);

        // Check if a consultation already exists for this appointment
        $existing = Consultation::where('appointment_id', $request->appointment_id)->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Consultation already exists',
                'data' => $existing
            ]);
        }

        // Get the doctor to generate the codebash
          $doctor = Doctor::find($request->doctor_id);
        $doctorName = $doctor->user->name;
        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found'
            ], 404);
        }

        // Generate the codebash
        // Taking the first two letters of the doctor's first name (assuming 'first_name' column)
        $doctorCode = strtoupper(substr($doctorName, 0, 2));

        // You might want to ensure appointment_id is not null for this code
        $appointmentId = $request->appointment_id ?? 'N/A'; // Handle nullable appointment_id
        $patientId = $request->patient_id;

        $codebash = $doctorCode . $appointmentId . $patientId;

        // Create a new consultation
        $consultation = Consultation::create([
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'appointment_id' => $request->appointment_id,
            'codebash' => $codebash, // Store the generated codebash
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Consultation created successfully',
            'data' => $consultation
        ]);
    }
public function update(Request $request, $id)
{
    $request->validate([
        'consultation_name' => 'nullable|string',
      
    ]);
    try {
        $consultation = Consultation::findOrFail($id);

        // Update the consultation with the provided data
        $consultation->update([
            'name' => $request->consultation_name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Consultation updated successfully',
            'data' => $consultation
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update consultation: ' . $e->getMessage()
        ], 500);
    }
}


   public function generatePdf(Request $request)
    {
        $request->validate([
            'html' => 'required|string',
        ]);

        try {
            // THE "NO MATTER WHAT" SOLUTION: CUSTOM PAGE HEIGHT
            $pdf = Browsershot::html($request->input('html'))
        
                ->paperSize(210, 400, 'mm') 
                // We can still keep a small, clean margin around the content.                
                ->pdf();

            return response($pdf)
                ->header('Content-Type', 'application/pdf');

        } catch (CouldNotTakeBrowsershot $e) {
            // Log the detailed error for debugging
            \Log::error('Browsershot PDF generation failed: ' . $e->getMessage());

            // Return a user-friendly error
            return response()->json([
                'message' => 'Could not generate the PDF. Please contact support.'
            ], 500);
        }
    }


public function GetPatientConsultaionDoc($patientId)
{
    try {
        $documentType = request()->query('type', 'Consultation');
        $appointmentId = request()->query('appointment_id');

        $query = PatientDocement::with(['doctor.user'])
            ->where('patient_id', $patientId);

        if ($documentType === 'Consultation') {
            // If documentType is 'consultation', fetch both 'consultation' and 'prescription_type'
            $query->whereIn('document_type', ['Consultation', 'prescription_type']);
        } else {
            // Otherwise, fetch only the specified document type
            $query->where('document_type', $documentType);
        }

        if ($appointmentId) {
            $query->where('appointment_id', $appointmentId);
        }

        $documents = $query->orderBy('created_at', 'desc')->get();

        if ($documentType == 'prescription') {
            return response()->json([
                'success' => true,
                'data' => $documents->map(function($document) {
                    return [
                        'id' => $document->id,
                        'document_name' => $document->document_name,
                        'document_path' => $document->document_path,
                        'created_at' => $document->created_at,
                        'document_type' => $document->document_type,
                        'appointment_id' => $document->appointment_id,
                        'doctor' => [
                            'id' => $document->doctor_id,
                            'user' => [
                                'name' => $document->doctor->user->name ?? 'Unknown'
                            ]
                        ]
                    ];
                })
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $documents
        ]);
    } catch (\Exception $e) {
        \Log::error('Document fetch error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve documents: ' . $e->getMessage()
        ], 500);
    }
}
public function GetPatientConsulationIndex($patientId)
{
    try {
        $consultations = Consultation::where('patient_id', $patientId)
            ->with(['template', 'doctor', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->get();

        return patientConsulationResource::collection($consultations);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve consultations: ' . $e->getMessage()
        ], 500);
    }
    

}

public function savePdf(Request $request, $patientId)
{
    try {
        $request->validate([
            'html' => 'required|string',
            'template_ids' => 'required|string',
            'mime_type' => 'required|string',
            'appointment_id' => 'nullable|numeric',
            'folder_id'=>'required',
            'doctorId'=>'nullable',
        ]);
        // Decode the template_ids JSON string into a PHP array
        $decodedTemplateIds = json_decode($request->template_ids, true);
        // get the consulation using te appointmenid
        $consultation = Consultation::where('appointment_id', $request->appointment_id)->first();

        // Check if decoding was successful and if it's an array with at least one element
        if (is_array($decodedTemplateIds) && !empty($decodedTemplateIds)) {
            $templateId = (int) $decodedTemplateIds[0]; // Get the first ID as an integer
        } else {
            Log::error("Invalid or empty template_ids received: " . $request->template_ids);
            return response()->json([
                'success' => false,
                'message' => 'Invalid template ID provided. Please select a valid template.',
            ], 400); // Bad Request
        }

        // Set paper size and margin based on mime_type
        if ($request->mime_type === 'prescription_type') {
            // 20cm x 25cm with 2cm margin
            $browsershot = Browsershot::html($request->input('html'))
                ->paperSize(200, 250, 'mm')
                ->margins(15, 15, 10, 14); 
        } else {
            // Default: 210mm x 350mm (your original)
            $browsershot = Browsershot::html($request->input('html'))
                ->paperSize(210, 350, 'mm');
        }

        // Generate the PDF from the provided HTML
        $pdfContent = $browsershot->pdf();

        // Define a unique file name for the PDF
        $fileName = 'consultation-document-' . uniqid() . '.pdf';
        $pdfPath = "consultation-pdfs/{$patientId}/{$fileName}";

        // Ensure directory exists
        Storage::disk('public')->makeDirectory("consultation-pdfs/{$patientId}");

        // Save the generated PDF content to storage
        Storage::disk('public')->put($pdfPath, $pdfContent);
        
        // Create the document record here instead of relying on event
        $document = PatientDocement::create([
            'patient_id' => $patientId,
            'document_name' => $fileName,
            'document_path' => $pdfPath,
            'doctor_id' => $request->doctorId,
            'folder_id' => $request->folder_id,
            'document_size' => Storage::disk('public')->size($pdfPath),
            'document_type' => $request->mime_type,
            'appointment_id' => $request->appointment_id ?? null,
            'created_by' => auth()->id() ?? 1
        ]);

        return response()->json([
            'success' => true,
            'path' => Storage::url($pdfPath),
            'message' => 'PDF saved successfully',
            'document' => $document
        ]);

    } catch (CouldNotTakeBrowsershot $e) {
        Log::error('PDF generation failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to generate PDF: ' . $e->getMessage()
        ], 500);
    } catch (\Exception $e) {
        Log::error('PDF save error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to save PDF: ' . $e->getMessage()
        ], 500);
    }
}
  public function show($consultationId, Request $request) // Add Request as a parameter
    { 

        try {
            $query = Consultation::with([
                'patient',
                'doctor',
                'template',
                'placeholderAttributes.placeholder',
                'placeholderAttributes.attribute'
            ]);


            // Determine if the 'isdoneconsultation' filter should be applied
            // It's true by default if not present, or if explicitly 'true' (string or boolean)
            $isDoneConsultationFilter = $request->input('isdoneconsultation', true); // Default to true

            // Cast to boolean explicitly to handle 'true', 'false', 1, 0 from request input
            $applyDoneFilter = filter_var($isDoneConsultationFilter, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            // Only apply the whereHas condition if $applyDoneFilter is true
            // If $applyDoneFilter is false, we skip this condition, allowing non-done appointments.
            if ($applyDoneFilter === true) {
                $query->whereHas('appointment', function ($q) {
                    $q->where('status', 'Done') // IMPORTANT: Use your actual appointment status value
                      ->orWhere('status', 4);    // IMPORTANT: Use your actual appointment status value
                });
            }

            // Finally, apply the findOrFail for the specific consultationId
            $consultation = $query->findOrFail($consultationId);
// Finally, apply the findOrFail for the specific consultationId

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
                    return response()->json([
    'success' => true,
    'data' => new ConsulationResource($consultation),
    'barcode_base64' => $barcodeBase64
]);
        } catch (ModelNotFoundException $e) {
            // Adjust the message based on whether the filter was applied or not.
            $message = ($applyDoneFilter === true)
                ? 'Consultation not found or its associated appointment is not done.'
                : 'Consultation not found.';

            return response()->json([
                'success' => false,
                'message' => $message
            ], 404);
        } catch (\Exception $e) {
            // Catch any other general exceptions
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve consultation: ' . $e->getMessage()
            ], 500);
        }
    }
 public function getConsultationByAppointmentId($appointmentId, Request $request)
    {             


        try {
            $query = Consultation::with([
                'patient',
                'doctor',
                'template',
                'placeholderAttributes.placeholder',
                'placeholderAttributes.attribute'
            ])->where('appointment_id', $appointmentId);
              

            // Determine if the 'isdoneconsultation' filter should be applied
            // It's true by default if not present, or if explicitly 'true' (string or boolean)
            $isDoneConsultationFilter = $request->input('isdoneconsultation', true); // Default to true
            // Cast to boolean explicitly to handle 'true', 'false', 1, 0 from request input
            $applyDoneFilter = filter_var($isDoneConsultationFilter, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            // Only apply the whereHas condition if $applyDoneFilter is true (or null if you want null to behave like true)
            // If $applyDoneFilter is false, we skip this condition, allowing non-done appointments.
            if ($isDoneConsultationFilter === true) {
                $query->whereHas('appointment', function ($q) {
                    $q->where('status', 'Done') // IMPORTANT: Use your actual appointment status value
                      ->orWhere('status', 4);    // IMPORTANT: Use your actual appointment status value
                });
            }

            $consultation = $query->firstOrFail();
// Finally, apply the findOrFail for the specific consultationId

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
            return response()->json([
            'success' => true,
            'data' => new ConsulationResource($consultation),
            'barcode_base64' => $barcodeBase64 ?? 'null'
        ]);
        } catch (ModelNotFoundException $e) {
            // Adjust the message based on whether the filter was applied or not.
            // For simplicity, a generic message might suffice, or you can make it more dynamic.
            $message = ($applyDoneFilter === true)
                ? 'Consultation not found for the provided appointment ID or the appointment is not done.'
                : 'Consultation not found for the provided appointment ID.';

            return response()->json([
                'success' => false,
                'message' => $message
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve consultation: ' . $e->getMessage()
            ], 500);
        }
    }
}