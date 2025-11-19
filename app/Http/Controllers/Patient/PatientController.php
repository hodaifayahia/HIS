<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\PatientResource;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Log;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $cacheKey = "patients_list_page_{$page}";

        // Cache patients list for 10 minutes (600 seconds)
        $patients = Cache::remember($cacheKey, 600, function () {
            return Patient::paginate(10);
        });

        return [
            'data' => PatientResource::collection($patients),
            'meta' => [
                'total' => $patients->total(),
                'per_page' => $patients->perPage(),
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
                'from' => $patients->firstItem(),
                'to' => $patients->lastItem(),
            ],
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validatedData = $request->validate([
            'Firstname' => 'required|string|max:255',
            'Lastname' => 'required|string|max:255',
            'Parent' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'fax_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'gender' => 'required|in:0,1',
            'dateOfBirth' => 'nullable|date_format:Y-m-d',
            'Idnum' => 'nullable|string|max:255',
            'identity_document_type' => 'nullable|in:national_card,passport,foreigner_card,drivers_license,other',
            'identity_issued_on' => 'nullable|date_format:Y-m-d',
            'identity_issued_by' => 'nullable|string|max:255',
            'passport_number' => 'nullable|string|max:50',
            'professional_badge_number' => 'nullable|string|max:50',
            'foreigner_card_number' => 'nullable|string|max:50',
            'nss' => 'nullable|string|max:255',
            'birth_place' => 'nullable|string|max:255',
            'is_birth_place_presumed' => 'nullable|boolean',
            'additional_ids' => 'nullable|json',
            'age' => 'nullable|integer|min:0|max:150',
            'height' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'blood_group' => 'nullable|string|max:255',
            'marital_status' => 'nullable|string|max:255',
            'mother_firstname' => 'nullable|string|max:255',
            'mother_lastname' => 'nullable|string|max:255',
            'balance' => 'nullable|numeric',
            'is_faithful' => 'nullable|boolean',
            'firstname_ar' => 'nullable|string|max:255',
            'lastname_ar' => 'nullable|string|max:255',
            'other_clinical_info' => 'nullable|string',
        ]);

        $patient = Patient::create([
            'Firstname' => $validatedData['Firstname'],
            'Lastname' => $validatedData['Lastname'],
            'Parent' => $validatedData['Parent'] ?? null,
            'phone' => $validatedData['phone'] ?? null,
            'fax_number' => $validatedData['fax_number'] ?? null,
            'email' => $validatedData['email'] ?? null,
            'address' => $validatedData['address'] ?? null,
            'city' => $validatedData['city'] ?? null,
            'postal_code' => $validatedData['postal_code'] ?? null,
            'gender' => $validatedData['gender'],
            'dateOfBirth' => $validatedData['dateOfBirth'] ?? null,
            'Idnum' => $validatedData['Idnum'] ?? null,
            'identity_document_type' => $validatedData['identity_document_type'] ?? null,
            'identity_issued_on' => $validatedData['identity_issued_on'] ?? null,
            'identity_issued_by' => $validatedData['identity_issued_by'] ?? null,
            'passport_number' => $validatedData['passport_number'] ?? null,
            'professional_badge_number' => $validatedData['professional_badge_number'] ?? null,
            'foreigner_card_number' => $validatedData['foreigner_card_number'] ?? null,
            'nss' => $validatedData['nss'] ?? null,
            'birth_place' => $validatedData['birth_place'] ?? null,
            'is_birth_place_presumed' => $validatedData['is_birth_place_presumed'] ?? false,
            'additional_ids' => $validatedData['additional_ids'] ?? null,
            'age' => $validatedData['age'] ?? null,
            'height' => $validatedData['height'] ?? null,
            'weight' => $validatedData['weight'] ?? null,
            'blood_group' => $validatedData['blood_group'] ?? null,
            'marital_status' => $validatedData['marital_status'] ?? null,
            'mother_firstname' => $validatedData['mother_firstname'] ?? null,
            'mother_lastname' => $validatedData['mother_lastname'] ?? null,
            'balance' => $validatedData['balance'] ?? 0,
            'is_faithful' => $validatedData['is_faithful'] ?? false,
            'firstname_ar' => $validatedData['firstname_ar'] ?? null,
            'lastname_ar' => $validatedData['lastname_ar'] ?? null,
            'other_clinical_info' => $validatedData['other_clinical_info'] ?? null,
            'created_by' => Auth::id(),
        ]);

        // Clear cache when new patient is created
        Cache::flush();

        return new PatientResource($patient);
    }

    public function update(Request $request, $patientid)
    {
        $validatedData = $request->validate([
            'Firstname' => 'required|string|max:255',
            'Lastname' => 'required|string|max:255',
            'Parent' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'fax_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'gender' => 'required|in:0,1',
            'dateOfBirth' => 'nullable|date_format:Y-m-d',
            'Idnum' => 'nullable|string|max:255',
            'identity_document_type' => 'nullable|in:national_card,passport,foreigner_card,drivers_license,other',
            'identity_issued_on' => 'nullable|date_format:Y-m-d',
            'identity_issued_by' => 'nullable|string|max:255',
            'passport_number' => 'nullable|string|max:50',
            'professional_badge_number' => 'nullable|string|max:50',
            'foreigner_card_number' => 'nullable|string|max:50',
            'nss' => 'nullable|string|max:255',
            'birth_place' => 'nullable|string|max:255',
            'is_birth_place_presumed' => 'nullable|boolean',
            'additional_ids' => 'nullable|json',
            'age' => 'nullable|integer|min:0|max:150',
            'height' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'blood_group' => 'nullable|string|max:255',
            'marital_status' => 'nullable|string|max:255',
            'mother_firstname' => 'nullable|string|max:255',
            'mother_lastname' => 'nullable|string|max:255',
            'balance' => 'nullable|numeric',
            'is_faithful' => 'nullable|boolean',
            'firstname_ar' => 'nullable|string|max:255',
            'lastname_ar' => 'nullable|string|max:255',
            'other_clinical_info' => 'nullable|string',
        ]);

        $patient = Patient::findOrFail($patientid);

        // Update all validated data directly
        $patient->update($validatedData);

        // Clear patient-related cache when updated
        Cache::forget("patient_{$patientid}");
        Cache::flush();

        return new PatientResource($patient);
    }

    public function search(Request $request)
    {
        $searchTerm = $request->query('query');

        if (empty($searchTerm)) {
            // Cache empty search results for 5 minutes
            return Cache::remember('patients_all', 300, function () {
                return PatientResource::collection(
                    Patient::orderBy('created_at', 'desc')->get()
                );
            });
        }

        // Create cache key based on search term
        $cacheKey = 'patient_search_'.md5($searchTerm);

        return Cache::remember($cacheKey, 300, function () use ($searchTerm) {
            // Normalize slashes to dashes
            $searchTerm = str_replace('/', '-', $searchTerm);

            // Try to convert to a consistent date format if it's a valid date
            if (strtotime($searchTerm)) {
                $searchTerm = date('Y-m-d', strtotime($searchTerm));
            }

            $patients = Patient::where(function ($query) use ($searchTerm) {
                $searchTermLower = strtolower($searchTerm);

                $query->whereRaw('LOWER(Firstname) LIKE ?', ["%{$searchTermLower}%"])
                    ->orWhereRaw('LOWER(Lastname) LIKE ?', ["%{$searchTermLower}%"])
                    ->orWhereRaw("LOWER(CONCAT(Firstname, ' ', Lastname)) LIKE ?", ["%{$searchTermLower}%"])
                    ->orWhereRaw('LOWER(Parent) LIKE ?', ["%{$searchTermLower}%"])
                    ->orWhereRaw('LOWER(Idnum) LIKE ?', ["%{$searchTermLower}%"])
                    ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$searchTermLower}%"])
                    ->orWhereRaw("DATE_FORMAT(dateOfBirth, '%Y-%m-%d') LIKE ?", ["%{$searchTermLower}%"])
                    ->orWhereHas('consultations', function ($query) use ($searchTermLower) {
                        $query->whereRaw('LOWER(codebash) LIKE ?', ["%{$searchTermLower}%"]);
                    });
            })
                ->orderBy('created_at', 'desc')
                ->get();

            // Enrich patient data with today's fiche navette info
            $patients = $patients->map(function ($patient) {
                $ficheNavette = $patient->ficheNavettes()
                    ->whereDate('fiche_date', today())
                    ->first();

                $patient->today_fiche_navette = $ficheNavette ? [
                    'id' => $ficheNavette->id,
                    'reference' => $ficheNavette->reference,
                    'status' => $ficheNavette->status,
                ] : null;

                return $patient;
            });

            return PatientResource::collection($patients);
        });
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function PatientAppointments($PatientId)
    {
        // Log the received PatientId for debugging
        Log::info('Received PatientId:', ['PatientId' => $PatientId]);

        // Cache key for patient appointments
        $cacheKey = "patient_appointments_{$PatientId}";

        // Cache for 5 minutes (300 seconds)
        $appointments = Cache::remember($cacheKey, 300, function () use ($PatientId) {
            return Appointment::with(['patient', 'doctor.user'])
                ->where('patient_id', $PatientId)
                ->orderBy('appointment_date', 'desc')
                ->paginate(15);
        });

        // Check if any appointments were found
        if ($appointments->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No appointments found for the given patient.',
            ], 404);
        }

        // Add custom data or perform additional operations here if required
        $totalAppointments = $appointments->total(); // Total count of appointments

        // If you need to pass additional metadata to the frontend:
        $metaData = [
            'total' => $totalAppointments,
            'page' => $appointments->currentPage(),
            'per_page' => $appointments->perPage(),
            'last_page' => $appointments->lastPage(),
            'from' => $appointments->firstItem(),
            'to' => $appointments->lastItem(),
        ];

        // Use a custom resource collection which includes the metadata
        return AppointmentResource::collection($appointments)->additional(['meta' => $metaData]);
    }

    public function SpecificPatient($patientid)
    {
        // Cache specific patient for 10 minutes
        $cacheKey = "patient_{$patientid}";

        $patient = Cache::remember($cacheKey, 600, function () use ($patientid) {
            return Patient::find($patientid);
        });

        return new PatientResource($patient);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($patientid)
    {
        $patient = Patient::find($patientid);
        $patient->delete();

        // Clear cache when patient is deleted
        Cache::forget("patient_{$patientid}");
        Cache::forget("patient_appointments_{$patientid}");
        Cache::flush(); // Clear all patient lists cache

        return response()->json([
            'message' => 'Patient deleted successfully',
        ], 200); // Return 200 OK
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:patients,id', // Ensure all IDs exist in the patients table
        ]);

        try {
            $patientIds = $request->input('ids');
            Patient::whereIn('id', $patientIds)->delete();

            // Clear cache for deleted patients
            foreach ($patientIds as $id) {
                Cache::forget("patient_{$id}");
                Cache::forget("patient_appointments_{$id}");
            }
            Cache::flush(); // Clear all patient lists cache

            return response()->json(['message' => 'Patients deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete patients', 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
