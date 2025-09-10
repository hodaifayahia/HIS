<?php

namespace App\Http\Controllers;

use App\AppointmentSatatusEnum;
use App\DayOfWeekEnum;
use App\Enum\AppointmentStatusEnum;
use App\Http\Controllers\Exception;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\AppointmentAvailableMonth;
use App\Models\AppointmentForcer;
use App\Models\Doctor;
use App\Models\ExcludedDate;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\WaitList;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    public function index(Request $request, $doctorId)
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
                    'waitlist'
                ])
                ->whereHas('doctor', function ($query) {
                    $query->whereNull('deleted_at')
                        ->whereHas('user');
                })
                ->where('doctor_id', $doctorId)
                ->whereNull('deleted_at');
    
            // Apply filters using scopes
            if ($request->status != AppointmentSatatusEnum::CANCELED->value) {
                $query->filterFuture();
            }
            
            $query->filterByStatus($request->status)
                 ->when($request->filled('date'), function($q) use ($request) {
                     return $q->filterByDate($request->date);
                 })
                 ->when($request->filter === 'today', function($q) {
                     return $q->filterToday();
                 });
    
            $query->orderBy('appointment_date', 'asc')
                  ->orderBy('appointment_time', 'asc');
    
            $appointments = $query->paginate(30);
    
            return response()->json([
                'success' => true,
                'data' => AppointmentResource::collection($appointments),
                'meta' => [
                    'current_page' => $appointments->currentPage(),
                    'per_page' => $appointments->perPage(),
                    'total' => $appointments->total(),
                    'last_page' => $appointments->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch appointments',
                'error' => $e->getMessage()
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
                'waitlist'
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
            $query->where(function($q) use ($statuses) {
                foreach ($statuses as $status) {
                    $q->orWhere(function($subQuery) use ($status) {
                        $subQuery->where('status', $status);
                        
                        // ONWORKING (5) gets all appointments regardless of date
                        // DONE appointments should also show regardless of date (recently completed)
                        // For other statuses, apply future filter
                        if ($status != 5 && $status != AppointmentSatatusEnum::DONE->value) {
                            $subQuery->where(function($dateQuery) {
                                $dateQuery->where('appointment_date', '>', now()->toDateString())
                                    ->orWhere(function($todayQuery) {
                                        $todayQuery->where('appointment_date', now()->toDateString())
                                            ->where('appointment_time', '>=', now()->toTimeString());
                                    });
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
                ->when($request->filled('date'), function($q) use ($request) {
                    return $q->filterByDate($request->date);
                })
                ->when($request->filter === 'today', function($q) {
                    return $q->filterToday();
                });
        }

        // Apply search if provided
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->whereHas('patient', function($patientQuery) use ($searchTerm) {
                    $patientQuery->where('Firstname', 'like', "%{$searchTerm}%")
                        ->orWhere('Lastname', 'like', "%{$searchTerm}%")
                        ->orWhere('phone', 'like', "%{$searchTerm}%");
                });
            });
        }

        // Modified ordering to show recently completed appointments first for DONE status
        if ($request->status == AppointmentSatatusEnum::DONE->value || 
            (is_array($request->statuses) && in_array(AppointmentSatatusEnum::DONE->value, $request->statuses))) {
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
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch appointments',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public  $excludedStatuses = [
        AppointmentSatatusEnum::CANCELED->value, // Add CANCELED here
    ];
//rest of the code remain the same


    private $statusLabels = [
        0 => 'Scheduled',
        1 => 'Confirmed',
        2 => 'Canceled',
        3 => 'Pending',
        4 => 'Done',
        5 => 'OnWorking'
    ];

    public function generateAppointmentsPdf(Request $request)
    {
        try {
            // Start building the query
            $query = Appointment::query()
                ->with(['patient:id,Lastname,Firstname,phone,dateOfBirth', 
                       'doctor:id,user_id,specialization_id', 
                       'doctor.user:id,name'])
                ->whereHas('doctor', function ($query) {
                    $query->whereNull('deleted_at')
                          ->whereHas('user');
                })
                ->whereNull('deleted_at')
                ->orderBy('appointment_date', 'asc')
                ->orderBy('appointment_time', 'asc');

            // Apply all possible filters
            $this->applyFilters($query, $request);
            
            // $includedate
            
            // Fetch the filtered appointments
            $appointments = $query->get();
            // Get the doctor with include_time by matching user name
            $includeTime = (bool) (Doctor::whereHas('user', function ($q) use ($request) {
                $q->where('name', $request->doctorName);
            })->value('include_time'));


            // Transform appointments to include status labels
            $transformedAppointments = $appointments->map(function ($appointment) {
                $appointment->status_label = 'Unknown';
                // $appointment->includedate = $includedate;
                return $appointment;
            });
            // Get the filter summary for the PDF header
            $filterSummary = $this->getFilterSummary($request);

            // Generate PDF with filter summary
          $pdf = PDF::loadView('pdf.appointments', [
                'appointments' => $transformedAppointments,
                'filterSummary' => $filterSummary,
                'includeTime' => $includeTime, // <-- Pass to view
            ]);

            // Set paper size and orientation
            $pdf->setPaper('a4', 'landscape');

         $doctorName = preg_replace('/[^A-Za-z0-9\-]/', '_', $request->doctorName); // Clean filename
            $date = now()->format('Y-m-d');
            $fileName = "appointments_{$doctorName}_{$date}.pdf";

            return $pdf->download($fileName);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function applyFilters($query, Request $request)
    {
        // Patient Name Filter
        if ($request->filled('patientName')) {
            $query->whereHas('patient', function ($q) use ($request) {
                $q->where(function ($subQ) use ($request) {
                    $terms = explode(' ', $request->patientName);
                    foreach ($terms as $term) {
                        $subQ->where(function ($nameQ) use ($term) {
                            $nameQ->where('Firstname', 'like', '%' . $term . '%')
                                 ->orWhere('Lastname', 'like', '%' . $term . '%');
                        });
                    }
                });
            });
        }

        // Phone Filter
        if ($request->filled('phone')) {
            $query->whereHas('patient', function ($q) use ($request) {
                $q->where('phone', 'like', '%' . $request->phone . '%');
            });
        }

        // Date of Birth Filter
        if ($request->filled('dateOfBirth')) {
            $query->whereHas('patient', function ($q) use ($request) {
                $q->whereDate('dateOfBirth', $request->dateOfBirth);
            });
        }

        // Appointment Date Filter
        if ($request->filled('date')) {
            $query->whereDate('appointment_date', $request->date);
        }

        // Appointment Time Filter
        if ($request->filled('time')) {
            $query->where('appointment_time', 'like', '%' . $request->time . '%');
        }

        // Status Filter
        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        // Doctor Name Filter
        if ($request->filled('doctorName')) {
            $query->whereHas('doctor.user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->doctorName . '%');
            });
        }
    }

    private function getFilterSummary(Request $request)
    {
        $summary = [];

        if ($request->filled('patientName')) {
            $summary[] = "Patient Name: " . $request->patientName;
        }
        if ($request->filled('phone')) {
            $summary[] = "Phone: " . $request->phone;
        }
        if ($request->filled('dateOfBirth')) {
            $summary[] = "Date of Birth: " . $request->dateOfBirth;
        }
        if ($request->filled('date')) {
            $summary[] = "Appointment Date: " . $request->date;
        }
        if ($request->filled('time')) {
            $summary[] = "Appointment Time: " . $request->time;
        }
        if ($request->filled('status') && $request->status !== 'ALL') {
            $summary[] = "Status: " . ($this->statusLabels[$request->status] ?? $request->status);
        }
        if ($request->filled('doctorName')) {
            $summary[] = "Doctor: " . $request->doctorName;
        }

        return $summary;
    }
  public function ForPatient(Request $request, $Patientid)
  {
      try {
          // Get appointments only for this patient
          $appointments = Appointment::query()
              ->with(['patient', 'doctor:id,user_id,specialization_id', 'doctor.user:id,name', 'waitlist'])
              ->whereHas('doctor', function ($query) {
                  $query->whereNull('deleted_at')
                      ->whereHas('user');
              })
              ->where('patient_id', $Patientid)
              ->whereNull('deleted_at')
              ->orderBy('appointment_date', 'asc')
              ->orderBy('appointment_time', 'asc');
  
          // Apply filters if provided
          if ($request->filled('status') && $request->status !== 'ALL') {
              $appointments->where('status', $request->status);
          }
          if ($request->filled('date')) {
              $appointments->whereDate('appointment_date', $request->date);
          }
          if ($request->filled('filter') && $request->filter === 'today') {
              $appointments->whereDate('appointment_date', now()->toDateString())
                           ->whereIn('status', [0, 1]);
          }
  
          // Fetch results without pagination
          $appointments = $appointments->get();
  
          return response()->json([
              'success' => true,
              'data' => AppointmentResource::collection($appointments),
          ]);
  
      } catch (\Exception $e) {
          return response()->json([
              'success' => false,
              'message' => 'Failed to fetch appointments',
              'error' => $e->getMessage()
          ], 500);
      }
  }
  
  public function getPendingAppointment(Request $request)
  {
      try {
          // Build a query to return only pending appointments (status = 3)
          $query = Appointment::query()
              ->with(['patient', 'doctor:id,user_id,specialization_id', 'doctor.user:id,name', 'waitlist'])
              ->whereHas('doctor', function ($query) {
                  $query->whereNull('deleted_at')
                        ->whereHas('user');
              })
              ->whereNull('deleted_at')
              ->where('status', 3) // Only return pending appointments (status PENDING = 3)
              ->orderBy('appointment_date', 'asc');

          // Correctly access doctorId from the request query parameters
          $doctorId = $request->query('doctorId');
          if ($doctorId) {
              $query->where('doctor_id', $doctorId);
          }
          $date = $request->query('date');
if ($date) {
    $query->whereDate('appointment_date', $date);
}

          // Paginate the results
          $appointments = $query->paginate(50);


          return response()->json([
              'success' => true,
              'data' => AppointmentResource::collection($appointments),
              'meta' => [
                  'current_page' => $appointments->currentPage(),
                  'per_page' => $appointments->perPage(),
                  'total' => $appointments->total(),
                  'last_page' => $appointments->lastPage(),
              ]
          ]);
      } catch (\Exception $e) {
          return response()->json([
              'success' => false,
              'message' => 'Failed to fetch appointments',
              'error' => $e->getMessage()
          ], 500);
      }
  }
  public function GetAllAppointments(Request $request)
  {
      try {
          $query = DB::table('appointments')
              ->select([
                  'appointments.*',
                  'patients.firstname as patient_first_name',
                  'patients.lastname as patient_last_name',
                  'patients.id as patient_id', // Add this line
                  'patients.phone',
                  'patients.dateOfBirth as patient_Date_Of_Birth',
                  'users.name as doctor_name',
                  'doctors.id as doctor_id',
                  'doctors.specialization_id as specialization_id',
                  'appointments.status as appointment_status'
              ])
              ->join('patients', 'appointments.patient_id', '=', 'patients.id')
              ->join('doctors', 'appointments.doctor_id', '=', 'doctors.id')
              ->join('users', 'doctors.user_id', '=', 'users.id')
              ->whereNull('appointments.deleted_at')
              ->whereNull('doctors.deleted_at')
              ->orderBy('appointment_date', 'asc')
              ->orderBy('appointment_time', 'asc');

          if ($request->doctor_id) {
              $query->where('appointments.doctor_id', $request->doctor_id);
          }

          if ($request->filled('filter') && $request->filter === 'today') {
              $query->whereDate('appointment_date', now()->toDateString());
          }

          if ($request->filled('status')) {
              $query->where('appointments.status', $request->status);
          }

          if ($request->filled('patient_name')) {
              $searchTerm = $request->patient_name;
              $query->where(function ($q) use ($searchTerm) {
                  $q->where('patients.firstname', 'like', "%{$searchTerm}%")
                    ->orWhere('patients.lastname', 'like', "%{$searchTerm}%");
              });
          }

          if ($request->filled('date')) {
              $query->whereDate('appointment_date', $request->date);
          }

          $appointments = $query->get()->map(function ($appointment) {
              return [
                  'id' => $appointment->id,
                  'patient_id' => $appointment->patient_id, // Add this line
                  'patient_first_name' => $appointment->patient_first_name,
                  'patient_last_name' => $appointment->patient_last_name,
                  'phone' => $appointment->phone,
                  'patient_Date_Of_Birth' => $appointment->patient_Date_Of_Birth,
                  'appointment_date' => $appointment->appointment_date,
                  'appointment_time' => $appointment->appointment_time,
                  'doctor_name' => $appointment->doctor_name,
                  'specialization_id'=>$appointment->specialization_id,
                  'doctor_id' => $request->doctor_id ?? $appointment->doctor_id,
                  'status' => $this->getStatusInfo($appointment->appointment_status)
              ];
          });

          return response()->json([
              'success' => true,
              'data' => $appointments
          ]);

      } catch (\Exception $e) {
          Log::error('Error in GetAllAppointments', [
              'error' => $e->getMessage(),
              'trace' => $e->getTraceAsString()
          ]);

          return response()->json([
              'success' => false,
              'message' => 'Failed to fetch appointments',
              'error' => $e->getMessage()
          ], 500);
      }
  }

  private function getStatusInfo($status)
  {
      $statusMap = [
          0 => ['name' => 'Scheduled', 'color' => 'primary', 'icon' => 'fas fa-calendar'],
          1 => ['name' => 'Confirmed', 'color' => 'success', 'icon' => 'fas fa-check'],
          2 => ['name' => 'Canceled', 'color' => 'danger', 'icon' => 'fas fa-times'],
          3 => ['name' => 'Pending', 'color' => 'warning', 'icon' => 'fas fa-clock'],
          4 => ['name' => 'Done', 'color' => 'info', 'icon' => 'fas fa-check-double'],
          5 => ['name' => 'ONWORKING', 'color' => 'warning', 'icon' => 'fas fa-clock'],
      ];

      return [
          'value' => $status,
          'name' => $statusMap[$status]['name'] ?? 'Unknown',
          'color' => $statusMap[$status]['color'] ?? 'secondary',
          'icon' => $statusMap[$status]['icon'] ?? 'fas fa-question'
      ];
  }

public function search(Request $request)
{
    // Get query parameters
    $query = $request->input('query');
    $doctorId = $request->input('doctor_id');
    $appointmentDate = $request->input('appointment_date');
    $appointmentTime = $request->input('appointment_time');

    // Base query
    $appointments = Appointment::query()
        // Filter by patient name or date of birth
        ->when($query, function ($queryBuilder) use ($query) {
            $queryBuilder->whereHas('patient', function ($patientQuery) use ($query) {
                $patientQuery->where('Firstname', 'like', "%{$query}%")
                    ->orWhere('Lastname', 'like', "%{$query}%")
                    ->orWhere('dateOfBirth', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            });
        })
        // Filter by doctor ID
        ->when($doctorId, function ($queryBuilder) use ($doctorId) {
            $queryBuilder->where('doctor_id', $doctorId);
        })
        // Filter by appointment date
        ->when($appointmentDate, function ($queryBuilder) use ($appointmentDate) {
            $queryBuilder->whereDate('appointment_date', $appointmentDate);
        })
        // Filter by appointment time
        ->when($appointmentTime, function ($queryBuilder) use ($appointmentTime) {
            $queryBuilder->whereTime('appointment_time', $appointmentTime);
        })
        // Eager load the patient relationship
        ->with('patient')
        // Paginate results
        ->paginate(10);

    return AppointmentResource::collection($appointments);
}
public function getDoctorWorkingHours($doctorId, $date) {
    // Create a cache key for this specific doctor and date
    $cacheKey = "doctor_{$doctorId}_hours_{$date}";

    // Check if data is already cached (5 minutes is a reasonable time for appointment data)
    return Cache::remember($cacheKey, 5, function() use ($doctorId, $date) {
        // Parse the date parameter into a Carbon instance
        $dateObj = Carbon::parse($date);
        $dayOfWeek = DayOfWeekEnum::cases()[$dateObj->dayOfWeek]->value;
        
        // Fetch doctor data with a single query
        $doctor = Doctor::select(['id', 'patients_based_on_time', 'time_slot'])
                      ->findOrFail($doctorId);
        
        // Fetch excluded dates and regular schedules in parallel
        $excludedDate = ExcludedDate::select('start_time', 'end_time', 'number_of_patients_per_day', 'shift_period')
            ->where('doctor_id', $doctorId)
            ->where('exclusionType', 'limited')
            ->where(function ($query) use ($date) {
                $query->where('start_date', '<=', $date)
                      ->where(function ($q) use ($date) {
                          $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', $date);
                      });
            })
            ->where('is_active', true)
            ->first();
            
        // Only query regular schedules if no excluded date is found (avoid unnecessary query)
        $schedules = $excludedDate 
            ? collect([$excludedDate]) 
            : Schedule::select('start_time', 'end_time', 'number_of_patients_per_day', 'shift_period')
                ->where('doctor_id', $doctorId)
                ->where('is_active', true)
                ->where('day_of_week', $dayOfWeek)
                ->get();
        
        if ($schedules->isEmpty()) {
            return [];
        }
        
        $workingHours = [];
        
        // Get current date and time - do this once outside the loops
        $now = Carbon::now();
        $isToday = $dateObj->isSameDay($now);
        $bufferTime = $now->copy()->addMinutes(5);
        
        // Prepare date string once
        $dateString = $dateObj->format('Y-m-d');
        // If doctor has a fixed time slot
        if ($doctor->time_slot !== null && (int)$doctor->time_slot > 0) {
            $timeSlotMinutes = (int)$doctor->time_slot;
            
            foreach (['morning', 'afternoon'] as $shift) {
                $schedule = $schedules->firstWhere('shift_period', $shift);
                if (!$schedule) continue;
                
                // Optimize Carbon instance creation - only create what's needed
                $startTime = Carbon::parse($dateString . ' ' . $schedule->start_time);
                $endTime = Carbon::parse($dateString . ' ' . $schedule->end_time);
                
                // Pre-calculate total slots to avoid creating unnecessary Carbon instances
                $totalMinutes = abs($endTime->diffInMinutes($startTime));
                $totalSlots = abs(floor($totalMinutes / $timeSlotMinutes));

                for ($i = 0; $i < $totalSlots; $i++) {
                    $slotMinutes = $i * $timeSlotMinutes;
                    $slotTime = $startTime->copy()->addMinutes($slotMinutes);
                    
                    if ($slotTime >= $endTime) {
                        break; // Stop if we've passed the end time
                    }
                    
                    if (!$isToday || $slotTime->greaterThan($bufferTime)) {
                        $workingHours[] = $slotTime->format('H:i');
                    }
                }

            }
        } else {
            // Calculate based on number of patients per shift
            foreach (['morning', 'afternoon'] as $shift) {
                $schedule = $schedules->firstWhere('shift_period', $shift);
                if (!$schedule) continue;
                
                $startTime = Carbon::parse($dateString . ' ' . $schedule->start_time);
                $endTime = Carbon::parse($dateString . ' ' . $schedule->end_time);
                
                $patientsForShift = (int)$schedule->number_of_patients_per_day;
                
                if ($patientsForShift <= 0) continue;
                
                if ($patientsForShift == 1) {
                    if (!$isToday || $startTime->greaterThan($bufferTime)) {
                        $workingHours[] = $startTime->format('H:i');
                    }
                    continue;
                }
                
                // Calculate total duration and slot duration once
                $totalDuration = abs($endTime->diffInMinutes($startTime));
                $slotDuration = abs($totalDuration / ($patientsForShift - 1));
                
                // Pre-calculate all slot times at once
                for ($i = 0; $i < $patientsForShift; $i++) {
                    $minutesToAdd = round($i * $slotDuration);
                    $slotTime = $startTime->copy()->addMinutes($minutesToAdd);
                    
                    if (!$isToday || $slotTime->greaterThan($bufferTime)) {
                        $workingHours[] = $slotTime->format('H:i');
                    }
                }
            }
        }
        
        return array_unique($workingHours);
    });
}
private function getBookedSlots($doctorId, $date)
{
    return Appointment::where('doctor_id', $doctorId)
        ->whereDate('appointment_date', $date)
        ->whereNotIn('status', $this->excludedStatuses) // Correct filtering
        ->pluck('appointment_time') // Get only appointment times
        ->map(fn($time) => Carbon::parse($time)->format('H:i')) // Proper formatting
        ->unique() // Ensure uniqueness if necessary
        ->toArray();
}

public function ForceAppointment(Request $request)
{
    $validated = $request->validate([
        'doctor_id' => 'required|exists:doctors,id',
        'days' => 'nullable|integer',
        'date' => 'nullable|date_format:Y-m-d', // Accept date directly
    ]);

    $doctorId = $validated['doctor_id'];
    $days = (int)($validated['days'] ?? 0); // Ensure days is an integer
    $dateInput = $validated['date'] ?? null; // Get the date input if provided
    try {
        // Use the provided date if available, otherwise calculate it based on days
        $date = $dateInput ? Carbon::parse($dateInput)->format('Y-m-d') : Carbon::now()->addDays($days)->format('Y-m-d');

        $doctor = Doctor::find($doctorId);
        $timeSlotMinutes = $this->calculateTimeSlotMinutes($doctor, $date);
        $schedules = $this->fetchSchedules($doctorId, $date);
        $morningSchedule = $schedules->firstWhere('shift_period', 'morning');
        $afternoonSchedule = $schedules->firstWhere('shift_period', 'afternoon');
         $excludedDate = ExcludedDate::select('start_time', 'end_time', 'number_of_patients_per_day', 'shift_period')
        ->where('doctor_id', $doctorId)
        ->where('exclusionType', 'limited')
        ->where(function ($query) use ($date) {
            $query->where('start_date', '<=', $date)
                  ->where(function ($q) use ($date) {
                      $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $date);
                  });
        })
        ->where('is_active', true)
        ->get();

        $gapSlots = [];
        $additionalSlots = [];

        if (!$morningSchedule && !$afternoonSchedule || $excludedDate) {
            // If the doctor does not work on this date, generate slots from 8:00 to 17:00
            $additionalSlots = $this->generateDefaultSlots($doctorId,$date, $timeSlotMinutes);
        } else {
            // Handle morning and afternoon schedules
            list($gapSlots, $additionalSlots) = $this->handleSchedules(
                $morningSchedule,
                $afternoonSchedule,
                $date,
                $timeSlotMinutes
            );
        }

        return response()->json([
            'gap_slots' => $gapSlots,
            'additional_slots' => $additionalSlots,
            'next_available_date' => $date,
            'time_slot_minutes' => $timeSlotMinutes,
        ]);
    } catch (\Exception $e) {
        Log::error('Error calculating slots: ' . $e->getMessage());
        return response()->json([
            'error' => 'Error calculating appointment slots: ' . $e->getMessage(),
        ], 500);
    }
}

/**
 * Fetch doctor details.
 */
private function fetchDoctorDetails($doctorId)
{
    return Doctor::find($doctorId, ['time_slot']);
}

/**
 * Calculate time slot duration dynamically if not set.
 */
private function calculateTimeSlotMinutes($doctor, $date)
{
    $timeSlotMinutes = is_numeric($doctor->time_slot) ? (int) $doctor->time_slot : 0;

    if ($timeSlotMinutes <= 0) {
        $dayOfWeek = DayOfWeekEnum::cases()[Carbon::parse($date)->dayOfWeek]->value;

        // Fetch the schedule once and ensure it's active
        $schedules = Schedule::where('doctor_id', $doctor->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();
        
        // Initialize patient counts
        $morningPatients = 0;
        $afternoonPatients = 0;
        
        // Loop through the schedules to correctly assign values
        foreach ($schedules as $schedule) {
            if ($schedule->shift_period === 'morning' && $schedule->number_of_patients_per_day !== null) {
                $morningPatients += $schedule->number_of_patients_per_day;
            }
            if ($schedule->shift_period === 'afternoon' && $schedule->number_of_patients_per_day !== null) {
                $afternoonPatients += $schedule->number_of_patients_per_day;
            }
        }
        
        // Total number of patients
        $totalPatients = $morningPatients + $afternoonPatients;
        
        $totalAvailableTime = $this->calculateTotalAvailableTime($doctor->id, $date);
        
        if ($totalPatients > 0 ) {
            $timeSlotMinutes = (int)(abs($totalAvailableTime) / $totalPatients);
        } else {
            $timeSlotMinutes = 30; // Default to 30 minutes
        }
    }

    return $timeSlotMinutes;
}


/**
 * Calculate total available time for the day.
 */
private function calculateTotalAvailableTime($doctorId, $date)
{
    $schedules = Schedule::where('doctor_id', $doctorId)
        ->where('is_active', true)
        ->where('day_of_week', DayOfWeekEnum::cases()[Carbon::parse($date)->dayOfWeek]->value)
        ->get();

    $totalAvailableTime = 0;

    foreach ($schedules as $schedule) {
        $startTime = Carbon::parse($date . ' ' . $schedule->start_time);
        $endTime = Carbon::parse($date . ' ' . $schedule->end_time);
        $totalAvailableTime += $endTime->diffInMinutes($startTime);
    }

    return $totalAvailableTime;
}

/**
 * Fetch schedules for the given doctor and date.
 */
private function fetchSchedules($doctorId, $date)
{
    return Schedule::where('doctor_id', $doctorId)
        ->where('is_active', true)
        ->where('day_of_week', DayOfWeekEnum::cases()[Carbon::parse($date)->dayOfWeek]->value)
        ->get();
}

/**
 * Generate default slots from 8:00 to 17:00.
 */
private function generateDefaultSlots($doctorId, $date, $timeSlotMinutes)
{
    // Default start and end times
    $forceappointment = AppointmentForcer::where('doctor_id', $doctorId)->first();
    $defaultStartTime =  $forceappointment->start_time ?? '08:00';
    $defaultEndTime =  $forceappointment->end_time ?? '17:00';
    if ($forceappointment->start_time  != null && $forceappointment->end_time != null) {
        $numberofpatient = $forceappointment->number_of_patients;
        $timeSlotMinutes = abs(Carbon::parse($forceappointment->end_time)->diffInMinutes(Carbon::parse($forceappointment->start_time)) / $numberofpatient);
    }

    $startTime = Carbon::parse($date . ' ' . $defaultStartTime);
    $endTime = Carbon::parse($date . ' ' . $defaultEndTime);
    $slots = [];

    $bookedSlots = $this->getBookedSlots($doctorId, $date);

    $currentTime = clone $startTime;
    while ($currentTime < $endTime) {
        $slotTime = $currentTime->format('H:i');

        if (!in_array($slotTime, $bookedSlots)) {
            $slots[] = $slotTime;
        }

        $currentTime->addMinutes($timeSlotMinutes);
    }

    return $slots;
}
public function printTicket(Request $request) {
    try {
        $data = $request->validate([
            'patient_first_name' => 'required|string|max:255',
            'patient_last_name' => 'required|string|max:255',
            'doctor_name' => 'nullable|string|max:255',
            'doctor_id' =>'nullable|integer',
            'appointment_date' => 'required',
            'appointment_time' => 'required|date_format:H:i',
            'description' => 'nullable|string|max:1000'
        ]);
        if ($data['doctor_id']) {
            $doctor = Doctor::with('user')->find($data['doctor_id']);
            if ($doctor) {
                $data['doctor_name'] = $doctor->user->name;
            }
        }
        $data['user_name'] = Auth::user()->name;

        // Set paper size for thermal printer (typically 80mm width)
        $customPaper = array(0, 0, 226.77, 141.73); // 80mm x 50mm in points (1 inch = 72 points)
         // Set paper size for 8 cm thermal printer (approximately 226.77 points width)
         $customPaper = array(0, 0, 226.77, 283.46); // 8cm width x 10cm height in points
        
         $pdf = Pdf::setOptions([
             'isHtml5ParserEnabled' => true,
             'isRemoteEnabled' => true,
             'isPhpEnabled' => true,
             'isFontSubsettingEnabled' => true,
             'defaultFont' => 'XB Zar',
             'chroot' => storage_path('app/public'),
             'paperSize' => $customPaper,
             'margin-top' => 2,
             'margin-right' => 2,
             'margin-bottom' => 2,
             'margin-left' => 2,
             'dpi' => 203 // Standard DPI for thermal printers
         ])->loadView('tickets.appointment', $data);

        // Force the PDF to be black and white
        $pdf->setOption('grayscale', true);
        
        return $pdf->download('ticket.pdf');
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to generate ticket',
            'error' => $e->getMessage()
        ], 500);
    }
}
public function printConfirmationTicket(Request $request) {
    try {
        $data = $request->validate([
            'patient_first_name' => 'required|string|max:255',
            'patient_last_name' => 'required|string|max:255',
            'specialization_id' =>'nullable|max:255',
            'doctor_name' => 'required|string|max:255',
            'appointment_date' => 'required',
            'appointment_time' => 'required|date_format:H:i',
            'description' => 'nullable|string|max:1000'
        ]);
        
        $data['specialization_name'] = \App\Models\Specialization::find($data['specialization_id'])->name;
        $data['user_name'] = Auth::user()->name;
        
        // Set paper size for thermal printer (80mm width)
        
        $pdf = Pdf::setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isPhpEnabled' => true,
            'isFontSubsettingEnabled' => true,
            'defaultFont' => 'XB Zar',
            'chroot' => storage_path('app/public'),
            'margin-top' => 2,
            'margin-right' => 2,
            'margin-bottom' => 2,
            'margin-left' => 2,
        ])->loadView('tickets.Confirmation', $data);

        // Force black and white output
        $pdf->setOption('grayscale', true);
        
        return $pdf->download('ticket.pdf');
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to generate ticket',
            'error' => $e->getMessage()
        ], 500);
    }
}
/**
 * Handle schedules for the given date.
 */
private function handleSchedules($morningSchedule, $afternoonSchedule, $date, $timeSlotMinutes)
{
    $gapSlots = [];
    $additionalSlots = [];

    if ($morningSchedule && !$afternoonSchedule) {
        $morningEndTime = Carbon::parse($date . ' ' . $morningSchedule->end_time);
        $additionalSlots = $this->generateAdditionalSlots($morningEndTime, $timeSlotMinutes);
    } elseif (!$morningSchedule && $afternoonSchedule) {
        $afternoonEndTime = Carbon::parse($date . ' ' . $afternoonSchedule->end_time);
        $additionalSlots = $this->generateAdditionalSlots($afternoonEndTime, $timeSlotMinutes);
    } elseif ($morningSchedule && $afternoonSchedule) {
        $morningEndTime = Carbon::parse($date . ' ' . $morningSchedule->end_time);
        $afternoonStartTime = Carbon::parse($date . ' ' . $afternoonSchedule->start_time);
        $afternoonEndTime = Carbon::parse($date . ' ' . $afternoonSchedule->end_time);

        // Generate gap slots between morning and afternoon shifts
        $currentTime = clone $morningEndTime;
        while ($currentTime < $afternoonStartTime) {
            $gapSlots[] = $currentTime->format('H:i');
            $currentTime->addMinutes($timeSlotMinutes);
        }

        // Generate additional slots after the afternoon shift
        $additionalSlots = $this->generateAdditionalSlots($afternoonEndTime, $timeSlotMinutes);
    }

    return [$gapSlots, $additionalSlots];
}

/**
 * Generate additional slots after a given end timec.
 */
private function generateAdditionalSlots($endTime, $timeSlotMinutes)
{
    $slots = [];
    $currentTime = clone $endTime;

    for ($i = 0; $i < 20; $i++) {
        $currentTime->addMinutes($timeSlotMinutes);
        $slots[] = $currentTime->format('H:i');
    }

    return $slots;
}
 /**
     * Retrieve a specific appointment for a doctor.
     *
     * @param  int  $doctorId
     * @param  int  $appointmentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAppointment($doctorId, $appointmentId)
    {
        $appointment = Appointment::where('doctor_id', $doctorId)
            ->where('id', $appointmentId)
            ->first();

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new AppointmentResource($appointment)
        ]);
    }

    public function checkAvailability(Request $request)
    {
        try {
            $validated = $request->validate([
                'doctor_id' => 'required|exists:doctors,id',
                'date' => 'nullable|date_format:Y-m-d',
                'days' => 'nullable|integer|min:1',
                'range' => 'nullable|integer|min:0',
                'include_slots' => 'nullable|in:true,false,1,0'
            ]);
    
            $doctorId = $validated['doctor_id'];
    
            // Determine the start date for the search
            if (isset($validated['date'])) {
                $startDate = Carbon::parse($validated['date']);
            } else {
                $days = isset($validated['days']) ? (int) $validated['days'] : 0;
                $startDate = Carbon::now()->addDays($days);
            }
    
            // Get range if provided, default to 0
            $range = isset($validated['range']) ? (int) $validated['range'] : 0;
    
            // Check if the doctor has any dates in the Schedule model
            $doctorHasSchedule = Schedule::where('doctor_id', $doctorId)
                ->where('date', $startDate->format('Y-m-d')) // Add date condition
                ->where('is_active', true) // Ensure the schedule is active
                ->exists();
    
            // Get excluded dates for the specific doctor and for all doctors
            $excludedDates = ExcludedDate::where(function ($query) use ($doctorId) {
                $query->where('doctor_id', $doctorId) // Excluded dates for this doctor
                      ->where('exclusionType', 'complete')
                      ->orWhereNull('doctor_id'); // Excluded dates for all doctors
            })->get();
    
            // Find next available appointment based on range
            if ($range > 0) {
                $nextAvailableDate = $this->findNextAvailableAppointmentWithinRange($startDate, $doctorId, $range, $doctorHasSchedule, $excludedDates);
            } else {
                $nextAvailableDate = $this->findNextAvailableAppointment($startDate, $doctorId, $doctorHasSchedule, $excludedDates);
            }
    
            if (!$nextAvailableDate) {
                return  $response = [
                    'next_available_date' => null,
                ];
            }
    
            // Calculate period difference from current date
            $daysDifference = abs($nextAvailableDate->diffInDays(Carbon::now()));
            $period = $this->calculatePeriod($daysDifference);
    
            // Build the response
            $response = [
                'current_date' => Carbon::now()->format('Y-m-d'),
                'next_available_date' => $nextAvailableDate->format('Y-m-d'),
                'period' => $period
            ];
    
            // Convert string boolean to actual boolean
            $includeSlots = isset($validated['include_slots']) && 
                in_array($validated['include_slots'], ['true', '1'], true);
    
            if ($includeSlots) {
                $workingHours = $this->getDoctorWorkingHours($doctorId, $nextAvailableDate->format('Y-m-d'));
                $bookedSlots = $this->getBookedSlots($doctorId, $nextAvailableDate->format('Y-m-d'));
                // Find available slots by subtracting booked slots from working hours
                $availableSlots = array_diff($workingHours, $bookedSlots);
    
                // Add slots information to response
                $response['available_slots'] = array_values($availableSlots);
            }
    
            return response()->json($response);
    
        } catch (\Exception $e) {
            // Log the error
    
            // Return a user-friendly error response
            return response()->json([
                'message' => 'An error occurred while checking availability.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    private function findNextAvailableAppointmentWithinRange(Carbon $startDate, $doctorId, $range, $doctorHasSchedule, $excludedDates)
{
    // First check the start date itself
    $currentDate = clone $startDate;
    
    // Get all available dates within range
    $availableDates = collect();
    
    // Check backward
    if ($range > 0) {
        $checkDate = clone $startDate;
        for ($i = $range; $i > 0; $i--) {
            $checkDate = clone $startDate;
            $checkDate->subDays($i);
            
            $month = $checkDate->month;
            $year = $checkDate->year;
            
            $isMonthAvailable = AppointmentAvailableMonth::where('doctor_id', $doctorId)
                ->where('month', $month)
                ->where('year', $year)
                ->where('is_available', true)
                ->exists();
                
            if (!$isMonthAvailable) {
                continue;
            }
            
            if ($this->isDateAvailableforthisdate($doctorId, $checkDate, $doctorHasSchedule, $excludedDates)) {
                $availableDates->push(clone $checkDate);
            }
        }
    }
    
    // Check current date
    if ($this->isDateAvailableforthisdate($doctorId, $currentDate, $doctorHasSchedule, $excludedDates)) {
        $availableDates->push(clone $currentDate);
    }
    
    // Check forward
    $forwardDate = clone $startDate;
    for ($i = 1; $i <= $range; $i++) {
        $forwardDate = clone $startDate;
        $forwardDate->addDays($i);
        
        $month = $forwardDate->month;
        $year = $forwardDate->year;
        
        $isMonthAvailable = AppointmentAvailableMonth::where('doctor_id', $doctorId)
            ->where('month', $month)
            ->where('year', $year)
            ->where('is_available', true)
            ->exists();
            
        if (!$isMonthAvailable) {
            continue;
        }
        
        if ($this->isDateAvailableforthisdate($doctorId, $forwardDate, $doctorHasSchedule, $excludedDates)) {
            $availableDates->push(clone $forwardDate);
        }
    }
    
    // Sort dates and return the earliest one
    if ($availableDates->isNotEmpty()) {
        return $availableDates->sort()->first();
    }
    
    return null;
}

private function findNextAvailableAppointment(Carbon $startDate, $doctorId, $doctorHasSchedule, $excludedDates) {
    // Get doctor to check same-day appointment policy
    $doctor = Doctor::find($doctorId);
    
    // If not allowed to book today, skip today if searching from today
    if ($doctor && !$doctor->allowed_appointment_today && $startDate->isToday()) {
        $startDate = $startDate->copy()->addDay();
    }
    
    $currentDate = $startDate->copy();
    $endOfSearchPeriod = Carbon::now()->addYear()->endOfYear(); 
    
    $availableMonths = AppointmentAvailableMonth::where('doctor_id', $doctorId)
        ->where('year', '>=', $currentDate->year)
        ->where('year', '<=', $endOfSearchPeriod->year)
        ->where('is_available', true)
        ->get()
        ->mapToGroups(function ($item) {
            return ["{$item->year}" => $item->month];
        });
        
    $excludedDateRanges = $excludedDates->filter(function($date) {
        return $date->exclusionType === 'complete';
    })->map(function($date) {
        return [
            'start' => $date->start_date->format('Y-m-d'),
            'end' => optional($date->end_date)->format('Y-m-d') ?? $date->start_date->format('Y-m-d')
        ];
    });
    
    while ($currentDate->lte($endOfSearchPeriod)) {
        $year = $currentDate->year;
        $month = $currentDate->month;
        $dayOfWeek = DayOfWeekEnum::cases()[$currentDate->dayOfWeek]->value;
        $dateString = $currentDate->format('Y-m-d');
        
        // Skip past dates
        if ($currentDate->startOfDay()->lt(Carbon::now()->startOfDay())) {
            $currentDate->addDay();
            continue;
        }
        
        // Skip today if doctor doesn't allow same-day appointments
        if ($currentDate->isToday() && $doctor && !$doctor->allowed_appointment_today) {
            $currentDate->addDay();
            continue;
        }
        
        $isMonthAvailable = $availableMonths->has((string)$year) && 
                            in_array($month, $availableMonths[(string)$year]->toArray());
        
        if (!$isMonthAvailable) {
            $currentDate->addDay();
            continue;
        }
        
        $isExcluded = $excludedDateRanges->contains(function($range) use ($dateString) {
            return $dateString >= $range['start'] && $dateString <= $range['end'];
        });
        
        if ($isExcluded) {
            $currentDate->addDay();
            continue;
        }
        
        // Check for specific date schedules first
        $specificDateSchedules = Schedule::where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->where('date', $dateString)
            ->get();
            
        if ($specificDateSchedules->isNotEmpty()) {
            $schedules = $specificDateSchedules;
        } else {
            // If no specific date schedule, check for recurring schedule
            $schedules = Schedule::where('doctor_id', $doctorId)
                ->where('is_active', true)
                ->whereNull('date')
                ->where('day_of_week', $dayOfWeek)
                ->get();
        }
        
        if ($schedules->isNotEmpty()) {
            $workingHours = $this->getDoctorWorkingHours($doctorId, $dateString);
            if (!empty($workingHours)) {
                $bookedSlots = $this->getBookedSlots($doctorId, $dateString);
                
                if (count(array_diff($workingHours, $bookedSlots)) > 0) {
                    return $currentDate;
                }
            }
        }
        
        $currentDate->addDay();
    }
    
    return null;
}



private function isDateAvailableforthisdate($doctorId, Carbon $date, $doctorHasSchedule, $excludedDates)
{
    // Check if the date is in the past
    if ($date->startOfDay()->lt(Carbon::now()->startOfDay())) {
        return false;
    }
    
    // Get doctor to check same-day appointment policy
    $doctor = Doctor::find($doctorId);
    
    // If the date is today and doctor doesn't allow same-day appointments, return false
    if ($date->isToday() && $doctor && !$doctor->allowed_appointment_today) {
        return false;
    }

    // Check excluded dates
    $excludedType = null;
    $isExcluded = $excludedDates->contains(function ($excludedDate) use ($date, &$excludedType) {
        $endDate = $excludedDate->end_date ?? $excludedDate->start_date;
        if ($date->between($excludedDate->start_date, $endDate)) {
            $excludedType = $excludedDate->exclusionType;
            return true;
        }
        return false;
    });
    
    // If exclusionType is 'complete', totally exclude this date
    if ($isExcluded && $excludedType === 'complete') {
        return false;
    }

    // Check month availability
    $isMonthAvailable = AppointmentAvailableMonth::where('doctor_id', $doctorId)
        ->where('month', $date->month)
        ->where('year', $date->year)
        ->where('is_available', true)
        ->exists();

    if (!$isMonthAvailable) {
        return false;
    }

    $dayOfWeek = DayOfWeekEnum::cases()[$date->dayOfWeek]->value;
    
    // Get all schedules for this doctor in one query
    $schedules = Schedule::where('doctor_id', $doctorId)
        ->where(function($query) use ($date, $dayOfWeek) {
            $query->where('date', $date->format('Y-m-d'))
                  ->orWhere(function($q) use ($dayOfWeek) {
                      $q->whereNull('date')
                        ->where('day_of_week', $dayOfWeek);
                  });
        })
        ->where('is_active', true)
        ->get();

    $hasSpecificDateSchedule = $schedules->where('date', $date->format('Y-m-d'))->isNotEmpty();
    $hasRecurringSchedule = $schedules->whereNull('date')->where('day_of_week', $dayOfWeek)->isNotEmpty();

    // If doctor has a scheduled working date (either specific or recurring), it's available
    if ($hasSpecificDateSchedule || $hasRecurringSchedule) {
        // Check if the doctor has working hours on this date
        $workingHours = $this->getDoctorWorkingHours($doctorId, $date->format('Y-m-d'));
        if (!empty($workingHours)) {
            // Check if there are available slots after booked slots
            $bookedSlots = $this->getBookedSlots($doctorId, $date->format('Y-m-d'));
            if (count(array_diff($workingHours, $bookedSlots)) > 0) {
                return true;
            }
        }
    }

    // If no schedule is found, but the date is 'limited' excluded and is sooner than any scheduled date, allow it
    if ($isExcluded && $excludedType === 'limited') {
        return true;
    }

    return false;
}
public function getAllCanceledAppointments(Request $request)
{
    try {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
        ]);

        $doctorId = $validated['doctor_id'];

        // Get excluded dates for the specific doctor and for all doctors
        $excludedDates = ExcludedDate::where(function ($query) use ($doctorId) {
            $query->where('doctor_id', $doctorId) // Excluded dates for this doctor
            ->where('exclusionType', 'complete')

                  ->orWhereNull('doctor_id'); // Excluded dates for all doctors
        })->get();

        // Fetch all canceled appointments for the doctor
        $canceledAppointments = $this->findAllCanceledAppointments($doctorId, $excludedDates);

        if ($canceledAppointments->isEmpty()) {
            return response()->json([
                'message' => 'No canceled appointments found for the specified doctor.',
                'canceled_appointments' => []
            ]);
        }

        // Build the response
        $response = [
            'current_date' => Carbon::now()->format('Y-m-d'),
            'canceled_appointments' => $canceledAppointments
        ];

        return response()->json($response);

    } catch (\Exception $e) {
        // Log the error

        // Return a user-friendly error response
        return response()->json([
            'message' => 'An error occurred while fetching canceled appointments.',
            'error' => $e->getMessage()
        ], 500);
    }
}

private function findAllCanceledAppointments($doctorId, $excludedDates)
{
    // Fetch all canceled appointments for the doctor
    $appointments = Appointment::where('doctor_id', $doctorId)
        ->where('status', 'canceled')
        ->get();

    // Filter out appointments that fall on excluded dates or violate other restrictions
    $filteredAppointments = $appointments->filter(function ($appointment) use ($excludedDates, $doctorId) {
        $appointmentDate = Carbon::parse($appointment->date);

        // Check if the date is excluded
        $isExcluded = $excludedDates->contains(function ($excludedDate) use ($appointmentDate) {
            return $appointmentDate->between($excludedDate->start_date, $excludedDate->end_date);
        });

        if ($isExcluded) {
            return false;
        }

        // Check if the month and year are available for appointments
        $month = $appointmentDate->month;
        $year = $appointmentDate->year;

        $isMonthAvailable = AppointmentAvailableMonth::where('doctor_id', $doctorId)
            ->where('month', $month)
            ->where('year', $year)
            ->where('is_available', true)
            ->exists();

        if (!$isMonthAvailable) {
            return false;
        }

        // Check if the doctor has a schedule for this date (if applicable)
        $hasSpecificDates = Schedule::where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->whereNotNull('date')
            ->exists();

        if ($hasSpecificDates) {
            $isDateScheduled = Schedule::where('doctor_id', $doctorId)
                ->where('date', $appointmentDate->format('Y-m-d'))
                ->where('is_active', true)
                ->exists();

            if (!$isDateScheduled) {
                return false;
            }
        }

        return true;
    });

    return $filteredAppointments->values(); // Reset keys for JSON response
}
 /**
     * Check if a specific date is available.
     *
     * @param int $doctorId
     * @param Carbon $date
     * @return bool
     */
    private function isDateAvailable($doctorId, Carbon $date)
    {
        // Retrieve available slots for the given doctor on the specified date
        $workingHours = $this->getDoctorWorkingHours($doctorId, $date->format('Y-m-d'));
        
        $bookedSlots = $this->getBookedSlots($doctorId, $date->format('Y-m-d'));

        // If there are any available slots, the date is available
        $availableSlots = array_diff($workingHours, $bookedSlots);
        return !empty($availableSlots);
    }

    /**
     * Find the next available appointment starting from a given date.
     *
     * @param mixed $startDate
     * @param int $doctorId
     * @return Carbon|null
     */


  /**
 * Calculate period in days/months/years.
 *
 * @param int $days
 * @return string
 */

 private function calculatePeriod($days)
 {
     // Ensure that $days is an integer before processing
     $days = (int) $days;
 
     if ($days >= 365) {
         $years = floor($days / 365);
         $remainingDays = $days % 365;
         return $years . ' year(s)' . ($remainingDays ? ' and ' . $remainingDays . ' day(s)' : '');
     }
 
     if ($days >= 30) {
         $months = floor($days / 30);
         $remainingDays = $days % 30;
         return $months . ' month(s)' . ($remainingDays ? ' and ' . $remainingDays . ' day(s)' : '');
     }
 
     return $days . ' day(s)';
 }
 public function store(Request $request)
{
    // Validate the request
    $validated = $request->validate([
        'patient_id' => 'required|exists:patients,id',
        'doctor_id' => 'required|exists:doctors,id',
        'appointment_date' => 'required|date_format:Y-m-d',
        'appointment_time' => 'required|date_format:H:i',
        'description' => 'nullable|string|max:1000',
        'addToWaitlist' => 'nullable|boolean',
    ]);

    // Check if the slot is already booked using the model method
    if (!Appointment::isSlotAvailable(
        $validated['doctor_id'],
        $validated['appointment_date'],
        $validated['appointment_time'],
        $this->excludedStatuses
    )) {
        return response()->json([
            'message' => 'This time slot is already booked.',
            'errors' => ['appointment_time' => ['The selected time slot is no longer available.']]
        ], 422);
    }

    // Create the appointment
    $appointment = Appointment::create([
        'patient_id' => $validated['patient_id'],
        'doctor_id' => $validated['doctor_id'],
        'appointment_date' => $validated['appointment_date'],
        'appointment_time' => $validated['appointment_time'],
        'add_to_waitlist' => $validated['addToWaitlist'] ?? false,
        'notes' => $validated['description'] ?? null,
        'status' => 0,
        'created_by' => Auth::id(),
    ]);

    // If adding to the waitlist, create a record
    if ($validated['addToWaitlist'] ?? false) {
        $doctor = Doctor::find($validated['doctor_id']);
        WaitList::create([
            'doctor_id' => $validated['doctor_id'],
            'patient_id' => $validated['patient_id'],
            'is_Daily' => false,
            'specialization_id' => $doctor->specialization_id ?? null,
            'importance' => null,
            'appointmentId' => $appointment->id ?? null,
            'notes' => $validated['description'] ?? null,
            'created_by' => Auth::id(),
        ]);
    }

    return new AppointmentResource($appointment);
}

 public function nextAppointment(Request $request, $appointmentId)
{
    // Find the existing appointment and mark it as done
    $existingAppointment = Appointment::findOrFail($appointmentId);
    $existingAppointment->update(['status' => 4]); // Mark as DONE

    // Validate the request for the new appointment
    $validated = $request->validate([
        'patient_id' => 'required|exists:patients,id',
        'doctor_id' => 'required|exists:doctors,id',
        'appointment_date' => 'required|date_format:Y-m-d',
        'appointment_time' => 'required|date_format:H:i',
        'description' => 'nullable|string|max:1000',
        'addToWaitlist' => 'nullable|boolean',
    ]);

    // Check if the new slot is already booked
    $excludedStatuses = [AppointmentSatatusEnum::CANCELED->value];

    $isSlotBooked = Appointment::where('doctor_id', $validated['doctor_id'])
        ->whereDate('appointment_date', $validated['appointment_date'])
        ->where('appointment_time', $validated['appointment_time'])
        ->whereNotIn('status', $excludedStatuses)
        ->exists();

    if ($isSlotBooked) {
        return response()->json([
            'message' => 'This time slot is already booked.',
            'errors' => ['appointment_time' => ['The selected time slot is no longer available.']]
        ], 422);
    }

    // Create the new appointment
    $newAppointment = Appointment::create([
        'patient_id' => $validated['patient_id'],
        'doctor_id' => $validated['doctor_id'],
        'appointment_date' => $validated['appointment_date'],
        'appointment_time' => $validated['appointment_time'],
        'add_to_waitlist' => $validated['addToWaitlist'] ?? false,
        'notes' => $validated['description'] ?? null,

        'status' => 0,  // Default to pending
        'created_by' => Auth::id(), // Assuming created_by is the user ID
    ]);

    // Handle waitlist addition if necessary
    if ($validated['addToWaitlist'] ?? false) {
        $doctor = Doctor::find($validated['doctor_id']);
        $specialization_id = $doctor->specialization_id;

        WaitList::create([
            'doctor_id' => $validated['doctor_id'],
            'patient_id' => $validated['patient_id'],
            'is_Daily' => false,
            'specialization_id' => $specialization_id,
            'importance' => null,
            'appointmentId' => $newAppointment->id ?? null,
            'notes' => $validated['description'] ?? null,
            'created_by' => Auth::id(),
        ]);
    }

    return new AppointmentResource($newAppointment);
}

    public function show($id)
    {
        $appointment = Appointment::findOrFail($id);
        return new AppointmentResource($appointment);
    }
    public function AvailableAppointments(Request $request)
    {
        try {
            $validated = $request->validate([
                'doctor_id' => 'required|exists:doctors,id',
            ]);
    
            $doctorId = $validated['doctor_id'];
            $now = Carbon::now();
    
            $excludedDates = ExcludedDate::where(function ($query) use ($doctorId) {
                $query->where('doctor_id', $doctorId)
                    ->where('exclusionType', 'complete') // Changed from 'complate' to 'complete'
                    ->orWhereNull('doctor_id');
            })->get();
    
            // Check if doctor has any schedule
            $doctorHasSchedule = Schedule::where('doctor_id', $doctorId)
                ->where('is_active', true)
                ->exists();
    
            // Check if doctor has specific dates
            $hasSpecificDates = Schedule::where('doctor_id', $doctorId)
                ->where('is_active', true)
                ->whereNotNull('date')
                ->exists();
    
            // Get canceled appointments
            $canceledAppointments = Appointment::where('doctor_id', $doctorId)
                ->where('status', AppointmentSatatusEnum::CANCELED->value)
                ->where(function ($query) use ($now) {
                    $query->where('appointment_date', '>', $now->format('Y-m-d'))
                        ->orWhere(function ($query) use ($now) {
                            $query->where('appointment_date', '=', $now->format('Y-m-d'))
                                ->where('appointment_time', '>', $now->format('H:i'));
                        });
                })
                ->with(['patient', 'doctor:id,user_id', 'doctor.user:id,name'])
                ->get();
    
            // Process canceled appointments with same conditions as checkAvailability
            $canceledAppointmentsResult = [];
            $groupedCanceledAppointments = $canceledAppointments->groupBy('appointment_date');
    
            foreach ($groupedCanceledAppointments as $date => $appointments) {
                $searchDate = Carbon::parse($date);
                
                // Use the same availability check as checkAvailability
                if ($this->isDateAvailableforthisdate($doctorId, $searchDate, $doctorHasSchedule, $excludedDates)) {
                    $availableSlots = [];
                    foreach ($appointments as $appointment) {
                        $time = Carbon::parse($appointment->appointment_time)->format('H:i');
                        
                        // Check if slot is rebooked
                        $isRebooked = Appointment::where('doctor_id', $doctorId)
                            ->where('appointment_date', $date)
                            ->where('appointment_time', $time)
                            ->where('status', '!=', AppointmentSatatusEnum::CANCELED->value)
                            ->exists();
    
                        if (!$isRebooked) {
                            // Check if the slot is within working hours
                            $workingHours = $this->getDoctorWorkingHours($doctorId, $date);
                            if (in_array($time, $workingHours)) {
                                $availableSlots[] = $time;
                            }
                        }
                    }
    
                    if (!empty($availableSlots)) {
                        $canceledAppointmentsResult[] = [
                            'date' => $date,
                            'available_times' => $availableSlots,
                        ];
                    }
                }
            }
    
            // Find next available non-canceled appointment using same logic as checkAvailability
            $startDate = Carbon::now();
            $nextAvailableDate = $this->findNextAvailableAppointment($startDate, $doctorId, $doctorHasSchedule, $excludedDates);
    
            $normalAppointmentResult = null;
            if ($nextAvailableDate) {
                $daysDifference = $nextAvailableDate->diffInDays($now);
                $workingHours = $this->getDoctorWorkingHours($doctorId, $nextAvailableDate->format('Y-m-d'));
                $bookedSlots = $this->getBookedSlots($doctorId, $nextAvailableDate->format('Y-m-d'));
                $availableSlots = array_values(array_diff($workingHours, $bookedSlots));
    
                $normalAppointmentResult = [
                    'date' => $nextAvailableDate->format('Y-m-d'),
                    'available_times' => $availableSlots,
                    'period' => $this->calculatePeriod($daysDifference)
                ];
            }
    
            return response()->json([
                'canceled_appointments' => $canceledAppointmentsResult,
                'normal_appointments' => $normalAppointmentResult
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while checking availability.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    private function isDateAvailableForSearch($doctorId, Carbon $date, $doctorHasSchedule, $excludedDates, $hasSpecificDates)
    {
        // Check if the date is in the past
        if ($date->startOfDay()->lt(Carbon::now()->startOfDay())) {
            return false;
        }
    
        // Check if the date is excluded (for both consecutive and single off days)
        $isExcluded = $excludedDates->contains(function ($excludedDate) use ($date) {
            if (is_null($excludedDate->end_date)) {
                // For single off days
                return $date->format('Y-m-d') === $excludedDate->start_date->format('Y-m-d');
            } else {
                // For consecutive off days
                return $date->between(
                    $excludedDate->start_date->startOfDay(),
                    $excludedDate->end_date->endOfDay()
                );
            }
        });
    
        if ($isExcluded) {
            return false;
        }
    
        // Check if the month is available
        $isMonthAvailable = AppointmentAvailableMonth::where('doctor_id', $doctorId)
            ->where('month', $date->month)
            ->where('year', $date->year)
            ->where('is_available', true)
            ->exists();
    
        if (!$isMonthAvailable) {
            return false;
        }
    
        // Check if the date has specific schedules
        if ($hasSpecificDates) {
            $hasSchedule = Schedule::where('doctor_id', $doctorId)
                ->where('date', $date->format('Y-m-d'))
                ->where('is_active', true)
                ->exists();
    
            if (!$hasSchedule) {
                return false;
            }
        }
    
        // Check if the doctor has a general schedule for the day of the week
        if ($doctorHasSchedule) {
            $hasSchedule = Schedule::where('doctor_id', $doctorId)
                ->whereNull('date')
                ->where('day_of_week', $date->dayOfWeek)
                ->where('is_active', true)
                ->exists();
    
            if (!$hasSchedule) {
                return false;
            }
        }
    
        // Check if there are available time slots for the date
        $workingHours = $this->getDoctorWorkingHours($doctorId, $date->format('Y-m-d'));
    
        // If no working hours are available, the date is not available for search
        return !empty($workingHours);
    }
    private function isMonthAvailable($doctorId, $month)
    {
        // Get the current year and month
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
    
        // If the month has already passed this year, return false (do not look into the next year)
        if ($month < $currentMonth) {
            return false;
        }
    
        // Check if the month is available in the AppointmentAvailableMonth model for the current year
        return AppointmentAvailableMonth::where('doctor_id', $doctorId)
            ->where('month', $month)
            ->where('is_available', true)
            ->exists();
    }
  
    public function changeAppointmentStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|integer|in:' . implode(',', array_column(AppointmentSatatusEnum::cases(), 'value')),
            'reason' => 'nullable|string'
        ]);
    
        $appointment = Appointment::findOrFail($id);
        $appointment->status = $validated['status'];
    
        // Ensure 'reason' is never NULL
        if ($validated['status'] == 2) {
            $appointment->reason = $validated['reason'] ?? '--'; // Use provided reason or default '--'
            $appointment->canceled_by = Auth::id();

        } else {

            $appointment->reason = '--'; // Default when status is not 2
        }
    
        $appointment->save();
    
        return response()->json([
            'message' => 'Appointment status updated successfully.',
            'appointment' => new AppointmentResource($appointment),
        ]);
    }
    
    
    public function destroy($appointmentid)
    {
        $appointment = Appointment::findOrFail($appointmentid);
        $appointment->delete();

        return response()->json([
            'message' => 'Appointment deleted successfully.'
        ]);
    }

    public function update(Request $request, $id)
    {
        // Validate the request data
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'doctor_id' => 'sometimes|required',
            'appointment_date' => 'sometimes|required|date_format:Y-m-d',
            'appointment_time' => 'sometimes|required|date_format:H:i',
            'description' => 'nullable|string|max:1000',
            'addToWaitlist' => 'nullable|boolean',
            'importance' => 'nullable|integer',
        ]);
    
        // Find the appointment by ID
        $appointment = Appointment::findOrFail($id);
        $doctorId = $validated['doctor_id'] ?? $appointment->doctor_id;
        $appointmentDate = $validated['appointment_date'] ?? $appointment->appointment_date;
        $appointmentTime = $validated['appointment_time'] ?? $appointment->appointment_time;
    
        // Check if the time slot is already booked
    
        if (!Appointment::isSlotAvailableForUpdate($doctorId, $appointmentDate, $appointmentTime, $this->excludedStatuses, $appointment->id)) {
            return response()->json([
                'message' => 'This time slot is already booked.',
                'errors' => ['appointment_time' => ['The selected time slot is no longer available.']]
            ], 422);
        }
    
        // Update the patient details
        $patient = Patient::findOrFail($appointment->patient_id);
        $patient->update([
            'Firstname' => $validated['first_name'] ?? $patient->Firstname,
            'Lastname' => $validated['last_name'] ?? $patient->Lastname,
            'phone' => $validated['phone'] ?? $patient->phone,
        ]);
    
        // Update the appointment details
        $appointment->update([
            'doctor_id' => $doctorId,
            'appointment_date' => $appointmentDate,
            'appointment_time' => $appointmentTime,
            'status' => 0,
            'updated_by' => Auth::id(),
            'add_to_waitlist' => $validated['addToWaitlist'] ?? $appointment->add_to_waitlist,
            'notes' => $validated['description'] ?? $appointment->notes,
        ]);
    
        // Handle waitlist logic using the model method
        if (isset($validated['addToWaitlist'])) {
            $doctor = Doctor::find($doctorId);
            WaitList::updateOrDeleteWaitlist(
                $doctorId,
                $patient->id,
                $validated['addToWaitlist'],
                $doctor->specialization_id ?? null,
                $validated['importance'] ?? null,
                $validated['description'] ?? null
            );
        }
    
        return new AppointmentResource($appointment);
    }
    
    public function checkSameDayAvailability(Request $request)
{
    try {
        $doctorId = $request->input('doctor_id');
        $prestationId = $request->input('prestation_id');
        $today = Carbon::today();

        $doctor = Doctor::find($doctorId);
        
        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found'
            ], 404);
        }
        
        $response = [
            'success' => true,
            'doctor_allows_same_day' => $doctor->allowed_appointment_today,
            'today' => $today->format('Y-m-d'),
            'available_slots' => [],
            'next_available_date' => null,
            'waiting_list_available' => true // Always allow waiting list
        ];
        
        if ($doctor->allowed_appointment_today) {
            // Scenario 1: Doctor allows same-day appointments
            // Use existing checkAvailability method for today
            $availabilityRequest = new Request([
                'doctor_id' => $doctorId,
                'date' => $today->format('Y-m-d'),
                'include_slots' => 'true'
            ]);
            
            $availabilityResponse = $this->checkAvailability($availabilityRequest);
            $availabilityData = json_decode($availabilityResponse->getContent(), true);
            
            if (isset($availabilityData['available_slots'])) {
                $response['available_slots'] = $this->formatTodaySlots($availabilityData['available_slots']);
            }
        } else {
            // Scenario 2: Doctor doesn't allow same-day appointments
            // Use existing checkAvailability method for next available
            $nextDayRequest = new Request([
                'doctor_id' => $doctorId,
                'days' => 1, // Start from tomorrow
                'include_slots' => 'true'
            ]);
            
            $nextAvailabilityResponse = $this->checkAvailability($nextDayRequest);
            $nextAvailabilityData = json_decode($nextAvailabilityResponse->getContent(), true);
            
            if (isset($nextAvailabilityData['next_available_date'])) {
                $response['next_available_date'] = $nextAvailabilityData['next_available_date'];
                $response['available_slots'] = $nextAvailabilityData['available_slots'] ?? [];
                $response['period'] = $nextAvailabilityData['period'] ?? '';
            }
        }
        
        return response()->json($response);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error checking availability: ' . $e->getMessage()
        ], 500);
    }
}


private function formatTodaySlots($slots): array
{
    $now = Carbon::now();
    $formattedSlots = [];
    
    foreach ($slots as $slot) {
        $slotTime = Carbon::today()->setTimeFromTimeString($slot);
        
        // Only show future slots for today
        if ($slotTime->gt($now->copy()->addMinutes(5))) {
            $formattedSlots[] = [
                'time' => $slot,
                'datetime' => $slotTime->format('Y-m-d H:i:s'),
                'formatted_time' => $slotTime->format('H:i')
            ];
        }
    }
    
    return $formattedSlots;
}

public function bookSameDayAppointment(Request $request)
{
    try {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'patient_id' => 'required|exists:patients,id',
            'appointment_time' => 'required',
            'prestation_id' => 'nullable|exists:prestations,id'
        ]);
        
        $doctor = Doctor::find($validated['doctor_id']);
        
        if (!$doctor || !$doctor->allowed_appointment_today) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor does not allow same-day appointments'
            ], 400);
        }
        
        $appointmentDateTime = Carbon::parse($validated['appointment_time']);
        $appointmentDate = $appointmentDateTime->format('Y-m-d');
        $appointmentTime = $appointmentDateTime->format('H:i');
        
        // Check if slot is still available using existing model method
        if (!Appointment::isSlotAvailable(
            $validated['doctor_id'],
            $appointmentDate,
            $appointmentTime,
            $this->excludedStatuses
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Selected time slot is no longer available'
            ], 400);
        }
        
        // Create appointment
        $appointment = Appointment::create([
            'patient_id' => $validated['patient_id'],
            'doctor_id' => $validated['doctor_id'],
            'appointment_date' => $appointmentDate,
            'appointment_time' => $appointmentTime,
            'status' => 0, // Scheduled
            'notes' => 'Same-day appointment',
            'created_by' => Auth::id(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Same-day appointment booked successfully',
            'appointment' => new AppointmentResource($appointment)
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error booking appointment: ' . $e->getMessage()
        ], 500);
    }
}
}
