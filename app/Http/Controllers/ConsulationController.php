<?php

namespace App\Http\Controllers;
use App\AppointmentSatatusEnum;

use Illuminate\Http\Request;
use App\Http\Resources\AppointmentResource;

use App\Models\Template;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PatientDocement;
use App\Models\Appointment;
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
use App\Models\MANAGER\PatientTracking;
use App\Events\ConsultationBlocked;
use App\Events\ConsultationCompleted;
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
     
        // Check if patient is already in another active consultation (today)
        $activeTracking = PatientTracking::where('patient_id', $request->patient_id)
            ->where('status', 'in_progress')
            ->whereDate('check_in_time', now()->toDateString())
            ->with(['prestation', 'creator'])
            ->first();
        if ($activeTracking) {
            // Get the active doctor who is currently consulting
            // The creator of the tracking is a User, we need to find their doctor record
            $activeDoctor = null;
            if ($activeTracking->creator) {
                $activeDoctor = Doctor::where('user_id', $activeTracking->creator->id)->first();
            }
            
            $requestingDoctor = Doctor::find($request->doctor_id);
            $patient = Patient::find($request->patient_id);

            // Broadcast event to notify requesting doctor
            if ($activeDoctor && $requestingDoctor && $patient) {
                event(new ConsultationBlocked($patient, $requestingDoctor, $activeDoctor));
            }

            return response()->json([
                'success' => false,
                'message' => 'Patient is currently in another consultation. Please wait until the current consultation is complete.',
                'in_consultation' => true,
                'active_doctor' => $activeDoctor ? ($activeDoctor->user->name ?? 'Another doctor') : 'Another doctor',
                'prestation' => $activeTracking->prestation->name ?? 'Unknown'
            ], 409); // 409 Conflict
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

        // Update all patient tracking records for this patient to 'in_progress'
        $updated = PatientTracking::where('patient_id', $request->patient_id)
            ->where('status', 'pending')
            ->whereNull('check_out_time')
            ->whereDate('check_in_time', now()->toDateString())
            ->update([
                'status' => 'in_progress',
                'updated_at' => now()
            ]);

        // If no pending tracking existed for today, create a new in_progress tracking
        if (empty($updated)) {
            // Try to infer prestation from the appointment if available
            $prestationId = null;
            if (!empty($request->appointment_id)) {
                $appointment = Appointment::with('appointmentPrestations')->find($request->appointment_id);
                if ($appointment && $appointment->appointmentPrestations && $appointment->appointmentPrestations->count() > 0) {
                    $prestationId = $appointment->appointmentPrestations->first()->prestation_id ?? null;
                }
            }

            PatientTracking::create([
                'fiche_navette_item_id' => null,
                'patient_id' => $request->patient_id,
                'salle_id' => null,
                'specialization_id' => null,
                'prestation_id' => $prestationId,
                'check_in_time' => now(),
                'status' => 'in_progress',
                'notes' => 'Auto-created when consultation started',
                'created_by' => Auth::id(),
            ]);
        }

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


    public function consulationappointment(Request $request, $doctorId)
    {
        try {
            $query = Appointment::query()
                ->with([
                    'patient:id,Lastname,Firstname,phone,dateOfBirth',
                    'doctor:id,user_id,specialization_id',
                    'doctor.user:id,name',
                    'createdByUser:id,name',
                    'updatedByUser:id,name',
                    'canceledByUser:id,name',
                    'waitlist',
                    // load appointment prestations and their prestation info so resource can inspect fiche items
                    'appointmentPrestations.prestation',
                ])
                ->whereHas('doctor', function ($query) {
                    $query->whereNull('deleted_at')
                        ->whereHas('user');
                })
                ->where('doctor_id', $doctorId)
                ->whereNull('deleted_at');

            // Handle multiple statuses or single status
            if ($request->has('statuses') && is_array($request->statuses)) {
                // Handle multiple statuses [0, 1, 4, 5]
                $statuses = $request->statuses;

                // Apply future filter for all statuses except ONWORKING (5) and DONE
                $query->where(function ($q) use ($statuses) {
                    foreach ($statuses as $status) {
                        $q->orWhere(function ($subQuery) use ($status) {
                            $subQuery->where('status', $status);

                            // ONWORKING (5) gets all appointments regardless of date
                            // DONE appointments should also show regardless of date (recently completed)
                            // For other statuses, apply future filter
                            if ($status != 5 && $status != AppointmentSatatusEnum::DONE->value) {
                                $subQuery->where(function ($dateQuery) {
                                    $dateQuery->where('appointment_date', '>', now()->toDateString());
                                });
                            }

                            // For DONE status, show only appointments completed today
                            if ($status == AppointmentSatatusEnum::DONE->value) {
                                // Show only appointments that were marked as DONE today
                                $subQuery->whereDate('updated_at', now()->toDateString());
                            }
                        });
                    }
                });
            } else {
                // Handle single status (existing logic with modifications)
                if ($request->status != AppointmentSatatusEnum::CANCELED->value) {
                    // Apply future filter only if status is not ONWORKING (5) and not DONE
                    if ($request->status != 5 && $request->status != AppointmentSatatusEnum::DONE->value) {
                        $query->filterFuture();
                    }

                    // For DONE status, show only appointments completed today
                    if ($request->status == AppointmentSatatusEnum::DONE->value) {
                        // Show only appointments marked as DONE today
                        $query->whereDate('updated_at', now()->toDateString());
                    }
                }

                $query->filterByStatus($request->status)
                    ->when($request->filled('date'), function ($q) use ($request) {
                        return $q->filterByDate($request->date);
                    })
                    ->when($request->filter === 'today', function ($q) {
                        return $q->filterToday();
                    });
            }

            // Apply search if provided
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereHas('patient', function ($patientQuery) use ($searchTerm) {
                        $patientQuery->where('Firstname', 'like', "%{$searchTerm}%")
                            ->orWhere('Lastname', 'like', "%{$searchTerm}%")
                            ->orWhere('phone', 'like', "%{$searchTerm}%");
                    });
                });
            }

            // Modified ordering to show recently completed appointments first for DONE status
            if (
                $request->status == AppointmentSatatusEnum::DONE->value ||
                (is_array($request->statuses) && in_array(AppointmentSatatusEnum::DONE->value, $request->statuses))
            ) {
                $query->orderBy('updated_at', 'desc') // Recently completed first
                    ->orderBy('appointment_date', 'desc')
                    ->orderBy('appointment_time', 'desc');
            } else {
                $query->orderBy('appointment_date', 'asc')
                    ->orderBy('appointment_time', 'asc');
            }

            $appointments = $query->paginate(30);

            return response()->json([
                'success' => true,
                'data' => AppointmentResource::collection($appointments),
                'meta' => [
                    'current_page' => $appointments->currentPage(),
                    'per_page' => $appointments->perPage(),
                    'total' => $appointments->total(),
                    'last_page' => $appointments->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch appointments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

  public function generatePdf(Request $request)
{
    $request->validate([
        'html' => 'required|string',
    ]);

    try {
        $pdf = Browsershot::html($request->input('html'))
        
                ->paperSize(210, 400, 'mm') 
                // We can still keep a small, clean margin around the content.                
                ->pdf();

        return response($pdf)
            ->header('Content-Type', 'application/pdf');
    } catch (CouldNotTakeBrowsershot $e) {
        \Log::error('Browsershot PDF generation failed: ' . $e->getMessage());
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
            $browsershot = $this->makeBrowsershotInstance($request->input('html'))
                ->paperSize(200, 250, 'mm')
                ->margins(15, 15, 10, 14); 
        } else {
            $browsershot = $this->makeBrowsershotInstance($request->input('html'))
                ->paperSize(210, 300, 'mm');
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

protected function makeBrowsershotInstance(string $html): Browsershot
{
    $browsershot = Browsershot::html($html)
        ->setOption('args', $this->chromeLaunchArguments());

    if ($chromePath = $this->resolveChromePath()) {
        $browsershot->setChromePath($chromePath);
    }

    return $browsershot;
}

protected function resolveChromePath(): ?string
{
    $configuredPath = env('BROWSERSHOT_CHROME_PATH');

    $candidatePaths = array_filter([
        $configuredPath,
        config('browsershot.chrome_path') ?? null,
        '/usr/bin/google-chrome-stable',
        '/usr/bin/google-chrome',
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser',
    ]);

    foreach ($candidatePaths as $path) {
        if ($path && File::exists($path)) {
            return $path;
        }
    }

    Log::warning('Browsershot Chrome path could not be resolved. Falling back to default search paths.');

    return null;
}

protected function chromeLaunchArguments(): array
{
    return [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-background-networking',
        '--disable-default-apps',
        '--disable-extensions',
        '--disable-gpu',
        '--disable-sync',
        '--disable-translate',
        '--hide-scrollbars',
        '--metrics-recording-only',
        '--mute-audio',
        '--no-first-run',
        '--safebrowsing-disable-auto-update',
        '--disable-component-update',
    ];
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