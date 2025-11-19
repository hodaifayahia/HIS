<?php

namespace App\Http\Controllers;

use \Log;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\PatientResource;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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
            'phone' => 'nullable|string',
            'gender' => 'required|string', // 0 for
            'Parent' => 'nullable|string',
            'dateOfBirth' => 'nullable|date',
            'Idnum' => 'nullable|string|max:20', // Assuming ID can be up to 20 characters long
        ]);
    
        // Parse dateOfBirth if provided
        $dateOfBirth = null;
        if (!empty($validatedData['dateOfBirth'])) {
            try {
                $dateOfBirth = \Carbon\Carbon::parse($validatedData['dateOfBirth'])->format('Y-m-d');
            } catch (\Exception $e) {
                $dateOfBirth = null;
            }
        }
    
        $patient = Patient::create([
            'Firstname' => $validatedData['Firstname'],
            'Lastname' => $validatedData['Lastname'],
            'phone' => $validatedData['phone'],
            'gender' => $validatedData['gender'] =='male' ? 1 : 0, // Convert to integer
            'dateOfBirth' => $dateOfBirth,
            'Parent' => $validatedData['Parent'] ?? null, // Handle optional parent
            'Idnum' => $validatedData['Idnum'] ?? null, // Handle optional ID number
            'created_by' => Auth::id(), // Assuming you're using Laravel's built-in authentication
        ]);
       
        // Clear cache when new patient is created
        Cache::flush(); // Or use Cache::tags(['patients'])->flush() if using cache tags
    
        return new PatientResource($patient);
    }
    
    public function update(Request $request,  $patientid)
    {
        $validatedData = $request->validate([
           'Firstname' => 'required|string|max:255',
            'Lastname' => 'required|string|max:255',
            'Parent' => 'nullable|string',
            'phone' => 'nullable|string',
            'gender' => 'required|integer|in:0,1',
            'dateOfBirth' => 'nullable|date',
            'Idnum' => 'nullable|string|max:20',
        ]);
        
        $patient = Patient::find($patientid);
        
        // Parse dateOfBirth if provided
        $dateOfBirth = $patient->dateOfBirth; // Keep existing if not provided
        if (array_key_exists('dateOfBirth', $validatedData) && !empty($validatedData['dateOfBirth'])) {
            try {
                $dateOfBirth = \Carbon\Carbon::parse($validatedData['dateOfBirth'])->format('Y-m-d');
            } catch (\Exception $e) {
                $dateOfBirth = null;
            }
        } elseif (array_key_exists('dateOfBirth', $validatedData) && empty($validatedData['dateOfBirth'])) {
            $dateOfBirth = null; // Allow clearing the date
        }
    
        $patient->update([
            'Firstname' => $validatedData['Firstname'],
            'Lastname' => $validatedData['Lastname'],
            'phone' => $validatedData['phone'],
            'gender' => $validatedData['gender'],
            'dateOfBirth' => $dateOfBirth,
            'Parent' => $validatedData['Parent'] ?? null,
            'Idnum' => $validatedData['Idnum'] ?? null,
        ]);

        // Clear patient-related cache when updated
        Cache::forget("patient_{$patientid}");
        Cache::flush(); // Clear all pagination cache
    
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
        $cacheKey = "patient_search_" . md5($searchTerm);
        
        return Cache::remember($cacheKey, 300, function () use ($searchTerm) {
            // Normalize slashes to dashes
            $searchTerm = str_replace('/', '-', $searchTerm);

            // Try to convert to a consistent date format if it's a valid date
            if (strtotime($searchTerm)) {
                $searchTerm = date('Y-m-d', strtotime($searchTerm));
            }

          $patients = Patient::where(function($query) use ($searchTerm) {
            $searchTermLower = strtolower($searchTerm);

            $query->whereRaw("LOWER(Firstname) LIKE ?", ["%{$searchTermLower}%"])
                ->orWhereRaw("LOWER(Lastname) LIKE ?", ["%{$searchTermLower}%"])
                ->orWhereRaw("LOWER(CONCAT(Firstname, ' ', Lastname)) LIKE ?", ["%{$searchTermLower}%"])
                ->orWhereRaw("LOWER(Parent) LIKE ?", ["%{$searchTermLower}%"])
                ->orWhereRaw("LOWER(Idnum) LIKE ?", ["%{$searchTermLower}%"])
                ->orWhereRaw("LOWER(phone) LIKE ?", ["%{$searchTermLower}%"])
                ->orWhereRaw("DATE_FORMAT(dateOfBirth, '%Y-%m-%d') LIKE ?", ["%{$searchTermLower}%"])
                ->orWhereHas('consultations', function ($query) use ($searchTermLower) {
                    $query->whereRaw("LOWER(codebash) LIKE ?", ["%{$searchTermLower}%"]);
                });
        })
        ->orderBy('created_at', 'desc')
        ->get();


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
                'message' => 'No appointments found for the given patient.'
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
    public function SpecificPatient( $patientid)
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
